<?php
/**
 * Created by PhpStorm.
 * User: cxl
 * Date: 2019/6/26
 * Time: 11:57
 */

namespace App\pool;

class PDOpool extends DBPool {

    public function __construct(int $min = 5, int $max = 10)
    {
        parent::__construct($min, $max);
        \Swoole\Runtime::enableCoroutine(true);
    }
    protected function newDB()
    {
        $dsn="mysql:host=47.107.132.11;dbname=test";
        $pdo=new \PDO($dsn,"root","root");
        return $pdo;

        // TODO: Implement newDB() method.
    }
}