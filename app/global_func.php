<?php


include_once('config.php');


function role_landing_page($role){
    global $roles, $roles_landing, $pages;

    if(!array_has($roles, $role))return false;

    return check_val($pages, $roles_landing[$role], false);
}

function role_allowed_page($role, $page){
    global $roles, $roles_pages, $pages;

    if(!array_has($roles, $role))return false;

    return array_has($roles_pages[$role], $page);
}


function get_investment_strategies(){global $investment_strategies; return $investment_strategies; }


function print_sidebar(){
    extract($GLOBALS);
    
    $role = $auth->role();

    /* return eval(' ?>'.file_get_contents("$app_root/components/navigation/sidebar".($role === false ? "" : "_$role").".php").'<?php '); */
    return eval(' ?>'.file_get_contents("$app_root/components/navigation/sidebar.php").'<?php ');

}

function print_topbar(){
    extract($GLOBALS);
    
    return eval(' ?>'.file_get_contents("$app_root/components/navigation/topbar.php").'<?php ');

}

function print_js(){
    extract($GLOBALS);

    $role = $auth->role();

    $modules = [ pathinfo($_SERVER['PHP_SELF'])['filename'] ]; //array();
    if(func_num_args() > 0 && is_array(func_get_arg(0)))
        $modules = array_merge($modules, func_get_arg(0));
    else
        $modules = [ pathinfo($_SERVER['PHP_SELF'])['filename'] ];

    $js_dirs = ['js'];
    
    $modules = array_unique($modules);

    $js = "";

    // for each module
    foreach($modules as $module){
        
        // for each js dir
        foreach($js_dirs as $js_dir){

            // list all module js
            $js_files = list_dir("$modules_dir/$module/js");

            foreach($js_files as $js_file){
                
                if(!endsWith($js_file, '.js'))continue;

                $path = "/modules/$module/js/$js_file";
                
                if(file_exists($app_root.$path)){
                    $js .= '
                            <script src="'.$base_web.$path.'?v='.time().'"></script>';
                }
            }

            
        }
    }
    
    return $js;

}

function print_css(){
    extract($GLOBALS);


    $role = $auth->role();

    $modules = [ pathinfo($_SERVER['PHP_SELF'])['filename'] ]; //array();
    if(func_num_args() > 0 && is_array(func_get_arg(0)))
        $modules = array_merge($modules, func_get_arg(0));
    else
        $modules = [ pathinfo($_SERVER['PHP_SELF'])['filename'] ];

    $css_dirs = ['css'];

    $modules = array_unique($modules);
    

    $css = "";

    // for each module
    foreach($modules as $module){
        
        // for each css dir
        foreach($css_dirs as $css_dir){

            // list all module css
            $css_files = list_dir("$modules_dir/$module/css");

            foreach($css_files as $css_file){

                
                if(!endsWith($css_file, '.css'))continue;

                $path = "/modules/$module/css/$css_file";
                
                if(file_exists($app_root.$path)){
                    $css .= '
                            <link href="'.$base_web.$path.'?v='.time().'"  rel="stylesheet">';
                }
            }

            
        }
    }
    
    return $css;

}


function print_footer(){
    extract($GLOBALS);
    
    
    return eval(' ?>'.file_get_contents("$app_root/components/footer.php").'<?php ');

}



function print_header($headers = []){
    extract($GLOBALS);
    
    return eval(' ?>'.file_get_contents("$app_root/components/header.php").'<?php ');

}

function view($module, $name, $vars = []){
    global $modules_dir, $auth; 

    $view = "$modules_dir/$module/views/$name";
    $rand = rand(9, 9999);
    $arand = 'a'.rand(9, 9999);


    if(file_exists($view)){
        include_once($view);
        // return true;
    }else{
        return false;
    }
}

// if you find a filter field name in $repl, replace it with ["value":"", "query":""]
function format_filters(&$filters, $repl = []){

        $ret = array();        
        $count = 0;
        
        // get filters and values
        if(isset($filters) && is_array($filters) && !empty($repl) && is_assoc($repl)){
            $rem = []; $keys = array_keys($repl);

            // for each filter
            for($i=0; $i<count($filters); $i++){ 
                
                // is is within $repl values
                $filter = $filters[$i]; $field = $filter['field']; $value = $filter['value'];

                // if so
                if(array_has($keys, $field) !== false){ 
                    $curr = "filter_val".($count++); 
                    $ret[$curr] = [  
                                    "value" => str_replace("?", $value, $repl[$field]["value"]),
                                    "query" => str_replace("?", $curr, $repl[$field]["query"]) ]; 
                    
                    array_push($rem, $i);
                } 
            }

            foreach($rem as $ri)unset($filters[$ri]);
        }

        return $ret;

}

