<?php

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');

    include_once('ClientUsers.class.php');
    include_once('ClientPositions.class.php');

    // check if logged in and validated 
    if(!$auth->isValidated()){
        // send response
        $db->close();
        die_response(['error' => 'Unauthorized Access']);
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