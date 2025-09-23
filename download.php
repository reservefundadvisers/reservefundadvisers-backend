<?php


include_once(__DIR__.'/app/init.php');


// check if logged in and validated 
if(!$auth->isValidated()){
    // send response
    $db->close();
    die('');
}

// check if request is correct
$request = check_request('file'); 
if($request === false){
    die('');
}

// Get file path command
$file = "";
if(isset($request['file'])){ $file = $request['file']; unset($request['file']); }

$requested_file = "$modules_dir/$file";

// check if module exists
if(!file_exists($requested_file) || is_dir($requested_file)){
    die('');
}


if(empty($requested_file_name))
    $requested_file_name = basename($requested_file);

$requested_file_size = filesize($requested_file);
$requested_file_mime_type = mime_content_type($requested_file);



ob_end_clean();

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Accept-Ranges: bytes');
header('Content-Disposition: attachment; filename="'.$requested_file_name.'"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $requested_file_size);
readfile($requested_file);


exit;




?>