// add all filters to $conds "raw"
function append_filter(&$conds, $filters, $type = "AND"){

    $condsRaw = array();
    
    if(is_valid($conds, 'raw')) $condsRaw = [$conds['raw']]; // add any raw available to array

    foreach($filters as $f => $v){
        $conds["*$f"] = $v['value']; 
        array_push($condsRaw, $v['query']);
    }

    if(!empty($condsRaw))$conds['raw'] = implode(" $type ", $condsRaw);
    
}

/*
    tables is used for 'filter', as follow: ['*'=>'table_name_to_use_for_everything_else', 'table_name'=>['array_of_fields_to_use']]
    ex: ['*'=>'clients', 'users'=>['fn', 'ln']]  => ['clients.association'=>'abc', 'clients.email'=>'a@a.a', 'users'=>'users.fn']
*/
function format_conds($data, $search_str = "", $tables = []){
    
    $conds = array();    
    
    // has id
    if(is_valid($data, 'id'))$conds['id'] = $data['id'];
    
    // if filter            
    if(isset($data['filter']) && is_assoc($data['filter']))$conds += $data['filter'];   
    
    
    // if search
    $search = null;
    if(isset($data['search']) && is_string($data['search']) && strlen($data['search'])){                
        $search = $data['search'];
        $conds['raw'] = (isset($conds['raw']) ? $conds['raw']." AND " : '').$search_str;
        $conds['*search'] = "%$search%";     
    }


    // if table filters: "filters": {"field":"", "type":"", "value":""}
    if(isset($data['filters']) && is_array($data['filters'])){
        $anyTable = is_valid($tables, '*') ? $tables['*']."." : "";
        foreach($data['filters'] as $filter){
            $field = $filter['field'];
            $value = $filter['value'];

            $key = array_val($tables, $field);
            $key = $key === false ? "$anyTable$field" : "$key.$field";
            
            $conds["%$key"] = "$value";
        }
 
    }



    return $conds;
}

function format_pagination($data){
    
    $pagination = null;

    // check if pagination
    $is_pagination = is_valid($data, 'pagination'); 
    $max_allowed_size = 50; $size = 50; $page = 0;  $total = 0;

    if($is_pagination){
        if(array_has($data, 'size') && is_numeric($data['size'])) { 
            /*$size = intval($data['size']);*/ 
            $size = intval($data['size']);                    
        }   
        if(array_has($data, 'page') && is_numeric($data['page'])) { 
            $page = intval($data['page'])-1; 
            if($page < 0)$page = 0;   
        }

        if($size > $max_allowed_size)$size = $max_allowed_size;

        $pagination['size'] = $size;
        $pagination['page'] = $page + 1;
        $pagination['offset'] = $page;
        $pagination['query'] = " LIMIT $size  OFFSET ".($page*$size);
    }

    return $pagination;
    
}

function get_element($table, $conds=[], $select = "*", $extra = ""){
    global $db;


    if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
        $where = format_where($conds);
        $results = $db->fetchRow("SELECT $select FROM $table $where $extra", $conds);
    }else{
        $results = $db->fetchRow("SELECT $select FROM $table $extra");
    }
    
    if($results === null)$results = array();
    
    foreach($results as $key => $value){
        unset($results['row']);
        if($value === null)$results[$key] = "";
    }

    return $results;

}


function get_element_query($query, $conds=[]){
    global $db;
    
    $results = array();
    try{    

        if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
            $results = $db->fetchRow($query, $conds);
        }else{
            $results = $db->fetchRow($query);
        }

    }catch(Exception $e) {           
        log_info($e);
        
        return $results;
    }

    if($results === null)$results = array();

    
    foreach($results as $key => $value){
        unset($results['row']);
        //if($value === null)$row[$key] = "";
    }
    

    return $results;

}


function get_elements($table, $conds=[], $select = "*", $extra = "", $whereType = "AND"){
    global $db;


    if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
        $where = format_where($conds);
        $results = $db->fetchRowMany("SELECT SQL_CALC_FOUND_ROWS $select FROM $table $where $extra", $conds);
    }else{
        $results = $db->fetchRowMany("SELECT SQL_CALC_FOUND_ROWS $select FROM $table $extra");
    }
        
    if($results === null)$results = array();

    foreach($results as &$row){
        foreach($row as $key => $value){
            unset($row['row']);
            if($value === null)$row[$key] = "";
        }
    }

    return $results;

}


