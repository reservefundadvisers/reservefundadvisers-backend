<?php

include_once('global_func.php');
include_once('global_utils.php');
include_once('lang.php');

include_once('config.php');
include_once('payment_methods.php');
include_once('auth.class.php');
include_once('config.class.php');

\header('Content-type: text/html; charset=utf-8');


use Simplon\Mysql\Mysql;
use Simplon\Mysql\PDOConnector;

require $app_root . '/vendor/autoload.php';


//------------------
// ERROR REPORTING
//------------------
//error_reporting(0); 
ini_set('display_errors', 0);
//ini_set('display_startup_errors', 0);
ini_set("log_errors", 1);
//error_log( "Hello, errors!" );
ini_set("error_log", $log_file);



/**
 *  INIT DATABASE
 */

$pdo = new PDOConnector(
	$db_config['host'],
	$db_config['user'],
	$db_config['pass'],
	$db_config['name']
);

$db = null;

try{
    $c = $pdo->connect();
    $db = new Mysql($c);

}catch(\Exception $e){
    //echo $e->getMessage();
}


//define_db_table_cols(['modelItemsTable']);

/***************** */

//------------------
// Init Auth Class
//------------------

$auth = new Auth();


//------------------
// Init Configuration Class
//------------------

//$configuration = new Configuration();




?>