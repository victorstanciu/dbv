<?php

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config_list.php';
if ($current == '')
	throw new Exception('Make sure $current in "' . dirname(__FILE__) . DIRECTORY_SEPARATOR
						. 'config_list.php" is set to the path of your configuration file');
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . "config/" . $current;
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/functions.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'DBV.php';

$dbv = DBV::instance();
$dbv->authenticate();
$dbv->dispatch();