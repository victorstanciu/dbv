<?php

function __($message, $params = array())
{
    $return = gettext($message);
    if (count($params)) {
        foreach ($params as $key => $value) {
            $return = str_replace("#{" . $key . "}", $value, $return);
        }
    }

    return $return;
}
