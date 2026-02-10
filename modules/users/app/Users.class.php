<?php

class Users
{  
	
    private $module_name = 'users';
	
	function __construct()
	{
	

	}

	
	public function process($cmd, $data)
	{
        $response = "";

        switch($cmd){
            case 'load': $response = view($this->module_name, 'users.php'); break;
            case 'get': $response = $this->list($data); break;
            case 'edit': $response = $this->edit($data); break;
            case 'save': $response = $this->save($data); break;     
            case 'delete': $response = $this->delete($data); break;  
            case 'set': $response = $this->set($data); break;    
            case 'profile': $response = $this->getProfile($data); break;        
        }

        return $response;

	}


    public function list($data){
        global $auth, $usersTable, $roles_name, $roles_badge;


        $filters = array();
        if(is_valid($data, 'filters'))
        $filters = format_filters(  $data['filters'], 
                                    ['name'=>["value"=>'%?%', "query"=> "CONCAT($usersTable.fn, ' ', $usersTable.ln) LIKE :?"] ]);
                       
        
        // check if an id is given
        $conds = format_conds($data, "( CONCAT($usersTable.fn, ' ', $usersTable.ln) LIKE :search OR $usersTable.username LIKE :search OR
                                        $usersTable.email LIKE :search OR $usersTable.phone LIKE :search )");
              
        
        append_filter($conds, $filters);

        
        $conds[';role'] = ['client_admin', 'client_user'];

        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);


        if($is_pagination){
            $count = get_element(  $usersTable, $conds,"COUNT(id) AS count" );            
            if(isset($count['count']))$total = (int)(intval($count['count'])/$pagination['size'])+1;
        }

        $results = get_elements($usersTable, $conds,
                                format_select($usersTable, "*", ['row_id', 'password']), " GROUP BY $usersTable.id ORDER BY $usersTable.row_id DESC".check_val($pagination, 'query'));

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
        if(is_valid($data, 'id'))
            $element = get_element($usersTable, ['id' => $data['id']], format_select($usersTable, "*", ['row_id', 'password']));


