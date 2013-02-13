<?php

interface DBV_Adapter_Interface
{

    /**
     * Connects to the database
     * @throws DBV_Exception
     */
    public function connect(
        $host = false, $port = false, $username = false, $password = false, $database_name = false
    );

    /**
     * Runs an SQL query
     * @throws DBV_Exception
     */
    public function query($sql);

    /**
     * Must return an array() that contains all the schema object names in the database
     * @example return array('articles', 'comments', 'posts')
     * @throws DBV_Exception
     * @return array()
     */
    public function getSchema();

    /**
     * Given a schema object name, returns the SQL query that will create 
     * that schema object on any machine running the DBMS of choice.
     * @example CREATE TABLE / CREATE PROCEDURE queries in MySQL
     * @throws DBV_Exception
     */
    public function getSchemaObject($name);
    
    /**
     * Return an array containing the revision identifier strings for 
     * all revisions that have already been run on this database.
     * @example array('1', '3', '4', '5')
     * @throws DBV_Exception
     */
    public function getExistingRevisions();
    
    /**
     * Mark a given revision [identifier string] as having already
     * been run on this database.  Returns boolean true/false.
     * @throws DBV_Exception
     */
    public function setRevisionAsExisting($revision);
    
}