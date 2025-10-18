<?php


//-------------------------
// Variables
//-------------------------

$app_root = "".dirname(__DIR__)."";
$base_url = "http://localhost:8000";
$base_web = "";

$noreply_email = "louis@reservefundadvisers.com";

$signup_email = "jeff@advisortechpartners.com";

$payment_details =  [
                        'paypal'=>[
                                'key'=>'Aab9BZd1ZlbmNwyh8c4Dq5Lfm0lv43XQI7mXybOjHhu5pbOPV-j4cdnB77PaH6WgJZGBqjWgIIHgCJH2',
                                'secret'=>'EO3LAqnfLtSEoI_daaO-3VJbT9BxTihtpePOFqCkpifGJMQZzwReu4AzXhNM1kc3-KCSMSP_M7YHpaX6',
                                'subscriptions'=>[
                                    'Free'=>['name'=>'RFA Data Vizualiser Free Trial', 'desc'=>'Free Trial Subscription to RFA Data Vizualiser', 'frequency'=>'MONTH', 'frequencyInterval'=>1, 'subscription'=>['value'=>180, 'currency'=>'USD']], 
                                    'monthly'=>['name'=>'RFA Data Vizualiser Monthly', 'desc'=>'Monthly Subscription to RFA Data Vizualiser', 'frequency'=>'MONTH', 'frequencyInterval'=>1, 'subscription'=>['value'=>180, 'currency'=>'USD']], 
                                    'yearly'=>['name'=>'RFA Data Vizualiser Yearly', 'desc'=>'Yearly Subscription to RFA Data Vizualiser', 'frequency'=>'YEAR', 'frequencyInterval'=>1, 'subscription'=>['value'=>1800, 'currency'=>'USD']]
                                ]
                        ],

                        'stripe'=>[
                            'key'=>'pk_live_51ON02xF0kLC1tXYxzgvfvH4CgfXEHdPvBrT70qg1wPjNn2yQ30SxwloL4OBbDGsSXOmdzPxaeGpN4NpKfmixbZz800ckCUjbMm',
                            'secret'=>'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',
                            //'key'=>'pk_test_51ON02xF0kLC1tXYxsXdJ8SJmOSET6iTrabdGG5aJXKXkemGPRrqNM3BoMugQobUke7oFkR8uQNMJ4FzWsQQDIAAS00Dxg6mCNn',
                            //'secret'=>'sk_test_51ON02xF0kLC1tXYxD21p7Gmt6jH8vGPDrsYRxsRhctd8LnMnwLyIkgRjfhlmuUsMkWZKfe41OaNTOILTfJAJ5d3T00ixFPMGa5',
                            'payment_methods'=>['card', 'cashapp'],
                            'subscriptions'=>[
                                'free'=>['plan_id'=>'price_1ON2nlF0kLC1tXYxAPlXJQdJ', 'name'=>'RFA Data Vizualiser Free Trial', 'type'=>'service', 'trial'=>30],
                                'monthly'=>['plan_id'=>'price_1ON2nOF0kLC1tXYxwUELGaOq', 'name'=>'RFA Data Vizualiser Monthly', 'type'=>'service', 'trial'=>30],
                                'yearly'=>['plan_id'=>'price_1ON2nlF0kLC1tXYxAPlXJQdJ', 'name'=>'RFA Data Vizualiser Yearly', 'type'=>'service', 'trial'=>30]
								
								//'free'=>['plan_id'=>'price_1ON6efF0kLC1tXYxfqLCq2jC', 'name'=>'RFA Data Vizualiser Free Trial', 'type'=>'service', 'trial'=>30],
                                //'monthly'=>['plan_id'=>'price_1ON6fAF0kLC1tXYxuhe0E7M8', 'name'=>'RFA Data Vizualiser Monthly', 'type'=>'service', 'trial'=>30],
                                //'yearly'=>['plan_id'=>'price_1ON6fXF0kLC1tXYxF7dLNJAy', 'name'=>'RFA Data Vizualiser Yearly', 'type'=>'service', 'trial'=>30]
                            ]
                        ]
                    ];

$app_name = "Reserve Fund Advisors";
   
$log_dir = "$app_root/app/log";
$log_file = "$log_dir/app.log";

$modules_dir = "$app_root/modules";

// ------------------------
// MySQL Configuration :
// ------------------------

$db_config['host'] = $_ENV['DB_HOST'] ?? "127.0.0.1";
$db_config['user'] = $_ENV['DB_USER'] ?? "root";
$db_config['pass'] = $_ENV['DB_PASS'] ?? "";
$db_config['name'] = $_ENV['DB_NAME'] ?? "orloff_updated";

// $db_config['host'] = "127.0.0.1";
// $db_config['user'] = "root";
// $db_config['pass'] = "";
// $db_config['name'] = "orloff_updated";



?>