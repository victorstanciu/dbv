<?php

$conf_list = array();

if ($handle = opendir('config')) {
    while (false !== ($entry = readdir($handle))) {
		if (array_pop(explode('.', $entry)) == 'php')
			array_push($conf_list, $entry);
    }
    closedir($handle);
}

$current_id = 1;
$current = $conf_list[$current_id];
