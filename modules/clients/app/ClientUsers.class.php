<?php



class ClientUsers
{  
	
    private $module_name = 'clients';
	
	function __construct()
	{
	

	}

	
	public function process($cmd, $data)
	{
        $response = "";

        if(endsWith($cmd, '_position')){
            $clientPositions = new ClientPositions();
            return $clientPositions->process(str_replace('_position', '', $cmd), $data);
        }

        switch($cmd){
            case 'get': $response = $this->list($data); break;
            case 'edit': $response = $this->edit($data); break;
            case 'save': $response = $this->save($data); break;     
            case 'delete': $response = $this->delete($data); break;  
            case 'set': $response = $this->set($data); break;            
        }

        return $response;

	}


    public function list($data){
        global $auth, $usersTable, $clientPositionsTable, $roles_name, $roles_badge;

        
        if(!is_valid($data, 'client_id'))return ['error' => $this->errors['client_id']];

        /* CONDS */
        // check if an id is given
        $conds = format_conds($data, "( $usersTable.fn LIKE :search OR $usersTable.ln LIKE :search OR $usersTable.username LIKE :search OR
                                        $clientPositionsTable.value LIKE :search OR $usersTable.email LIKE :search OR $usersTable.phone LIKE :search )");

        $conds[',role'] = ['client_admin', 'client_user', 'company_admin', 'company_user'];
        $conds['client_id'] = $data['client_id'];
        /* ******* */


        /* FETCH */
        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);


        if($is_pagination){
            $count = get_element_join(  $usersTable, $conds,
                                    "LEFT JOIN $clientPositionsTable ON $clientPositionsTable.id $usersTable.position_id",
                                    "COUNT(id) AS count" );            
            if(isset($count['count']))$total = (int)(intval($count['count'])/$pagination['size'])+1;
        }

        $results = get_elements_join($usersTable, $conds,
                                "LEFT JOIN $clientPositionsTable ON $clientPositionsTable.id = $usersTable.position_id",
                                format_select($usersTable, "*", ['row', 'password']).", $clientPositionsTable.value AS position", " GROUP BY $usersTable.id ORDER BY $usersTable.row DESC".check_val($pagination, 'query'));
        /* ******* */
       

        if($is_pagination)
            return ['last_page'=>$total, 'data'=>$results, 'total'=>$count['count']];
        else
            return $results;
        
    }

    public function view($data){
        
        view($this->module_name, 'client_user.view.php');

    }


    public function edit($data){
        global $usersTable, $clientsTable;
        
        $element = [];
        if(is_valid($data, 'id')){
            $element = get_element($usersTable, ['id' => $data['id']], format_select($usersTable, "*", ['row', 'password']));
            $element['client_type'] = explode('_', $element['role'])[0];

        }else if(is_valid($data, 'client_id')){
            $element['client_id'] = $data['client_id'];
            $element['client_type'] = check_val(get_element($clientsTable, ['id'=>$data['client_id']], 'type'), 'type', 'client');
            
        }
       

        view($this->module_name, 'client_user.edit.php', $element);

    }

    public function save($data, $withEmail = true){
        global $auth, $usersTable, $clientsTable, $client_roles;

        if(!is_valid($data, 'client_id') || !exists($clientsTable, ['id'=>$data['client_id']]))
            return ['error' => $this->errors['client_id']];

        $checkFor = [ /*'username',*/ 'position_id', 'email', 'fn', 'ln', 'role'];
       
    
        $ret = check_missing($checkFor, $data, $this->errors); 
        if($ret !== true)
            return $ret;
        

        $data['role'] = array_has($client_roles, $data['role']) ? $data['role'] : 'client_user'; 

        // use email as username for clients
        $data['username'] = $data['email'];

        if($this->username_exists($data['username'], check_val($data, 'id')))
            // return ['error' =>  $this->errors['username_exists'] ];
            return ['error' =>  $this->errors['email_exists'] ];

        // hash password
        
        if(!empty(check_val($data, 'password'))){
            $data['password'] = $auth->hashpass($data['password']);
        }else{
            if(isset($data['password']))unset($data['password']);
        }
        
        $ret_id = save_element($usersTable, $data);

        if($ret_id === false)
            return ['error' => ''];    
                
        if(!is_valid($data, 'id') && $withEmail){
            $auth->generate_reset($data['username']);
        }

        return ['success' => $ret_id];
    }


    
        
    
    public function delete($data){
        global $auth, $usersTable;


        
        $res = array();

        if(!is_valid($data, 'id') && !is_valid($data, 'client_id')){
            return ['error' => 'unspecified'];
        }
        
        $checkFor = is_valid($data, 'id') ? 'id' : 'client_id';

        $trans_started = start_transaction();

                                
        // and delete only for this
        $res = delete_elements_by_id($usersTable, $data[$checkFor], $checkFor);
    
        if($trans_started)end_transaction();

        
        $count = count($data[$checkFor]) - count($res);
        

        if(!empty($res))return ['error' => $res];
        else return ['success' => ''];

    }
    
    
    
    public function set($data){
        global $auth, $usersTable;

        if(!is_valid($data, 'id')){
            return ['error' => $this->errors['id']];
        }
        
        if(isset($data['password'])){
            
            // get user by id 
            $user = get_element($usersTable, ['id' => $data['id']]);
            if(empty($user))return ['error' => $this->errors['id']];

            // generate password reset link
            $ret = $auth->generate_reset($user['username']);
            if(empty($ret))return ['error' => $this->errors['password_reset']];
            else return ['success' => $this->tr['password_reset']];
        }

        return set_property($usersTable, $data);

    }

    public function username_exists($username, $id = null){
        global $usersTable;

        if(empty($username))return false;
        
        $conds = ['username'=>$username/*, 'role'=>'client'*/];
        if(!empty($id))$conds['!id'] = $id;

        return exists($usersTable, $conds);
    }


    private $errors = [ "id" => "<b>User</b> is invalid !",
                        "association" => "<b>Association Name</b> invalid !",
                        "fn" => "<b>First Name</b> invalid !",
                        "ln" => "<b>Last Name</b> invalid !",
                        "position_id" => "<b>Position</b> invalid !",
                        "username" => "<b>Username</b> invalid !",
                        "username_exists" => "<b>Username</b> already exists !",
                        "email_exists" => "<b>Email</b> already assigned !",
                        "email" => "<b>Email</b> invalid !",
                        "password" => "<b>Password</b> invalid !",
                        "password_reset" => "<b>Password Reset</b> couldn't be generated !",
                        "role" => "<b>User Role</b> invalid !",
                        "self" => "Operation not allowed on this user",
                        "password_short" => "<b>Password</b> must be at least 6 characters !",
                        "client_id" => "Please choose a <b>Client</b> first !",
                        "unspecified" => "Please choose a <b>User</b> first !"   ];

    private $tr = [     "password_reset" => "<b>Password Reset</b> link has been sent to the User's <u>Email</u> !"];
                        
    private $color = [  "admin" => "purple",
                        "manager" => "deep-orange"   ];

}