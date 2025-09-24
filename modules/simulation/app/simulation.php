<?php

    // $allowed_origin = "http://192.168.1.24:5143/";
    // header("Access-Control-Allow-Origin: ".$allowed_origin);
    // Dynamic CORS origin handling
    $allowed_origins = [
        'http://localhost:5173',
        'http://localhost:5174',
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

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');

    include_once('Simulation.class.php');

    // check if logged in and validated 
    if(!$auth->isValidated()){
        // send response
        $db->close();
        die_response(['error' => 'Unauthorized Access']);
    }

    // script response array
    $response = [];

    $className = 'Simulation';
    

    // commands permissions
    $allowed_cmd =  [
                        // 'get_association' => ['admin', 'manager'],
                        
                        'load_settings' => ['admin'],
                        'get_settings' => ['admin'],
                        'set_settings' => ['admin']

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