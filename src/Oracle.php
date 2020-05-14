<?php
/*
 * @Description:
 * @Author: Misuoka
 * @Github: https://github.com/misuoka
 * @Licensed: MIT
 * @Version: 1.0.0
 * @Date: 2019-04-10 14:52:31
 * @LastEditTime: 2019-04-26 13:54:38
 */

namespace misuoka\think; //

use misuoka\think\Query;
use ocipdo\PDO as OCIPDO;
use think\Container;
use think\Db;
use think\db\Connection as BaseConnection;
use think\Exception;
use think\exception\PDOException;
use \PDO;

/**
 *  连接类
 */
class Oracle extends BaseConnection
{
    // 使用Builder类
    protected $builderClassName = '\\misuoka\\think\\Builder';
    // Builder对象
    protected $builder;
    // 数据库连接参数配置
    protected $config = [
        // 数据库类型
        'type'            => '\\misuoka\\think\\Oracle',
        // 服务器地址
        'hostname'        => '',
        // 数据库名
        'database'        => 'orcl',
        // 用户名
        'username'        => '',
        // 密码
        'password'        => '',
        // 端口
        'hostport'        => '1521',
        // 连接dsn(Oracle连接字符串)
        'dsn'             => '',
        // 数据库连接参数
        'params'          => [],
        // 数据库编码默认采用utf8
        'charset'         => 'utf8',
        // 数据库表前缀
        'prefix'          => '',
        // 触发器前缀
        'prefix_trigger'  => '',
        // 序列前缀
        'prefix_sequence' => '',
        // 数据库调试模式
        'debug'           => false,
        // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
        'deploy'          => 0,
        // 数据库读写是否分离 主从式有效
        'rw_separate'     => false,
        // 读写分离后 主服务器数量
        'master_num'      => 1,
        // 指定从服务器序号
        'slave_no'        => '',
        // 模型写入后自动读取主服务器
        'read_master'     => false,
        // 是否严格检查字段是否存在
        'fields_strict'   => true,
        // 数据集返回类型
        'resultset_type'  => '',
        // 自动写入时间戳字段
        'auto_timestamp'  => false,
        // 时间字段取出后的默认时间格式
        'datetime_format' => 'Y-m-d H:i:s',
        // 是否需要进行SQL性能分析
        'sql_explain'     => false,
        // Builder类
        'builder'         => '\\misuoka\\think\\Builder',
        // Query类
        'query'           => '\\misuoka\\think\\Query',
        // 是否需要断线重连
        'break_reconnect' => false,
        // 断线标识字符串
        'break_match_str' => [],
    ];

    // orale数据类型对于PDO数据类型
    protected $dataTypes = [
        'NUMBER'    => \PDO::PARAM_STR,
        'DECIMAL'   => \PDO::PARAM_STR,
        'INTEGER'   => \PDO::PARAM_INT,
        'INT'       => \PDO::PARAM_INT,
        'SMALLINT'  => \PDO::PARAM_INT,
        'FLOAT'     => self::PARAM_FLOAT,
        'DATE'      => \PDO::PARAM_STR, // SQLT_DAT 无效
        'CHAR'      => \PDO::PARAM_STR,
        'NCHAR'     => \PDO::PARAM_STR,
        'VARCHAR2'  => \PDO::PARAM_STR,
        'NVARCHAR2' => \PDO::PARAM_STR,
        'VARCHAR'   => \PDO::PARAM_STR,
        'STRING'    => \PDO::PARAM_STR,
        'CLOB'      => \PDO::PARAM_LOB,
        'NCLOB'     => \PDO::PARAM_LOB,
        'BLOB'      => \PDO::PARAM_LOB+\PDO::PARAM_LOB, // 自行定义的
        // 'BFILE'     => SQLT_BFILEE,
        // 'CFILE'     => SQLT_CFILEE,
        // 'RAW'       => SQLT_BIN,
        // 'LONG RAW'  => SQLT_LBI,
        // 'ROWID'     => SQLT_RDD, // SQLT_ROWID 无效,
    ];

