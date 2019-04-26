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

class UpdateTest extends TestCase
{
    public function testUpdate()
    {
        global $config;

        $res = Db::connect($config)->name('menu')->where('rid', 6)->update(['MENU_NAME' => '好菜单6', 'UPDATE_TIME' => date('Y-m-d H:i:s')]);
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertEquals(1, $res);

        $res = Db::connect($config)->name('menu')->where('rid', 33)->data(['MENU_NAME' => '真菜单33', 'UPDATE_TIME' => date('Y-m-d H:i:s')])->update();
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertEquals(1, $res);
    }
}