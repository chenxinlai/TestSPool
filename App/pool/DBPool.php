<?php
/**
 * Created by PhpStorm.
 * User: cxl
 * Date: 2019/6/26
 * Time: 11:49
 */

namespace App\pool;

abstract class DBPool{
    private $min;
    private $max;
    private $conns;
    private $count;//当前连接数用来判断是否超出连接数

    private $idleTime=10;//连接空闲时间秒 超出时间清理
    abstract protected function newDB();
    function __construct($min=5,$max=10)
    {
        $this->min=$min;
        $this->max=$max;

        $this->conns=new \Swoole\Coroutine\Channel($this->max);
    }
    public function initPool(){ //根据最小连接数，初始化池
        for($i=0;$i<$this->min;$i++){
           $this->addDBtopool();
        }

        \Swoole\Timer::tick(2000,function(){
            $this->cleanPool();
        });
    }

    public function getConnection(){//取出
        $getObject = false;
        if($this->conns->isEmpty()){
            if($this->count < $this->max){ //链接池没满

                 $this->addDBtopool();
                $getObject = $this->conns->pop();
            }else{
                $getObject =  $this->conns->pop(5); //如果连接池为空就等待5秒看看有没有其他链接池返回
                if (empty($getObject)){
                    echo '链接池为空';
                }

            }

        }else{
            $getObject = $this->conns->pop();
        }
           $getObject->usedTime = time();
        return $getObject;

    }
    public function close($conn){//放回
        if($conn){
            $this->conns->push($conn);
        }
    }
    public function getCount(){
        return $this->count;
    }

    public function addDBtopool(){
        try{
            //echo $this->conns->length().PHP_EOL;
            //  echo $this->count.PHP_EOL;
            $this->count++;
            $db=$this->newDB();
            if (!$db){
                throw new \Exception('没有可用连接池');
            }

            $dbObject =new \stdClass();
            $dbObject->usedTime = time();
            $dbObject->db=$db;
            $this->conns->push($dbObject);

        }catch (\Throwable $t){
            $this->count--;
        }
    }

    private function cleanPool(){
          //这里判断固定在min值数得链接池
        if($this->conns->length()<=$this->min && $this->conns->length()<intval($this->max*.6))
            return ;
        echo "开始执行清理".PHP_EOL;

        $dbbak=[];
        while(true){
            if($this->conns->isEmpty()) break;
            $obj=$this->conns->pop(0.1);
            if($this->count> $this->min && (time()-$obj->usedTime)>$this->idleTime)//销毁大于初始化得链接池
                $this->count--;
            else
                $dbbak[]=$obj;
        }

        foreach ($dbbak as $db){
            $this->conns->push($db);
        }
        echo "当前连接数".$this->count.PHP_EOL;
    }
}