<?php

/**
 *  Read a file containing JSON and return it as assoc array
 */
function read_json($path){

    $json = array();

    
    if(!file_exists($path) || !is_file($path))
        return $json;
    
    $json = parse_json(file_get_contents($path));
    

    return $json;

}

/**
 *  Parse a JSON string and return it as assoc array
 */
function parse_json($json){
    
    $returnAsValue = (func_num_args() > 1 && func_get_arg(1) == true);

    if(!is_string($json)){ return $json;}

    $arr = json_decode($json, true);
    
    if($arr == false && !$returnAsValue){
        return array();
    }else if($arr == false && $returnAsValue){        
        return $json;
    }

    return $arr;

}

/**
 *  Check if string is JSON
 */
function is_json($json){
    
    if(empty($json) || is_array($json))return false;

    return json_decode($json, true) !== null;
}



function sanitize_request(&$arr){

    if(!is_array($arr)){
        $arr = str_replace('<?php', '', $arr);
        $arr = str_replace('<?', '', $arr); 
        $arr = str_replace('?>', '', $arr);
        return;
    }

    if(is_assoc($arr)){
        foreach($arr as $k => $v){
            sanitize_request($arr[$k]);
        }
    }else{
        for($i=0; $i<count($arr); $i++){
            sanitize_request($arr[$i]);
        }
    }
}



/**
 *  Get the HTTP request either POST or GET
 */
function check_request(){

    if(empty($_POST) && empty($_GET)){
        return false;
    }

    $checkFor = func_num_args() > 0 ? func_get_arg(0) : 'cmd';

    $request = array();

    // check for commands
    if(isset($_POST)){
        //$tmp = $_POST;
        $request = $request + $_POST;
        unset($_POST);
        //return $tmp;
    }

    if (isset($_GET)){
        //$tmp = $_GET;
        $request = $request + $_GET;
        unset($_GET);
        //return $tmp;
    }

    sanitize_request($request);
    
    if(empty($request) || !isset($request[$checkFor]) )
        return false;
    else
        return $request;
}


/**
 *  Die as a response
 */
function die_response($response){

    if(!is_array($response))
        die($response."");
    else
        die(json_encode($response));

}

/**
 *  Check if an array is assoc
 */
function is_assoc($arr)
{
    if(!is_array($arr))return false; 

    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}


/**
 *  Check if array has element, third param true if key is returned
 */
 function array_has($array, $key){
    
    // if it's assoc, check if element is set as key
    if(is_assoc($array) && !empty($array)){
         
        $res = isset($array[$key]);
        if(func_num_args() > 2 && func_get_arg(2) === true){
            return $key;
        }else{
            return $res;
        }

    // else if it's regular array, check the index 
    }else if(is_array($array) && !empty($array)){
        
        $index = array_search($key, $array);
        if(func_num_args() > 2 && func_get_arg(2) === true){
            return $index;
        }else{
            return $index !== false;
        }
    }else{
        return false;
    }
 }


 // return first level key 
 function array_val($array, $val){

    $found = false;

    // if its assoc
    if(is_assoc($array) && !empty($array)){
        // for each element
        foreach($array as $k => $v){

            // if it's assoc, recursive
            if(is_assoc($v)){
                $found = array_val($v, $val);
            }else{
                // if it's not array
                if(!is_array($v)){
                    $found = $v == $val ? $k : false;
                }else if(is_array($v) && count($v) > 0){
                    
                    $found = in_array($val, $v) ? $k : false;                    
                }
            }

            if($found !== false)break;
        }
    }
    
    return $found;

 }

 function array_remove(&$array, $value){
    if (($key = array_search($value, $array)) !== false) {
        unset($array[$key]);
    }
 }

 function is_valid($var, $index){

    if(!isset($var[$index]))return false;

    if(!is_array($var[$index]) && strlen($var[$index]."") == 0)return false;    

    if(func_num_args() > 2){return $var[$index] == func_get_arg(2); }

    return true;
}


/**
 *  Put In Path
 */

function put_in_path(&$obj, $path, $value){
    if(!is_string($path))return false;

    $checkIfPathExists = func_num_args() > 3 ? func_get_arg(3) : false ;
    
    // get path and check if valid
    $paths = explode("/", $path);
    if(count($paths) < 1)return false;
    
    // check if given path exists
    if($checkIfPathExists && !isset($obj[$paths[0]]))return false;

    // if last path element, set value
    if(count($paths) == 1){
        $obj[$paths[0]] = $value;
        return true;
    
    // else recursive
    }else{
        return put_in_path($obj[$paths[0]], implode('/', array_slice($paths, 1)), $value);
    }
    
}


/**
 *  Get From Path
 */

