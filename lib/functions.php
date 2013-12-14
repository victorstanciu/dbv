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

    function authenticate()
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
