<?php

    echo __DIR__;

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');

    include_once('Clients.class.php');
    include_once('ClientUsers.class.php');

    // check if logged in and validated 
    if(!$auth->isValidated()){
        // send response
        $db->close();
        die_response(['error' => 'Unauthorized Access']);
    }

    // script response array
    $response = [];

    $className = 'Clients';
    

    // commands permissions
    $allowed_cmd =  [
                        'load_company' => ['admin', 'manager'],
                        'get_company' => ['admin', 'manager'],
                        'edit_company' => ['admin', 'manager'],
                        'save_company' => ['admin', 'manager'],
                        
                        'load_client' => ['admin', 'manager', 'company_admin'],
                        'get_client' => ['admin', 'manager', 'company_admin', 'company_user'],
                        'edit_client' => ['admin', 'manager', 'company_admin'],
                        'save_client' => ['admin', 'manager', 'company_admin'],
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