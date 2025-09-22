<?php

    include_once('../../../app/init.php');
    include_once('../../../app/global_func.php');

    include_once('Users.class.php');

    // check if logged in and validated 
    if(!$auth->isValidated()){
        // send response
        $db->close();
        die_response(['error' => 'Unauthorized Access']);
    }

    // script response array
    $response = [];

    $className = 'Users';
    

    // commands permissions
    $allowed_cmd =  [
                        'load' => ['admin'],
                        'get' => ['admin'],
                        'edit' => ['admin'],
                        'save' => ['admin'],
                        'delete' => ['admin'],
                        'set' => ['admin']
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