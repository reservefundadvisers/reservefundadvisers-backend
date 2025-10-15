<?php

include_once('../../../app/init.php');
include_once('../../../app/global_func.php');

include_once('Banks.class.php');

// check if logged in and validated 
if (!$auth->isValidated()) {

    /** @var \Simplon\Mysql\Mysql $db */
    $db->close(); // send response
    send_json_response(false, 401, 'Unauthorized Access');
    die();
}

// script response array
$response = [];

$className = 'Banks';

$allowed_cmd_roles = ['admin', 'manager', 'company_admin', 'company_user', 'client_admin'];

// commands permissions
$allowed_cmd =  [
    'get' => $allowed_cmd_roles,
    'edit' => $allowed_cmd_roles,
    'save' => $allowed_cmd_roles,
    'delete' => $allowed_cmd_roles,
    'set' => $allowed_cmd_roles,
];

$request = check_request();

ob_end_clean();

if (!isset($request['cmd'])) {

    return send_json_response(false, 400, 'Bad Request');
    
} else {

    $cmd = $request['cmd'];
    unset($request['cmd']);

    // check if current user is allowed to execute a command
    if (isset($allowed_cmd[$cmd]) && array_has($allowed_cmd[$cmd], $auth->role()) === false) {
       return send_json_response(false, 401, 'Unauthorized operation');
    } else {

        $handler = new $className();
        $response = $handler->process($cmd, $request);
    }
}


/** @var \Simplon\Mysql\Mysql $db */
$db->close();
die_response($response);
