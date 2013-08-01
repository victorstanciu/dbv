<?php

require_once dirname(__FILE__) . DS . 'Interface.php';

class DBV_Adapter_SQLite implements DBV_Adapter_Interface
{

    /**
     * @var PDO
     */
    protected $_connection;

    /**
     * @param type $database_name name of the database
     * @param type $username unused!
     * @param type $password unused!
     * @param type $database_name unused!
     * @throws DBV_Exception
     */
    public function connect($database_name = false, $username = false, $password = false, $database_name = false)
    {
        $this->database_name = $database_name; 
        
        try {
            $this->_connection = new PDO("sqlite:$database_name");
            $this->_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            throw new DBV_Exception($e->getMessage(), $e->getCode());
        }
    }

    public function query($sql)
    {
        try {
            return $this->_connection->query($sql);
        } catch (PDOException $e) {
            throw new DBV_Exception("Error during SQL query.", 0, $e);
        }
    }
    
    public function prepare($sql)
    {
        try {
            return $this->_connection->prepare($sql);
        } catch (PDOException $e) {
            throw new DBV_Exception("Error during SQL prepartion.", 0, $e);
        }
    }

    public function getSchema()
    {
        return array_merge(
            $this->getTables()
        );
    }

    public function getTables($prefix = false)
    {
        $return = array();

        try {
            $stmt = $this->query("SELECT name,type FROM sqlite_master WHERE type='table' ORDER BY name");
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
            }
            $stmt->closeCursor();
        }
        catch (DBV_Exception $ex){
            error_log($ex->getMessage());
            error_log($ex->getPrevious()->getMessage());
            error_log($ex->getTraceAsString());
        }
        return $return;
    }
    
    public function getTriggers($prefix = false)
    {
        $return = array();

        try {
            $stmt = $this->query("SELECT name,type FROM sqlite_master WHERE type='trigger' ORDER BY name");
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
            }
            $stmt->closeCursor();
        }
        catch (DBV_Exception $ex){
            error_log($ex->getMessage());
            error_log($ex->getPrevious()->getMessage());
            error_log($ex->getTraceAsString());
        }
        return $return;
    }
    
    public function getViews($prefix = false)
    {
        $return = array();

        try {
            $stmt = $this->query("SELECT name,type FROM sqlite_master WHERE type='view' ORDER BY name");
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $return[] = ($prefix ? "{$prefix} " : '') . $row[0];
            }
            $stmt->closeCursor();
        }
        catch (DBV_Exception $ex){
            error_log($ex->getMessage());
            error_log($ex->getPrevious()->getMessage());
            error_log($ex->getTraceAsString());
        }
        return $return;
    }
    
    public function getCreateView($name)
    {
        try {
            $stmt = $this->query("SELECT * FROM sqlite_master WHERE type='view' AND name = :name");
            $stmt->bindParam("name", $name);
            
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $stmt->closeCursor();
            return $row[4];
        }
        catch (DBV_Exception $ex){
            error_log($ex->getMessage());
            error_log($ex->getPrevious()->getMessage());
            error_log($ex->getTraceAsString());
        }
    }
    
    public function getCreateTable($name)
    {
        try {
            $stmt = $this->prepare("SELECT * FROM sqlite_master WHERE type='table' AND name = :name");
            $stmt->bindParam("name", $name);
            $result = $stmt->execute();
            
            if(! $result){
                throw new Exception("Error while executing prepared statement.", 0, null);
            }
            
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $stmt->closeCursor();
            
            return $row[4];
        }
        catch (DBV_Exception $ex){
            error_log($ex->getMessage());
            error_log($ex->getPrevious()->getMessage());
            error_log($ex->getTraceAsString());
        }
    }
    public function getCreateTrigger($name)
    {
        try {
            $stmt = $this->query("SELECT * FROM sqlite_master WHERE type='trigger' AND name = :name");
            $stmt->bindParam("name", $name);
            
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $stmt->closeCursor();
            return $row[4];
        }
        catch (DBV_Exception $ex){
            error_log($ex->getMessage());
            error_log($ex->getPrevious()->getMessage());
            error_log($ex->getTraceAsString());
        }
    }

    public function getSchemaObject($name)
    {
        switch ($name) {
            case in_array($name, $this->getTables()):
                return $this->getCreateTable($name);
            case in_array($name, $this->getViews()):
                return $this->getCreateView($name);
            case in_array($name, $this->getTriggers()):
            return $this->getCreateTrigger($name);
            default:
                throw new DBV_Exception("<strong>$name</strong> not found in the database");
        }

    }

}
?>