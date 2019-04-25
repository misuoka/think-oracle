<?php

require_once dirname(__FILE__) . '/../vendor/topthink/framework/base.php';
$myconfig = require dirname(__FILE__) . '/../database.php'; // 加载数据库配置

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
    'query'           => '\\think\\oci\\Query',
    'type'            => '\\think\\oci\\Connection',
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

class InsertTest extends TestCase
{
    public function testInsert()
    {
        global $config;
        // ['UID', 'NUMBER PRIMARY KEY'],
        // ['USERNAME', 'VARCHAR2(64)'],
        // ['PASSWORD', 'VARCHAR2(64)'],
        // ['PHONE', 'VARCHAR2(11)'],
        // ['SEX', 'NUMBER'],
        // ['CREATE_TIME', 'VARCHAR2(19)'],
        // ['UPDATE_TIME', 'VARCHAR2(19)'],
        // ['DELETE_TIME', 'VARCHAR2(19)'],
        // ['LAST_LOGIN_TIME', 'VARCHAR2(19)'],
        // ['STATUS', 'NUMBER'],
        $data = [
            'USERNAME'    => '测试用户',
            'PASSWORD'    => md5('PWD' . microtime()),
            'PHONE'       => '138' . rand(0000, 9999) . rand(0000, 9999),
            'SEX'         => 1,
            'CREATE_TIME' => date('Y-m-d H:i:s'),
            'STATUS'      => 1,
        ];
        $res = Db::connect($config)->name('user')->insert($data); // ok
        if(is_bool($res)) {
            $this->assertFalse($res);
        } else {
            $this->assertEquals(1, $res);
        }
    }
}
