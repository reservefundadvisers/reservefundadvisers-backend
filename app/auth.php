<?php
    
include_once('init.php');
 
/**
 * Encode a string using base64url, which is a variant of base64 that is safe for use in URLs.
 * This function is used internally by the JWT library to encode the JWT header and payload.
 * @param string $input The string to be encoded.
 * @return string The base64url encoded string.
 */     
function jwt_base64url_encode($input){
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

/**
 * Signs a string using the HS256 algorithm.
 * This function is used internally by the JWT library to sign the JWT payload.
 * @param string $data The string to be signed.
 * @param string $secret The secret key to use for signing.
 * @return string The signed string.
 */
function jwt_sign_hs256($data, $secret){
    return hash_hmac('sha256', $data, $secret, true);
}

/**
 * Generates a JSON Web Token (JWT) for a user.
 * The JWT is signed with the configured JWT_SECRET, or a fallback value
 * if JWT_SECRET is not configured.
 * The JWT payload contains the user's information and expiration time.
 *
 * @param array $userPayload The user's information to be included in the JWT.
 * @return array The generated JWT and its expiration time in seconds.
 */
function generate_user_jwt($userPayload){
    global $auth_conf;

    $issuedAt = time();
    $jwtTtl = intval($_ENV['JWT_TTL'] ?? getenv('JWT_TTL') ?: 86400);
    if ($jwtTtl <= 0) $jwtTtl = 86400;

    $secret = trim($_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: '');
    rfa_create_log(print_r(['secret' => $secret], true));
    if ($secret === '') {
        // Fallback for local/dev environments where JWT_SECRET is not configured.
        $secret = ($auth_conf['salt_1'] ?? '') . ($auth_conf['salt_2'] ?? '');
    }

    $header = [
        'alg' => 'HS256',
        'typ' => 'JWT'
    ];

    $payload = [
        'iat' => $issuedAt,
        'exp' => $issuedAt + $jwtTtl,
        'user' => $userPayload
    ];

    $encodedHeader = jwt_base64url_encode(json_encode($header));
    $encodedPayload = jwt_base64url_encode(json_encode($payload));
    $signature = jwt_base64url_encode(jwt_sign_hs256("$encodedHeader.$encodedPayload", $secret));

    return [
        'token' => "$encodedHeader.$encodedPayload.$signature",
        'expires_in' => $jwtTtl
    ];
}

/**
 * Decodes a base64url encoded string.
 * This function is used internally by the JWT library to decode the JWT header and payload.
 * @param string $input The base64url encoded string to be decoded.
 * @return string The decoded string.
 */
function jwt_base64url_decode($input) {
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $input .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}

function decode_and_verify_jwt($jwt, $secret) {

    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return false;
    }

    [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

    // Recreate signature
    $expectedSignature = jwt_base64url_encode(
        jwt_sign_hs256("$encodedHeader.$encodedPayload", $secret)
    );

    if (!hash_equals($expectedSignature, $encodedSignature)) {
        return false; // Invalid signature
    }

    $payload = json_decode(jwt_base64url_decode($encodedPayload), true);

    // Expiration check
    if (isset($payload['exp']) && time() >= $payload['exp']) {
        return false; // Token expired
    }

    return $payload;
}

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
            $session_info = $auth->sessioninfo();

            $user_info = [];
            $userId = check_val($session_info, 'uid', null);

            if(!empty($userId)){
                $user_info = get_element($usersTable, ['id' => $userId]);
                // Remove sensitive information
                unset($user_info['password']);
            }

            $jwtData = generate_user_jwt($user_info);
            $secret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
            $token = $jwtData['token'];
            $data = decode_and_verify_jwt($token, $secret);

            send_json_response(true, 200, $lang[$loc]['auth']['login_success'], [
                    'data' => [
                        'user'      => $_POST['username'],
                        'sessionid' => $sessionHash,
                        'cookie'    => "auth_session=" . $sessionHash ,
                        'user_info' => $user_info ? $user_info : null,
                        'session_info' => $session_info ? $session_info : null,
                        'token_type' => 'Bearer',
                        'jwt_token' => $jwtData['token'],
                        'expires_in' => $jwtData['expires_in']
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

        $response = $auth->forgot_password($_POST);

        if($response && isset($response['success']) && $response['success']) {
            send_json_response(true, 200, $auth->successmsg[0] ?? 'Success', [
                'user_id' => $response['user_id'] ?? '',
                'email'   => $response['email'] ?? ''  // Add this line to include the email in the response
            ]);
        }
        else 
            send_json_response(false, 400, $auth->errormsg[0] ?? 'Error');

    }

    else if($cmd == 'update_user_password'){

        $res = $auth->update_user_password($_POST);

        if($res)
            send_json_response(true, 200, $auth->successmsg[0] ?? 'Success');
        else 
            send_json_response(false, 400, $auth->errormsg[0] ?? 'Error');
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

    else if($cmd == 'resend_invite_email'){

        include('mail.php');     

        $user_id = check_val($_POST, 'user_id');

        $user_details = get_elements('users', ['id' => $user_id]);

        $user =$user_details[0]; 

        $name  = ($user['fn'] ?? '') . ' ' . ($user['ln'] ?? '');
        $email =$user['email'] ?? '';
        $url =  $_ENV['FRONTEND_URL'] ?? ''; 
        $user_token = $user['invite_token'] ?? '';

        if(empty($user_token)){
            $user_token = $auth->generate_token(64);
            $update_data = [
                'invite_token' => $user_token
            ];
            $update_condition = [
                'id' => $user['id']
            ];
            $update_user = update_element('users', $update_data, $update_condition);
        }
        
        $inviteLink = $url . "invite-member?token=" . $user_token;
        $sendername = $auth->user_fnln() ?? '';

        $template = emailTemplateInviteMember($name, $sendername, $inviteLink, "Reserve Fund System");
        $subject = $template['subject'] ?? ''   ;
        $body = $template['body'] ?? '';

        $res = sendOtpEmail($email, $subject, $body);

        if($res)
            send_json_response(true, 200, $lang[$loc]['auth']['invitation_resent_success']);
        else 
           send_json_response(false, 400, $auth->errormsg[0]);
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

    else if($cmd == 'verify_token') {
        $res = $auth->verify_token($_POST); 

        if ($res['success'] && isset($res['data']) && $res['data']) {
            send_json_response(true, 200, $res['message'] ?? 'Success', [
                'data' => $res['data'] ?? '',
            ]);
        } else {
            send_json_response(false, 400, $res['message'] ?? 'Verification failed');
        }
    }

    /**
     * Decode JWT
     * 
     * Expected POST parameter:
     * - token: The JWT string to decode
     * Returns the decoded header and payload as JSON.
     */
    else if($cmd == 'decode_jwt') {
       $token = check_val($_POST, 'token', '');

        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            send_json_response(false, 400, 'Invalid JWT format');
            return;
        }

        list($headerB64, $payloadB64, $signatureB64) = $parts;

        $header  = json_decode(jwt_base64url_decode($headerB64), true);
        $payload = json_decode(jwt_base64url_decode($payloadB64), true);

        $data = [
            'header'  => $header,
            'payload' => $payload,
            // signature is intentionally ignored for decoding
        ];

        send_json_response(true, 200, 'JWT decoded successfully', ['data' => $data]);

    }

    
}




?>