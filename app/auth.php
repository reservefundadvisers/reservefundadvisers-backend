<?php
    
include_once('init.php');
 

// check if user is logged in and redirect to page
// else send to Login.php
function auth_handle_login(){
    extract($GLOBALS);

    
    // is NOT logged in
    if(!$auth->islogged()){
        // if current page IS NOT Login.php, redirect to it
        // else stay in current page
        if($_SERVER['PHP_SELF'] !== $pages['login']){
            header("location: ".$pages['login']);    
            die('');
        }    
    
    // if logged in
    }else{
        
        // if Two Way Auth not validated, redirect to page
        if(!$auth->isValidated()){
            header("location: ".$pages['auth_validate']);    
            die('');
        }

        // if current page IS Login.php, redirect to role landing page
        if($_SERVER['PHP_SELF'] === $pages['login']){
            header("location: ".role_landing_page($auth->role()));    
            die('');
        
        // else stay in current page
        }else{

        }
    }
    

}


// handle users pages
function auth_handle_user(){
    extract($GLOBALS);

    // is logged in
    if($auth->islogged()){

        // get role and current page
        $role = $auth->role();
        $page = pathinfo($_SERVER['PHP_SELF'])['filename'];
        $landing_page = role_landing_page($auth->role());

        
        // if current user has no specified role, or the current page is not 
        // allowed to the current user, redirect to current users landing page
        if($role === false || $landing_page === false){

            $auth->deletesession();
            header("location: ".$pages['login']); 
            die('');
            
        }else if($role !== false && !role_allowed_page($role, $page) ){

            //echo $pages[$role];
            header("location: $landing_page"); 
            die('');
            
        }   
        
        
    }else{
        header("location: ".$pages['login']);    
            die('');
    }
    
    

}

function auth_handle_disconnect(){
    extract($GLOBALS);

    if(isset($_GET['disconnect'])){
        $auth->deletesession();
        header("location: ".$pages['login']);    
            die('');
    }

}



function auth_disconnect(){
    extract($GLOBALS);
    
    if($auth->islogged()){       
        $auth->deletesession();    
    }
    

}

// check if user is logged in and redirect to page
// else send to Login.php
function auth_handle_validate(){
    extract($GLOBALS);

     // if Two Way Auth not validated, redirect to page
    if(!$auth->islogged()){
        header("location: ".$pages['login']);    
        die('');
    }   
    // if Two Way Auth not validated, redirect to page
    else if($auth->isValidated()){
        header("location: ".role_landing_page($auth->role()));   
        die('');
    }
    

}


if(isset($_POST['cmd'])){

    $cmd = $_POST['cmd'];

    unset($_POST['cmd']);

    // login
    if($cmd == 'login'){

        $res = $auth->login($_POST['username'], $_POST['password']);
        
        if ($res !== FALSE) {
            // set session for user
            $sessionHash = isset($_COOKIE['auth_session']) ? $_COOKIE['auth_session'] : null;

            send_json_response(true, 200, $lang[$loc]['auth']['login_success'], [
                    'data' => [
                        'user'      => $_POST['username'],
                        'sessionid' => $sessionHash,
                        'cookie'    => "auth_session=" . $sessionHash
                    ]
                ]);
        }
        else 
            return send_json_response( false, 400, $auth->errormsg[0]);
    
    // login resend validation code
    } else if($cmd == 'logout'){

         if($auth->islogged()){       
            $auth->deletesession();    
        }
        return send_json_response( true, 200, $lang[$loc]['auth']['logout_success'] );
    }
    else if($cmd == 'resend_auth'){

        $res = $auth->generate_auth();

        if($res !== false)
            die(json_encode(['success' => $lang[$loc]['auth']['login_auth_sent']]));
        else 
            die(json_encode(['error' => $auth->errormsg[0]]));
    
    // login validation code check
    }else if($cmd == 'auth_login'){

        $res = $auth->validate_auth($_POST);

        if($res !== false)
            die(json_encode(['success' => $lang[$loc]['auth']['login_auth_validated']]));
        else 
            die(json_encode(['error' => $auth->errormsg[0]]));
    }


    // forgot password
    else if($cmd == 'forgot_password'){

        $res = $auth->generate_reset(trim(check_val($_POST, 'username')));

        if($res)
            die(json_encode(['success' => $lang[$loc]['auth']['resetpass_email_sent']]));
        else 
            die(json_encode(['error' => $auth->errormsg[0]]));

    }

    // forgot password
    else if($cmd == 'reset_password'){

        $res = $auth->reset_password($_POST);

        if($res)
            die(json_encode(['success' => $lang[$loc]['auth']['resetpass_success']]));
        else 
            die(json_encode(['error' => $auth->errormsg[0]]));

    }

    // sign up
    else if($cmd == 'signup'){

        $res = $auth->new_signup($_POST, $signup_err, $signup_success);

        if($res)
            send_json_response(true, 201, $signup_success, ['user_id' => $res]);
        else 
            send_json_response(false, 400, $signup_err);

    }

    else if($cmd == 'validate_otp'){

        $res = $auth->validate_otp($_POST);

        if($res){
            $sessionHash = isset($_COOKIE['auth_session']) ? $_COOKIE['auth_session'] : null;
		    $purpose = check_val($_POST, 'purpose', 'verification');
           
             if ($purpose == 'signup' && $sessionHash) {
                send_json_response(true, 200, $lang[$loc]['auth']['signup_otp_validated'], [
                    'sessionid' => $sessionHash,
                    'cookie'    => "auth_session=" . $sessionHash
                ]);
            } else {
                send_json_response(true, 200, $lang[$loc]['auth']['signup_otp_validated']);
            }
        }
        else 
            send_json_response(false, 400, $auth->errormsg[0]);
    }

    else if($cmd == 'send_otp'){

        $res = $auth->send_otp();

        if($res)
            send_json_response(true, 200, $lang[$loc]['auth']['signup_otp_sent']);
        else 
           send_json_response(false, 400, $auth->errormsg[0]);
    }

    
}




?>