<?php

require_once dirname(__FILE__) . '/../vendor/topthink/framework/base.php';
$myconfig = require dirname(__FILE__) . '/../database.php';  // 加载数据库配置

use PHPUnit\Framework\TestCase;
use think\Db;
use think\facade\Config;

$config = [
    'username'        => $myconfig['username'],
    'password'        => $myconfig['password'],
    'hostname'        => $myconfig['hostname'],
    'hostport'        => $myconfig['hostport'],
    'database'        => $myconfig['database'],
    'debug'           => true,
    'query'           => '\\misuoka\\think\\Query',
    'type'            => '\\misuoka\\think\\Oracle',
    'prefix'          => 'mdbtb_',
    'prefix_sequence' => 'mdbts_',
];

Config::set([
    'cache'    => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        //'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],
    'database' => $config,
]);

class DeleteTest extends TestCase
{
    public function testDelete()
    {
        global $config;

        $res = Db::connect($config)->name('role')->delete(16); // ok
        $this->assertEquals(1, $res);
        $res = Db::connect($config)->name('menu')->delete([13,26,49]); // ok
        $this->assertEquals(3, $res);
        $res = Db::connect($config)->name('user')->where('UID','<=', 100)->delete(); // ok
        $this->assertEquals(100, $res);
    }

    /**
     * 测试全表删除
     *
     * @return void
     */
    public function testDeleteAll()
    {
        global $config;

        $res = Db::connect($config)->name('role')->delete(true); // ok
        $this->assertEquals(20, $res);  // 20 这个数字是根据之前共插入的 21 行数据及删除 1 行数据所得
        $res = Db::connect($config)->name('menu')->delete(true); // ok
        $this->assertEquals(47, $res);  // 50 这个数字是根据之前共插入的 50 行数据及删除 3 行数据所得
        $res = Db::connect($config)->name('user')->delete(true); // ok
        $this->assertEquals(2, $res); // 2 这个数字是根据之前共插入的数据及删除 100 行数据所得
    }
}