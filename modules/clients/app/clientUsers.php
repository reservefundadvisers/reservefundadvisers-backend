<?php

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');

    include_once('ClientUsers.class.php');
    include_once('ClientPositions.class.php');
    $request = check_request();

    // Allow public access for get_position and get_positions commands
    if(!(isset($request['cmd']) && ($request['cmd'] === 'get_position' || $request['cmd'] === 'get_positions'))) {
        // check if logged in and validated 
        if(!$auth->isValidated()){
            // send response
            $db->close();
            die_response(['error' => 'Unauthorized Access']);
        }
    }

    // script response array    
    $response = [];

    $className = 'ClientUsers';
    

    // commands permissions
    $allowed_cmd =  [
                        'get' => ['admin', 'manager', 'company_admin'],
                        'view' => ['admin', 'manager', 'company_admin'],
                        'edit' => ['admin', 'manager', 'company_admin'],
                        'save' => ['admin', 'manager', 'company_admin'],
                        'delete' => ['admin', 'manager', 'company_admin'],
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