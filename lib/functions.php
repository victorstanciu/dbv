<?php

function __($message, $params = array())
{
    if (function_exists("gettext")) {
        $return = gettext($message);
    } else {
        $return = $message;
    }
    if (count($params)) {
        foreach ($params as $key => $value) {
            $return = str_replace("#{" . $key . "}", $value, $return);
        }
    }

    return $return;
}
