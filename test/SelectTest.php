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

class SelectTest extends TestCase
{
    public function testFind()
    {
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 6)->find();
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertArrayHasKey('ROLE_NAME', $res);

        $res = \think\facade\Db::connect('oci')->name('role')->where('rid', 6)->find();
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertArrayHasKey('ROLE_NAME', $res);
    }

    public function testFindOrFail()
    {
        $this->expectException('think\db\exception\DataNotFoundException');
        $this->expectExceptionMessage('table data not Found:mdbtb_role');

        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 1000)->findOrFail();
    }

    public function testFindOrEmpty()
    {
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 1000)->findOrEmpty();
        $this->assertEmpty($res);
    }

    public function testSelect()
    {
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 13)->select();
        $this->assertCount(1, $res);
        $this->assertArrayHasKey('ROLE_NAME', $res[0]);
        $res = \think\facade\Db::connect('oci')->name('role')->where('rid', 13)->select();
        $this->assertCount(1, $res);
        $this->assertArrayHasKey('ROLE_NAME', $res[0]);
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 1000)->select();
        $this->assertEmpty($res);
    }

    public function testSelectOrFail()
    {
        $this->expectException('think\db\exception\DataNotFoundException');
        $this->expectExceptionMessage('table data not Found:mdbtb_role');
        // $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('RID', 1000)->select(false);
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('RID', 1000)->selectOrFail();
    }

    public function testValue()
    {
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('RID', 13)->value('ROLE_NAME');
        $this->assertNotEmpty($res);
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('RID', 500)->value('ROLE_NAME');
        $this->assertNull($res);
    }

    public function testColumn()
    {
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 13)->column('ROLE_NAME'); // ok
        $this->assertNotEmpty($res);
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 13)->column('ROLE_NAME','RID'); // ok
        $this->assertNotEmpty($res);
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', '>', 13)->column('ROLE_NAME','RID'); // ok
        $this->assertNotEmpty($res);
        $res = \think\facade\Db::connect('oci')->table('mdbtb_role')->where('rid', 13)->column('*','RID'); // ok
        $this->assertNotEmpty($res);
    }

    public function testCount()
    {
        $res = \think\facade\Db::connect('oci')->name('menu')->whereNotNull('UPDATE_TIME')->count();
        $this->assertEquals(2, $res);
    }
}
