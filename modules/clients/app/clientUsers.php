<?php

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');
    include_once('../../../app/mail.php');

    include_once('ClientUsers.class.php');
    include_once('ClientPositions.class.php');
    include_once('ClientPositionRoles.php');
    $request = check_request();

    // Dynamic CORS origin handling
    $allowed_origins = [
        'http://localhost:5173',
        'http://localhost:5174',
        'https://absd.frontend.reservefundadvisors.com',
        'http://absd.frontend.reservefundadvisors.com'  // Add production domains as needed
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Credentials: true");
    } else {
        // Fallback for development - be cautious with this in production
        header("Access-Control-Allow-Origin: *");
        // Note: Cannot use credentials with wildcard
    }
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, auth_session, cookie");

    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(); // Allow preflight to succeed, but do not block real requests
    }

    // Allow public access for get_position and get_positions commands
    if(!(isset($request['cmd']) && ($request['cmd'] === 'get_position' || $request['cmd'] === 'get_positions'))) {
        // check if logged in and validated 
        if(!$auth->isValidated()){
            // send response
            $db->close();
            // die_response(['error' => 'Unauthorized Access']);
            send_json_response(false, 401, 'Unauthorized Access');
            die();
        }
    }

    // script response array    
    $response = [];

    $className = 'ClientUsers';
    

    // commands permissions
    $allowed_cmd =  [
                        'get' => ['admin', 'manager', 'company_admin','client_admin', 'property_manager'],
                        'view' => ['admin', 'manager', 'company_admin'],
                        'edit' => ['admin', 'manager', 'company_admin' ,'client_admin'],
                        'save' => ['admin', 'manager', 'company_admin', 'client_admin', 'property_manager'],
                        'delete' => ['admin', 'manager', 'company_admin', 'client_admin','property_manager'],
                        'set' => ['admin', 'manager', 'company_admin']
                    ];

    ob_end_clean();

    if(!isset($request['cmd'])){
        
        $response = ['error' => 'Bad Request'];

    }else{

        $cmd = $request['cmd']; unset($request['cmd']);

        // Allow public access for get_position and get_positions commands
        if($cmd !== 'get_position' && $cmd !== 'get_positions') {
            // check if current user is allowed to execute a command
            if(isset($allowed_cmd[$cmd]) && array_has($allowed_cmd[$cmd], $auth->role()) === false){
                $response = ['error' => 'Unauthorized operation'];
            }else{
                $handler = new $className();
                $response = $handler->process($cmd, $request);
            }
        } else {
            $handler = new $className();
            $response = $handler->process($cmd, $request);
        }
    }


    // send response
    $db->close();
    die_response($response);


    

?>