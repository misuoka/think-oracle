<?php
/*
 * @Description: 
 * @Author: Misuoka
 * @Github: https://github.com/misuoka
 * @Licensed: MIT
 * @Version: 1.0.0
 * @Date: 2019-04-10 14:52:25
 * @LastEditTime: 2019-04-25 10:24:31
 */

namespace misuoka\think;  // 原命名空间为 think\oracle，但与官方的库存在冲突

use think\db\Builder as BaseBuilder;

class Builder extends BaseBuilder
{   
    /**
     * Connection 数据库连接对象实例
     *
     * @var Connection
     */
    protected $connection;


    // SQL表达式
    protected $selectSql = 'SELECT%FORCE%%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER% %LOCK%%COMMENT%';
    // protected $selectSqlLimit = 'SELECT * FROM (SELECT tp_.*,ROWNUM AS RN FROM (SELECT%FORCE%%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%) tp_ ) %LIMIT%%COMMENT%'; 
    protected $selectSqlLimit = 'SELECT * FROM (SELECT tp_.*,ROWNUM RN FROM (SELECT%FORCE%%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%) tp_%LIMIT_END%)%LIMIT_BEGIN%%COMMENT%'; // limit 专用 sql
    // SELECT * FROM 
    // (    
    //     SELECT thinkphp.*,ROWNUM RN FROM (  ) thinkphp WHERE ROWNUM <= 
    // ) WHERE RN > 
    
    protected $insertSql = 'INSERT INTO %TABLE% (%FIELD%) VALUES (%DATA%) %COMMENT%';

    protected $insertAllSql = 'INSERT INTO %TABLE% (%FIELD%) %DATA% %COMMENT%';

    // protected $updateSql = 'UPDATE %TABLE% SET %SET%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';
    protected $updateSql = 'UPDATE %TABLE% SET %SET%%JOIN%%WHERE%%ORDER% %COMMENT%';

    // protected $deleteSql = 'DELETE FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';
    protected $deleteSql = 'DELETE FROM %TABLE%%USING%%JOIN%%WHERE%%ORDER% %COMMENT%';


