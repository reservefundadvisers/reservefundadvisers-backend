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

        if(endsWith($cmd, '_position_roles')){
            $clientPositionRoles = new ClientPositionRoles();
            return $clientPositionRoles->process(str_replace('_position_role', '', $cmd), $data);
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
        global $auth, $usersTable, $clientPositionsTable, $clientPositionRolesTable, $userModelsTable;


        if(!is_valid($data, 'client_id'))return ['error' => $this->errors['client_id']];

        /* CONDS */
        // check if an id is given
        $conds = format_conds($data, "( $usersTable.fn LIKE :search OR $usersTable.ln LIKE :search OR $usersTable.username LIKE :search OR
                                        $clientPositionsTable.value LIKE :search OR $clientPositionRolesTable.value LIKE :search OR $usersTable.email LIKE :search OR $usersTable.phone LIKE :search )");

        $conds[',role'] = ['client_admin', 'client_user', 'property_manager', 'company_admin', 'company_user'];
        $conds['client_id'] = $data['client_id'];
        /* ******* */


        /* FETCH */
        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);


        if($is_pagination){
            $count = get_element_join(  $usersTable, $conds,
                                    "LEFT JOIN $clientPositionsTable ON $clientPositionsTable.id = $usersTable.position_id LEFT JOIN $clientPositionRolesTable ON $clientPositionRolesTable.id = $usersTable.position_role_id",
                                    "COUNT(id) AS count" );
            if(isset($count['count']))$total = (int)(intval($count['count'])/$pagination['size'])+1;
        }

        $results = get_elements_join($usersTable, $conds,
                                "LEFT JOIN $clientPositionsTable ON $clientPositionsTable.id = $usersTable.position_id LEFT JOIN $clientPositionRolesTable ON $clientPositionRolesTable.id = $usersTable.position_role_id",
                                format_select($usersTable, "*", ['row_id', 'password']).", $clientPositionsTable.value AS position, $clientPositionRolesTable.value AS position_role", " GROUP BY $usersTable.id ORDER BY $usersTable.row_id DESC".check_val($pagination, 'query'));
        /* ******* */

        #exclude loggedin user from list
        foreach($results as $key => $user){
            if($user['id'] == $auth->uid()){
                unset($results[$key]);
                break;;
            }
        }

        # Check if model_id is provided and add is_invited_for_model flag
        $model_id = check_val($data, 'model_id', null);
        if(!empty($model_id)){
            $client_id = $data['client_id'];
            foreach($results as $key => $user){
                $invitation = get_element($userModelsTable, [
                    'user_id' => $user['id'],
                    'model_id' => $model_id,
                    'client_id' => $client_id
                ]);
                $results[$key]['is_invited_for_model'] = !empty($invitation);
            }
        }

        if($is_pagination)
            return send_json_response(true, 200, $this->success['success'], ['last_page'=>$total, 'data'=>$results, 'total'=>$count['count']]);
            // return ['last_page'=>$total, 'data'=>$results, 'total'=>$count['count']];
        else
            return send_json_response(true, 200, $this->success['success'], ['data' => $results]);

    }

    public function view($data){
        
        view($this->module_name, 'client_user.view.php');

    }


    public function edit($data){
        global $usersTable, $clientsTable;
        
        $element = [];
        if(is_valid($data, 'id')){
            $element = get_element($usersTable, ['id' => $data['id']], format_select($usersTable, "*", ['row_id', 'password']));
            $element['client_type'] = explode('_', $element['role'])[0];

        }else if(is_valid($data, 'client_id')){
            $element['client_id'] = $data['client_id'];
            $element['client_type'] = check_val(get_element($clientsTable, ['id'=>$data['client_id']], 'type'), 'type', 'client');
            
        }
       

        view($this->module_name, 'client_user.edit.php', $element);

    }

    public function save($data, $withEmail = true){
        global $auth, $usersTable, $clientsTable, $client_roles;

        if(!is_valid($data, 'client_id') || !exists($clientsTable, ['id'=>$data['client_id']])){

            $error = 'Missing Or Invalid Field ';
            $error .= is_valid($data, 'client_id') ? 'Client ID' : 'Client ID';
            
            return send_json_response(false, 400, $error);
        }

        $checkFor = [ /*'username',*/ 'position_id', 'email', 'fn', 'ln', 'role'];
    
        $ret = check_missing($checkFor, $data, $this->errors); 
        if($ret !== true){
            $ret['message'] = 'Missing required fields !';
            return send_json_response(false, 400, null, [ $ret ]);
        }
        
        $is_invite = $data['invited'] ?? false;

        $data['role'] = array_has($client_roles, $data['role']) ? $data['role'] : 'client_user'; 

        // use email as username for clients
        $data['username'] = $data['email'];

        if($this->username_exists($data['username'], check_val($data, 'id')))
        {
            // return ['error' =>  $this->errors['username_exists'] ];
            // return ['error' =>  $this->errors['email_exists'] ];
            return send_json_response(false, 400, $this->errors['email_exists']);
        }

        // hash password
        
        if(!empty(check_val($data, 'password'))){
            $data['password'] = $auth->hashpass($data['password']);
        }else{
            if(isset($data['password']))unset($data['password']);
        }
        
        $ret_id = save_element($usersTable, $data);

        if($ret_id === false){
            // return ['error' => ''];   
            send_json_response(false, 400, $this->errors['internal_error']); 
        }
                
        if(!is_valid($data, 'id') && $withEmail){
            $auth->generate_reset($data['username']);
        }

        if($is_invite){

            // get user role
            $role = $auth->role();

            // send invite email only for these roles
            if(in_array($role, ['client_admin','company_admin','admin','manager','property_manager'])){

                $url = $_ENV['FRONTEND_URL'] ?? '';
                $token = $auth->generate_token(64);
                $invite_url = $url . "invite-member?token=" . $token;
                $receipientName = $data['fn'] . ' ' . $data['ln'];
                $sendername = $auth->user_fnln() ?? '';
                
                $template = emailTemplateInviteMember($receipientName, $sendername , $invite_url,  'Our Platform');
                $sent = sendOtpEmail($data['email'], $template['subject'], $template['body']);

                if($sent){
                    //Update DB with token
                   $invite =  save_element($usersTable, ['id'=>$ret_id, 'invite_token'=>$token]);
                }
            }

            $paren_data = [
                'id' => $ret_id,
                'parent_user_id' => $auth->uid()
            ];
            save_element($usersTable, $paren_data);

        }

        return send_json_response(true, 200, $this->success['success'], ['id'=>$ret_id]);
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
        


        if(!empty($res)){
             return send_json_response(false, 404 , $this->errors['not_found'], ['id' => $res]);
        }
        else {
            return send_json_response(true, 200, $this->success['delete_success'], ['id' => $data['id']]);
        }

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


    private $errors = [ "id" => "User is invalid !",
                        "association" => "Association Name invalid !",
                        "fn" => "First Name invalid !",
                        "ln" => "Last Name invalid !",
                        "position_id" => "Position invalid !",
                        "username" => "Username invalid !",
                        "username_exists" => "Username already exists !",
                        "email_exists" => ['key' => 'EMAIL','message' => "Email already assigned !"],
                        "email" => "Email invalid !",
                        "password" => "Password invalid !",
                        "password_reset" => "Password Reset couldn't be generated !",
                        "role" => "User Role invalid !",
                        "self" => "Operation not allowed on this user",
                        "password_short" => "Password must be at least 6 characters !",
                        "client_id" => "Please choose a Client first !",
                        "unspecified" => "Please choose a User first !" ,
                        "not_found" => "No Users found !",
                        'internal_error' => 'Internal Error !' ];

    private $tr = [     "password_reset" => "Password Reset link has been sent to the User's <u>Email</u> !"];
                        
    private $color = [  "admin" => "purple",
                        "manager" => "deep-orange"   ];

    private $success = ["created" => "Created successfully !" , 
                        'success' => "Successfully !" , 
                        'delete_success' => "Deleted successfully !" ];

}