function get_elements_join($table, $conds=[], $join, $select = "*", $extra = ""){
    global $db;

    if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
        $where = format_where($conds, $table);
        //log_info("SELECT $select FROM $table $join $where $extra");
        $results = $db->fetchRowMany("SELECT SQL_CALC_FOUND_ROWS $select FROM $table $join $where $extra", $conds);
    }else{
        $results = $db->fetchRowMany("SELECT SQL_CALC_FOUND_ROWS $select FROM $table $join $extra");
    }
    
    if($results === null)$results = array();

    foreach($results as &$row){
        foreach($row as $key => $value){
            unset($row['row']);
            if($value === null)$row[$key] = "";
        }
    }
    
    return $results;

}


function get_element_join($table, $conds=[], $join, $select = "*", $extra = ""){
    global $db;


    if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
        $where = format_where($conds, $table);
        $results = $db->FetchRow("SELECT $select FROM $table $join $where $extra", $conds);
    }else{
        $results = $db->FetchRow("SELECT $select FROM $table $join $extra");
    }
    
    if($results === null)$results = array();

    foreach($results as $key => $value){
        unset($results['row']);
        if($value === null)$results[$key] = "";
    }
    
    return $results;

}


function delete_elements_by_id($table, $conds){
    global $db;

    $type = func_num_args() > 2 ? func_get_arg(2) : "";


    $errors = array();
    
    if(empty($conds))return $errors;

    if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
        
        foreach($conds as $cond){
        

            if($db->delete($table, ['id' => $cond]) === false){
                array_push($errors, $cond);
            }

        }
    }else{
        if($db->delete($table, ['id' => $conds]) === false){
            array_push($errors, $cond);
        }
    }
    
    /*
    if(count($errors) == 0)
        return true;
    else */
        return $errors;

}


function delete_elements_by_cond($table, $conds){
    global $db;

    $condData = func_num_args() > 2 ? func_get_arg(2) : "";

    $errors = array();
    if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
        
        foreach($conds as $cond => $val){
            if(is_array($val)){
                foreach($val as $v){
                    if($db->delete($table, [$cond => $v]) == false){
                        array_push($errors, $v);
                    }
                }
            }else{
                if($db->delete($table, [$cond => $val]) == false){
                    array_push($errors, $val);
                }
            }
            

        }
    }else if(is_string($conds) && !empty($conds)){
        
        $condQuery = $conds;

        if(empty($condData)){
            $res = $db->executeSql("DELETE FROM $table WHERE $condQuery");
        }else{
            $res = $db->delete($table, $condData, $condQuery);
        }
        if($res != true){
            array_push($errors, 'error');
        }
    }
    
    /*
    if(count($errors) == 0)
        return true;
    else */
        return $errors;

}



function delete_elements_query($query, $conds){
    global $db;

    $result = false;
    try{
        
        if($conds !== null && is_array($conds) && count(array_keys($conds)) > 0 ){
            $where = format_where($conds);
            $db->fetchRow($query, $conds);
        }
    
            
    }catch(Exception $e) {           
        log_error($e);
        return $result;
    }

    return true;

}



function get_last_found_rows(){
    global $db;
    
    return check_val($db->fetchRow('SELECT FOUND_ROWS() as found'), 'found', 0);
}

function get_affected_rows(){
    global $db;
    
    return check_val($db->fetchRow('SELECT ROW_COUNT() as count'), 'count', 0);
}


function start_transaction(){
    global $db;
    
    if(is_in_transaction())return false;
    return $db->transactionBegin();
}

function is_in_transaction(){
    global $db;

    return $db->isInTransaction();
}

function end_transaction(){
    global $db;
    
    return $db->transactionCommit();
}

