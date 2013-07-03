<?php

$conf_list = array();

if ($handle = opendir('config')) {
    while (false !== ($entry = readdir($handle))) {
		if (array_pop(explode('.', $entry)) == 'php')
			array_push($conf_list, $entry);
    }
    closedir($handle);
}

$current_id = 2;
if (!isset($conf_list[$current_id]))
	$current_id = 0;
$current = $conf_list[$current_id];
