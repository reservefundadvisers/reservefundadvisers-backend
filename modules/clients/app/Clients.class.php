<?php


class Clients
{  
	
    private $module_name = 'clients';
    private $type = 'client';
	
	function __construct()
	{

        

	}

	
	/**
	 * Process the command and return the response.
	 *
	 * @param string $cmd The command to process.
	 * @param array $data The data associated with the command.
	 * @return mixed The response from the command.
	 */
	public function process($cmd, $data)
	{
        $response = "";

        list($command, $type) = explode('_', $cmd);
        
        $this->type = empty($type) ? 'client' : $type;


        switch($command){
            case 'create': $response = $this->save_company_by_name($data); break;
            case 'load': $response = view($this->module_name, $this->type.'.php'); break;
            case 'get': $response = $this->list($data); break;
            case 'getassociationlist': $response = $this->get_association_by_company_id($data); break;
            case 'edit': $response = $this->edit($data); break;
            case 'save': $response = $this->save($data); break;     
            case 'delete': $response = $this->delete($data); break;  
            case 'set': $response = $this->set($data); break;         
            case 'assign': $response = $this->assignAssociationToUser($data); break;   
        }

        return $response;

	}


    public function list($data){
        global $auth, $clientsTable, $clientPositionsTable, $usersTable;



        $filters = array();
        if(is_valid($data, 'filters'))
        $filters = format_filters(  $data['filters'], 
                                    ['users'=>["value"=>'%?%', "query"=> "($usersTable.username LIKE :? OR CONCAT($usersTable.fn, ' ', $usersTable.ln) LIKE :? OR $usersTable.email LIKE :? OR $usersTable.phone LIKE :?)"] ]);
                   

        

        // check if an id is given
        $conds = format_conds($data, "( $clientsTable.association LIKE :search OR 
                                        $clientsTable.email LIKE :search OR $clientsTable.phone LIKE :search OR 
                                        $clientsTable.address LIKE :search OR $clientsTable.city LIKE :search OR $clientsTable.zip LIKE :search OR $clientsTable.state LIKE :search OR
                                        
                                        $usersTable.username LIKE :search OR CONCAT($usersTable.fn, ' ', $usersTable.ln) LIKE :search OR $usersTable.email LIKE :search OR $usersTable.phone LIKE :search)");

        
        append_filter($conds, $filters);

        // client type
        $conds['type'] = $this->type;

        // if company
        if($auth->clientType() == 'company'){
            $conds['company_id'] = $auth->clientId();
        }
        // else if($auth->clientType() == 'client'){
        //     $conds['id'] = $auth->clientId();
        // }
        

        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);



        if($is_pagination){
            
            $total = get_last_found_rows();
            $pages = ceil($total/check_val($pagination, 'size', 1));
            
        }


        $results = get_elements_join($clientsTable, $conds, 
                                     "LEFT JOIN $usersTable ON $usersTable.client_id = $clientsTable.id AND $usersTable.client_id IS NOT NULL
                                      LEFT JOIN $clientsTable company_t ON company_t.id = $clientsTable.company_id",
                                     "$clientsTable.*, IFNULL(company_t.company, $clientsTable.company) AS company",
                                     "GROUP BY $clientsTable.id ORDER BY row_id DESC ".check_val($pagination, 'query'));
        
        // decode association_style for each record
        if (is_array($results) && !empty($results)) {
            foreach ($results as $key => $row) {
                if (!empty($row['association_style'])) {
                    $decoded = json_decode($row['association_style'], true);
                    $results[$key]['association_style'] = $decoded;
                } else {
                    $results[$key]['association_style'] = []; // fallback
                }
            }
        }

