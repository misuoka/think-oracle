<?php
// require_once diROLE_NAME(__FILE__) . "/../vendor/autoload.php";
require_once diROLE_NAME(__FILE__) . '/../vendor/topthink/framework/base.php';
$myconfig = require diROLE_NAME(__FILE__) . '/../database.php';  // 加载数据库配置

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

class SelectTest extends TestCase
{
    public function testFind()
    {
        global $config;

        $res = Db::connect($config)->table('mdb_role')->where('rid', 6)->find();
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertArrayHasKey('ROLE_NAME', $res);

        $res = Db::connect($config)->name('role')->where('rid', 6)->find();
        $this->assertNotNull($res);
        $this->assertNotEmpty($res);
        $this->assertArrayHasKey('ROLE_NAME', $res);
    }

    public function testFindOrFail()
    {
        global $config;

        $this->expectException('think\db\exception\DataNotFoundException');
        $this->expectExceptionMessage('table data not Found:mdbtb_role');

        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 1)->findOrFail();
    }

    public function testFindOrEmpty()
    {
        global $config;

        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 1)->findOrEmpty();
        $this->assertEmpty($res);
    }

    public function testSelect()
    {
        global $config;

        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 13)->select();
        $this->assertCount(1, $res);
        $this->assertArrayHasKey('ROLE_NAME', $res[0]);
        $res = Db::connect($config)->name('role')->where('rid', 13)->select();
        $this->assertCount(1, $res);
        $this->assertArrayHasKey('ROLE_NAME', $res[0]);
        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 1)->select();
        $this->assertEmpty($res);
    }

    public function testSelectOrFail()
    {
        global $config;

        $this->expectException('think\db\exception\DataNotFoundException');
        $this->expectExceptionMessage('table data not Found:mdbtb_role');

        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 1)->selectOrFail();
        $res = Db::connect($config)->name('role')->where('rid', 1)->selectOrFail();
    }

    public function testValue()
    {
        global $config;

        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 13)->value('ROLE_NAME');
        $this->assertNotEmpty($res);
        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 1)->value('ROLE_NAME');
        $this->assertNull($res);
    }

    public function testColumn()
    {
        global $config;

        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 13)->column('ROLE_NAME'); // ok
        $this->assertNotEmpty($res);
        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 13)->column('ROLE_NAME','RID'); // ok
        $this->assertNotEmpty($res);
        $res = Db::connect($config)->table('mdbtb_role')->where('rid', '>', 13)->column('ROLE_NAME','RID'); // ok
        $this->assertNotEmpty($res);
        $res = Db::connect($config)->table('mdbtb_role')->where('rid', 13)->column('*','RID'); // ok
        $this->assertNotEmpty($res);
    }
}
