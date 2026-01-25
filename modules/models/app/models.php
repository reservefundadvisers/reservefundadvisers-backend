<?php

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');

    include_once('Models.class.php');

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
                        'load' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'get' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'edit' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'save' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'delete' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'set' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'reset_simulations' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'edit_inv' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager'],
                        'save_inv' => ['admin', 'manager', 'company_admin', 'company_user', 'client_admin', 'property_manager']
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