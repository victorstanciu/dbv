<?php

require_once dirname(__FILE__) . DS . 'Interface.php';
require_once dirname(__FILE__) . DS . 'PDO.php';

class DBV_Adapter_MySQL extends DBV_Adapter_PDO
{
    protected function _getPDOAdapterParams()
    {
        return array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
        );
    }

    protected function _buildDsn($host = false, $port = false, $database_name = false)
    {
        if ($host[0] == "/") {
            $location = "unix_socket=$host";
        } else {
            $location = "host=$host;port=$port";
        }
        return "mysql:$location;dbname=$database_name";
    }

    protected function _getTablesQuery()
    {
        return $this->query('SHOW FULL TABLES');
    }

    protected function _getViewsQuery()
    {
        return $this->query('SHOW FULL TABLES');
    }

    protected function _getTriggersQuery()
    {
        return $this->query('SHOW TRIGGERS');
    }

    protected function _getFunctionsQuery()
    {
        return $this->query("SHOW FUNCTION STATUS WHERE Db = '{$this->database_name}'");
    }

    protected function _getProceduresQuery()
    {
        return $this->query("SHOW PROCEDURE STATUS WHERE Db = '{$this->database_name}'");
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
