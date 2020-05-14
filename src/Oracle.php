<?php
/*
 * @Description:
 * @Author: Misuoka
 * @Github: https://github.com/misuoka
 * @Licensed: MIT
 * @Version: 1.0.0
 * @Date: 2020-05-12 13:33:51
 * @LastEditTime: 2020-05-12 13:33:51
 */
declare (strict_types = 1);

namespace misuoka\think;

use PDO;
// use PDOStatement;
use think\db\connector\Oracle as ThinkOracle;
use ocipdo\PDO as OCIPDO;
use think\db\exception\BindParamException;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\db\exception\PDOException;

class Oracle extends ThinkOracle
{
    /**
     * 连接数据库方法
     * @access public
     * @param array      $config         连接参数
     * @param integer    $linkNum        连接序号
     * @param array|bool $autoConnection 是否自动连接主数据库（用于分布式）
     * @return PDO
     * @throws PDOException
     */
    public function connect(array $config = [], $linkNum = 0, $autoConnection = false): PDO
    {
        if (isset($this->links[$linkNum])) {
            return $this->links[$linkNum];
        }

        if (empty($config)) {
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
        $this->attrCase = $params[PDO::ATTR_CASE];

        if (!empty($config['break_match_str'])) {
            $this->breakMatchStr = array_merge($this->breakMatchStr, (array) $config['break_match_str']);
        }

        try {
            if (empty($config['dsn'])) {
                $config['dsn'] = $this->parseDsn($config);
            }

            $startTime = microtime(true);

            $this->links[$linkNum] = new OCIPDO($config['dsn'], $config['username'], $config['password'], $params); // 连接 oracle pdo => misuoka/ocipdo

            // SQL监控
            if (!empty($config['trigger_sql'])) {
                $this->trigger('CONNECT:[ UseTime:' . number_format(microtime(true) - $startTime, 6) . 's ] ' . $config['dsn']);
            }

            return $this->links[$linkNum];
        } catch (\PDOException $e) {
            if ($autoConnection) {
                $this->db->log($e->getMessage(), 'error');
                return $this->connect($autoConnection, $linkNum);
            } else {
                throw $e;
            }
        }
    }

    /**
     * 获取当前连接器类对应的Builder类
     * @access public
     * @return string
     */
    public function getBuilderClass(): string
    {
        return '\\misuoka\\think\\Builder';
    }

    /**
     * 创建PDO实例
     * @param $dsn
     * @param $username
     * @param $password
     * @param $params
     * @return PDO
     */
    protected function createPdo($dsn, $username, $password, $params)
    {
        return new OCIPDO($dsn, $username, $password, $params);
    }

    /**
     * 取得数据表的字段信息
     * @access public
     * @param string $tableName
     * @return array
     */
    public function getFields(string $tableName): array
    {
        [$tableName] = explode(' ', $tableName);
        $sql         = "select a.column_name,data_type,DECODE (nullable, 'Y', 0, 1) notnull,data_default, DECODE (A .column_name,b.column_name,1,0) pk from all_tab_columns a,(select column_name from all_constraints c, all_cons_columns col where c.constraint_name = col.constraint_name and c.constraint_type = 'P' and c.table_name = '" . strtoupper($tableName) . "' ) b where table_name = '" . strtoupper($tableName) . "' and a.column_name = b.column_name (+)";

        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        if ($result) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);

                $info[$val['column_name']] = [
                    'name'    => $val['column_name'],
                    'type'    => $val['data_type'],
                    'notnull' => $val['notnull'],
                    'default' => $val['data_default'],
                    'primary' => $val['pk'],
                    'autoinc' => $val['pk'] && $val['data_type'] == 'number' ? 1 : 0, // 根据字段类型来确定，字符串主键哪来都自增
                ];
            }
        }

        return $this->fieldCase($info);
    }

    /**
     * 获取最近插入的ID
     * @access public
     * @param BaseQuery $query    查询对象
     * @param string    $sequence 自增序列名
     * @return mixed
     */
    public function getLastInsID(\think\db\BaseQuery $query, string $sequence = null)
    {
        return $sequence ? $this->linkID->lastInsertId($sequence) : -1;
        // $pdo    = $this->linkID->query("select {$sequence}.currval as id from dual");
        // $result = $pdo->fetchColumn();

        // return $result;
    }

    /**
     * 根据表名，表前缀，序列前缀，获取oracle数据库序列名称
     *
     * @param [type] $options
     * @return void
     */
    protected function getSequence(\think\db\BaseQuery $query, array $options = null)
    {
        $sequence  = $options['sequence'] ?? null;

        if(!$sequence) {
            if(!empty($options) && isset($this->config['prefix_sequence']) && $this->config['prefix_sequence'] && !empty($options['table'])) {
                $seqtemp = $this->config['prefix_sequence'] . str_ireplace($this->config['prefix'], "", $options['table']);
                $sql     = "SELECT COUNT(*) SEQ_COUNT FROM user_sequences WHERE sequence_name='" . strtoupper($seqtemp) . "'";
                $stmt    = $this->queryPDOStatement($query, $sql, []);
                if ($stmt->fetchColumn()) {
                    $sequence = $seqtemp;
                }
            }
        }

        return $sequence;
    }

    /**
     * 插入记录
     * @access public
     * @param BaseQuery $query        查询对象
     * @param boolean   $getLastInsID 返回自增主键
     * @return mixed
     */
    public function insert(\think\db\BaseQuery $query, bool $getLastInsID = false)
    {
        // 分析查询表达式
        $options = $query->parseOptions();

        // 生成SQL语句
        $sql = $this->builder->insert($query);

        // 执行操作
        $result = '' == $sql ? 0 : $this->execute($query, $sql, $query->getBind());

        if ($result) {
            $sequence  = $this->getSequence($query, $options);  // 改变获取 sequence 的方式
            $lastInsId = $this->getLastInsID($query, $sequence);

            $data = $options['data'];

            if ($lastInsId) {
                $pk = $query->getAutoInc();
                if ($pk) {
                    $data[$pk] = $lastInsId;
                }
            }

            $query->setOption('data', $data);

            $this->db->trigger('after_insert', $query);

            if ($getLastInsID && $lastInsId) {
                return $lastInsId;
            }
        }

        return $result;
    }

    /**
     * 存储过程的输入输出参数绑定
     * @access public
     * @param array $bind 要绑定的参数列表
     * @return void
     * @throws BindParamException
     */
    protected function bindParam(array $bind): void
    {
        foreach ($bind as $key => $val) {
            $param = is_numeric($key) ? $key + 1 : ':' . $key;

            if (is_array($val)) {
                $result = $this->PDOStatement->bindParam($param, $bind[$key][0], $bind[$key][1], $bind[$key][2] ?? -1);
                // array_unshift($val, $param);
                // $result = call_user_func_array([$this->PDOStatement, 'bindParam'], $val); // 不支持不能引用的参数
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
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
}
