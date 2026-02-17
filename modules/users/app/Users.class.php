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
            case 'permissions': $response = $this->get_role_permissions(); break;
            case 'scope': $response = $this->get_scopes($data); break;
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
        global $auth, $usersTable, $usersTable, $modelsTable, $clientPositionsTable, $userAssociationsTable, $userCompaniesTable, $clientsTable, $userRoleAssignmentsTable;

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
        
        // Parse last_activity_data if it exists
        if(!empty($user_data['last_activity_data'])){
            $user_data['last_activity_data'] = parse_json($user_data['last_activity_data'], null);
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

        // Get user's associations list from user_associations table (include child users)
        $user_ids = [$user_info['uid']];
        $child_users = get_elements($usersTable, ['parent_user_id' => $user_info['uid']], 'id');
        if(!empty($child_users)){
            foreach($child_users as $child_user){
                if(!empty($child_user['id'])){
                    $user_ids[] = $child_user['id'];
                }
            }
        }

        $user_associations = get_elements($userAssociationsTable, [',user_id' => $user_ids], '*', "ORDER BY created_at DESC");
        $associations_list = [];
        if(!empty($user_associations)){
            foreach($user_associations as $association){
                // Get the association/client details
                $association_details = get_element($clientsTable, ['id' => $association['client_id']], 'id, association, company, type, email, media, phone, address, address2, city, zip, state, created_by_user_id');
                if(!empty($association_details)){
                    if(!empty($association_details['created_by_user_id'])){
                        $created_by = get_element($usersTable, ['id' => $association_details['created_by_user_id']], 'id, fn, ln, email');
                        if(!empty($created_by)){
                            $association_details['created_by'] = $created_by;
                        }
                    }
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

        // Recursive function to get all descendants (children and their children)
        function getAllDescendants($usersTable, $parentId) {
            $descendants = [];
            $children = get_elements($usersTable, ['parent_user_id' => $parentId], 'id, parent_user_id, fn, ln, email, phone, role, created_at, parent_user_id');
            
            if (!empty($children)) {
                foreach ($children as $child) {
                    $descendants[] = $child;
                    // Recursively get children of this child
                    $childDescendants = getAllDescendants($usersTable, $child['id']);
                    if (!empty($childDescendants)) {
                        $descendants = array_merge($descendants, $childDescendants);
                    }
                }
            }
            
            return $descendants;
        }

        // Get all descendants (children, grandchildren, etc.)
        $all_descendants = getAllDescendants($usersTable, $user_info['uid']);
        
        $managers_list = [];
        if (!empty($all_descendants)) {
            foreach ($all_descendants as $manager) {
                // Get the manager details with created_by fields
                $manager_details = get_element($usersTable, ['id' => $manager['id']], 'id, fn, ln, email, phone, role, created_at, parent_user_id');
                if (!empty($manager_details)) {
                    // Add created_by details
                    if (!empty($manager_details['parent_user_id'])) {
                        $created_by = get_element($usersTable, ['id' => $manager_details['parent_user_id']], 'id, fn, ln, email');
                        if (!empty($created_by)) {
                            $manager_details['created_by'] = $created_by;
                        }
                    }
                    $managers_list[] = $manager_details;
                }
            }
        }
        $user_info['users_list'] = $managers_list;

        $user_scrope = get_element($userRoleAssignmentsTable, ['user_id' => $user_info['uid']], 'id,scope_id, role');
        // User Scope
        $user_info['my_scope'] = [];
        if(!empty($user_scrope)){
            $user_info['my_scope'] = [
                'id' => $user_scrope['id'],
                'scope_id' => $user_scrope['scope_id'],
                'role' => $user_scrope['role']
            ];
        }

        return send_json_response(true, 200, 'Success', ['data'=> $user_info]);
        } catch (Exception $e) {
            return send_json_response(false, 500, 'An unexpected error occurred: ' . $e->getMessage());
        }
    }

    public function get_scopes($data){
        global $userRoleAssignmentsTable, $auth, $usersTable, $userAssociationsTable, $userCompaniesTable, $clientsTable;

        $user_provided_scope_id = check_val($data, 'id') ? $data['id'] : null;
        if(empty($user_provided_scope_id)){
            return send_json_response(false, 400, 'ID is required');
        }

        $scope_data = get_element($userRoleAssignmentsTable, ['id' => $user_provided_scope_id], '*');
        if(empty($scope_data)){
            return send_json_response(false, 404, 'Scope not found');
        }

        //Get Client from scope
        $client_data = get_element($clientsTable, ['id' => $scope_data['scope_id']], '*');
        if(empty($client_data)){
            return send_json_response(false, 404, 'Client not found for the scope');
        }
        $final_data= [];

        if($scope_data['role'] == 'property_manager' && $client_data['type'] == 'company'){
            $user_select = format_select($usersTable, "*", ['row_id', 'password']);

            $get_users_from_links = function($link_table, $link_conds) use ($usersTable, $user_select) {
                $links = get_elements($link_table, $link_conds, 'user_id');
                $user_ids = [];
                if(!empty($links)){
                    foreach($links as $link){
                        if(!empty($link['user_id']))$user_ids[] = $link['user_id'];
                    }
                }

                $user_ids = array_values(array_unique($user_ids));
                if(empty($user_ids))return [];

                return get_elements($usersTable, [',id' => $user_ids, ';role' => ['manager', 'admin']], $user_select);
            };

            $companies_list = get_elements($clientsTable, ['id' => $client_data['id'], 'type' => 'company'], '*');
            $final_companies = [];

            if(!empty($companies_list)){
                foreach($companies_list as $company){
                    $company_id = $company['id'];

                    $company['users'] = $get_users_from_links($userCompaniesTable, ['company_id' => $company_id]);

                    $associations = get_elements($clientsTable, ['company_id' => $company_id, 'type' => 'client'], '*');
                    $association_list = [];

                    if(!empty($associations)){
                        foreach($associations as $association){
                            $association_id = $association['id'];
                            $association['users'] = $get_users_from_links($userAssociationsTable, ['client_id' => $association_id]);
                            $association_list[] = $association;
                        }
                    }

                    $company['associations'] = $association_list;
                    $final_companies[] = $company;
                }
            }

            $final_data['companies_list'] = $final_companies;
        }else if($scope_data['role'] == 'client_admin'){
           
        }else if($scope_data['role'] == 'client_user'){
            $final_data['property_manager'] = true;
        }

        $scopes = [];
        $scopes[] = [
            'id' => $scope_data['id'],
            'scope_id' => $scope_data['scope_id'],
            'role' => $scope_data['role'],
            'data' => $final_data
        ];

        return $scopes;
    }

    public function get_role_permissions(){
        global $auth, $permissionsTable;

        // take user id from session
        $user_id = $auth->uid();
        if(empty($user_id))return false;

        // get user data
        $user_data = get_element('users', ['id' => $user_id], 'role');
        if(empty($user_data))return false;

        // get permissions for the role
        if(empty($user_data['role']))return false;

        $permissions = [
            'company' => [],
            'association' => [],
            'manager' => []
        ];

        if($user_data['role'] == 'admin'){
            $permissions['company'] = ['view', 'edit', 'delete', 'create'];
            $permissions['association'] = ['view', 'edit', 'delete', 'create'];
            $permissions['manager'] = ['view', 'edit', 'delete', 'create'];
        } else if($user_data['role'] == 'manager'){
            $permissions['company'] = ['view', 'edit', 'create'];
            $permissions['association'] = ['view', 'edit', 'create'];
            $permissions['manager'] = ['view'];
        } else if($user_data['role'] == 'client_admin'){
            $permissions['company'] = ['view'];
            $permissions['association'] = ['view'];
            $permissions['manager'] = ['view'];
        } else if($user_data['role'] == 'client_user'){
            $permissions['company'] = ['view'];
            $permissions['association'] = ['view'];
            $permissions['manager'] = ['view'];
        }else if($user_data['role'] == 'company_admin'){
            $permissions['company'] = ['view'];
            $permissions['association'] = ['view'];
            $permissions['manager'] = ['view'];
        }else if($user_data['role'] == 'company_user'){
            $permissions['company'] = ['view'];
            $permissions['company'] = ['view'];
            $permissions['association'] = ['view'];
            $permissions['manager'] = ['view'];
        }

        return $permissions;
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