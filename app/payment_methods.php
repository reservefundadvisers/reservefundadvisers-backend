<?php

use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use PayPal\Api\ChargeModel;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;
use PayPal\Api\Agreement;
use PayPal\Api\Payer;


use Stripe\StripeClient;


function generate_paypal($data, &$errormsg, &$successmsg){
    
    global $checkoutsTable, $payment_details, $paypal_id, $paypal_secret, $lang, $loc, $base_url;
    
    $payment_method = check_val($data, 'payment', 'monthly');
    if(!isset($payment_details['paypal']['subscriptions'][$payment_method])){
        $errormsg = $lang[$loc]['auth']['signup_subscription_error'];	
        return false;
    }

    $payment_config = $payment_details['paypal']['subscriptions'][$payment_method];

    // Paypal API 
    $apiContext = new ApiContext(
        new OAuthTokenCredential(
            $payment_details['paypal']['key'],
            $payment_details['paypal']['secret']
        )
    );

    $apiContext->setConfig(
        array(
            'mode' => 'sandbox',
            'log.LogEnabled' => false,
            'log.LogLevel' => 'DEBUG', // PLEASE USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
            'cache.enabled' => false,
            //'cache.FileName' => '/PaypalCache' // for determining paypal cache directory
            // 'http.CURLOPT_CONNECTTIMEOUT' => 30
            // 'http.headers.PayPal-Partner-Attribution-Id' => '123123123'
            //'log.AdapterFactory' => '\PayPal\Log\DefaultLogFactory' // Factory class implementing \PayPal\Log\PayPalLogFactory
        )
    );

    $order_id = save_element($checkoutsTable, ['order'=> json_encode($data), 'type'=>'paypal']);
    if($order_id === FALSE){
        $errormsg = $lang[$loc]['auth']['signup_error'];
        return false;
    }


    // Paypal subscription plan
    $plan = new Plan();
    $plan->setName($payment_config['name'])
         ->setDescription($payment_config['desc'])
          ->setType('INFINITE');;

    // Set billing plan definitions
    $paymentDefinition = new PaymentDefinition();
    $paymentDefinition->setName(ucwords("$payment_method subscrption"))
        ->setType('REGULAR')
        ->setFrequency($payment_config['frequency'])
        ->setFrequencyInterval($payment_config['frequencyInterval'])
        ->setAmount(new Currency($payment_config['subscription']));


    // Set Merchant preferences
    $merchantPreferences = new MerchantPreferences();
    $merchantPreferences->setReturnUrl("$base_url/signup.php?status=success&order_id=$order_id")
                        ->setCancelUrl("$base_url/signup.php?status=cancel&order_id=$order_id")
                        ->setAutoBillAmount('yes')
                        ->setInitialFailAmountAction('CONTINUE')
                        ->setMaxFailAttempts('0');
    
    $plan->setPaymentDefinitions(array($paymentDefinition));
    $plan->setMerchantPreferences($merchantPreferences);

    try {
        $createdPlan = $plan->create($apiContext);
    } catch (PayPal\Exception\PayPalConnectionException $ex) {
        
        log_info($ex);
        $errormsg = $lang[$loc]['auth']['signup_payment_error'];
        return false;
    } catch (Exception $ex) {
        log_info($ex);
        $errormsg = $lang[$loc]['auth']['signup_payment_error'];
        return false;
    }

    // Activating Plan
    try {
        $patch = new Patch();
        $value = new PayPalModel('{"state":"ACTIVE"}');
        $patch->setOp('replace')
                ->setPath('/')
                ->setValue($value);
        $patchRequest = new PatchRequest();
        $patchRequest->addPatch($patch);
        $createdPlan->update($patchRequest, $apiContext);
        $patchedPlan = Plan::get($createdPlan->getId(), $apiContext);
        
        // require_once "createPHPTutorialSubscriptionAgreement.php";

    } catch (PayPal\Exception\PayPalConnectionException $ex) {
        log_info($ex);
        $errormsg = $lang[$loc]['auth']['signup_payment_error'];
        return false;
    } catch (Exception $ex) {
        log_info($ex);
        $errormsg = $lang[$loc]['auth']['signup_payment_error'];
        return false;
    }

    // Create new agreement
    $startDate = date('c', time() + 3600);
    $agreement = new Agreement();
    $agreement->setName($payment_config['name'].' Agreement')
            ->setDescription($payment_config['name'] . ' Billing Agreement')
            ->setStartDate($startDate);

    // Set plan id
    $plan = new Plan();
    $plan->setId($patchedPlan->getId());
    $agreement->setPlan($plan);

    // Add payer type
    $payer = new Payer();
    $payer->setPaymentMethod('paypal');
    $agreement->setPayer($payer);

    
    try {
        // Create agreement
        $agreement = $agreement->create($apiContext);
        
        // Extract approval URL to redirect user
        $approvalUrl = $agreement->getApprovalLink();
        
        $successmsg = $approvalUrl;
        return true;

    } catch (PayPal\Exception\PayPalConnectionException $ex) {
        
        log_info($ex);
        $errormsg = $lang[$loc]['auth']['signup_payment_error'];
        return false;
    } catch (Exception $ex) {
        log_info($ex);
        $errormsg = $lang[$loc]['auth']['signup_payment_error'];
        return false;
    }

}


