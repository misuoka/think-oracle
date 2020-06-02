<?php

$myconfig = require dirname(__FILE__) . '/../database.php'; // 加载数据库配置

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

$res = \think\facade\Db::connect('oci')->name('user')
    ->alias('u')
    ->leftJoin('UserRole ur', 'u.uid = ur.uid')
    // ->leftJoin('mdbtb_user_role ur', 'u.uid = ur.uid')
    // ->leftJoin(['MDBTB_USER_ROLE' => 'ur'], 'u.uid = ur.uid')
    ->where('U.uId', 1)
    ->fetchSql(true)
    ->select();
    
// $res = \think\facade\Db::connect('oci')->name('user')->where('"UID"','<=', 100)->fetchSql(true)->delete(); // ok
echo $res;