function save_element($table, $data){
    global $db;

    // used for update, example: checkFor 'id'
    // used for update, example: checkFor 'id'
    $checkFor = func_num_args() > 2 ? func_get_arg(2) : "id";

    $is_recursive = func_num_args() > 3 ? func_get_arg(3) : false;

    $update = false;

    
    if(!is_array($checkFor) && strlen($checkFor) > 0 && isset($data[$checkFor])){
        $where = array(); $where[$checkFor] = $data[$checkFor];
        $update = exists($table, $where, true);        
    }else if(is_array($checkFor)){
        
        $where = array(); 
        foreach($checkFor as $key){
            if(isset($data[$key]))
                $where[$key] = $data[$key];
        }

        $update = exists($table, $where, true); 

    }
    

    if($update !== false){
        $data['updated_at'] = time();
        checkInsertData($data, $table);
        
        try{
            $db->update($table, ['id' => $update ], $data);        
            $result = $update;
        }catch(Exception $e){
            log_info($e->getMessage());
            $result = false;
        }

    }else{

        
        $data['created_at'] = time();
        checkInsertData($data, $table);
        
        //if(isset($data['id']))unset($data['id']);
        $data['id'] = !isset($data['id']) || empty(trim($data['id'])) ? generate_id() : $data['id'];
      
        try{
                        
            $result = $db->insert($table, $data);
            if($result !== false)$result = $data['id'];
            
        }catch(Exception $e) { 

            log_info($e->getMessage());
            // if IO duplicate error
            $parsed_error = parse_json($e->getMessage());
            if(isset($parsed_error['errorInfo']['code']) &&  $parsed_error['errorInfo']['code'] == '1062' && !$is_recursive){
                $data['id'] = '';
                for($i=0; $i<10; $i++){
                    $res = save_element($table, $data, $checkFor, true);
                    if(!empty($res))return $res;
                }
            }
            
            return false;
        }
        
    }

    return $result;
}



function update_element($table, $data){
    global $db;

    // used for update, example: checkFor 'id'
    $checkFor = func_num_args() > 2 ? func_get_arg(2) : "id";

    $where = array();

    if(!is_array($checkFor) && strlen($checkFor) > 0 && isset($data[$checkFor])){
        $where[$checkFor] = $data[$checkFor];        
    }else if(is_assoc($checkFor)){
        $where = $checkFor;
    }else if(is_array($checkFor)){
        foreach($checkFor as $key){
            if(isset($data[$key]))
                $where[$key] = $data[$key];
        }
    }
    
    if(empty($where) || empty($data))return false;

    //$data['updated_at'] = time();
    checkInsertData($data, $table);
    try{               
        $result = $db->update($table, $where, $data); 
    }catch(Exception $e){
        log_info($e);
        $result = false;
    }

    return $result;
}



function set_element($table, $data){
    global $db;

    
    $checkFor = func_num_args() > 2 ? func_get_arg(2) : 'id';

    if(!isset($data[$checkFor])){
        return false;
    }        

    checkInsertData($data, $table);
    $result = $db->update($table, [$checkFor => $data[$checkFor] ], $data);

    return true;
}



// set property
function set_property($table, $data, $checkFor = 'id'){
    
    
    $result = set_element($table, $data, $checkFor);
    if($result === true){
        return ['success'=>''];
    }else{
        return ['error'=>'property'];
    }

}



function belongs_to_client($table, $id, $onlyClient = false, $checkAsCompany = false){
    global $auth, $clientsTable, $db_table_cols;

    // if admin or manager, true
    if(!$onlyClient && !$auth->isClientRole())return true;

    if(empty($id))return false;
    

    // if management company
    if(!$onlyClient && $checkAsCompany && $auth->checkRoleType('company') && array_has($db_table_cols[$table], 'client_id')){
        $exists = get_element_join($table, ['id'=>$id, "company_t.company_id"=>$auth->clientId()],
                                    "LEFT JOIN $clientsTable company_t ON company_t.id = $table.client_id");

        return !empty($exists);
    }else{
        
        return exists($table, ['id'=>$id, 'client_id'=>$auth->clientId()]);
    }

    

}



function belongs_to_user($table, $id){
    global $auth;

    if(empty($id))return false;

    return exists($table, ['id'=>$id, 'user_id'=>$auth->uid()]);

}


function exists($table, $conds){
    global $db;
    
    $return_id = func_num_args() > 2 && func_get_arg(2) != null ? func_get_arg(2) : false;
    $type = func_num_args() > 3 && func_get_arg(3) != null ? func_get_arg(3) : "AND";
    $checkFor = func_num_args() > 4 && func_get_arg(4) != null ? func_get_arg(4) : "id";
    

    $where = format_where($conds, $table, $type); 

    if(count(array_keys($conds))> 0){
        $result = $db->fetchRow("SELECT $table.$checkFor AS search_id FROM $table $where", $conds);
    }else{
        $result = $db->fetchRow("SELECT $table.$checkFor AS search_id FROM $table $where");
    }


    if($return_id === true)
        return ($result !== null) ? $result['search_id'] : false;
    else
        return ($result !== null);
        

}


