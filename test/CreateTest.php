<?php
require_once dirname(__FILE__) . "/../vendor/autoload.php";
$myconfig = require dirname(__FILE__) . '/../database.php'; // 加载数据库配置
// 'username' => '',
// 'password' => '',
// 'hostname' => '',
// 'hostport' => '',
// 'database' => '',

use ocipdo\PDO as OCIPDO;
use PHPUnit\Framework\TestCase;

class CreateTest extends TestCase
{
    protected static $pdo;
    protected static $tableNames = [
        'USER' => [
            ['UID', 'NUMBER PRIMARY KEY'],
            ['USERNAME', 'VARCHAR2(64)'],
            ['PASSWORD', 'VARCHAR2(64)'],
            ['PHONE', 'VARCHAR2(11)'],
            ['SEX', 'NUMBER'],
            ['CREATE_TIME', 'VARCHAR2(19)'],
            ['UPDATE_TIME', 'VARCHAR2(19)'],
            ['DELETE_TIME', 'VARCHAR2(19)'],
            ['LAST_LOGIN_TIME', 'VARCHAR2(19)'],
            ['STATUS', 'NUMBER'],
        ],
        'ROLE' => [
            ['RID', 'NUMBER PRIMARY KEY'],
            ['ROLE_NAME', 'VARCHAR2(64)'],
            ['ROLE_REMARK', 'VARCHAR2(512)'],
            ['CREATE_TIME', 'VARCHAR2(19)'],
            ['DELETE_TIME', 'VARCHAR2(19)'],
            ['UPDATE_TIME', 'VARCHAR2(19)'],
            ['STATUS', 'NUMBER'],
        ],
        'MENU' => [
            ['RID', 'NUMBER PRIMARY KEY'],
            ['MENU_NAME', 'VARCHAR2(64)'],
            ['MENU_REMARK', 'VARCHAR2(512)'],
            ['CREATE_TIME', 'VARCHAR2(19)'],
            ['UPDATE_TIME', 'VARCHAR2(19)'],
            ['DELETE_TIME', 'VARCHAR2(19)'],
            ['STATUS', 'NUMBER'],
            ['URL', 'VARCHAR2(255)'],
        ],
        'USER_ROLE' => [
            ['URID', 'NUMBER PRIMARY KEY'],
            ['UID', 'NUMBER'],
            ['RID', 'NUMBER'],
        ],
        'ROLE_MENU' => [
            ['RMID', 'NUMBER PRIMARY KEY'],
            ['RID', 'NUMBER'],
            ['MID', 'NUMBER'],
        ]
    ];

    protected static $tbPrefix = "MDBTB_"; // 数据表前缀
    protected static $tsPrefix = "MDBTS_"; // 自增序列前缀
    protected static $trPrefix = "MDBTR_"; // 触发器前缀

    public function setUp()
    {
        global $myconfig;

        $dsn       = sprintf('oci:dbname=//%s:%d/%s', $myconfig['hostname'], $myconfig['hostport'], $myconfig['database']);
        self::$pdo = new OCIPDO($dsn, $myconfig['username'], $myconfig['password']);
    }

    /**
     * 测试创建对象
     *
     * @return void
     */
    public function testObject()
    {
        $this->assertNotNull(self::$pdo);
    }

    /**
     * 测试连接
     *
     * @return null
     */
    public function testConnection()
    {
        $this->assertNotNull(self::$pdo->getConnection());
    }

    /**
     * 删除表测试
     *
     * @return void
     */
    public function testDropTable()
    {
        // PDOException:
        $sql = "SELECT TABLE_NAME FROM USER_TABLES";
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = array_column ($result, 'TABLE_NAME');

        $ret = true;
        try {
            $data = array_keys(self::$tableNames);
            foreach($data as $value) {
                $table = self::$tbPrefix . $value;
                if(in_array($table, $result)) {
                    // 执行删除语句
                    $sql = "DROP TABLE {$table}";
                    $stmt = self::$pdo->prepare($sql);
                    $stmt->execute();
                } 
            }
        } catch(Exception $e) {
            var_dump($e->getMessage());
            $ret = false;
        }

        $this->assertTrue($ret);
    }

    /**
     * 创建数据表测试
     *
     * @return void
     */
    public function testCreateTable()
    {
        $ret = true;
        try {
            foreach(self::$tableNames as $key => $value) {
                $sql = "CREATE TABLE " . self::$tbPrefix . $key;
                $fields = [];
                foreach ($value as $vo) {
                    $fields[] = '"' . $vo[0] . '" ' . $vo[1];
                }
                $sql .= "(" . implode(',', $fields) . ")";

                $stmt = self::$pdo->prepare($sql);
                $stmt->execute();
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $ret = false;
        }
        
        $this->assertTrue($ret);
    }

    /**
     * 删除自增序列
     *
     * @return void
     */
    public function testDropSequence()
    {
        global $myconfig;

        $sql = "SELECT SEQUENCE_NAME FROM dba_sequences WHERE sequence_owner = '" . strtoupper($myconfig['username']) . "'";
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = array_column($result, 'SEQUENCE_NAME');

        $ret = true;
        try {
            $data = array_keys(self::$tableNames);
            foreach($data as $value) {
                $sequence = self::$tsPrefix . $value;
                if(in_array($sequence, $result)) {
                    // 执行删除语句
                    $sql = "DROP SEQUENCE {$sequence}";
                    $stmt = self::$pdo->prepare($sql);
                    $stmt->execute();
                } 
            }
        } catch(Exception $e) {
            var_dump($e->getMessage());
            $ret = false;
        }

        $this->assertTrue($ret);
    }

    /**
     * 创建序列测试
     *
     * @return void
     */
    public function testCreateSequence()
    {
        $ret = true;
        try {
            foreach(self::$tableNames as $key => $value) {
                $sql = "CREATE SEQUENCE " . (self::$tsPrefix . $key) . " INCREMENT BY 1 START WITH 1 NOMAXVALUE NOCACHE";
                $stmt = self::$pdo->prepare($sql);
                $stmt->execute();
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $ret = false;
        }
        
        $this->assertTrue($ret);
    }

    /**
     * 创建触发器测试
     *
     * @return void
     */
    public function testCreateTrigger()
    {
        $ret = true;
        try {
            foreach(self::$tableNames as $key => $value) {
                $table = self::$tbPrefix . $key;
                $sequence = self::$tsPrefix . $key;
                $trigger = self::$trPrefix . $key;
                $sql = "CREATE OR REPLACE
                TRIGGER {$trigger} BEFORE INSERT ON {$table} FOR EACH ROW WHEN (new.\"{$value[0][0]}\" IS null)
                BEGIN
                SELECT {$sequence}.nextval
                into:new.\"{$value[0][0]}\"
                FROM dual;
                END;";
                $stmt = self::$pdo->prepare($sql);
                $stmt->execute();
            }
        } catch (Exception $e) {
            var_dump($e->getMessage());
            $ret = false;
        }
        
        $this->assertTrue($ret);
    }
}
