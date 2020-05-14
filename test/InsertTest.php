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

class InsertTest extends TestCase
{
    public function testInsert()
    {
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
        $res = \think\facade\Db::connect('oci')->name('user')->insert($data);
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

        $res = \think\facade\Db::connect('oci')->name('user')->strict(false)->insert($data);
        if(is_bool($res)) {
            $this->assertFalse($res);
        } else {
            $this->assertEquals(1, $res);
        }
    }

    public function testInsertAll()
    {
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

        $res = \think\facade\Db::connect('oci')->name('user')->insertAll($data); // ok
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

        $res = \think\facade\Db::connect('oci')->name('menu')->insertAll($data); // ok
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

        $res = \think\facade\Db::connect('oci')->name('role')->insertAll($data); // ok
        if(is_bool($res)) {
            $this->assertFalse($res);
        } else {
            $this->assertEquals(20, $res);
        }
    }

    public function testInsertGetId()
    {
        $data = [
            'ROLE_NAME'   => '我的角色',
            'ROLE_REMARK' => '主键ID一定是21',
            'CREATE_TIME' => date('Y-m-d H:i:s'),
            'STATUS'      => 1,
        ];

        // $res = \think\facade\Db::connect('oci')->name('role')->sequence('MDBTS_ROLE')->insertGetId($data); // ok
        $res = \think\facade\Db::connect('oci')->name('role')->insertGetId($data); // ok
        $this->assertEquals(21, $res);
    }
}