function format_where(&$conds){
        

    $table = func_num_args() > 1 ? func_get_arg(1)."." : "";
    $type = func_num_args() > 2 ? func_get_arg(2) : "AND";


    $where = "";

    if(count($conds) > 0){
        $tmp = array();
        foreach($conds as $key => $value){

            // skippable * elements
            if(startsWith($key, '*')){ unset($conds[$key]); $key = ltrim($key, '*'); $conds[$key] = $value; continue; }

            if($key == "raw"){
                array_push($tmp, " $value ");
                unset($conds['raw']);
                continue;
            }
            
            if(is_array($value) && !startsWith($key, ',') && !startsWith($key, ';')){
                foreach($value as $v)
                    array_push($tmp, format_where_cond($table, $key, $v, $conds));
            }else{  
                array_push($tmp, format_where_cond($table, $key, $value, $conds));
            }
            

            
        }    
        $where = "WHERE ".implode(strlen($type) > 0 ? $type : " AND ", $tmp);
    }
    
    return $where;

}

function format_where_cond($table, $key, $value, &$conds){
    

    $opr = "=";

    $addParenthes = false;

    $valKey = str_replace('.', '_', $key);
    

    if(strpos($key, '!') === 0){$opr = '!='; unset($conds[$key]); $key = ltrim($key, '!'); $valKey = ltrim($valKey, '!'); $conds[$valKey] = $value; }
    else if(strpos($key, '>') === 0){$opr = '>='; unset($conds[$key]); $key = ltrim($key, '>'); $valKey = ltrim($valKey, '>'); $conds[$valKey] = $value; }
    else if(strpos($key, '<') === 0){$opr = '<='; unset($conds[$key]); $key = ltrim($key, '<'); $valKey = ltrim($valKey, '<'); $conds[$valKey] = $value; }
    else if(strpos($key, '%') === 0){$opr = 'LIKE'; unset($conds[$key]); $key = ltrim($key, '%'); $valKey = ltrim($valKey, '%'); $conds[$valKey] = "%$value%"; }
    else if(strpos($key, ',') === 0){$opr = 'IN'; unset($conds[$key]); $key = ltrim($key, ','); $valKey = ltrim($valKey, ','); $conds[$valKey] = $value; $addParenthes = true;}
    else if(strpos($key, ';') === 0){$opr = 'NOT IN'; unset($conds[$key]); $key = ltrim($key, ';'); $valKey = ltrim($valKey, ';'); $conds[$valKey] = $value; $addParenthes = true;}
    else { unset($conds[$key]); $conds[$valKey] = $value; }


    if(str_has($key, '.'))$table = '';

    if(is_null($value) && $value !== 0){
            unset($conds[$valKey]);
        if($opr == '!='){
            return " $table$valKey IS NOT NULL ";
        }else{
            return " $table$valKey IS NULL ";
        }
    }



    // final conds format $conds['value_name_as_:'] = value;

    return " $table$key $opr ".($addParenthes ? "(:$valKey)" : ":$valKey")." ";
}

function checkInsertData(&$data, $table){
    global $db, $db_table_cols;


    $columns = array();
    if(is_string($table) && isset($db_table_cols[$table])){
        // Look directly in table description
        /*
        $results = $db->fetchRowMany("DESCRIBE $table");
        foreach($results as $row){
            array_push($columns, $row['Field']);
        }
        */

        // Look in Global Defined Table Columns
        $columns = $db_table_cols[$table];
    }

    if(empty($columns))return;

    foreach($data as $key => $value){
        if(array_search($key, $columns) === false){
            unset($data[$key]);
        }
    }


}

function format_select($table, $columns){
    global $db;

    $select = array();
    $except = func_num_args() > 2 ? func_get_arg(2) : array();
    

    if(is_array($columns)){
        foreach($columns as $column){
            array_push($select, "$table.$column"); 
        }
    }else if($columns === "*"){
        $results = $db->fetchRowMany("DESCRIBE $table");
        foreach($results as $row){
            if(count($except) > 0 && array_search($row['Field'], $except) !== false)continue;
            array_push($select, "$table.".$row['Field']);
        }
    }

    return implode(', ', $select);
    

}


function get_auto_inc($table){
    global $db;
    $results = $db->fetchColumn('SELECT AUTO_INCREMENT FROM  INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME  = :table', ['table' => $table]);
        
    return $results === null ? 0 : intval($results); 
}



