<?php

$myconfig = require dirname(__FILE__) . '/../database.php'; // 加载数据库配置

use PHPUnit\Framework\TestCase;
use think\facade\Db;

require(__DIR__ . '/../vendor/autoload.php');

\think\facade\Db::setConfig([
    'connections' => [
        'oci' => [
            'type' => 'misuoka\\think\\Oracle',
            // 服务器地址
            'hostname'    => $myconfig['hostname'],
            // 数据库名
            'database'    => $myconfig['database'],
            // 数据库用户名
            'username'    => $myconfig['username'],
            // 数据库密码
            'password'    => $myconfig['password'],
            // 数据库连接端口
            'hostport'    => $myconfig['hostport'],
            // 数据库连接参数
            'params'      => [],
            // 数据库编码默认采用utf8
            'charset'     => 'utf8',
            // 数据库表前缀
            'prefix'      => 'mdbtb_',

            'prefix_sequence' => 'mdbts_'
        ]
    ]
]);

class DeleteTest extends TestCase
{
    public function testDelete()
    {
        $res = \think\facade\Db::connect('oci')->name('role')->delete(16); // ok
        $this->assertEquals(1, $res);
        $res = \think\facade\Db::connect('oci')->name('menu')->delete([13,26,49]); // ok
        $this->assertEquals(3, $res);
        $res = \think\facade\Db::connect('oci')->name('user')->where('UID','<=', 100)->delete(); // ok
        $this->assertEquals(100, $res);
    }

    /**
     * 测试全表删除
     *
     * @return void
     */
    public function testDeleteAll()
    {
        $res = \think\facade\Db::connect('oci')->name('role')->delete(true); // ok
        $this->assertEquals(20, $res);  // 20 这个数字是根据之前共插入的 21 行数据及删除 1 行数据所得
        $res = \think\facade\Db::connect('oci')->name('menu')->delete(true); // ok
        $this->assertEquals(47, $res);  // 50 这个数字是根据之前共插入的 50 行数据及删除 3 行数据所得
        $res = \think\facade\Db::connect('oci')->name('user')->delete(true); // ok
        $this->assertEquals(2, $res); // 2 这个数字是根据之前共插入的数据及删除 100 行数据所得
    }
}