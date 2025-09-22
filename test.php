<?php

    include_once('app/server.php');

    if(empty($_GET['goto'])){
        die('');
    }

    list($module) = explode('.', $_GET['goto']);

    $path = "$modules_dir/$module/$module.php";


    if(file_exists($path)){
        header("Location: $module/$module.php");
    }else{
        //echo "KO";
    }

?>