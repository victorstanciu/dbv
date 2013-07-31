<?php

/**
*    Call this file from the command line to jump to specific revision.
*    When using no arguments, it jumps to the last revision, example:
*    $ php cl.php
*
*    Or specify a specific commit as the first argument, example:
*    $ php cl.php 4 
*
*    Or if you havae the option to log to the db enabled, specify a commit as the first argument, example:
*    $ php cl.php jklrer328ujd92nmd
*/


require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib/functions.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'DBV.php';

$_GET['a'] = 'jumpto';
$_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

$dbv = DBV::instance();

if(!$argv[1]){
	$_POST['revision'] = $dbv->findLastRevision();
}elseif(!$argv[2]){
	$_POST['revision'] = $argv[1];
}else{
	$rev = $dbv->findRevisionFromCommit($argv[1]);
    if($rev){
    	$_POST['revision'] = $rev;
    }else{
    	die('Could not find revision');
    }
}

$dbv->authenticate();
$dbv->dispatch();
