<?php
/*
 * @Description:
 * @Author: Misuoka
 * @Github: https://github.com/misuoka
 * @Licensed: MIT
 * @Version: 1.0.0
 * @Date: 2019-04-10 14:52:38
 * @LastEditTime: 2019-04-25 10:12:00
 */

namespace think\oci;


// use think\Db;
// use think\Collection;
// use think\db\Expression;
// use think\db\Where;
// use think\Exception;
// use think\model\relation\OneToOne;
// use think\oci\Connection;
// use think\Loader;
use think\db\Query as BaseQuery;

class Query extends BaseQuery
{
    /**
     * 当前数据表前缀
     * @var string
     */
    protected $prefix = '';

    /**
     * 当前数据表序列前缀
     * @var string
     */
    protected $prefixSequence = '';

    /**
     * 日期查询表达式 TODO: 需要改为适用于oracle的
     * @var array
     */
    protected $timeRule = [
        'today'      => ['today', 'tomorrow'],
        'yesterday'  => ['yesterday', 'today'],
        'week'       => ['this week 00:00:00', 'next week 00:00:00'],
        'last week'  => ['last week 00:00:00', 'this week 00:00:00'],
        'month'      => ['first Day of this month 00:00:00', 'first Day of next month 00:00:00'],
        'last month' => ['first Day of last month 00:00:00', 'first Day of this month 00:00:00'],
        'year'       => ['this year 1/1', 'next year 1/1'],
        'last year'  => ['last year 1/1', 'this year 1/1'],
    ];

    /**
     * 日期查询快捷定义
     * @var array
     */
    protected $timeExp = ['d' => 'today', 'w' => 'week', 'm' => 'month', 'y' => 'year'];

    /**
     * 构造函数
     *
     * @param Connection $connection 数据库对象实例，可空
     */
    public function __construct(Connection $connection = null)
    {
        if (is_null($connection)) {
            $this->connection = Connection::instance();
        } else {
            $this->connection = $connection;
        }

        $this->prefix = $this->connection->getConfig('prefix');
        $this->prefixSequence = $this->connection->getConfig('prefix_sequence');
    }

    /**
     * 指定当前操作的数据表
     * @access public
     * @param  mixed $table 表名
     * @return $this
     */
    public function table($table)
    {
        if (is_string($table)) {
            if (strpos($table, ')')) {
                // 子查询 
                // 增对oracle进行处理，此处是解决子查询中存在 oracle limit 的情况，可以无极限嵌套，只是查询结果会不断多出 RN、RN_、RN__ ......
                $table = preg_replace_callback(
                    '/\bRN[_]*\b/i',
                    function ($match) {
                        return $match[0] . '_';
                    },
                    $table
                );
            } elseif (strpos($table, ',')) {
                $tables = explode(',', $table);
                $table  = [];

                foreach ($tables as $item) {
                    list($item, $alias) = explode(' ', trim($item));
                    if ($alias) {
                        $this->alias([$item => $alias]);
                        $table[$item] = $alias;
                    } else {
                        $table[] = $item;
                    }
                }
            } elseif (strpos($table, ' ')) {
                list($table, $alias) = explode(' ', $table);

                $table = [$table => $alias];
                $this->alias($table);
            }
        } else {
            $tables = $table;
            $table  = [];

            foreach ($tables as $key => $val) {
                if (is_numeric($key)) {
                    $table[] = $val;
                } else {
                    $this->alias([$key => $val]);
                    $table[$key] = $val;
                }
            }
        }

        $this->options['table'] = $table;

        return $this;
    }

    /**
     * 随机排序 TODO:
     * @access public
     * @return $this
     */
    public function orderRand()
    {
        $this->options['order'][] = '[rand]';
        return $this;
    }

    /**
     * 查询日期或者时间 TODO: 需要修改适用于oracle的
     * @access public
     * @param  string       $field 日期字段名
     * @param  string|array $op    比较运算符或者表达式
     * @param  string|array $range 比较范围
     * @param  string       $logic AND OR
     * @return $this
     */
    public function whereTime($field, $op, $range = null, $logic = 'AND')
    {
        if (is_null($range)) {
            if (is_array($op)) {
                $range = $op;
            } else {
                if (isset($this->timeExp[strtolower($op)])) {
                    $op = $this->timeExp[strtolower($op)];
                }

                if (isset($this->timeRule[strtolower($op)])) {
                    $range = $this->timeRule[strtolower($op)];
                } else {
                    $range = $op;
                }
            }

            $op = is_array($range) ? 'between' : '>=';
        }

        return $this->parseWhereExp($logic, $field, strtolower($op) . ' time', $range, [], true);
    }

    /**
     * 查询当前时间在两个时间字段范围
     * @access public
     * @param  string    $startField    开始时间字段
     * @param  string    $endField 结束时间字段
     * @return $this
     */
    public function whereBetweenTimeField($startField, $endField)
    {
        return $this->whereTime($startField, '<=', time())
            ->whereTime($endField, '>=', time());
    }

    /**
     * 查询当前时间不在两个时间字段范围
     * @access public
     * @param  string    $startField    开始时间字段
     * @param  string    $endField 结束时间字段
     * @return $this
     */
    public function whereNotBetweenTimeField($startField, $endField)
    {
        return $this->whereTime($startField, '>', time())
            ->whereTime($endField, '<', time(), 'OR');
    }

    /**
     * 查询日期或者时间范围
     * @access public
     * @param  string    $field 日期字段名
     * @param  string    $startTime    开始时间
     * @param  string    $endTime 结束时间
     * @param  string    $logic AND OR
     * @return $this
     */
    public function whereBetweenTime($field, $startTime, $endTime = null, $logic = 'AND')
    {
        if (is_null($endTime)) {
            $time    = is_string($startTime) ? strtotime($startTime) : $startTime;
            $endTime = strtotime('+1 day', $time);
        }

        return $this->parseWhereExp($logic, $field, 'between time', [$startTime, $endTime], [], true);
    }

    /**
     * 设置关联查询JOIN预查询 TODO: ?
     * @access public
     * @param  string|array $with 关联方法名称
     * @return $this
     */
    public function with($with)
    {
        if (empty($with)) {
            return $this;
        }

        if (is_string($with)) {
            $with = explode(',', $with);
        }

        $first = true;

        /** @var Model $class */
        $class = $this->model;
        foreach ($with as $key => $relation) {
            $closure = null;

            if ($relation instanceof \Closure) {
                // 支持闭包查询过滤关联条件
                $closure  = $relation;
                $relation = $key;
            } elseif (is_array($relation)) {
                $relation = $key;
            } elseif (is_string($relation) && strpos($relation, '.')) {
                list($relation, $subRelation) = explode('.', $relation, 2);
            }

            /** @var Relation $model */
            $relation = Loader::parseName($relation, 1, false);
            $model    = $class->$relation();

            if ($model instanceof OneToOne && 0 == $model->getEagerlyType()) {
                $table = $model->getTable();
                $model->removeOption()
                    ->table($table)
                    ->eagerly($this, $relation, true, '', $closure, $first);
                $first = false;
            }
        }

        $this->via();

        $this->options['with'] = $with;

        return $this;
    }
}