    /**
     * 构造函数
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!empty($config)) {
            $this->config = array_merge($this->config, $config);
        }
        putenv("NLS_LANG=SIMPLIFIED CHINESE_CHINA.AL32UTF8"); // 设置oracle客户端字符集

        $this->builder = new Builder($this);

        // 执行初始化操作
        $this->initialize();
    }

    /**
     * 取得数据库连接类实例
     * @access public
     * @param  mixed         $config 连接配置
     * @param  bool|string   $name 连接标识 true 强制重新连接
     * @return Connection
     * @throws Exception
     */
    public static function instance($config = [], $name = false)
    {
        if (false === $name) {
            $name = md5(serialize($config));
        }

        if (true === $name || !isset(self::$instance[$name])) {
            if (empty($config['type'])) {
                throw new InvalidArgumentException('Undefined db type');
            }

            $options = self::parseConfig($config);

            // 记录初始化信息
            Container::get('app')->log('[ DB ] INIT ' . $config['type']);

            if (true === $name) {
                $name = md5(serialize($config));
            }

            self::$instance[$name] = new static($options); // 直接创建自己
        }

        return self::$instance[$name];
    }

    /**
     * 获取当前连接器类对应的Builder类
     * @access public
     * @return string
     */
    public function getBuilderClass()
    {
        return $this->builderClassName;
    }

