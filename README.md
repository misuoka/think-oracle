# think-oracle
用于 thinkphp6 框架上的基于 OCI 的 Oracle 数据库驱动。

由于官方很久没有对 PDO_OCI 更新了，其驱动源码默认不支持 Oracle 11g 及以上版本的数据库，当然也可在编译前修改 config.m4 文件使之支持。但安装之后，PDO_OCI 使用中却存在问题，如果数据库中存储中文，查询后会出现字符截断，无法得到预期的结果。

本库使用基于 OCI API 封装的 PDO 接口数据库驱动 [misuoka\ocipdo](https://github.com/misuoka/ocipdo)，用来对 Oracle 数据库进行操作。

根据 Oracle 数据库的特性，对 thinkphp6 的数据库访问层进行稍作修改，使之适用于 Oracle 数据库，以便在 thinkphp6 框架中以其原有方式完美操作 Oracle 数据库。如果你使用的是 thinkphp5.1 框架，请安装 1.x.x 版本。

> 有关 PDO_OCI 字符截断问题的链接：https://my.oschina.net/startphp/blog/195333

## 使用方法

使用 composer 进行安装 `composer require misuoka/think-oracle`

安装完成后，在 thinkphp6 的数据库配置文件 database.php 中，进行如下配置：

```php
$config = [
    // 数据库连接配置信息
    'connections'     => [
        'oracle' => [
            // 数据库类型
            'type'            => '\misuoka\think\Oracle',
            // 服务器地址
            'hostname'        => '', // 填写数据库 IP 地址
            // 数据库名
            'database'        => '', // 数据库实例 SID 名称，如 ORCL
            // 用户名
            'username'        => '', // 用户名
            // 密码
            'password'        => '', // 密码
            // 端口
            'hostport'        => '', // 端口号，如 1521
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8
            'charset'         => 'utf8',
            // 数据库表前缀
            'prefix'          => '',
            // 自增序列名前缀（新增的，针对 Oracle 特有的），除前缀外，名称与表名一致。如果不是，请在新增数据时使用 sequence 方法设置序列
            'prefix_sequence' => '',
        ],
    ],
];
```

配置完成后，即可在PHP业务代码中，按 thinkphp6 官方开发手册的方法使用。

## 变更之处

- 由官方的 PDO 驱动连接变更为 [misuoka\ocipdo](https://github.com/misuoka/ocipdo) 驱动连接
- 更改对 Oracle 存储过程调用的判断
- 获取 LastInserID 的修改
    - 增加了序列名的自动获取，如果用户配置了序列前缀，则根据规则（序列前缀 + 去掉表前缀的表名）自动获取序列名称，如果存在则返回序列名
    - 如果用户查询设置了返回自增ID，但又不显示填写序列名并且自动获取序列名失败，则结果返回 -1 
- 去掉 REPLACE 功能，Oracle 没有该用法
- 修改 insertAll 方法，使之适用于 Oracle 的批量插入
- 对数据表及字段都转大写后加`"`，避免遇到系统关键字，导致 SQL 处理错误。如：用户ID为UID，如果删除时（`DELETE TB_USER WHERE UID < 100`）UID不加上双引号，这会导致全部数据被删除，经过处理后的语句（`DELETE "TB_USER" WHERE "UID" < 100`）只会删除 UID 小于100的数据。


## thinkphp5.1 上安装方式

使用 composer 进行安装 `composer require misuoka/think-oracle 1.x`

安装完成后，在 thinkphp6 的数据库配置文件 database.php 中，进行如下配置：

```php
$config = [
    // 数据库类型
    'type'            => '\misuoka\think\Oracle',
    // Query类
    'query'           => '\misuoka\think\Query',  // 如果是在 database.php 中配置，不需要填写此项，但如果是这种用法 Db::connect($config)，请填写此项
    // 服务器地址
    'hostname'        => '', // 填写数据库 IP 地址
    // 数据库名
    'database'        => '', // 数据库实例名称，如 ORCL
    // 用户名
    'username'        => '', // 用户名
    // 密码
    'password'        => '', // 密码
    // 端口
    'hostport'        => '', // 端口号，如 1521
    // 连接dsn
    'dsn'             => '', // 不填写，如果填写，则数据库连接将以此为连接串，将忽略除账号密码外的参数
    // 数据库连接参数
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 数据库表前缀
    'prefix'          => '',
    // 自增序列名前缀（新增的，针对 Oracle 特有的），除前缀外，名称与表名一致。如果不是，请在新增数据时使用 sequence 设置序列
    'prefix_sequence' => '',
];
```

配置完成后，即可在PHP业务代码中，按 thinkphp5.1 官方开发手册的方法使用。