        view($this->module_name, 'user.edit.php', $element);

    }

    public function save($data){
        global $auth, $usersTable;

        $checkFor = ['fn', 'ln', 'username', 'email'];
        $allowed_roles = ['admin', 'manager'];

        if(!is_valid($data, 'id'))array_push($checkFor, 'role');
    
        $ret = check_missing($checkFor, $data, $this->errors); if($ret !== true)return $ret;

        //added comment to remove undefine variable warning
        $client_roles = '';


        // check if role is allowed client role, to avoid creating system users
        $data['role'] = isset($data['role']) && array_has($client_roles, $data['role']) ? $data['role'] : 'manager'; 
        
        if(is_valid($data, 'password')){
            if(strlen($data['password']) < 6)return ['error' => $this->errors['password_short']];
            $data['password'] = $auth->hashpass($data['password']);
        }

        $data['client_id'] = NULL; 

        
        if(check_val($data, 'id') == $auth->uid()){
            unset($data['role']);
        }


        if($this->username_exists($data['username'], check_val($data, 'id')))
            return ['error' =>  $this->errors['username_exists'] ];
        
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
        
            
        $trans_started = start_transaction();

                                
        // and delete only for this company_id
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
        }

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

    /**
     * Returns user profile session information.
     *
     * @param array $data Unused parameter.
     *
     * @return array User profile session information or error message.
     */
    public function getProfile($data){
        global $auth, $usersTable, $usersTable, $modelsTable, $clientPositionsTable, $userAssociationsTable, $userCompaniesTable, $clientsTable;

        try {
            // Log API call start

            $user_info = $auth->sessioninfo();
            if(empty($user_info)){
                return send_json_response(false, 404, $this->errors['not_found']);
            }

        $user_data = get_element($usersTable, ['id' => $user_info['uid']], '*');
        if(empty($user_data)){
            return send_json_response(false, 500, 'Failed to retrieve user data');
        }
        $user_info['userdata'] = $user_data;


        $user_client_id = get_element($usersTable, ['id' => $user_info['uid']], 'client_id');
        $user_position_key = get_element($clientPositionsTable, ['id' => $user_info['userdata']['position_id']], 'position_key');
        if(!empty($user_position_key)){
            $user_info['userdata']['position_key'] = $user_position_key['position_key'];
            $user_info['profile_type'] = $user_position_key['position_key'];
        }

        $user_info['is_association_created'] = false;
        if(!empty($user_client_id['client_id']) && !empty($user_info['association'])){
            $user_info['is_association_created'] = true;
        }

        $user_info['is_company_created'] = false;
        if(!empty($user_info['company'])){
            $user_info['is_company_created'] = true;
        }

        // $user_info['is_association_created'] = $user_client_id ? true : false;
        // rfa_create_log(print_r($user_info['is_association_created'], true));
        $user_info['is_models_created'] = false;
        if($user_client_id){
            $user_client_models = get_elements($modelsTable, ['client_id' => $user_client_id]);
            $user_info['is_models_created'] = $user_client_models ? true : false;
        }

        // get company profile
        if(!empty($user_info['client_type']) && $user_info['client_type'] == 'company' || $user_info['role'] == 'property_manager'){
            $company_profile = get_element('clients', ['id' => $user_info['client_id']], '*');
            if(!empty($company_profile)){
                $user_info['company_profile'] = $company_profile;
            }
        }

        // Get user's associations list from user_associations table
        $user_associations = get_elements($userAssociationsTable, ['user_id' => $user_info['uid']], '*', "ORDER BY created_at DESC");
        $associations_list = [];
        if(!empty($user_associations)){
            foreach($user_associations as $association){
                // Get the association/client details
                $association_details = get_element($clientsTable, ['id' => $association['client_id']], 'id, association, company, type, email, media, phone, address, address2, city, zip, state');
                if(!empty($association_details)){
                    $associations_list[] = $association_details;
                }
            }
        }
        $user_info['associations_list'] = $associations_list;

        // Get user's companies list from user_companies table
        $user_companies = get_elements($userCompaniesTable, ['user_id' => $user_info['uid']], '*', "ORDER BY created_at DESC");
        
        $companies_list = [];
        if(!empty($user_companies)){
            foreach($user_companies as $company){
                // Get the company details
                $company_details = get_element($clientsTable, ['id' => $company['company_id']], 'id, company, company_details, type, tag_line, media, email, phone, address, address2, city, zip, state');
                if(!empty($company_details)){
                    $companies_list[] = $company_details;
                }
            }
        }
        
        // If company list is empty and user is a property_manager, get parent's companies
        if(empty($companies_list) && $user_info['role'] == 'property_manager'){
            $parent_user = get_element($usersTable, ['id' => $user_info['uid']], 'parent_user_id');
            
            if(!empty($parent_user['parent_user_id'])){
                $parent_companies = get_elements($userCompaniesTable, ['user_id' => $parent_user['parent_user_id']], '*', "ORDER BY created_at DESC");
            
                if(!empty($parent_companies)){
                    foreach($parent_companies as $company){
                        // Get the company details
                        $company_details = get_element($clientsTable, ['id' => $company['company_id']], 'id, company, company_details, type, tag_line, media, email, phone, address, address2, city, zip, state');
                        if(!empty($company_details)){
                            $companies_list[] = $company_details;
                        }
                    }
                }
            }
        }
        $user_info['companies_list'] = $companies_list;

        // user managr lists
        $user_managers = get_elements($usersTable, ['parent_user_id' => $user_info['uid'], 'role' => 'property_manager'], '*', "ORDER BY created_at DESC");
        $managers_list = [];
        if(!empty($user_managers)){
            foreach($user_managers as $manager){
                // Get the manager details
                $manager_details = get_element($usersTable, ['id' => $manager['id']], 'id, fn, ln, email, phone');
                if(!empty($manager_details)){
                    $managers_list[] = $manager_details;
                }
            }
        }
        $user_info['managers_list'] = $managers_list;

        return send_json_response(true, 200, 'Success', ['data'=> $user_info]);
        } catch (Exception $e) {
            return send_json_response(false, 500, 'An unexpected error occurred: ' . $e->getMessage());
        }
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
                        "password" => "<b>Password</b> invalid !",
                        "role" => "<b>Role</b> invalid !",
                        "self" => "Operation not allowed on this user",
                        "password_short" => "<b>Password</b> must be at least 6 characters !" ,
                        "not_found" => "User profile not found !"];

    private $tr = [     "admin" => "administrator",
                        "manager" => "manager"   ];
                        
    private $color = [  "admin" => "purple",
                        "manager" => "deep-orange"   ];

}