    /**
     * 解析Oracle oci的连接字符串connection_string
     * @access protected
     * @param  array $config 连接信息
     * @return string
     */
    protected function parseDsn($config)
    {
        $dsn = "oci:dbname=" . sprintf('(DESCRIPTION=(ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = %s)(PORT = %u)))(CONNECT_DATA=(SID=%s)))', $config['hostname'], $config['hostport'], $config['database']); // oracle 连接字符串

        return $dsn;
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param  string $tableName
     * @return array
     */
    public function getFields($tableName)
    {
        list($tableName) = explode(' ', $tableName);
        $tableNames      = explode('.', $tableName);
        $tableName       = isset($tableNames[1]) ? $tableNames[1] : $tableNames[0];
        $sql             = sprintf("SELECT A.COLUMN_NAME,DATA_TYPE,DECODE(NULLABLE, 'Y', 0, 1) NOTNULL,DATA_DEFAULT,DECODE(A.COLUMN_NAME,B.COLUMN_NAME,1,0) PK FROM USER_TAB_COLUMNS A,(SELECT COLUMN_NAME FROM USER_CONSTRAINTS C,USER_CONS_COLUMNS COL WHERE C.CONSTRAINT_NAME = COL.CONSTRAINT_NAME AND C.CONSTRAINT_TYPE = 'P' AND C.TABLE_NAME = '%1\$s') B WHERE TABLE_NAME = '%1\$s' AND A .COLUMN_NAME = B.COLUMN_NAME (+)", strtoupper($tableName)); // 查询oracle数据表信息

        $stmt   = $this->query($sql, [], false, true);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $info = [];
        if ($result) {
            foreach ($result as $key => $val) {
                $val                       = array_change_key_case($val, CASE_LOWER);
                $info[$val['column_name']] = [
                    'name'    => $this->valueCase($val['column_name']), // 字段大小写，根据this->attrCase来决定
                    'type'    => $this->valueCase($val['data_type']), // 数据类型，根据this->attrCase来决定
                    'notnull' => $val['notnull'],
                    'default' => $val['data_default'],
                    'primary' => $val['pk'],
                    'autoinc' => $val['pk'] && $val['data_type'] == 'number' ? 1 : 0,
                ];
            }
        }

        return $this->fieldCase($info);
    }

    /**
     * 取得数据库的表信息
     * @access public
     * @param string $dbName
     * @return array
     */
    public function getTables($dbName = '')
    {
        $stmt   = $this->query("SELECT TABLE_NAME FROM USER_TABLES", [], false, true);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $info   = [];

        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * SQL性能分析
     * @access protected
     * @param  string $sql
     * @return array
     */
    protected function getExplain($sql)
    {
        // oracle数据库的SQL计划查询
        $explain = [
            "EXPLAIN PLAN FOR {$sql}",
            "SELECT PLAN_TABLE_OUTPUT FROM TABLE(DBMS_XPLAN.DISPLAY('PLAN_TABLE'))",
        ];

        $stmt1  = $this->linkID->query($explain[0]);
        $stmt2  = $this->linkID->query($explain[1]);
        $result = $stmt2->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            // TODO: 这个地方必须处理
            $this->log('SQL:' . $sql . ', EXPLAIN PLAN ERROR:' . $e['message'], 'warn');
        }

        return $result;
    }

    /**
     * 根据attrCase对值进行大小写转换
     * @param [type] $info
     * @return void
     */
    protected function valueCase($info)
    {
        switch ($this->attrCase) {
            case \PDO::CASE_LOWER:
                $info = is_array($info) ? array_map('strtolower', $info) : strtolower($info);
                break;
            case \PDO::CASE_UPPER:
                $info = is_array($info) ? array_map('strtoupper', $info) : strtoupper($info);
                break;
            case \PDO::CASE_NATURAL:
            default:
                // 不做转换
        }

        return $info;
    }

    /**
     * 对返数据表字段信息进行大小写转换出来
     * @access public
     * @param  array $info 字段信息
     * @return array
     */
    public function fieldCase($info, $recursive = false)
    {
        // 字段大小写转换
        switch ($this->attrCase) {
            case \PDO::CASE_LOWER:
                // $info = array_change_key_case($info, CASE_LOWER);
                $info = $this->arrayChangeKeyCaseRecursive($info, CASE_LOWER, $recursive);
                break;
            case \PDO::CASE_UPPER:
                // $info = array_change_key_case($info, CASE_UPPER);
                $info = $this->arrayChangeKeyCaseRecursive($info, CASE_UPPER, $recursive);
                break;
            case \PDO::CASE_NATURAL:
            default:
                // 不做转换
        }

        return $info;
    }

    /**
     * 递归进行大小写转换
     *
     * @param [type] $array
     * @param [type] $case
     * @param boolean $flag  默认false，即不递归
     * @return void
     */
    protected function arrayChangeKeyCaseRecursive($array, $case = CASE_LOWER, $flag = false)
    {
        $temp = array_change_key_case($array, $case);
        if ($flag) {
            foreach ($temp as $key => $value) {
                if (is_array($value)) {
                    $temp[$key] = $this->arrayChangeKeyCaseRecursive($value, $case, true);
                }
            }
        }
        return $temp;
    }

    /**
     * 获取字段绑定类型 
     * @access public
     * @param  string $type 字段类型
     * @return integer
     */
    public function getFieldBindType($type)
    {
        // 从字段映射表中，把oracle字段类型转换为PDO类型
        return $this->dataTypes[strtoupper($type)] ?? \PDO::PARAM_STR;
    }

    /**
     * 连接数据库方法
     * @access public
     * @param  array         $config 连接参数
     * @param  integer       $linkNum 连接序号
     * @param  array|bool    $autoConnection 是否自动连接主数据库（用于分布式）
     * @return PDO
     * @throws Exception
     */
    public function connect(array $config = [], $linkNum = 0, $autoConnection = false)
    {
        if (isset($this->links[$linkNum])) {
            return $this->links[$linkNum];
        }

        if (!$config) {
            $config = $this->config;
        } else {
            $config = array_merge($this->config, $config);
        }

        // 连接参数
        if (isset($config['params']) && is_array($config['params'])) {
            $params = $config['params'] + $this->params;
        } else {
            $params = $this->params;
        }

        // 记录当前字段属性大小写设置
        $this->attrCase = $params[\PDO::ATTR_CASE];

        if (!empty($config['break_match_str'])) {
            $this->breakMatchStr = array_merge($this->breakMatchStr, (array) $config['break_match_str']);
        }

        try {
            if (empty($config['dsn'])) {
                $config['dsn'] = $this->parseDsn($config);
            }

            if ($config['debug']) {
                $startTime = microtime(true);
            }

            // $this->links[$linkNum] = new PDO($config['dsn'], $config['username'], $config['password'], $params);
            $this->links[$linkNum] = new OCIPDO($config['dsn'], $config['username'], $config['password'], $params); // 连接 oracle pdo => misuoka/ocipdo

            if ($config['debug']) {
                // 记录数据库连接信息
                $this->log('[ DB ] CONNECT:[ UseTime:' . number_format(microtime(true) - $startTime, 6) . 's ] ' . $config['dsn']);
            }

            return $this->links[$linkNum];
        } catch (\PDOException $e) {
            if ($autoConnection) {
                $this->log($e->getMessage(), 'error');
                return $this->connect($autoConnection, $linkNum);
            } else {
                throw $e;
            }
        }
    }

    /**
     * 执行查询 使用生成器返回数据
     * @access public
     * @param  string    $sql sql指令
     * @param  array     $bind 参数绑定
     * @param  bool      $master 是否在主服务器读操作
     * @param  Model     $model 模型对象实例
     * @param  array     $condition 查询条件
     * @param  mixed     $relation 关联查询
     * @return \Generator
     */
    public function getCursor($sql, $bind = [], $master = false, $model = null, $condition = null, $relation = null)
    {
        $this->initConnect($master);

        // 记录SQL语句
        $this->queryStr = $sql;

        $this->bind = $bind;

        Db::$queryTimes++;

        // 调试开始
        $this->debug(true);

        // 预处理
        $this->PDOStatement = $this->linkID->prepare($sql);

        // 是否为存储过程调用
        $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
        $procedure = $procedure || strtolower(substr(trim($sql), 0, 5)) === 'begin'; // oracle 另一种存储过程判断

        // 参数绑定
        if ($procedure) {
            $this->bindParam($bind);
        } else {
            $this->bindValue($bind);
        }

        // 执行查询
        $this->PDOStatement->execute();

        // 调试结束
        $this->debug(false, '', $master);

        // 返回结果集
        while ($result = $this->PDOStatement->fetch($this->fetchType)) {
            if ($model) {
                $instance = $model->newInstance($result, $condition);

                if ($relation) {
                    $instance->relationQuery($relation);
                }

                yield $instance;
            } else {
                yield $result;
            }
        }
    }

    /**
     * 执行查询 返回数据集
     * @access public
     * @param  string    $sql sql指令
     * @param  array     $bind 参数绑定
     * @param  bool      $master 是否在主服务器读操作
     * @param  bool      $pdo 是否返回PDO对象
     * @return array
     * @throws BindParamException
     * @throws \PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function query($sql, $bind = [], $master = false, $pdo = false)
    {
        $this->initConnect($master);

        if (!$this->linkID) {
            return false;
        }

        // 记录SQL语句
        $this->queryStr = $sql;

        $this->bind = $bind;

        Db::$queryTimes++;

        try {
            // 调试开始
            $this->debug(true);

            // 预处理
            $this->PDOStatement = $this->linkID->prepare($sql);

            // 是否为存储过程调用
            // BEGIN PROC_TEST(:id, :name); END;
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
            $procedure = $procedure || strtolower(substr(trim($sql), 0, 5)) === 'begin'; // oracle存储过程判断

            // 参数绑定
            if ($procedure) {
                $this->bindParam($bind);
            } else {
                $this->bindValue($bind);
            }

            // 执行查询
            $this->PDOStatement->execute();

            // 调试结束
            $this->debug(false, '', $master);

            // 返回结果集
            return $this->getResult($pdo, $procedure);
        } catch (\PDOException $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $master, $pdo);
            }

            throw new PDOException($e, $this->config, $this->getLastsql());
        } catch (\Throwable $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $master, $pdo);
            }

            throw $e;
        } catch (\Exception $e) {
            if ($this->isBreak($e)) {
                return $this->close()->query($sql, $bind, $master, $pdo);
            }

            throw $e;
        }
    }

    /**
     * 执行语句
     * @access public
     * @param  string        $sql sql指令
     * @param  array         $bind 参数绑定
     * @param  Query         $query 查询对象
     * @return int
     * @throws BindParamException
     * @throws \PDOException
     * @throws \Exception
     * @throws \Throwable
     */
    public function execute($sql, $bind = [], \think\db\Query $query = null)
    {
        $this->initConnect(true);

        if (!$this->linkID) {
            return false;
        }

        // 记录SQL语句
        $this->queryStr = $sql;

        $this->bind = $bind;

        Db::$executeTimes++;
        try {
            // 调试开始
            $this->debug(true);

            // 预处理
            $this->PDOStatement = $this->linkID->prepare($sql);

            // 是否为存储过程调用
            $procedure = in_array(strtolower(substr(trim($sql), 0, 4)), ['call', 'exec']);
            $procedure = $procedure || strtolower(substr(trim($sql), 0, 5)) === 'begin'; // oracle存储过程判断

            // 参数绑定
            if ($procedure) {
                $this->bindParam($bind);
            } else {
                $this->bindValue($bind);
            }

            // 执行语句
            $this->PDOStatement->execute();

            // 调试结束
            $this->debug(false, '', true);

            if ($query && !empty($this->config['deploy']) && !empty($this->config['read_master'])) {
                $query->readMaster();
            }

            $this->numRows = $this->PDOStatement->rowCount();

            return $this->numRows;
        } catch (\PDOException $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind, $query);
            }

            throw new PDOException($e, $this->config, $this->getLastsql());
        } catch (\Throwable $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind, $query);
            }

            throw $e;
        } catch (\Exception $e) {
            if ($this->isBreak($e)) {
                return $this->close()->execute($sql, $bind, $query);
            }

            throw $e;
        }
    }

    /**
     * 插入记录
     * @access public
     * @param  Query   $query        查询对象
     * @param  boolean $replace      是否replace
     * @param  boolean $getLastInsID 返回自增主键
     * @param  string  $sequence     自增序列名
     * @return integer|string
     */
    public function insert(\think\db\Query $query, $replace = false, $getLastInsID = false, $sequence = null)
    {
        // 分析查询表达式
        $options = $query->getOptions();

        // 生成SQL语句
        $sql = $this->builder->insert($query, $replace);

        $bind = $query->getBind();

        if (!empty($options['fetch_sql'])) {
            // 获取实际执行的SQL语句
            return $this->getRealSql($sql, $bind);
        }

        // 执行操作
        $result = '' == $sql ? 0 : $this->execute($sql, $bind, $query);

        if ($result) {
            // $sequence  = $sequence ?: (isset($options['sequence']) ? $options['sequence'] : null);
            $sequence  = $sequence ?: (isset($options['sequence']) ? $options['sequence'] : $this->getSequence($options)); // 在未设置序列的情况下，通过序列前缀获取oracle序列名称（规则：前缀+去掉表前缀的表名）
            $lastInsId = $this->getLastInsID($sequence);

            $data = $options['data'];

            if ($lastInsId) {
                $pk = $query->getPk($options);
                if (is_string($pk)) {
                    $data[$pk] = $lastInsId;
                }
            }

            $query->setOption('data', $data);

            $query->trigger('after_insert');

            if ($getLastInsID) {
                return $lastInsId;
            }
        }

        return $result;
    }

    /**
     * 存储过程的输入输出参数绑定
     * @access public
     * @param  array $bind 要绑定的参数列表
     * @return void
     * @throws BindParamException
     */
    protected function bindParam($bind)
    {
        foreach ($bind as $key => $val) {
            $param = is_int($key) ? $key + 1 : ':' . $key;

            if (is_array($val)) {
                $result = $this->PDOStatement->bindParam($param, $bind[$key][0], $bind[$key][1], $bind[$key][2] ?? -1);
                // array_unshift($bind[$key], $param);
                // $result = call_user_func_array([$this->PDOStatement, 'bindParam'], $bind[$key]);  // 不支持不能引用的参数
            } else {
                $result = $this->PDOStatement->bindParam($param, $val);
            }

            if (!$result) {
                $param = array_shift($val);

                throw new BindParamException(
                    "Error occurred  when binding parameters '{$param}'",
                    $this->config,
                    $this->getLastsql(),
                    $bind
                );
            }
        }
    }

    /**
     * 根据表名，表前缀，序列前缀，获取oracle数据库序列名称
     *
     * @param [type] $options
     * @return void
     */
    protected function getSequence($options = null)
    {
        if (!empty($options) && $this->config['prefix_sequence'] && !empty($options['table'])) {
            $sequence = $this->config['prefix_sequence'] . str_ireplace($this->config['prefix'], "", $options['table']);
            $sql      = "SELECT COUNT(*) SEQ_COUNT FROM user_sequences WHERE sequence_name='" . strtoupper($sequence) . "'";
            $stmt     = $this->query($sql, [], false, true);
            if ($stmt->fetchColumn()) {
                return $sequence;
            }
        }

        return null;
    }
    /**
     * 获取最近插入的ID
     * @access public
     * @param  string  $sequence     自增序列名
     * @return string
     */
    public function getLastInsID($sequence = null)
    {
        return $sequence ? $this->linkID->lastInsertId($sequence) : -1; // 没有序列名称，则返回最后ID为-1，避免自动获取报异常
    }
}