function get_from_path(&$obj, $path){
    
    if(!is_string($path))return null;
    
    // get path and check if valid
    $paths = explode("/", $path); 
    if(count($paths) < 1)return null;
    
    // check if given path exists
    if(!isset($obj[$paths[0]]))return null;

    // if last path element, set value
    if(count($paths) == 1){
        return $obj[$paths[0]];
    
    // else recursive
    }else{
        return get_from_path($obj[$paths[0]], implode('/', array_slice($paths, 1)));
    }
    
}


function check_val($var, $index){

    $return_val = func_num_args() > 2 ? func_get_arg(2) : '';

    $value = null;

    if(str_has($index, '/')){
        $value = get_from_path($var, $index);
    }else{
        if(!isset($var[$index]))return $return_val;
        $value = $var[$index];
    }

    if(empty($value) && (!is_array($value) && "$value" !== "0"))return $return_val;

    return $value;
}


function startsWith($haystack, $needle){
    return stripos($haystack, $needle) === 0;
}


function endsWith($haystack, $needle){
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}


function str_has($haystack, $needle){
    return stripos($haystack, $needle) !== false;
}

/**
 *  Check assoc for keys
 */
function check_assoc(&$arr, $keys){

    $init_on_missing = func_num_args() > 2 ? func_get_arg(2) : false;

    $res = true;

    foreach($keys as $k){
        if(strpos($k, '/') !== false){
            $subKeys = explode("/", $k);
            $subKey = array_shift($subKeys);
            if(!check_assoc($arr[$subKey], [ implode('/', $subKeys) ], $init_on_missing)){
                
                $res = false;
                break;
            }
        }else{
            if(!isset($arr[$k]) || (!is_numeric($arr[$k]) && empty($arr[$k])) ){
                if(!$init_on_missing){
                    $res = false;
                    break;
                }else{
                    $arr[$k] = '';
                }
            }
        }
        
    }

    return $res;

}


function array_clone(&$dst, $org){

    $dst = [];
    if(is_assoc($org)){
        foreach($org as $k => $v){
            $dst[$k] = $v;
        }
    }else if(is_array($org)){
        foreach($org as $v){
            array__push($dst, $v);
        }
    }

}

// extract array with given $keys array
function array_extract(&$src, $keys, $remove = false){
    if(     !is_assoc($src) || !is_array($keys)  
        ||  empty($src)  || empty($keys))return [];

    $dst = [];
    $tmp = array(); array_clone($tmp, $src);    
    

    foreach($tmp as $k => $v){
        $kIndex = array_has($keys, $k, true);        
        if($kIndex !== false){
            $tmpK = $keys[$kIndex];
            $dst[$tmpK] = $src[$tmpK];
            unset($keys[$tmpK]);
            
            if($remove)unset($src[$tmpK]);
        }else if(is_assoc($v)){
            $found = array_extract($src[$k], $keys, $remove);
            if(!empty($found)){
                
                if(!isset($dst[$k]))$dst[$k] = array();
                $dst[$k] = array_merge($dst[$k], $found);
                if($remove && empty($src[$k]))unset($src[$k]);
                
            }
        }
    }
    

    return $dst;

}


function check_missing($checkFor, $data, $errors){

    // check if required inputs are available
    foreach($checkFor as $v){
        if(!check_assoc($data, [$v])){
            return ['error'=> check_val($errors, $v, $v)]; 
        }
    }

    return true;
}


function log_info(){
	
	for($i=0; $i<func_num_args(); $i++){
		$error = func_get_arg($i);
		if(is_array($error) || is_object($error))
			error_log(print_r($error, true));
		else
			error_log($error);
		//Analog::log($e->getMessage());

	}
	
	
}

function list_dir($dir){
    
    if(!is_dir($dir))return [];
    return array_diff(scandir($dir), array('.', '..'));

}

//-------------------------
// Define DB Table Columns
//-------------------------

function define_db_table_cols(){
	extract($GLOBALS);

	echo "<pre>";

	// add table name variable without '$', exemple: $tableName -> 'tableName';
	$tables = [];
	if(func_num_args() > 0){
		$ts = func_get_arg(0);
		if(is_string($ts))$tables = [$ts];
		else if(is_array($ts) && !is_assoc($ts))$tables = $ts;
	}

	foreach($tables as $table){
		$t = '';
		eval('$t = $$table;');
		$results = $db->fetchRowMany("DESCRIBE ".$t);
		$columns = array();
		foreach($results as $row){
			array_push($columns, "'".$row['Field']."'");
		}
	
		echo '$'.$table.' => ['.implode(', ', $columns).'], </br>';
	}

	echo "</pre>";
}


?>