    /**
     * 时间范围查询 TODO:
     * @access protected
     * @param  Query     $query        查询对象
     * @param  string    $key
     * @param  string    $exp
     * @param  mixed     $value
     * @param  string    $field
     * @param  integer   $bindType
     * @return string
     */
    protected function parseBetweenTime(\think\db\Query $query, $key, $exp, $value, $field, $bindType)
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        return $key . ' ' . substr($exp, 0, -4)
        . $this->parseDateTime($query, $value[0], $field, $bindType)
        . ' AND '
        . $this->parseDateTime($query, $value[1], $field, $bindType);

    }

    /**
     * 日期时间条件解析 TODO:
     * @access protected
     * @param  Query     $query        查询对象
     * @param  string    $value
     * @param  string    $key
     * @param  integer   $bindType
     * @return string
     */
    protected function parseDateTime(\think\db\Query $query, $value, $key, $bindType = null)
    {
        $options = $query->getOptions();

        // 获取时间字段类型
        if (strpos($key, '.')) {
            list($table, $key) = explode('.', $key);

            if (isset($options['alias']) && $pos = array_search($table, $options['alias'])) {
                $table = $pos;
            }
        } else {
            $table = $options['table'];
        }

        $type = $this->connection->getTableInfo($table, 'type');

        if (isset($type[$key])) {
            $info = $type[$key];
        }

        if (isset($info)) {
            if (is_string($value)) {
                $value = strtotime($value) ?: $value;
            }

            if (preg_match('/(datetime|timestamp)/is', $info)) {
                // 日期及时间戳类型
                $value = date('Y-m-d H:i:s', $value);
            } elseif (preg_match('/(date)/is', $info)) {
                // 日期及时间戳类型
                $value = date('Y-m-d', $value);
            }
        }

        $name = $query->bind($value, $bindType);

        return ':' . $name;
    }

    /**
     * limit分析
     * @access protected
     * @param  Query     $query        查询对象
     * @param  mixed     $limit
     * @return string
     */
    protected function parseLimit(\think\db\Query $query, $limit)
    {
        $limitStr = '';

        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr = "(RN > " . $limit[0] . ") AND (RN <= " . ($limit[0] + $limit[1]) . ")";
            } else {
                $limitStr = "(RN > 0 AND RN <= " . $limit[0] . ")";
            }
        }
        
        return $limitStr ? ' WHERE ' . $limitStr : '';
    }

    /**
     * 获取limit的最大行数
     *
     * @param [type] $limit
     * @return void
     */
    protected function limitRowsCount($limit)
    {
        $limit = explode(',', $limit);
        if (count($limit) > 1) {
            $limit = $limit[1] - $limit[0];
        } else {
            $limit = $limit[0];
        }

        return $limit;
    }

    /**
     * 获取limit的起始范围（针对Oracle分页特性，选择高效的分页方式）
     *
     * @param Query $query
     * @param [type] $limit
     * @return void
     */
    protected function parseLimitBegin(\think\db\Query $query, $limit)
    {
        $limitStr = '';

        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr = ' WHERE RN > ' . $limit[0];
            } else {
                $limitStr = ' WHERE RN > 0 ';
            }
        }
        
        return $limitStr;
    }

    /**
     * 获取limit的终止范围（针对Oracle分页特性，选择高效的分页方式）
     *
     * @param Query $query
     * @param [type] $limit
     * @return void
     */
    protected function parseLimitEnd(\think\db\Query $query, $limit)
    {
        $limitStr = '';

        if (!empty($limit)) {
            $limit = explode(',', $limit);
            if (count($limit) > 1) {
                $limitStr = ' WHERE ROWNUM <= ' . ($limit[0] + $limit[1]);
            } else {
                $limitStr = ' WHERE ROWNUM <= ' . $limit[0];
            }
        }
        
        return $limitStr;
    }

    /**
     * 设置锁机制
     * @access protected
     * @param  Query         $query        查询对象
     * @param  bool|string   $lock
     * @return string
     */
    protected function parseLock(\think\db\Query $query, $lock = false)
    {

        // "SELECT * FROM (SELECT thinkphp.*,ROWNUM AS NUMROW FROM (SELECT /*+INDEX(su INDEX_TEST_NOW)*/ * FROM tb_systemuser su) thinkphp )  WHERE (numrow>50) AND (numrow<=150) FOR UPDATE NOWAIT"
        // oci_execute(): ORA-02014: 不能从具有 DISTINCT, GROUP BY 等的视图选择 FOR UPDATE
        if (is_bool($lock)) {
            return $lock ? ' FOR UPDATE NOWAIT' : '';  // FOR UPDATE, FOR UPDATE NOWAIT, FOR UPDATE WAIT 3 -- oracle还有这种形式
        } elseif (is_string($lock) && !empty($lock)) {
            return ' ' . trim($lock) . ' ';
        }
    }

    /**
     * 生成查询SQL
     * @access public
     * @param  Query  $query  查询对象
     * @return string
     */
    public function select(\think\db\Query $query)
    {
        $options = $query->getOptions();

        if(!empty($options['limit'])) {
            // 增加oracle分页查询的sql
            return str_replace(
                ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT_BEGIN%', '%LIMIT_END%', '%UNION%', '%COMMENT%', '%FORCE%'],
                [
                    $this->parseTable($query, $options['table']),
                    $this->parseDistinct($query, $options['distinct']),
                    $this->parseField($query, $options['field']),
                    $this->parseJoin($query, $options['join']),
                    $this->parseWhere($query, $options['where']),
                    $this->parseGroup($query, $options['group']),
                    $this->parseHaving($query, $options['having']),
                    $this->parseOrder($query, $options['order']),
                    $this->parseLimitBegin($query, $options['limit']),
                    $this->parseLimitEnd($query, $options['limit']),
                    $this->parseUnion($query, $options['union']),
                    $this->parseComment($query, $options['comment']),
                    $this->parseForce($query, $options['force']),
                ],
                $this->selectSqlLimit);
        } else {
            return str_replace(
                ['%TABLE%', '%DISTINCT%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
                [
                    $this->parseTable($query, $options['table']),
                    $this->parseDistinct($query, $options['distinct']),
                    $this->parseField($query, $options['field']),
                    $this->parseJoin($query, $options['join']),
                    $this->parseWhere($query, $options['where']),
                    $this->parseGroup($query, $options['group']),
                    $this->parseHaving($query, $options['having']),
                    $this->parseOrder($query, $options['order']),
                    '', // limit 替换为空串
                    $this->parseUnion($query, $options['union']),
                    $this->parseLock($query, $options['lock']),
                    $this->parseComment($query, $options['comment']),
                    $this->parseForce($query, $options['force']),
                ],
                $this->selectSql);
        }
    }

    /**
     * 生成Insert SQL
     * @access public
     * @param  Query     $query   查询对象
     * @param  bool      $replace 是否replace
     * @return string
     */
    public function insert(\think\db\Query $query, $replace = false)
    {
        $options = $query->getOptions();

        // 分析并处理数据
        $data = $this->parseData($query, $options['data']);
        if (empty($data)) {
            return '';
        }

        $fields = array_keys($data);
        $values = array_values($data);

        // oracle 没有 REPLACE 功能，去掉replace的替换
        return str_replace(
            ['%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                $this->parseTable($query, $options['table']),
                implode(' , ', $fields),
                implode(' , ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertSql);
    }

    /**
     * 生成insertall SQL
     * @access public
     * @param  Query     $query   查询对象
     * @param  array     $dataSet 数据集
     * @param  bool      $replace 是否replace
     * @return string
     */
    public function insertAll(\think\db\Query $query, $dataSet, $replace = false)
    {
        $options = $query->getOptions();

        // 获取合法的字段
        if ('*' == $options['field']) {
            $allowFields = $this->connection->getTableFields($options['table']);
        } else {
            $allowFields = $options['field'];
        }

        // 获取绑定信息
        $bind = $this->connection->getFieldsBind($options['table']);

        foreach ($dataSet as $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind);

            $values[] = 'SELECT ' . implode(',', array_values($data)) . ' from dual'; // 改为 oracle 的
            // $values[] = 'SELECT ' . implode(',', array_values($data));

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        $fields = [];

        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field);
        }

        // oracle 没有 REPLACE 功能，去掉replace的替换
        return str_replace(
            ['%TABLE%', '%FIELD%', '%DATA%', '%COMMENT%'], 
            [
                $this->parseTable($query, $options['table']),
                implode(' , ', $fields),
                implode(' UNION ALL ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertAllSql);
    }

    /**
     * 生成slect insert SQL TODO: ?
     * @access public
     * @param  Query     $query  查询对象
     * @param  array     $fields 数据
     * @param  string    $table  数据表
     * @return string
     */
    public function selectInsert(\think\db\Query $query, $fields, $table)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        foreach ($fields as &$field) {
            $field = $this->parseKey($query, $field, true);
        }

        return 'INSERT INTO ' . $this->parseTable($query, $table) . ' (' . implode(',', $fields) . ') ' . $this->select($query);
    }
}
