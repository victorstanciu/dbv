<?php

require_once dirname(__FILE__) . DS . 'Interface.php';

class DBV_Adapter_MySQL implements DBV_Adapter_Interface
{

    /**
     * @var PDO
     */
    protected $_connection;

    public function connect($host = false, $port = false, $username = false, $password = false, $database_name = false)
    {
        $this->database_name = $database_name; // the DB name is later used to restrict SHOW PROCEDURE STATUS and SHOW_FUNCTION_STATUS to the current database

        try {
            $this->_connection = new PDO("mysql:host=$host;port=$port;dbname=$database_name", $username, $password, array(
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
            ));
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            throw new DBV_Exception($e->getMessage(), (int) $e->getCode());
        }
    }

    public function query($sql)
    {
        try {
            return $this->_connection->query($sql);
        } catch (PDOException $e) {
            throw new DBV_Exception($e->getMessage(), (int) $e->getCode());
        }
    }

    public function getSchema()
    {
        return array_merge(
            $this->getTables(),
            $this->getViews(),
            $this->getTriggers(),
            $this->getProcedures(),
            $this->getFunctions()
        );
    }

    public function getTables($prefix = false)
    {
        $return = array();

        $result = $this->query('SHOW FULL TABLES');
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            if ($row[1] != 'BASE TABLE') {
                continue;
            }
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }

        return $return;
    }

    public function getViews($prefix = false)
    {
        $return = array();

        $result = $this->query('SHOW FULL TABLES');
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            if ($row[1] != 'VIEW') {
                continue;
            }
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }

        return $return;
    }

    public function getTriggers($prefix = false)
    {
        $return = array();

        $result = $this->query('SHOW TRIGGERS');
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
        }

        return $return;
    }

    public function getFunctions($prefix = false)
    {
        $return = array();

        $result = $this->query("SHOW FUNCTION STATUS WHERE Db = '{$this->database_name}'");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[1];
        }

        return $return;
    }

    public function getProcedures($prefix = false)
    {
        $return = array();

        $result = $this->query("SHOW PROCEDURE STATUS WHERE Db = '{$this->database_name}'");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $return[] = ($prefix ? "{$prefix} " : '') . $row[1];
        }

        return $return;
    }

    public function getSchemaObject($name)
    {
        $index = 1;
        switch ($name) {
            case in_array($name, $this->getTables()):
                $type = 'table';
                break;
            case in_array($name, $this->getViews()):
                $type = 'view';
                break;
            case in_array($name, $this->getTriggers()):
                $type = 'trigger';
                $index = 2;
                break;
            case in_array($name, $this->getProcedures()):
                $type = 'procedure';
                $index = 2;
                break;
            case in_array($name, $this->getFunctions()):
                $type = 'function';
                $index = 2;
                break;
            default:
                throw new DBV_Exception("<strong>$name</strong> not found in the database");
        }

        $query = "SHOW CREATE $type `$name`";
        $result = $this->query($query);

        $row = $result->fetch(PDO::FETCH_NUM);
        $return = $row[$index];

        // MySQL's SHOW CREATE TABLE command also includes the AUTO_INCREMENT value, so we're removing it here
        if ($type == 'table') {
            $return = preg_replace("/\s?AUTO_INCREMENT=\d+\s?/", " ", $return);
        }

        return $return;
    }

}