function generate_id() {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';

    $n = 23;
  
    for ($i = 0; $i < $n; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }

    $randomString .= substr(microtime(false)."", -2).rand(1,9);
  
    return $randomString;
}

/**
 * Generates a random OTP (One-Time Password) of a given length.
 *
 * The OTP is generated from a string of numbers (0-9) and is of a fixed length.
 * The length of the OTP can be specified as a parameter, otherwise it defaults to 6.
 *
 * @param int $length The length of the OTP to generate. Defaults to 6.
 * @return string The generated OTP.
 */
function generate_otp($length = 6) {
    $characters = '0123456789';
    $randomString = '';
  
    for ($i = 0; $i < $length; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $randomString .= $characters[$index];
    }
  
    return $randomString;
}

/**
 * Handles a file upload and saves it to a specified directory.
 *
 * @param array $fileInput The file input data, either as a single file or an array of files.
 * @param string $uploadDir The directory to save the uploaded file to. Defaults to 'uploads/'.
 *
 * @return array An associative array containing the success status, a public path to the uploaded file (if successful), and a message.
 */
function handle_file_upload($fileInput, $uploadDir = 'uploads/') {
    // --- Configuration ---
    $maxSize = 2 * 1024 * 1024; // 2 MB
    $allowedExtensions = ['jpeg', 'jpg', 'png'];
    $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png'];

    // --- Get absolute root path ---
    $rootDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/';
    $uploadPath = $rootDir . trim($uploadDir, '/\\') . '/';

    // --- Ensure upload directory exists ---
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0777, true);
    }

    // --- Handle input format ---
    if (is_array($fileInput) && isset($fileInput['name'])) {
        $file = $fileInput; // single file
    } else {
        $firstKey = array_key_first($fileInput);
        $file = $fileInput[$firstKey];
    }

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'No file uploaded or upload error occurred.'
        ];
    }

    $fileName = basename($file['name']);
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // --- Validate extension ---
    if (!in_array($extension, $allowedExtensions)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Only JPEG and PNG are allowed.'
        ];
    }

    // --- Validate MIME type ---
    $fileMime = mime_content_type($file['tmp_name']);
    if (!in_array($fileMime, $allowedMimeTypes)) {
        return [
            'success' => false,
            'message' => 'Invalid file content. Only JPEG and PNG images are allowed.'
        ];
    }

    // --- Validate file size ---
    if ($file['size'] > $maxSize) {
        return [
            'success' => false,
            'message' => 'File too large. Maximum size is 2 MB.'
        ];
    }

    // --- Generate unique name & move file ---
    $newFileName = uniqid('img_', true) . '.' . $extension;
    $targetPath = $uploadPath . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Return web-accessible relative path
        $publicPath = '/' . trim($uploadDir, '/\\') . '/' . $newFileName;

        return [
            'success' => true,
            'path' => $publicPath,
            'message' => 'File uploaded successfully.'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to move uploaded file.'
        ];
    }
}

/**
 * Send a consistent JSON response for all API endpoints
 *
 * @param bool   $status   true for success, false for failure
 * @param int    $code     HTTP status code (e.g. 200, 400, 201)
 * @param string $message  A readable message for the frontend
 * @param array  $payload  Optional data or error details
 */
function send_json_response($status, $code, $message, $payload = null) {
    http_response_code($code);

    $response = [
        'code'   => $code,
        'status' => $status,
    ];

    if ($status === true) {
        $response['data'] = [
            'message' => $message
        ];
        if (!empty($payload)) {
            // Merge any extra data (e.g. user info)
            $response['data'] = array_merge($response['data'], $payload);
        }
    } else {
        $response['error'] = is_array($payload) ? $payload : ['message' => $message];
    }

    die(json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

/**
 * Write a log to a file
 * 
 * @param string $log The log message to write
 * 
 * This function writes a log message to a file named "rfa_log.log" in the "log" directory.
 * The log message is prefixed with a timestamp in the format "Y-m-d H:i:s".
 */
function rfa_create_log($log)
{
    // Define a fixed log file path (inside project "log" folder)
    $logFile = __DIR__ . '/log/rfa_log.log';

    // Create log directory if not exists
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }

    // Add timestamp for readability
    $date = date('Y-m-d H:i:s');
    $message = "[$date] $log" . PHP_EOL;

    // Append log to file
    file_put_contents($logFile, $message, FILE_APPEND);
}

?>