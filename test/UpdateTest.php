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

class UpdateTest extends TestCase
{
    public function testUpdate()
    {
        $res = \think\facade\Db::connect('oci')->name('menu')->where('rid', 6)->update(['MENU_NAME' => '好菜单6', 'UPDATE_TIME' => date('Y-m-d H:i:s')]);
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertEquals(1, $res);

        $res = \think\facade\Db::connect('oci')->name('menu')->where('rid', 33)->data(['MENU_NAME' => '真菜单33', 'UPDATE_TIME' => date('Y-m-d H:i:s')])->update();
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertEquals(1, $res);
    }
}