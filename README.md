# think-oracle
用于 thinkphp5.1 框架上的基于 OCI 的 Oracle 数据库驱动。

由于官方很久没有对 PDO_OCI 更新了，其驱动源码默认不支持 Oracle 11g 及以上版本的数据库，当然也可在编译前修改 config.m4 文件使之支持。但安装之后，PDO_OCI 使用中却存在问题，如果数据库中存储中文，查询后会出现字符截断，无法得到预期的结果。

本库使用基于 OCI API 封装的 PDO 接口数据库驱动 [misuoka\ocipdo](https://github.com/misuoka/ocipdo)，用来对 Oracle 数据库进行操作。

根据 Oracle 数据库的特性，对 thinkphp5.1 的数据库访问层进行稍作修改，使之适用于 Oracle 数据库，以便在 thinkphp5.1 框架中以其原有方式完美操作 Oracle 数据库。

> 有关 PDO_OCI 字符截断问题的链接：https://my.oschina.net/startphp/blog/195333

## 使用方法

在 thinkphp5.1 的数据库配置文件 database.php 中，进行如下填写：

```php
$config = [
    // 数据库类型
    'type'            => '\\misuoka\\think\Oracle',
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
    // 自增序列名前缀（新增的，针对 Oracle 特有的）
    'prefix_sequence' => '',
    // Builder类
    'builder'         => '', // 可不填，若填则为：\\misuoka\\think\\Builder
    // Query类
    'query'           => '', // 可不填，若填则为：\\misuoka\\think\\Query
];
```

配置完成后，即可在PHP业务代码中，按 thinkphp5.1 官方开发手册的方法使用。

## 变更之处

- 由官方的 PDO 驱动连接变更为 [misuoka\ocipdo](https://github.com/misuoka/ocipdo) 驱动连接
- 更改对 Oracle 存储过程调用的判断
- 获取数据库表信息的修改
- 获取数据表字段信息的修改
- SQL 性能分析的修改，使用 Oracle 的方式
- 数据库字段类型对应绑定类型的修改
- 获取 LastInserID 的修改
    - 增加了序列名的自动获取，如果用户配置了序列前缀，则根据规则（序列前缀 + 去掉表前缀的表名）自动获取序列名称，如果存在则返回序列名
    - 如果用户查询设置了返回自增ID，但又不显示填写序列名并且自动获取序列名失败，则结果返回 -1 
- 查询锁的修改，修改为适用于 Oracle 的方式`FOR UPDATE NOWAIT`，但只能用于没有分页查询的 SQL 语句
- 强制使用索引的修改
- 去掉 REPLACE 功能，Oracle 没有该用法
- 修改 insertAll 方法，使之适用于 Oracle 的批量插入
- 分页查询修改。使用 Oracle 的分页查询方式，同时对非分页查询保留原有的 SQL 语句形式（同时能够兼容了子查询下列冲突的问题）
- 对生成具有分页查询的子查询进行兼容，避免子查询中存在分页查询后列冲突的问题
- 对数据表及字段都转大写后加`"`，避免遇到系统关键字，导致 SQL 处理错误。如：用户ID为UID，如果删除时（`DELETE TB_USER WHERE UID < 100`）UID不加上双引号，这会导致全部数据被删除，经过处理后的语句（`DELETE "TB_USER" WHERE "UID" < 100`）只会删除 UID 小于100的数据。如果你的字段是骆驼峰命名的，或者有大小写字母混用，请在设置字段时，自行加上双引号，如：`where('"goodID"', '<', 100)`


## 详细说明

### Oracle SQL 性能分析
```PHP
// oracle数据库的SQL计划查询
$explain = [
    "EXPLAIN PLAN FOR {$sql}",
    "SELECT PLAN_TABLE_OUTPUT FROM TABLE(DBMS_XPLAN.DISPLAY('PLAN_TABLE'))",
];
```
### 数据库字段类型对应
```PHP
// orale数据类型对于PDO数据类型
protected $dataTypes = [
    'NUMBER'    => \PDO::PARAM_STR,
    'DECIMAL'   => \PDO::PARAM_STR,
    'INTEGER'   => \PDO::PARAM_INT,
    'INT'       => \PDO::PARAM_INT,
    'SMALLINT'  => \PDO::PARAM_INT,
    'FLOAT'     => self::PARAM_FLOAT,
    'DATE'      => \PDO::PARAM_STR, 
    'CHAR'      => \PDO::PARAM_STR,
    'NCHAR'     => \PDO::PARAM_STR,
    'VARCHAR2'  => \PDO::PARAM_STR,
    'NVARCHAR2' => \PDO::PARAM_STR,
    'VARCHAR'   => \PDO::PARAM_STR,
    'STRING'    => \PDO::PARAM_STR,
    'CLOB'      => \PDO::PARAM_LOB,
    'NCLOB'     => \PDO::PARAM_LOB,
    'BLOB'      => \PDO::PARAM_LOB+\PDO::PARAM_LOB, // 自行定义的
];
```
### 查询语句模板

```PHP
// 非分页查询
protected $selectSql = 'SELECT%FORCE%%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER% %LOCK%%COMMENT%';

// 分页查询
protected $selectSqlLimit = 'SELECT * FROM (SELECT thinkphp.*,ROWNUM RN FROM (SELECT%FORCE%%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%) thinkphp%LIMIT_END%)%LIMIT_BEGIN%%COMMENT%';

```
具体分页形式
```SQL
SELECT * FROM 
(    
    SELECT thinkphp.*,ROWNUM RN FROM ( 具体查询语句 ) thinkphp WHERE ROWNUM <= 100
) WHERE RN > 0 
```
对分页查询和非分页查询SQL区分的意义在于，避免生成的子查询语句无法使用。

如，子查询：
```PHP
$subQuery = Db::table('tb_user')
    ->field('id,name')
    ->where('id', '>', 10)
    ->fetchSql(true)
    ->select();
```
如果只使用一个 SQL 模板，则无论是否有分页，都生成嵌套的 SQL 语句：
```SQL
SELECT * FROM 
(
    SELECT thinkphp.*,ROWNUM RN FROM ( 
        SELECT ID,USER_NAME FROM TB_USER WHERE ID > 10
    ) thinkphp 
) 
```
那么再将此子查询 SQL 添加到主查询时，就会导致列名冲突，分页列名 RN 冲突：ORA-00918: 未明确定义列。

如，使用子查询：
```PHP
Db::table($subQuery . ' a')
    ->where('a.user_name', 'like', 'thinkphp')
    ->order('id', 'desc')
    ->limit(100)
    ->select();
```
则对应的查询语句：
```SQL
SELECT * FROM (
    SELECT thinkphp.*,ROWNUM RN FROM ( 
        SELECT * FROM (

            -- 子查询
            SELECT * FROM (
                SELECT thinkphp.*,ROWNUM RN FROM ( 
                    SELECT ID,USER_NAME FROM TB_USER WHERE ID > 10
                ) thinkphp 
            ) 

        ) A WHERE A.USER_NAME LIKE '%thinkphp%' ORDER BY ID DESC
     ) thinkphp WHERE ROWNUM <= 100
) WHERE RN > 0 

# 执行错误，ORA-00918: 未明确定义列
```
因此，如果非分页查询与分页查询使用不同 SQL 模板，则对应的查询语句：
```SQL
SELECT * FROM (
    SELECT thinkphp.*,ROWNUM RN FROM ( 
        SELECT * FROM (

            -- 子查询
            SELECT ID,USER_NAME FROM TB_USER WHERE ID > 10

        ) A WHERE A.USER_NAME LIKE '%thinkphp%' ORDER BY ID DESC
     ) thinkphp WHERE ROWNUM <= 100
) WHERE RN > 0 
```

### 兼容子查询也存在分页的情况

子查询也存在分页，如：
```PHP
$subSql = Db::table('tb_user')->field('id,user_name')->where('status', '=', 1)->limit(0, 200)->buildSql(); 
$res = Db::table($subSql . ' a')
    ->where('a.user_name', 'like', '%thinkphp%')
    ->order('id', 'desc')
    ->limit(0, 200)
    ->select();  // ok ---- 子查询存在limit
```
对应的查询 SQL：
```SQL
SELECT * FROM (
    SELECT thinkphp.*,ROWNUM RN FROM ( 
        SELECT * FROM (

            -- 子查询
            SELECT * FROM (
                SELECT thinkphp.*,ROWNUM RN_ FROM ( 
                    SELECT ID,USER_NAME FROM TB_USER WHERE STATUS = 1
                ) thinkphp WHERE ROWNUM <= 200
            ) WHERE RN_ > 0

        ) A WHERE A.USER_NAME LIKE '%thinkphp%' ORDER BY ID DESC
     ) thinkphp WHERE ROWNUM <= 100
) WHERE RN > 0 
```
兼容的原因在于，把子查询中的 RN 替换为 RN_，这样就可以无限子查询也能够兼容，只是这样的话查询结果会出现 RN（一次分页查询）、RN_（一次含分页的子查询）、RN__（二次含分页的子查询）、RN___（三次含分页的子查询）等列。

