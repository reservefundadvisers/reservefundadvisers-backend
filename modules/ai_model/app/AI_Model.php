<?php

include_once('../../../app/init.php');
include_once('../../../app/global_func.php');

include_once('AI_Model.class.php');

use Dotenv\Dotenv;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/../../../');
    $dotenv->load();
} catch (Exception $e) {
    // Handle the exception if .env file is missing or cannot be loaded
    // rfa_create_log("[OTP] Could not load .env file: " . $e->getMessage());
    return false;
}

// check if logged in and validated 
if (!$auth->isValidated()) {
    
    // send response
    /** @var \Simplon\Mysql\Mysql $db */
    $db->close();
    // die_response(['error' => 'Unauthorized Access']);
    send_json_response(false, 401, 'Unauthorized Access');
    die();
}

// script response array
$response = [];

$className = 'AI_Model';

// commands permissions
$allowed_cmd =  [
    'get' => ['admin','client_admin','company_admin','manager'],
    'edit' => ['admin','client_admin','company_admin','manager'],
    'save' => ['admin','client_admin','company_admin','manager'],
    'delete' => ['admin','client_admin','company_admin','manager'],
    'set' => ['admin','client_admin','company_admin','manager']
];

$request = check_request();

ob_end_clean();

if (!isset($request['cmd'])) {

    $response = ['error' => 'Bad Request'];
} else {

    $cmd = $request['cmd'];
    unset($request['cmd']);

    // check if current user is allowed to execute a command
    if (isset($allowed_cmd[$cmd]) && array_has($allowed_cmd[$cmd], $auth->role()) === false) {
        $response = ['error' => 'Unauthorized operation'];
    } else {

        $handler = new $className();
        $response = $handler->process($cmd, $request);
    }
}

// send response
/** @var \Simplon\Mysql\Mysql $db */
$db->close();
die_response($response);
