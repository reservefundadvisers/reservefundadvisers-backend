<?php

class ClientUsers
{  
	
    private $module_name = 'client_users';
	
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
            case 'load': $response = view($this->module_name, 'client_users.php'); break;
            case 'get': $response = $this->list($data); break;
            case 'edit': $response = $this->edit($data); break;
            case 'save': $response = $this->save($data); break;     
            case 'delete': $response = $this->delete($data); break;  
            case 'set': $response = $this->set($data); break;            
        }

        return $response;

	}


    public function list($data){
        global $auth, $usersTable, $roles_name, $roles_badge, $clientPositionsTable;


        $filters = array();
        if(is_valid($data, 'filters'))
        $filters = format_filters(  $data['filters'], 
                                    ['name'=>["value"=>'%?%', "query"=> "CONCAT($usersTable.fn, ' ', $usersTable.ln) LIKE :?"] ]);
                       
        
        // check if an id is given
        $conds = format_conds($data, "( CONCAT($usersTable.fn, ' ', $usersTable.ln) LIKE :search OR $usersTable.username LIKE :search OR
                                        $usersTable.email LIKE :search OR $usersTable.phone LIKE :search )");
              
        
        append_filter($conds, $filters);

        
        $conds[';role'] = ['admin', 'manager'];
        $conds['client_id'] = $auth->clientId(); 


        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);


        if($is_pagination){
            $count = get_element(  $usersTable, $conds,"COUNT(id) AS count" );            
            if(isset($count['count']))$total = (int)(intval($count['count'])/$pagination['size'])+1;
        }

        $results = get_elements_join($usersTable, $conds,
                                     "LEFT JOIN $clientPositionsTable ON $clientPositionsTable.id = $usersTable.position_id",
                                     format_select($usersTable, "*", ['row', 'password']) . ", $clientPositionsTable.value AS position ", " GROUP BY $usersTable.id ORDER BY $usersTable.row DESC".check_val($pagination, 'query'));

        $self_id = $auth->uid();
        foreach($results as &$result){
            if($result['id'] == $self_id){
                $result['is_self'] = true;
            }

            $result['role_name'] = $roles_name[$result['role']];
            $result['role_color'] = $roles_badge[$result['role']];
        }

        if($is_pagination)
            return ['last_page'=>$total, 'data'=>$results, 'total'=>$count['count']];
        else
            return $results;
        
    }


    public function edit($data){
        global $usersTable;

        $element = [];
        if(is_valid($data, 'id')){
            if(!belongs_to_client($usersTable, $data['id']))return ['error' => $this->errors['not_allowed'] ]; 
            $element = get_element($usersTable, ['id' => $data['id']], format_select($usersTable, "*", ['row', 'password']));
        }
            

        view($this->module_name, 'client_user.edit.php', $element);

    }


    public function save($data){
        global $auth, $usersTable, $client_roles;


        if(is_valid($data, 'id') && !belongs_to_client($usersTable, $data['id']))
            return ['error' => $this->errors['not_allowed'] ];

        $checkFor = ['fn', 'ln', /* 'username', */ 'email'];
            
        $ret = check_missing($checkFor, $data, $this->errors); if($ret !== true)return $ret;

        

        $data['client_id'] = $auth->clientId(); 
        
        // check if role is allowed client role, to avoid creating system users
        $data['role'] = isset($data['role']) && array_has($client_roles, $data['role']) ? $data['role'] : 'client_user'; 
        
        // use email as username for clients
        $data['username'] = $data['email'];

        if(check_val($data, 'id') == $auth->uid()){
            unset($data['role']);
        }

        if(empty($data['client_id']))
            return ['error' => $this->errors['client_id']];  

        if($this->username_exists($data['username'], check_val($data, 'id')))
            return ['error' =>  $this->errors['email_exists'] ];
        
        $ret_id = save_element($usersTable, $data);

        if($ret_id === false)
            return ['error' => ''];    
          
        if(!is_valid($data, 'id')){
            $auth->generate_reset($data['username']);
        }
        
        return ['success' => $ret_id];
    }


    
        
    
    public function delete($data){
        global $auth, $usersTable;


        
        $res = array();

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }

        $self_id = $auth->uid();
        if(array_has($data['id'], $self_id)){
            if(count($data['id']) == 1)
                return ['error' => $this->errors['self']];
            else
                array_remove($data['id'], $self_id);
        }

        // check deleted users if belongs to client
        foreach($data['id'] as $id){            
            if(!belongs_to_client($usersTable, $id))
                return ['error' => $this->errors['not_allowed'] ];
        }
            
        $trans_started = start_transaction();

        
        $res = delete_elements_by_id($usersTable, $data['id']);
    
        if($trans_started)end_transaction();

        
        $count = count($data['id']) - count($res);
        

        if(!empty($res))return ['error' => $res];
        else return ['success' => ''];

    }
    
    
    
    public function set($data){
        global $auth, $usersTable;

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }else if(!belongs_to_client($usersTable, $data['id']))
            return ['error' => $this->errors['not_allowed'] ];

        if($data['id'] == $auth->uid() && isset($data['active']))return ['error' => $this->errors['self']];

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
        
        $conds = ['username'=>$username /*, '!role'=>'client'*/];
        if(!empty($id))$conds['!id'] = $id;

        return exists($usersTable, $conds);
    }


    private $errors = [ "fn" => "<b>First Name</b> invalid !",
                        "ln" => "<b>Last Name</b> invalid !",
                        "username" => "<b>Username</b> invalid !",
                        "username_exists" => "<b>Username</b> already exists !",
                        "email_exists" => "<b>Email</b> already assigned !",
                        "password" => "<b>Password</b> invalid !",
                        "role" => "<b>Role</b> invalid !",
                        "self" => "Operation not allowed on this user",
                        "client_id" => "<b>Client</b> invalid !",
                        "password_short" => "<b>Password</b> must be at least 6 characters !"   ];

    private $tr = [     "admin" => "administrator",
                        "manager" => "manager"   ];
                        
    private $color = [  "admin" => "purple",
                        "manager" => "deep-orange"   ];

}