        if($is_pagination)
            return ['last_page'=>$pages, 'data'=>$results, 'total'=>$total];
        else
            return send_json_response(true, 200, $this->success['success'], ['data' => $results]);
        
    }

    public function get_association_by_company_id($data){
        global $auth, $clientsTable;

        if(!is_valid($data, 'company_id') && !is_valid($data, 'client_id')){
            return send_json_response(false, 400, 'company_id or client_id is required !');
        }

        if(is_valid($data, 'company_id')){
            $conds['company_id'] = $data['company_id'];
            $clients_by_company_id = get_elements($clientsTable, $conds, "*", "ORDER BY row_id ASC");

            return send_json_response(true, 200, '', ['companies' => $clients_by_company_id]);

        }
    }

    public function edit($data){
        global $auth, $clientsTable;

        $element = [];
        if(is_valid($data, 'id')){
            
            // allow management company when id is specified
            if($auth->checkRoleType('company') && !exists($clientsTable, ['id'=>$data['id'], 'company_id'=>$auth->clientId()]))
                return ['error' => $this->errors['not_allowed'] ]; 

            $element = get_element($clientsTable, ['id' => $data['id']]);

            if($element['type'] != $this->type)
                return ['error'=>$this->errors['wrong_type']];
        }


        view($this->module_name, $this->type.'.edit.php', $element);

    }

    public function save($data){
        global $auth, $clientsTable, $upload_dir_association, $userAssociationsTable, $userCompaniesTable, $userRoleAssignmentsTable, $usersTable;

        if(!empty($_FILES['profile_picture'])){
            $image_upload = handle_file_upload($_FILES['profile_picture'], $upload_dir_association);
            // Upload the file
            if ($image_upload['success'] === true) {
                $image_path = $image_upload['path'];
                $data['media'] = $image_path;
            }
        }

        $checkFor = [];

        if($this->type == 'client')array_push($checkFor, 'association');

        $isNew = !is_valid($data, 'id');

        if($isNew){
            // Admin details are no longer required

            // add client id
            if(empty($data['company_id'])){
                $data['company_id'] = $auth->checkRoleType('company') ? $auth->clientId() : NULL;
            }

        }else{
            
            // allow management company when id is specified
            if($auth->checkRoleType('company') && !exists($clientsTable, ['id'=>$data['id'], 'company_id'=>$auth->clientId()]))
                return send_json_response(false, 403, $this->errors['not_allowed']); 
        }

        $ret = check_missing($checkFor, $data, $this->errors); if($ret !== true)return send_json_response(false, 400, $ret['error']);

        // client type and company type
        if($isNew){
            $data['type'] = $this->type;
            $data['created_by_user_id'] = $auth->uid();
            if($this->type == 'client')$data['company_type'] = NULL;
        }else{
            if(isset($data['type']))unset($data['type']);
        }

        if(empty($data['company_id']))$data['company_id'] = NULL;
        else $data['company'] = NULL;

        // check admin email 
        $clientUsers = new ClientUsers();
        
        $client_id = save_element($clientsTable, $data);

        if($client_id === false)
            return send_json_response(false, 500, $this->errors['not_allowed']);

        // Add record to user_associations table for tracking
            if($isNew){
                if($this->type == 'client'){

                    $association_data = [
                        'id' => generate_id(),
                        'user_id' => $auth->uid(),
                        'client_id' => $client_id
                    ];

                    $created_association = save_element($userAssociationsTable, $association_data);
                }else if($this->type == 'company'){
                    // For company type, link user to company
                    $company_data = [
                        'id' => generate_id(),
                        'user_id' => $auth->uid(),
                        'company_id' => $client_id
                    ];

                    $created_company = save_element($userCompaniesTable, $company_data);
                }
                // Update the logged-in user's client_id in the users table
                $update_result = update_element($usersTable, ['client_id' => $client_id], ['id' => $auth->uid()]);
            }   


            if($data['invited_manager_id'] != NULL){

                $userIdValidate = get_element($usersTable, ['id' => $data['invited_manager_id']]);
                if(empty($userIdValidate))
                    return send_json_response(false, 400, 'Invited Manager ID is invalid !');

                $role_assignment_data = [
                    'id' => generate_id(),
                    'user_id' => $data['invited_manager_id'],
                    'scope_id' => $client_id,
                    'role' => $data['role']
                ];

                $created_role_assignment = save_element($userRoleAssignmentsTable, $role_assignment_data);
            }



        if($isNew && isset($data['admin_fn']) && isset($data['admin_ln']) && isset($data['admin_email'])){

            $admin_user = [ 'fn'=>$data['admin_fn'],
                            'ln'=>$data['admin_ln'],
                            'email'=>$data['admin_email'],
                            'password'=>check_val($data, 'admin_password'),
                            'role'=>$this->type . '_admin',
                            'position_id'=>'US7EIBCL7II9FK8YNZESG2O031',
                            'client_id'=> $client_id];

            $ret = $clientUsers->save($admin_user);
            if(isset($ret['error'])){
                delete_elements_by_id($clientsTable, ['id'=>$client_id]);
                return send_json_response(false, 400, $ret['error']);
            }

        }
        
        

        return send_json_response(true, 200, $this->success['saved'], ['id'=>$client_id, 'company_id'=>$data['company_id']]);
    }


    
        
    
    public function delete($data){
        global $auth, $clientsTable, $usersTable;


        
        $res = array();

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }
            
        $trans_started = start_transaction();

        $errors = array();

        foreach($data['id'] as $id){

            // allow management company when id is specified
            if($auth->checkRoleType('company') && !exists($clientsTable, ['id'=>$id, 'company_id'=>$auth->clientId()])){
                array_push($errors, $id);
                continue;
            }

            // and delete only for this client_id
            $res = delete_elements_by_id($clientsTable, $id);
        }
    
        if($trans_started)end_transaction();

        
        $count = count($data['id']) - count($res);
        

        if(!empty($res))return ['error' => $res];
        else return send_json_response(true, 200, $this->success['deleted'], ['count'=>$count]);

    }
    
    
    
    public function set($data){
        global $auth, $clientsTable;

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }

        
        // allow management company when id is specified
        if($auth->checkRoleType('company') && !exists($clientsTable, ['id'=>$data['id'], 'company_id'=>$auth->clientId()]))
            return ['error' => $this->errors['not_allowed'] ]; 


        return set_property($clientsTable, $data);

    }

    public function save_company_by_name($data){
        global $clientsTable, $upload_dir_company, $auth, $usersTable, $userCompaniesTable;;

        if(!is_valid($data, 'company_name')){
            return send_json_response(false, 400, 'Company Name is required !');
        }
        
        if(!empty($_FILES['media'])){
            $image_upload = handle_file_upload($_FILES['media'], $upload_dir_company);
            // Upload the file
            if ($image_upload['success'] === true) {
                $image_path = $image_upload['path'];
                $data['media'] = $image_path;
            }
        }

        $data['type'] = 'company';
        $data['company'] = $data['company_name'];

        // save
        $company_id = save_element($clientsTable, $data);

        if($company_id === false){
            return send_json_response(false, 400, $this->errors['internal_error']);
        }

        // Add record to user_companies table for tracking
        $company_data = [
            'user_id' => $auth->uid(),
            'company_id' => $company_id
        ];
        save_element($userCompaniesTable, $company_data);

        update_element(
            $usersTable,
            ['client_id' => $company_id],
            ['id' => $auth->uid()]
        );

        return send_json_response(true, 200, $this->success['success'], ['company_id'=>$company_id]);
    }

    public function assignAssociationToUser($data){
        global $userRoleAssignmentsTable, $clientsTable, $usersTable, $auth;

        if(!is_valid($data, 'association_id') || !is_valid($data, 'user_id') || !is_valid($data, 'role')){
            return send_json_response(false, 400, 'association, user_id and role are required !');
        }

        // check if client exists
        if(!exists($clientsTable, ['id' => $data['association_id']]))
            return send_json_response(false, 400, 'Association not found !');

        // check if user exists
        if(!exists($usersTable, ['id' => $data['user_id']]))
            return send_json_response(false, 400, 'User not found !');

        if($data['role'] != 'client_admin' && $data['role'] != 'manager' && $data['role'] != 'property_manager')
            return send_json_response(false, 400, 'Invalid role !');

        $role_assignment_data = [
            'id' => generate_id(),
            'user_id' => $data['user_id'],
            'scope_id' => $data['association_id'],
            'role' => $data['role']
        ];

        $res = save_element($userRoleAssignmentsTable, $role_assignment_data);
        if($res === false)
            return send_json_response(false, 500, 'Failed to assign association to user !');

        return send_json_response(true, 200, 'Association assigned to user !');
    }


    private $errors = [
                        "not_allowed" => "Unauthorized operation",
                        "association" => "Association Name invalid !",
                        "wrong_type" => "Type invalid !",
                        "admin_fn" => "Administrator First Name invalid !",
                        "admin_ln" => "Administrator Last Name invalid !",
                        "admin_email" => "Administrator Email invalid !",
                        "phone" => "Phone invalid !",
                        "email" => "Email invalid !",
                        "zip" => "Zip Code invalid !",
                        "city" => "City invalid !",
                        "state" => "State invalid !",
                        ];
                        
    private $success = [
                        "saved" => "Client saved !",
                        "updated" => "Client updated !",
                        'success' => "Success !",
                        "deleted" => "Client deleted !",
                        ];

}