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

use think\db\builder\Oracle;


class Builder extends Oracle
{
    /**
     * 生成insertall SQL
     * @access public
     * @param  Query $query   查询对象
     * @param  array $dataSet 数据集
     * @return string
     */
    public function insertAll(\think\db\Query $query, array $dataSet): string
    {
        $options = $query->getOptions();

        // 获取绑定信息
        $bind = $query->getFieldsBindType();

        // 获取合法的字段
        if ('*' == $options['field']) {
            $allowFields = array_keys($bind);
        } else {
            $allowFields = $options['field'];
        }

        $fields = [];
        $values = [];

        foreach ($dataSet as $k => $data) {
            $data = $this->parseData($query, $data, $allowFields, $bind);
            
            $values[] = 'SELECT ' . implode(',', array_values($data)) . ' from dual'; // 改为 oracle 的

            if (!isset($insertFields)) {
                $insertFields = array_keys($data);
            }
        }

        foreach ($insertFields as $field) {
            $fields[] = $this->parseKey($query, $field);
        }

        return str_replace(
            ['%INSERT%', '%TABLE%', '%EXTRA%', '%FIELD%', '%DATA%', '%COMMENT%'],
            [
                !empty($options['replace']) ? 'REPLACE' : 'INSERT',
                $this->parseTable($query, $options['table']),
                $this->parseExtra($query, $options['extra']),
                implode(' , ', $fields),
                implode(' UNION ALL ', $values),
                $this->parseComment($query, $options['comment']),
            ],
            $this->insertAllSql);
    }

    /**
     * 字段和表名处理
     * @access public
     * @param  Query     $query 查询对象
     * @param  mixed     $key   字段名
     * @param  bool      $strict   严格检测
     * @return string
     */
    public function parseKey(\think\db\Query $query, $key, bool $strict = false): string
    {
        // $key = trim($key);

        // if (strpos($key, '->') && false === strpos($key, '(')) {
        //     // JSON字段支持
        //     [$field, $name] = explode($key, '->');
        //     $key            = $field . '."' . $name . '"';
        // }

        // return $key;

        if (is_int($key)) {
            return (string) $key;
        } elseif ($key instanceof Raw) {
            return $key->getValue();
        }

        $key = trim($key);

        if (strpos($key, '.') && !preg_match('/[,\'\"\(\)`\s]/', $key)) {
            [$table, $key] = explode('.', $key, 2);

            $alias = $query->getOptions('alias');

            if ('__TABLE__' == $table) {
                $table = $query->getOptions('table');
                $table = is_array($table) ? array_shift($table) : $table;
            }

            if (isset($alias[$table])) {
                $table = $alias[$table];
            }
        } else {
            $tableName = $query->getOptions('table');
            $marks     = $tableName ? $tableName != $key : true; // 表名，默认不加双引号
        }

        if ($strict && !preg_match('/^[\w\.\*]+$/', $key)) {
            throw new Exception('not support data:' . $key);
        }

        if ($marks && '*' != $key && !preg_match('/[,\'\"\*\(\)`.\s]/', $key)) {
            // $key = '"' . $key . '"'; 
            $key = '"' . strtoupper($key) . '"';
        }

        if (isset($table)) {
            if (strpos($table, '.')) {
                // $table = str_replace('.', '"."', $table);
                $table = str_replace('.', '"."', strtoupper($table));
            }

            $key = '"' . $table . '".' . $key;
        }

        return $key;
    }
}