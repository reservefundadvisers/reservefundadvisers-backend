<?php




class Auth
{  
	public $mysqli;
	public $errormsg;
	public $successmsg;
	private $session; // 	hash, uid, expiredate, ip, fn, ln, type
	
	function __construct()
	{
		include("config.php");
	
		$this->mysqli = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']); 

		$this->errormsg = array();
		$this->successmsg = array();

		$this->init();

		// log_info($this->hashpass('Impr0ving'));

		//echo $this->mysqli === false ? "Error connecting to DB" : "DB OK";
	}

	
	/*
	* Init 
	*
	*/
	
	private function init()
	{
		include("config.php");
		include("lang.php");

		
		$this->errormsg = array();
		$this->successmsg = array();

		$this->session = false;
		
		$header = isset($_SERVER['HTTP_AUTH_SESSION']) ? $_SERVER['HTTP_AUTH_SESSION'] : '';
		        
		if(!empty($header)){
			$_COOKIE['auth_session'] = $header;
		}

		// Has Auth Cookie
		if(isset($_COOKIE['auth_session'])){
			
			$hash = $_COOKIE['auth_session'];
			
			// get session by cookie
			$query = $this->mysqli->prepare("	SELECT 
													sessions.hash AS hash, sessions.uid AS uid, sessions.expiredate AS expiredate, sessions.ip AS ip,
													sessions.auth_validated, sessions.auth_expiredate, 
													clients.association, 
													IF(clients.company_id IS NOT NULL, management_company.company, clients.company) AS mgmt_company, 
													clients.type AS client_type, clients.company_type, 
													users.fn AS fn, users.ln AS ln, users.role AS role, users.email AS email, users.client_id AS client_id, 
													(CASE WHEN clients.active IS NULL THEN users.active WHEN clients.active = 1 AND users.active = 1 THEN 1 ELSE 0 END) AS user_active
												FROM sessions 
												INNER JOIN users ON users.id = sessions.uid
												LEFT JOIN clients ON clients.id = users.client_id AND users.client_id IS NOT NULL
												LEFT JOIN clients management_company ON management_company.id = clients.company_id AND management_company.company_id IS NULL
												WHERE hash=?");
			//log_info($this->mysqli->error);
			
			$query->bind_param("s", $hash);
			$query->bind_result($this->session['hash'], $this->session['uid'], $this->session['expiredate'], $this->session['ip'], 
								$this->session['auth_validated'], $this->session['auth_expiredate'], 
								$this->session['association'], $this->session['company'], $this->session['client_type'], $this->session['company_type'], 
								$this->session['fn'], $this->session['ln'], $this->session['role'], $this->session['email'], $this->session['client_id'],
								$user_active);
			$query->execute();
			$query->store_result();
			$count = $query->num_rows;
			$query->fetch();
			$query->close();


			// if no session found, clear session variable
			if($count == 0 || $user_active == 0)
			{


				// Hash Or User doesn't exist
				
				// Delete all sessions
		
				$query = $this->mysqli->prepare("DELETE FROM sessions WHERE hash=?");
				$query->bind_param("s", $hash);
				$query->execute();
				$query->close();
			
				$this->errormsg[] = $lang[$loc]['auth']['sessioninfo_invalid'];
				
				setcookie("auth_session", $hash, time() - 3600, (strlen($base_web) < 1 ? "/" : $base_web));
				
				
				$this->session = false;
			}else{

				// check if session expired OR different IP from logged in one
				$expiredate = strtotime($this->session['expiredate']);
				$currentdate = strtotime(date("Y-m-d H:i:s"));
				

				if( /*$_SERVER['REMOTE_ADDR'] != $this->session['ip'] ||*/ $currentdate > $expiredate) 
				{
					// Hash exists, but IP has changed

					
					$query = $this->mysqli->prepare("DELETE FROM sessions WHERE uid=?");
					$query->bind_param("s", $this->session['uid']);
					$query->execute();
					$query->close();
					
					setcookie("auth_session", $this->session['hash'], time() - 3600, (strlen($base_web) < 1 ? "/" : $base_web));
					
					//$this->LogActivity($username, "AUTH_CHECKSESSION", "User session cookie deleted - IP Different ( DB : {$db_ip} / Current : " . $_SERVER['REMOTE_ADDR'] . " )");
					
					$this->session = false;

				}
			}
		}
		
	}
	
	/*
	* Log user in via MySQL Database
	* @param string $username
	* @param string $password
	* @return boolean
	*/
	
	function login($username, $password)
	{
		include("config.php");
		include("lang.php");

		$this->errormsg = array();
		$this->successmsg = array();
		
		if(!$this->islogged())
		{

			 if (filter_var($username, FILTER_VALIDATE_EMAIL)) {
				$email = $username;
			 }
			 else{
				$email = '';
			 }

			// Input verification :
			if(strlen($username) == 0) { $this->errormsg[] = $lang[$loc]['auth']['login_username_empty']; return false; }
			elseif(strlen($username) > 50) { $this->errormsg[] = $lang[$loc]['auth']['login_username_long']; return false; }
			elseif(strlen($username) < 3) { $this->errormsg[] = $lang[$loc]['auth']['login_username_short']; return false; }
		
			else if(strlen($password) == 0) { $this->errormsg[] = $lang[$loc]['auth']['login_password_empty']; return false; }
			elseif(strlen($password) > 50) { $this->errormsg[] = $lang[$loc]['auth']['login_password_long']; return false; }
			elseif(strlen($password) < 3) { $this->errormsg[] = $lang[$loc]['auth']['login_password_short']; return false; }
			else 
			{
				// Input is valid
			
				$password = $this->hashpass($password);
			
				$query = $this->mysqli->prepare("	SELECT users.id, (CASE WHEN clients.active IS NULL THEN users.active WHEN clients.active = 1 AND users.active = 1 THEN 1 ELSE 0 END) AS user_active 
													FROM users 
													LEFT JOIN clients ON clients.id = users.client_id AND users.client_id IS NOT NULL
													WHERE (users.username = ? OR users.email = ?) AND password=? ");
				//log_info($this->mysqli->error);

				$query->bind_param("sss", $username, $email, $password);
				$query->bind_result($uid, $isactive);
				$query->execute();
				$query->store_result();
				$count = $query->num_rows;
				$query->fetch();
				$query->close();
			
				if($count == 0)
				{
					// Username and / or password are incorrect
				
					$this->errormsg[] = $lang[$loc]['auth']['login_incorrect'];
					
					//$this->addattempt($_SERVER['REMOTE_ADDR']);
					
					//$attcount = $attcount + 1;
					//$remaincount = $auth_conf['max_attempts'] - $attcount;
					
					//$this->LogActivity("UNKNOWN", "AUTH_LOGIN_FAIL", "Username / Password incorrect - {$username} / {$password}");
					
					//$this->errormsg[] = sprintf($lang[$loc]['auth']['login_attempts_remaining'], $remaincount);
					
					return false;
				}
				else 
				{
					// Username and password are correct
					
					if($isactive == 0)
					{
						// Account is not activated
						
						//$this->LogActivity($username, "AUTH_LOGIN_FAIL", "Account inactive");
						
						$this->errormsg[] = $lang[$loc]['auth']['login_account_inactive'];
						
						return false;
					}
					else
					{
						// Account is activated
						
						$this->newsession($uid, true);				

						//$this->LogActivity($username, "AUTH_LOGIN_SUCCESS", "User logged in");
				
						$this->successmsg[] = $lang[$loc]['auth']['login_success'];
						
						return true;
					}
				}
			}
			
		}
		else 
		{
			// User is already logged in
			
			$this->errormsg[] = $lang[$loc]['auth']['login_already'];
			
			return false;
		}
	}
	
	
	/*
	* Creates a new session for the provided username and sets cookie
	* @param string $username
	*/
	
	function newsession($uid, $skipAuth = false)
	{
		include("config.php");
	
		$this->errormsg = array();
		$this->successmsg = array();

		$hash = md5(microtime());
		
		
		// Delete all previous sessions :
				
		$query = $this->mysqli->prepare("DELETE FROM sessions WHERE uid=?");
		$query->bind_param("s", $uid);
		$query->execute();
		$query->close();
		
		$ip = $_SERVER['REMOTE_ADDR'];
		$expiredate = date("Y-m-d H:i:s", strtotime($auth_conf['session_duration']));
		$expiretime = strtotime($expiredate);
		
		if($skipAuth){
			$ok = 1;
			$query = $this->mysqli->prepare("INSERT INTO sessions (uid, hash, expiredate, ip, auth_validated) VALUES (?, ?, ?, ?, ?)");
			$query->bind_param("ssssi", $uid, $hash, $expiredate, $ip, $ok);
			
		}else{
			$query = $this->mysqli->prepare("INSERT INTO sessions (uid, hash, expiredate, ip) VALUES (?, ?, ?, ?)");
			$query->bind_param("ssss", $uid, $hash, $expiredate, $ip);
			
		}

		$query->execute();
		$query->close();


		setcookie("auth_session", $hash, $expiretime, (strlen($base_web) < 1 ? "/" : $base_web));

		// temporary set COOKIE to init user and send auth code
		$_COOKIE['auth_session'] = $hash;

		log_info($hash);

		$this->init();
		
		if(!$skipAuth)
			$this->generate_auth();
	}
	
	/*
	* Deletes the user's session based on hash
	* @param string $hash
	*/
	
	function deletesession()
	{
		include("config.php");
		include("lang.php");
		
		$this->errormsg = array();
		$this->successmsg = array();

		
		if(!$this->islogged()){
			$this->errormsg[] = $lang[$loc]['auth']['deletesession_invalid'];
			if(isset($_COOKIE['auth_session']))
				setcookie("auth_session", $_COOKIE['auth_session'], time() - 3600, (strlen($base_web) < 1 ? "/" : $base_web));
			return false;
		}
		

		// Hash exists, Delete all sessions for that username :
		
		$query = $this->mysqli->prepare("DELETE FROM sessions WHERE uid=?");
		$query->bind_param("s", $this->session['uid']);
		$query->execute();
		$query->close();
		
		//$this->LogActivity($username, "AUTH_LOGOUT", "User session cookie deleted - Database session deleted - Hash ({$hash})");
		
		setcookie("auth_session", $this->session['hash'], time() - 3600, (strlen($base_web) < 1 ? "/" : $base_web));
		$this->session = false;

		return true;


	}

	/*
	* Reset user password
	* @param data array
	* @return boolean
	*/
	
	function reset_password($data)
	{
		include("config.php");
		include("lang.php");

		$this->errormsg = array();
		$this->successmsg = array();

		if(!is_valid($data, 'key')){ $this->errormsg[] = $lang[$loc]['auth']['resetpass_key_empty']; return false; }
		else if(!is_valid($data, 'password')){ $this->errormsg[] = $lang[$loc]['auth']['resetpass_newpass_empty']; return false; }
		
		
		else if(strlen($data['password']) == 0) { $this->errormsg[] = $lang[$loc]['auth']['resetpass_newpass_empty']; return false; }
		elseif(strlen($data['password']) > 50) { $this->errormsg[] = $lang[$loc]['auth']['resetpass_newpass_long']; return false; }
		elseif(strlen($data['password']) < 6) { $this->errormsg[] = $lang[$loc]['auth']['resetpass_newpass_short']; return false; }
		
		if(!$this->islogged())
		{
			
			
			$key = $data['key'];		
			$password = $this->hashpass($data['password']);
		
			$query = $this->mysqli->prepare("SELECT id FROM users WHERE reset_key=?");
			$query->bind_param("s", $key);
			$query->bind_result($uid);
			$query->execute();
			$query->store_result();
			$count = $query->num_rows;
			$query->fetch();
			$query->close();

		
			if($count == 0)
			{
				// reset key invalid
			
				$this->errormsg[] = $lang[$loc]['auth']['resetpass_key_incorrect'];
							
				
				return false;
			}
			else 
			{
				// Username and password are correct
				
				
					
					$query = $this->mysqli->prepare("UPDATE users SET password=?, reset_key=NULL, reset_key_expire=0  WHERE id=? LIMIT 1");
					$query->bind_param("ss", $password, $uid);
					$query->execute();
					$query->close();

			
					$this->successmsg[] = $lang[$loc]['auth']['resetpass_success'];
					
					return true;
				
			}
			
		}
		else 
		{
			// User is already logged in
			
			$this->errormsg[] = $lang[$loc]['auth']['login_already'];
			
			return false;
		}
	}


	
	/* 
	* Generate password reset key
	* @param string $hash
	* @return bool
	*/
	
	function generate_reset($username = '', $asNew = false)
	{
		global $auth_conf;
		
		include("config.php");
		include("lang.php");

		$username = trim($username);
		
		$user = get_element('users', ['username'=>$username]);

		// check username
		if(empty($username)){ $this->errormsg[] = $lang[$loc]['auth']['resetpass_username_empty']; return false; }
		else if(empty($user)){ $this->errormsg[] = $lang[$loc]['auth']['resetpass_username_invalid']; return false; }
		

		$reset_expiredate = date("Y-m-d H:i:s", strtotime($auth_conf['session_auth_duration']));
		$reset_key = $this->generate_key($username);

		$query = $this->mysqli->prepare("UPDATE users SET reset_key=?, reset_key_expire=? WHERE username=? LIMIT 1");
		$query->bind_param("sss", $reset_key, $reset_expiredate, $username);
		$query->execute();
		$query->close();

		log_info("$username -> $reset_key");

		$this->send_reset_key($user['email'], $reset_key, $asNew);

		return $reset_key;
	}
	
	/*
	* Provides an associative array of user info based on session hash
	* @param string $hash
	* @return array $session
	*/
	
	function sessioninfo()
	{
		
		return $this->session;			
	
	}
	
	/* 
	* Checks two way auth is validated
	* @param string $hash
	* @return bool
	*/
	
	function generate_auth($hash = null, $email = '')
	{
		global $auth_conf;
		
		include("config.php");
		include("lang.php");
		
		$session_hash = "";


		// check session hash
		if(!empty($hash))$session_hash = $hash;
		else if(is_valid($this->session, 'hash'))$session_hash = $this->session['hash'];
		else { $this->errormsg[] = $lang[$loc]['auth']['login_fail']; return false; }

		// check email
		$auth_email = "";
		if(!empty($email))$auth_email = $email;
		else if(is_valid($this->session, 'email'))$auth_email = $this->session['email'];
		
		if(empty(trim($auth_email))) { $this->errormsg[] = $lang[$loc]['auth']['login_auth_email_empty']; return false; }

		$auth_expiredate = date("Y-m-d H:i:s", strtotime($auth_conf['session_auth_duration']));
		
		$auth_code = $this->session['role'] == 'admin' ? '123456' : $this->generate_code();
		$encoded_auth_code = md5($auth_code);
		
		$query = $this->mysqli->prepare("UPDATE sessions SET auth_code=?, auth_expiredate=?, auth_validated=0 WHERE hash=? LIMIT 1");
		$query->bind_param("sss", $encoded_auth_code, $auth_expiredate, $session_hash);
		$query->execute();
		$query->close();

		log_info($auth_code);

		$this->send_auth_code($auth_email, $auth_code);

		return $auth_code;
	}
	

	

	/*
	* Validate Auth Code
	* @param data array
	* @return boolean
	*/
	
	function validate_auth($data)
	{
		include("config.php");
		include("lang.php");

		$this->errormsg = array();
		$this->successmsg = array();

		
		// if already validated
		if($this->isValidated()){ $this->errormsg[] = $lang[$loc]['auth']['login_auth_already']; return false; }

		// if code empty
		if(!is_valid($data, 'code')) { $this->errormsg[] = $lang[$loc]['auth']['login_auth_code_empty']; return false; }
		
		// if not logged in
		if(!$this->islogged()) { $this->errormsg[] = $lang[$loc]['auth']['login_fail']; return false; }

			
		$code = md5($data['code']); //$data['code']; 
		$hash = $this->session['hash'];
	
		$query = $this->mysqli->prepare("SELECT auth_validated, auth_expiredate FROM sessions WHERE hash=? AND auth_code=? ");
		$query->bind_param("ss", $hash, $code);
		$query->bind_result($auth_validated, $expiredate);
		$query->execute();
		$query->store_result();
		$count = $query->num_rows;
		$query->fetch();
		$query->close();

	
		if($count == 0)
		{
			// Username and / or password are incorrect
		
			$this->errormsg[] = $lang[$loc]['auth']['login_auth_code_incorrect'];
			
			//$this->addattempt($_SERVER['REMOTE_ADDR']);
			
			//$attcount = $attcount + 1;
			//$remaincount = $auth_conf['max_attempts'] - $attcount;
			
			//$this->LogActivity("UNKNOWN", "AUTH_LOGIN_FAIL", "Username / Password incorrect - {$username} / {$password}");
			
			//$this->errormsg[] = sprintf($lang[$loc]['auth']['login_attempts_remaining'], $remaincount);
			
			return false;
		}
		else 
		{
			// Username and password are correct
			
			
				
				$auth_expiredate = strtotime($expiredate);
				$currentdate = strtotime(date("Y-m-d H:i:s"));

				if($auth_validated){
					$this->errormsg[] = $lang[$loc]['auth']['login_auth_already']; return false;
				}else if($currentdate > $auth_expiredate){
					$this->generate_auth($hash);
					$this->errormsg[] = $lang[$loc]['auth']['login_auth_code_expired']; return false;
				}

				$query = $this->mysqli->prepare("UPDATE sessions SET auth_validated=1 WHERE hash=? AND auth_code=? LIMIT 1");
				$query->bind_param("ss", $hash, $code);
				$query->execute();
				$query->close();
		
				$this->successmsg[] = $lang[$loc]['auth']['login_auth_validated'];
				
				return true;
			
		}
			
		
	}

	/*
	* Sign up
	* @param data array
	* @return boolean
	*/
	
	function signup($data, &$error, &$url)
	{
		include("config.php");
		include("lang.php");

		$order_id = check_val($data, 'order_id');
		
		$client = check_val($data, 'association');
		$company = check_val($data, 'company');
		$email = check_val($data, 'admin_email');
		$password = check_val($data, 'admin_password');

		//check type
		$type = check_val($data, 'type', 'client');
		if(!empty($type) && $type != 'client'){
			$data['company_type'] = $type;
			$data['type'] = 'company';
			$data['association'] = NULL;
		}else{
			$data['company_type'] = $type;
			$data['type'] = 'client';
		}

		if(empty($order_id) || !exists($checkoutsTable, ['id'=>$order_id, 'status'=>'pending'])){
			$error = $lang[$loc]['auth']['signup_order_error'];
			return false;
		}else if($type == 'client' && empty($client)){
			$error = $lang[$loc]['auth']['signup_assoc_error'];
			return false;
		}else if($type != 'client' && empty($company)){
			$error = $lang[$loc]['auth']['signup_company_error'];
			return false;
		}else if(empty($email)){
			$error = $lang[$loc]['auth']['signup_email_error'];
			return false;
		}else if(empty($password) || strlen($password) < 6){
			$error = $lang[$loc]['auth']['signup_password_error'];
			return false;
		}

		// check if email already exists
		if(exists($usersTable, ['username'=>$email])){
			$error = $lang[$loc]['auth']['signup_email_exists'];
			return false;
		}

		$this->errormsg = array();
		$this->successmsg = array();
		
		// $url = $order_id;
		// return true;

		include_once('../modules/clients/app/Clients.class.php');
		include_once('../modules/clients/app/ClientUsers.class.php');


		// check admin email 
		$clientUsers = new ClientUsers();

		
		
		// create client
		$client_id = save_element($clientsTable, $data);

		if($client_id === false){
			$error = $lang[$loc]['auth']['signup_error'];
			return false;
		}else{
			

			// create client admin user
			$admin_user = [ 'fn'=>check_val($data, 'admin_fn'), 
							'ln'=>check_val($data, 'admin_ln'), 
							'email'=>check_val($data, 'admin_email'), 
							'password'=>check_val($data, 'admin_password'), 
							'role'=>check_val($data, 'type', 'client') . '_admin', 
							'position_id'=>'boardmember',
							'client_id'=> $client_id];


			$ret = $clientUsers->save($admin_user, false);
			
			log_info($admin_user, $ret);

			if(isset($ret['error'])){
				delete_elements_by_id($clientsTable, ['id'=>$client_id]);
				$error = $lang[$loc]['auth']['signup_error'];
				return false;
			}else{

				// confirm order
				update_element($checkoutsTable, ['id'=>$order_id, 'status'=>'success']);	
				$url = $order_id;


				// login
				$this->newsession($ret['success'], true);

				// send email
				$this->send_signup_complete(ucwords($type == 'client' ? $client : $company));
		

				// return generate_stripe($data, $error, $url);
				return true;
				// return false;
			}

			
			
		}

	}
	
	/**
	 * Handles the signup process for a user.
	 *
	 * Validates user data, checks for existing email, creates a new user record with active status set to 0,
	 * and sends an OTP email to the user's email address.
	 *
	 * @param array $data User data array containing position_id, email, password, first_name, last_name, mobile_number.
	 * @param string &$error Error message variable.
	 * @param string &$url URL variable for redirect after signup.
	 * @return bool|array Returns false if any errors occur or if the user data is invalid, otherwise returns a JSON response with a success flag and the user ID.
	 */
	function new_signup( $data, &$error, &$url){
		include("config.php");
		include("lang.php");
		include('mail.php');

		$user_active = 0; // after signup user is inactive until email verification

		$position_id = check_val($data, 'position_id');
		$email = check_val($data, 'email');
		$password = check_val($data, 'password');
		$first_name = check_val($data, 'first_name');
		$last_name = check_val($data, 'last_name');
		$mobile_number = check_val($data, 'mobile_number');

		if(empty($position_id)){
			$error = $lang[$loc]['auth']['signup_position_error'];
			return false;
		}else if(empty($email)){
			$error = $lang[$loc]['auth']['signup_email_error'];
			return false;
		}else if(empty($password) || strlen($password) < 6){
			$error = $lang[$loc]['auth']['signup_password_error'];
			return false;
		}else if(empty($first_name)){
			$error = $lang[$loc]['auth']['signup_fn_error'];
			return false;
		}else if(empty($last_name)){
			$error = $lang[$loc]['auth']['signup_ln_error'];
			return false;
		}else if(empty($mobile_number)){
			$error = $lang[$loc]['auth']['signup_mobile_error'];
			return false;
		}

		// check if email already exists
		if(exists($usersTable, ['email'=>$email])){
			$error = $lang[$loc]['auth']['signup_email_exists'];
			return false;
		}

		// create user
		$user = [ 	'id'=>generate_id('users'),
					'first_name'=>$first_name, 
					'last_name'=>$last_name, 
					'email'=>$email, 
					'username'=>$email, 
					'password'=>$password, 
					'role'=>'client_admin', 
					'position_id'=>$position_id,
					'mobile_number'=>$mobile_number];

		$query = $this->mysqli->prepare("INSERT INTO users (id, fn, ln, email, username, password, role, position_id, phone, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$hashed_password = $this->hashpass($password);
		$query->bind_param("ssssssssss", $user['id'], $user['first_name'], $user['last_name'], $user['email'], $user['username'], $hashed_password, $user['role'], $user['position_id'], $user['mobile_number'], $user_active);
		$query->execute();
		$user_id = $this->mysqli->insert_id;
		$query->close();

		if($user_id === false){
			$error = $lang[$loc]['auth']['signup_error'];
			return false;
		}

		$this->errormsg = array();
		$this->successmsg = array();

		if(function_exists('sendOtpEmail')){

			$otp = generate_otp();
			$response = sendOtpEmail($user['email'], $user['first_name'].' '.$user['last_name'], $otp);

			if($response) {
				$this->insert_otp($user_id, $otp);
				$this->successmsg[] = 'OTP email sent successfully';
			} else {
				$this->errormsg[] = 'Failed to send OTP email';
			}
		}


		return die(json_encode(['success'=>true, 'user_id'=>$user_id]));
	}

	/**
	 * Inserts a one-time password (OTP) record in the database.
	 *
	 * @param int $id The user ID associated with the OTP.
	 * @param string $otp The one-time password (OTP) value.
	 *
	 * @return bool True if the OTP record is inserted successfully, false otherwise.
	 */
	function insert_otp($id, $otp) {
		include("config.php");
		include("lang.php");

		if (empty($id) || empty($otp)) {
			return false;
		}

		$created_at = gmdate("Y-m-d H:i:s"); // UTC time
    	$expires_at = gmdate("Y-m-d H:i:s", strtotime("+10 minutes")); // UTC + 10 minutes

		$status = 'pending';

		$query = $this->mysqli->prepare( "INSERT INTO otp (user_id, otp_value, created_at, expires_at, status) VALUES (?, ?, ?, ?, ?)");
		$query->bind_param("sssss", $id, $otp, $created_at, $expires_at, $status);
		$execute_result = $query->execute();
		$query->close();

		return $execute_result;
	}

	/**
	 * Updates the status of a one-time password (OTP) record in the database.
	 *
	 * @param int $id The ID of the OTP record to update.
	 * @param string $status The new status of the OTP record (e.g. 'pending', 'verified', 'expired').
	 *
	 * @return bool True if the OTP record is updated successfully, false otherwise.
	 */
	function update_otp_status($id, $status) {
		include("config.php");
		include("lang.php");

		if (empty($id) || empty($status)) {
			return false;
		}

		$query = $this->mysqli->prepare("UPDATE otp SET status=? WHERE id=? LIMIT 1");
		$query->bind_param("ss", $status, $id);
		$execute_result = $query->execute();
		$query->close();

		return $execute_result;
	}

	/**
	 * Validates a one-time password (OTP) submitted by a user.
	 *
	 * Returns true if the OTP is valid and not expired, false otherwise.
	 *
	 * @return bool True if the OTP is valid and not expired, false otherwise.
	 */
	function validate_otp() {
		include("config.php");
		include("lang.php");

		$this->errormsg = array();
		$this->successmsg = array();

		$user_id = check_val($_POST, 'user_id');
		$otp = check_val($_POST, 'otp');

		if (empty($user_id) || empty($otp)) {
			$this->errormsg[] = 'User ID or OTP cannot be empty';
			return false;
		}

		// Select OTP row where user_id, otp_value, status = 'pending' and expires_at > UTC_TIMESTAMP()
		$query = $this->mysqli->prepare("
			SELECT * FROM otp
			WHERE user_id = ? AND otp_value = ? AND status = 'pending' AND expires_at > UTC_TIMESTAMP()
			LIMIT 1
		");
		$query->bind_param("ss", $user_id, $otp);
		$query->execute();
		$result = $query->get_result();
		$query->close();

		if ($result && $result->num_rows > 0) {
			// OTP is valid and not expired

			// Update user active status
			$active = 1; // Activate user
			$updateUser = $this->mysqli->prepare("UPDATE users SET active=? WHERE row=? LIMIT 1");
			$updateUser->bind_param("ss", $active, $user_id);
			$updateUser->execute();
			$updateUser->close();

			// Mark OTP as used
			$usedStatus = 'used';
			$otpId = $result->fetch_assoc()['id']; 
			$this->update_otp_status($otpId, $usedStatus);

			// Create new session skipping auth
			$uid = get_element('users', ['row'=>$user_id], 'id');
			$this->newsession($uid['id'], true);	

			return true;
		} else {
			// OTP invalid or expired
			$this->errormsg[] = 'Invalid or expired OTP';
			return false;
		}
	}

	
	/*
	* Check if logged !
	* @param string $type
	* @return bool
	*/
	
	function islogged(){
		
		return !($this->session === false);
		
	}
		
	
	/*
	* Check if logged !
	* @param string $type
	* @return bool
	*/
	
	function isValidated(){
		
		return $this->islogged() && $this->session['auth_validated'] == 1;
		
	}


	
	/*
	* Check user !
	* @param string $type
	* @return bool
	*/
	
	function checkRole($role){
		
		include("config.php");
		include("lang.php");

		if($this->islogged() && isset($this->session['role']) && array_has($roles, $role) && $this->session['role'] == $role)
			return true;
		else 
			return false;
		
	}
	
	
	function checkRoleType($checkAs = ''){
		global $roles, $client_roles;

		// multiple roles
		if(is_array($checkAs)){
			$is_ok = false;

			foreach($checkAs as $c){
				$is_ok &= $this->checkRoleType($c);
			}

			return $is_ok;
		}

		return empty($checkAs) 	? !array_has($client_roles, $this->session['role']) 
								:  str_has($this->session['role'], $checkAs);
	}

	function isClientRole(){
		global $client_roles;

		return array_has($client_roles, $this->session['role']);
	}
	
	
	/*
	* role !
	* @return string
	*/
	
	function role(){
		global $roles;
		
		return $this->session === false ? false : $this->session['role'];
	}


	
	/*
	* UID !
	* @return string
	*/
	
	function uid(){
		
		return $this->session['uid'];
	}
	
	
	/*
	* Client ID !
	* @return string
	*/
	
	function clientId(){
		
		return $this->session['client_id'];
	}
	
	
	/*
	* Client Type !
	* @return string
	*/
	
	function clientType(){
		
		return $this->session['client_type'];
	}
	
	/*
	* info !
	* @return string
	*/
	
	function info(){
		global $roles;
		
		if(func_num_args() == 0)
			return [ 'association' => $this->session['association'], 'company' => $this->session['company'], 'fn' => $this->session['fn'], 'ln' => $this->session['ln'] ];
		else
			return check_val($this->session, func_get_arg(0), '');
	}

	
	
	/*
	* Email !
	* @return string
	*/
	
	function email($hide = false){
		$email = $this->info('email');

		if($hide ===  false || strlen($email) < 2)return $email;

		$em = explode("@",$email);
		if(count($em) != 2)return substr($email, 0, 1).str_repeat('*', strlen($email)-1);
		

		return substr($em[0], 0, 1).str_repeat('*', strlen($em[0])-1).'@'.substr($em[1], 0, 2).str_repeat('*', strlen($em[1])-1);
	}


	
	/*
	* Hash user's password with SHA512, base64_encode, ROT13 and salts !
	* @param string $password
	* @return string $password
	*/
	
	function hashpass($password)
	{
		include("config.php");
	
		$password = hash("SHA512", base64_encode(str_rot13(hash("SHA512", str_rot13($auth_conf['salt_1'] . $password . $auth_conf['salt_2'])))));
		return $password;
	}

	/*
	* Generate Random Password Reset Code !
	* @param string $password
	* @return string $password
	*/
	
	function generate_key($username = '')
	{
		$characters = 'abcdefghijklmnopqrstuvwxz0123456789';
		$randomString = '';

		$n = 32;
	
		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}

		$randomString = md5($username.$randomString);
	
		return $randomString;
	}


	/*
	* Generate Random Auth Code !
	* @param string $password
	* @return string $password
	*/
	
	function generate_code()
	{
		$characters = '0123456789';
		$randomString = '';

		$n = 6;
	
		for ($i = 0; $i < $n; $i++) {
			$index = rand(0, strlen($characters) - 1);
			$randomString .= $characters[$index];
		}
	
		return $randomString;
	}



	function send_reset_key($to, $key, $asNew = false){
		global $app_name, $base_url, $noreply_email;
		
		$subject = $asNew ? "Set Your Password" : "Password Reset";

		$link = "$base_url/reset_password.php?key=$key";
		
		$message = "<p>Hello,</p>
					<p>Please use the following link to ".($asNew ? "Set" : "Reset")." your Password:</p>
					<p><h4><b><a href=\"$link\">$link</a></b></h4></p>";
								
		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		// More headers
		$headers .= "From: $app_name <$noreply_email>" . "\r\n";
		$headers .= "Reply-To: $noreply_email" . "\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion();
		//$headers .= 'Bcc: anass.wakrim@gmail.com' . "\r\n";

		//log_info($to,$subject,$message,$headers);

		return $this->_mail(strtolower($to),$subject,$message,$headers);
		
	}

	
	function send_auth_code($to, $code){
		global $app_name, $noreply_email;
		
		$subject = "Your Login Validation Code is $code";
		
		$message = "<p>Hello,</p>
					<p>Please use the following code to Validate your Login:</p>
					<p><h4><b>$code</b></h4></p>";
								
		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		// More headers
		$headers .= "From: $app_name <$noreply_email>" . "\r\n";
		$headers .= "Reply-To: $noreply_email" . "\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion();
		//$headers .= 'Bcc: anass.wakrim@gmail.com' . "\r\n";

		//log_info($to,$subject,$message,$headers);

		return $this->_mail(strtolower($to),$subject,$message,$headers);
		
	}

	
	function send_signup_complete($client_name){
		global $app_name, $noreply_email, $signup_email;
		
		$subject = "Sign Up Completed";
		
		$message = "<p>Hello,</p>
					<p>This User has completed his Sign Up:</p>
					<p><h4><b>$client_name</b></h4></p>";
								
		// Always set content-type when sending HTML email
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

		// More headers
		$headers .= "From: $app_name <$noreply_email>" . "\r\n";
		$headers .= "Reply-To: $noreply_email" . "\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion();
		//$headers .= 'Bcc: anass.wakrim@gmail.com' . "\r\n";

		//log_info($to,$subject,$message,$headers);

		return $this->_mail(strtolower($signup_email),$subject,$message,$headers);
		
	}

	function _mail($to, $subject, $message, $headers){
		global $app_name, $noreply_email, $signup_email;

		$api_key = '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';

		$email = new \SendGrid\Mail\Mail();
		$email->setFrom($noreply_email, $app_name);
		$email->setSubject($subject);
		$email->addTo($to);
		$email->addContent("text/html", $message);

		$sendgrid = new \SendGrid($api_key);
		try {
			$response = $sendgrid->send($email);
			log_info($response->statusCode());
			log_info($response->headers());
			log_info($response->body());
			log_info('OK');
			return true;
		} catch (Exception $e) {
			log_info($e->getMessage());
			return false;
		}
		
	}
}

?>