<?php

/**
 * Copyright (c) 2012 Victor Stanciu (http://victorstanciu.ro)
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 * IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
 * CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
 * TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package DBV
 * @version 1.0.3
 * @author Victor Stanciu <vic.stanciu@gmail.com>
 * @link http://dbv.vizuina.com
 * @copyright Victor Stanciu 2012
 */
class DBV_Exception extends Exception
{

}

class DBV
{

    protected $_action = "index";
    protected $_adapter;
    protected $_log = array();
    protected $_revisions = array();
    public $run_revisions = array();
    
    private function __construct() {
        $this->_loadRunRevisions();
    }

    public function authenticate()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        } else {
            if (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                $authorization = $headers['HTTP_AUTHORIZATION'];
            }
        }

        list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) = explode(':', base64_decode(substr($authorization, 6)));
        if (strlen(DBV_USERNAME) && strlen(DBV_PASSWORD) && (!isset($_SERVER['PHP_AUTH_USER']) || !($_SERVER['PHP_AUTH_USER'] == DBV_USERNAME && $_SERVER['PHP_AUTH_PW'] == DBV_PASSWORD))) {
            header('WWW-Authenticate: Basic realm="DBV interface"');
            header('HTTP/1.0 401 Unauthorized');
            echo _('Access denied');
            exit();
        }
    }

    /**
     * @return DBV_Adapter_Interface
     */
    protected function _getAdapter($schema)
    {
        if (!$this->_adapter) {
            $file = DBV_ROOT_PATH . DS . 'lib' . DS . 'adapters' . DS . DB_ADAPTER . '.php';
            if (file_exists($file)) {
                require_once $file;

                $class = 'DBV_Adapter_' . DB_ADAPTER;
                if (class_exists($class)) {
                    $adapter = new $class;
                    try {
                        $adapter->connect(DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, $schema);//DB_NAME
                        $this->_adapter = $adapter;
                    } catch (DBV_Exception $e) {
                        $this->error("[{$e->getCode()}] " . $e->getMessage());
                    }
                }
            }
        } else {
            try {
                $this->_adapter->connect(DB_HOST, DB_PORT, DB_USERNAME, DB_PASSWORD, $schema);//DB_NAME
            } catch (DBV_Exception $e) {
                $this->error("[{$e->getCode()}] " . $e->getMessage());
            }
        }

        return $this->_adapter;
    }

    public function dispatch()
    {
        $action = $this->_getAction() . "Action";
        $this->$action();
    }

    public function indexAction()
    {
        if ($this->_getAdapter(DB_NAME)) {
            $this->schemas = $this->_getSchema();
            $this->active_schema = DB_NAME;
            $this->_revisions = $this->_getRevisions();
        }

        $this->_view("index");
    }

    public function schemaAction()
    {
        $items = isset($_POST['items']) ? $_POST['items'] : array();
        $schema = isset($_POST['schema']) ? $_POST['schema'] : '';

        if ($this->_isXMLHttpRequest()) {
            if (!count($items)) {
                return $this->_json(array('error' => __("You didn't select any objects")));
            }

            foreach ($items as $item) {
                switch ($_POST['action']) {
                    case 'create':
                        $this->_createSchemaObject($schema, $item);
                        break;
                    case 'export':
                        $this->_exportSchemaObject($schema, $item);
                        break;
                }
            }

            $return = array('messages' => array());
            foreach ($this->_log as $message) {
                $return['messages'][$message['type']][] = $message['message'];
            }

            $return['items'] = $this->_getSchema();

            $this->_json($return);
        }
    }

    public function revisionsAction()
    {
        $revisions = filter_input(INPUT_POST, "revisions", FILTER_UNSAFE_RAW, array("flags" => FILTER_REQUIRE_ARRAY));

        if (is_array($revisions)) {
            $revisions = array_reverse($revisions);

            foreach ($revisions as $revision) {
                $files = $this->_getRevisionFiles($revision);

                if (count($files)) {
                    foreach ($files as $file) {
                        $file = DBV_REVISIONS_PATH . DS . $revision . DS . $file;
                        if (!$this->_runFile($file, DB_NAME)) {
                            break 2;
                        }
                    }
                }

                $this->_markRevisionAsRun($revision);
                $this->confirm(__("Executed revision #{revision}", array('revision' => "<strong>$revision</strong>")));
            }
        }

        if ($this->_isXMLHttpRequest()) {
            $return = array(
                'messages' => array(),
                'run_revisions' => $this->_revisions
            );
            foreach ($this->_log as $message) {
                $return['messages'][$message['type']][] = $message['message'];
            }
            $this->_json($return);

        } else {
            $this->indexAction();
        }
    }
    
    public function markAsRanAction() {
        $revisions = filter_input(INPUT_POST, "revisions", FILTER_UNSAFE_RAW, array("flags" => FILTER_REQUIRE_ARRAY));
        
        if (is_array($revisions)) {
            foreach ($revisions as $revision) {
                $this->_markRevisionAsRun($revision);
                $this->confirm(__("Revision #{revision} marked as ran", array('revision' => "<strong>$revision</strong>")));
            }
        }
        
        if ($this->_isXMLHttpRequest()) {
            $return = array(
                'messages' => array(),
                'revisions_marked' => $this->_revisions
            );
            
            foreach ($this->_log as $message) {
                $return['messages'][$message['type']][] = $message['message'];
            }
            
            $this->_json($return);

        } else {
            $this->indexAction();
        }
    }

    public function saveRevisionFileAction()
    {
        $revision = $_POST['revision'];
        // if the revision doesn't start with a number then error
        if (!ctype_digit($revision[0])) {
            $this->_json(array(
                'error' => __("Revision names must start with a number.")
            ));
        }
        if (preg_match('/^[a-z0-9\._\-]+$/i', $_POST['file'])) {
            $file = $_POST['file'];
        } else {
            $this->_json(array(
                'error' => __("Filename #{file} contains illegal characters. Please contact the developer.", array('file' => $_POST['file']))
            ));
        }

        $path = DBV_REVISIONS_PATH . DS . $revision . DS . $file;
        if (!file_exists($path)) {
            $this->_404();
        }

        $content = $_POST['content'];

        if (!@file_put_contents($path, $content)) {
            $this->_json(array(
                'error' => __("Couldn't write file: #{path}<br />Make sure the user running DBV has adequate permissions.", array('path' => "<strong>$path</strong>"))
            ));
        }

        $this->_json(array('ok' => true, 'message' => __("File #{path} successfully saved!", array('path' => "<strong>$path</strong>"))));
    }

    protected function _createSchemaObject($schema, $item)
    {
        $file = DBV_SCHEMA_PATH . DS . $schema . DS . "{$item}.sql";

        if (file_exists($file)) {
            if ($this->_runFile($file, $schema)) {
                $this->confirm(__("Created schema object #{item}", array('item' => "<strong>{$item}</strong>")));
            }
        } else {
            $this->error(__("Cannot find file for schema object #{item} (looked in #{schema_path})", array(
                'item' => "<strong>{$item}</strong>",
                'schema_path' => DBV_SCHEMA_PATH . DS . $schema
            )));
        }
    }

    protected function _exportSchemaObject($schema, $item)
    {
        try {
            $sql = $this->_getAdapter($schema)->getSchemaObject($item);

            $file = DBV_SCHEMA_PATH . DS . $schema . DS . "{$item}.sql";

            if (@file_put_contents($file, $sql)) {
                $this->confirm(__("Wrote file: #{file}", array('file' => "<strong>{$file}</strong>")));
            } else {
                $this->error(__("Cannot write file: #{file}", array('file' => "<strong>{$file}</strong>")));
            }
        } catch (DBV_Exception $e) {
            $this->error(($e->getCode() ? "[{$e->getCode()}] " : '') . $e->getMessage());
        }
    }

    protected function _runFile($file, $schema)
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        switch ($extension) {
            case 'sql':
                $content = file_get_contents($file);
                if ($content === false) {
                    $this->error(__("Cannot open file #{file}", array('file' => "<strong>{$file}</strong>")));
                    return false;
                }

                try {
                    $this->_getAdapter($schema)->query($content);
                    return true;
                } catch (DBV_Exception $e) {
                    $this->error("[{$e->getCode()}] {$e->getMessage()} in <strong>{$file}</strong>");
                }
                break;
        }

        return false;
    }

    protected function _getAction()
    {
        if (isset($_GET['a'])) {
            $action = $_GET['a'];
            if (in_array("{$action}Action", get_class_methods(get_class($this)))) {
                $this->_action = $action;
            }
        }
        return $this->_action;
    }

    protected function _view($view)
    {
        $file = DBV_ROOT_PATH . DS . 'templates' . DS . "$view.php";
        if (file_exists($file)) {
            include($file);
        }
    }

    protected function _getSchema()
    {
        $return = array();
        $schemas = $this->_getSchemas();

        foreach ($schemas as $schema) {
            $database = $this->_getAdapter($schema)->getSchema();
            $disk = $this->_getDiskSchema($schema);

            foreach ($database as $item) {
                $return[$schema][$item]['database'] = true;
            }

            foreach ($disk as $item) {
                $return[$schema][$item]['disk'] = true;
            }

            ksort($return[$schema]);
        }

        return $return;
    }

    protected function _getSchemas()
    {
        $return = array();

        foreach (new DirectoryIterator(DBV_SCHEMA_PATH) as $dir) {
            if ($dir->isDir() && !$dir->isDot()) {
                $return[] = $dir->getFilename();
            }
        }

        return $return;
    }

    protected function _getDiskSchema($schema)
    {
        $return = array();

        foreach (new DirectoryIterator(DBV_SCHEMA_PATH . DS . $schema) as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'sql') {
                $return[] = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            }
        }

        return $return;
    }

    protected function _getRevisions()
    {
        $return = array();

        foreach (new DirectoryIterator(DBV_REVISIONS_PATH) as $file) {
            $base_name = $file->getBasename();
            // check that the file is a directory, not a . and starts with a number
            if ($file->isDir() && !$file->isDot() && is_numeric($base_name[0])) {
                $return[] = $file->getBasename();
            }
        }

        rsort($return, SORT_NUMERIC);

        return $return;
    }

    protected function _loadRunRevisions()
    {
        $file = DBV_META_PATH . DS . 'revision';
        if (file_exists($file)) {
            return $this->run_revisions = json_decode(file_get_contents($file));
        }
    }

    protected function _markRevisionAsRun($revision)
    {
        $this->run_revisions[] = $revision;
        $file = DBV_META_PATH . DS . 'revision';
        if (!@file_put_contents($file, json_encode(array_unique($this->run_revisions)))) {
            $this->error("Cannot write revision file");
        }
    }

    protected function _getRevisionFiles($revision)
    {
        $dir = DBV_REVISIONS_PATH . DS . $revision;
        $return = array();

        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isFile() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'sql') {
                $return[] = $file->getBasename();
            }
        }

        sort($return, SORT_REGULAR);
        return $return;
    }

    protected function _getRevisionFileContents($revision, $file)
    {
        $path = DBV_REVISIONS_PATH . DS . $revision . DS . $file;
        if (file_exists($path)) {
            return file_get_contents($path);
        }

        return false;
    }

    public function log($item)
    {
        $this->_log[] = $item;
    }

    public function error($message)
    {
        $item = array(
            "type" => "error",
            "message" => $message
        );
        $this->log($item);
    }

    public function confirm($message)
    {
        $item = array(
            "type" => "success",
            "message" => $message
        );
        $this->log($item);
    }

    protected function _404()
    {
        header('HTTP/1.0 404 Not Found', true);
        exit('404 Not Found');
    }

    protected function _json($data = array())
    {
        header("Content-type: application/json");
        echo (is_string($data) ? $data : json_encode($data));
        exit();
    }

    protected function _isXMLHttpRequest()
    {
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            return true;
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if ($headers['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
                return true;
            }
        }

        return false;
    }

    /**
     * Singleton
     * @return DBV
     */
    static public function instance()
    {
        static $instance;
        if (!($instance instanceof self)) {
            $instance = new self();
        }
        return $instance;
    }

}