function generate_stripe($data, &$errormsg, &$successmsg){
    global $checkoutsTable, $payment_details, $paypal_id, $paypal_secret, $lang, $loc, $base_url;
    
    $payment_method = check_val($data, 'payment', 'monthly'); 
    // log_info($payment_method);
    if(!isset($payment_details['stripe']['subscriptions'][$payment_method])){
        $errormsg = $lang[$loc]['auth']['signup_subscription_error'];	
        return false;
    }


    $payment_config = $payment_details['stripe']['subscriptions'][$payment_method];
    

    $order_id = save_element($checkoutsTable, ['order'=> json_encode($data), 'type'=>'stripe']);
    if($order_id === FALSE){
        $errormsg = $lang[$loc]['auth']['signup_error'];
        return false;
    }

    try{
        // Stripe init
        $stripe = new StripeClient($payment_details['stripe']['secret']);

        $subscription = [ 'items' => [[ 'plan' => $payment_config['plan_id'] ]] ];

        $trial_days = check_val($payment_config, 'trial', 0);
        if($trial_days > 0){
            $subscription['trial_period_days'] = $trial_days;
        }

        // Stripe checkout
        $session = $stripe->checkout->sessions->create([
            'subscription_data' => $subscription,
            'payment_method_types' => $payment_details['stripe']['payment_methods'],
            'success_url' => "$base_url/signup.php?status=success&order_id=$order_id&type=stripe",
            'cancel_url' => "$base_url/signup.php?status=cancel&order_id=$order_id&type=stripe",
            
        ]);
        

        // Stripe valid checkout
        $session_id = check_val($session, 'id');
        $session_url = check_val($session, 'url');
        if(empty($session_id) || empty($session_url)){
            $errormsg = $lang[$loc]['auth']['signup_payment_error'];
            return false;
        }

        // update order
        update_element($checkoutsTable, ['id'=>$order_id, 'details'=>$session_id]);

        // success
        $successmsg = $session_url;
        return true;

    }catch(Exception $ex){
        log_info($ex);
        $errormsg = $lang[$loc]['auth']['signup_payment_error'];
        return false;
    }
    
}




function generate_free($data, &$errormsg, &$successmsg){
    global $checkoutsTable, $payment_details, $paypal_id, $paypal_secret, $lang, $loc, $base_url;
    
    $payment_method = check_val($data, 'payment', 'monthly'); log_info($payment_method);
    if(!isset($payment_details['stripe']['subscriptions'][$payment_method])){
        $errormsg = $lang[$loc]['auth']['signup_subscription_error'];	
        return false;
    }


    

    $order_id = save_element($checkoutsTable, ['order'=> json_encode($data)]);
    if($order_id === FALSE){
        $errormsg = $lang[$loc]['auth']['signup_error'];
        return false;
    }

    // update order
    // update_element($checkoutsTable, ['id'=>$order_id, 'details'=>$session_id]);

    // success
    $successmsg = "$base_url/subscription.php?status=success&order_id=$order_id";
    return true;
    
}

?>