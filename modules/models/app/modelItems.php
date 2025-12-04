<?php

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');

    include_once('ModelItems.class.php');

    // Dynamic CORS origin handling
    $allowed_origins = [
        'http://localhost:5173',
        'http://localhost:5174',
        'https://absd.frontend.reservefundadvisors.com',
        'http://absd.frontend.reservefundadvisors.com',
        rtrim('https://absd.frontend.reservefundadvisors.com', '/'),  // Add production domains as needed
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Credentials: true");
        header("Vary: Origin");
    } else {
        // Fallback for development - be cautious with this in production
        header("Access-Control-Allow-Origin: *");
        // Note: Cannot use credentials with wildcard
    }
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Auth-Session, auth-session, cookie");

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(); // Allow preflight to succeed, but do not block real requests
    }

    // check if logged in and validated 
    if(!$auth->isValidated()){
        // send response
        $db->close();
        // die_response(['error' => 'Unauthorized Access']);
        send_json_response(false, 401, 'Unauthorized Access');
        die();
    }

    // script response array
    $response = [];

    $className = 'Models';
    

    // commands permissions
    $allowed_cmd =  [
                        'get' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin'],
                        'edit' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin'],
                        'save' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin'],
                        'delete' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin'],
                        'set' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin'],

                        'edit_actual' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin'],
                        'save_actual' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin']
                    ];

    $request = check_request();

    ob_end_clean();

    if(!isset($request['cmd'])){
        
        $response = ['error' => 'Bad Request'];

    }else{

        $cmd = $request['cmd']; unset($request['cmd']);

        // check if current user is allowed to execute a command
        if(isset($allowed_cmd[$cmd]) && array_has($allowed_cmd[$cmd], $auth->role()) === false){
            $response = ['error' => 'Unauthorized operation'];
        
        }else{
            
            $handler = new $className();
            $response = $handler->process($cmd, $request);
        }
    }


    // send response
    $db->close();
    die_response($response);


    

?>