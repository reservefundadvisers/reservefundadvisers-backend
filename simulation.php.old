<?php

	include_once('app/init.php');

	ini_set('display_errors', 0);




	// stripe
	if(isset($_GET['type']) && $_GET['type'] == 'generate'){

		$res = false;
		$url = '';

		if(isset($_GET['free'])){
			unset($_GET['free']);
			$res = generate_stripe($_GET + ['payment'=>'free'], $error, $url);
			
		}else if(isset($_GET['monthly'])){
			$res = generate_paypal(['payment'=>'monthly'], $error, $url);
	
		}else if(isset($_GET['yearly'])){
			$res = generate_stripe(['payment'=>'yearly'], $error, $url);
	
		}
		
		if($res){
			die('{"success":"'.$url.'"}');
		}else{
			die('{"error":"Could\'nt generate checkout link, please try again !"}');
		}

	}

	// save contact
	if(isset($_GET['save'])){
		
		$res = false;


		if(!empty($_GET['save'])){

			$user = $_GET['save'];
			log_info('subsription.php', $user);


			$res = true;

			// send email
			$subject = "New Sign Up";
		
			$message = "<p>Hello,</p>
							<p>The following user has completed the Sign Up form:</p>
						<table>
						<tr><td>Association &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><b>".ucwords($user['association'])."</b></td></tr>
						<tr><td>First Name &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><b>".$user['admin_fn']."</b></td></tr>
						<tr><td>Last Name &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><b>".$user['admin_ln']."</b></td></tr>
						<tr><td>Phone &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><b>".$user['phone']."</b></td></tr>
						<tr><td>Email &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><b>".$user['admin_email']."</b></td></tr>
						<tr><td>Address &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td><b>".$user['address']."<br>".$user['address2']."<br>".$user['city']." - ".$user['zip']."<br>".$user['state']."</b></td></tr>
						</table>

						";
									
			// Always set content-type when sending HTML email
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

			// More headers
			$headers .= "From: $app_name <$noreply_email>" . "\r\n";
			$headers .= "Reply-To: $noreply_email" . "\r\n";
			$headers .= "X-Mailer: PHP/" . phpversion();
			//$headers .= 'Bcc: anass.wakrim@gmail.com' . "\r\n";

			// log_info($signup_email,$subject,$message,$headers);

			mail($signup_email,$subject,$message,$headers);

		}

		
		if($res){
			die('{"success":""}');
		}else{
			die('{"error":Could\'nt generate checkout link, please try again !}');
		}
	}

	// offers
	if(isset($_GET['subscription'])){

		$res = false;
		$url = '';

		if(!empty($_GET['subscription'])){
			$subscription = $_GET['subscription'];
			$infos = check_val($_GET, 'infos', []);
	
			switch($subscription){
				case 'monthly_paypal': $res = generate_paypal(['payment'=>'monthly'] + $infos, $error, $url); break;
				case 'yearly_paypal': $res = generate_paypal(['payment'=>'yearly'] + $infos, $error, $url); break;
				
				case 'monthly_card': $res = generate_stripe(['payment'=>'monthly'] + $infos, $error, $url); break;
				case 'yearly_card': $res = generate_stripe(['payment'=>'yearly'] + $infos, $error, $url); break;
	
				case 'free_monthly_paypal': $res = generate_paypal(['payment'=>'monthly'] + $infos, $error, $url); break;
				case 'free_monthly_card': $res = generate_stripe(['payment'=>'monthly'] + $infos, $error, $url); break;
				case 'free_yearly_paypal': $res = generate_paypal(['payment'=>'yearly'] + $infos, $error, $url); break;
				case 'free_yearly_card': $res = generate_stripe(['payment'=>'yearly'] + $infos, $error, $url); break;
	
			}
		}

		

		
		if($res){
			die('{"success":"'.$url.'"}');
		}else{
			die('{"error":Could\'nt generate checkout link, please try again !}');
		}


	// wix webhook through zaper
	}else{

		// no status
		if(!isset($_GET['json'])){
			die('{"error":"KO1"}');
		}

		// parse order
		$request = parse_json(check_val($_GET, 'json'));

		// no plan id
		if(!isset($request['plan_id'])){
			die('{"error":"KO2"}');
		}

		// empty plan id
		$plan_id = $request['plan_id'];
		if(empty($plan_id)){
			die('{"error":"KO3"}');
		} 


		$order = [	'order'=>	json_encode([	'admin_fn' => check_val($request, 'contact/name/first'),
												'admin_ln' => check_val($request, 'contact/name/last'),
												'admin_email' => check_val($request, 'contact/email'),
												'phone' => check_val($request, 'contact/phone'),
												'email' => check_val($request, 'contact/email'),
												'address' => check_val($request, 'contact/address/addressLine'),
												'address2' => check_val($request, 'contact/address/addressLine'),
												'zip' => check_val($request, 'contact/address/postalCode'),
												'city' => check_val($request, 'contact/address/city')
											]),
					
					'type'=>'wix',

					'details'=> json_encode($request),
					
					'status'=>'pending'
				];

		$order_id = save_element($checkoutsTable, $order);
		if($order_id === FALSE){
			die('{"error":"KO4"}');
		}


		echo '{"success":"'.$order_id.'"}';

	}

	


?>