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
            'USERNAME'    => '测试用户1',
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

        $data = [
            'USERNAME'    => '测试用户2',
            'PASSWORD'    => md5('PWD' . microtime()),
            'PHONE'       => '138' . rand(0000, 9999) . rand(0000, 9999),
            'SEX'         => 2,
            'CREATE_TIME' => date('Y-m-d H:i:s'),
            'STATUS'      => 1,
            'NOTHVAE'     => '不存在的列', // 会被忽略
        ];

        $res = Db::connect($config)->name('user')->strict(false)->insert($data);
        if(is_bool($res)) {
            $this->assertFalse($res);
        } else {
            $this->assertEquals(1, $res);
        }
    }

    public function testInsertAll()
    {
        global $config;

        // 插入用户信息
        $data = [];
        for($i = 100; $i < 200; $i++) {
            $temp = [
                'USERNAME'    => '姓名' . $i,
                'PASSWORD'    => md5('PWD' . microtime()),
                'PHONE'       => '138' . rand(0000, 9999) . rand(0000, 9999),
                'SEX'         => rand(1, 2),
                'CREATE_TIME' => date('Y-m-d H:i:s'),
                'STATUS'      => 1,
            ];
            $data[] = $temp;
        }

        $res = Db::connect($config)->name('user')->insertAll($data); // ok
        if(is_bool($res)) {
            $this->assertFalse($res);
        } else {
            $this->assertEquals(100, $res);
        }

        // 'MENU' => [
        //     ['RID', 'NUMBER PRIMARY KEY'],
        //     ['MENU_NAME', 'VARCHAR2(64)'],
        //     ['MENU_REMARK', 'VARCHAR2(512)'],
        //     ['CREATE_TIME', 'VARCHAR2(19)'],
        //     ['UPDATE_TIME', 'VARCHAR2(19)'],
        //     ['DELETE_TIME', 'VARCHAR2(19)'],
        //     ['STATUS', 'NUMBER'],
        //     ['URL', 'VARCHAR2(255)'],
        // ],
        // 插入菜单信息
        $data = [];
        for($i = 1; $i <= 50; $i++) {
            $temp = [
                'MENU_NAME'   => '菜单' . $i,
                'MENU_REMARK' => md5('MENU_REMARK' . microtime()),
                'CREATE_TIME' => date('Y-m-d H:i:s'),
                'STATUS'      => 1,
                'URL'         => 'URL' . $i,
            ];
            $data[] = $temp;
        }

        $res = Db::connect($config)->name('menu')->insertAll($data); // ok
        if(is_bool($res)) {
            $this->assertFalse($res);
        } else {
            $this->assertEquals(50, $res);
        }

        // 'ROLE' => [
        //     ['RID', 'NUMBER PRIMARY KEY'],
        //     ['ROLE_NAME', 'VARCHAR2(64)'],
        //     ['ROLE_REMARK', 'VARCHAR2(512)'],
        //     ['CREATE_TIME', 'VARCHAR2(19)'],
        //     ['DELETE_TIME', 'VARCHAR2(19)'],
        //     ['UPDATE_TIME', 'VARCHAR2(19)'],
        //     ['STATUS', 'NUMBER'],
        // ],
        // 插入角色信息
        $data = [];
        for($i = 1; $i <= 20; $i++) {
            $temp = [
                'ROLE_NAME'   => '角色' . $i,
                'ROLE_REMARK' => md5('ROLE_REMARK' . microtime()),
                'CREATE_TIME' => date('Y-m-d H:i:s'),
                'STATUS'      => 1,
            ];
            $data[] = $temp;
        }

        $res = Db::connect($config)->name('role')->insertAll($data); // ok
        if(is_bool($res)) {
            $this->assertFalse($res);
        } else {
            $this->assertEquals(20, $res);
        }
    }

    public function testInsertGetId()
    {
        global $config;

        $data = [
            'ROLE_NAME'   => '我的角色',
            'ROLE_REMARK' => '主键ID一定是21',
            'CREATE_TIME' => date('Y-m-d H:i:s'),
            'STATUS'      => 1,
        ];

        $res = Db::connect($config)->name('role')->insertGetId($data); // ok
        $this->assertEquals(21, $res);
    }
}
