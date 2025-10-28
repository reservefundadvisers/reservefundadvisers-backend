<?php


class Clients
{  
	
    private $module_name = 'clients';
    private $type = 'client';
	
	function __construct()
	{

        

	}

	
	public function process($cmd, $data)
	{
        $response = "";

        list($command, $type) = explode('_', $cmd);
        
        $this->type = empty($type) ? 'client' : $type;


        switch($command){
            case 'load': $response = view($this->module_name, $this->type.'.php'); break;
            case 'get': $response = $this->list($data); break;
            case 'edit': $response = $this->edit($data); break;
            case 'save': $response = $this->save($data); break;     
            case 'delete': $response = $this->delete($data); break;  
            case 'set': $response = $this->set($data); break;            
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
        }else if($auth->clientType() == 'client'){
            $conds['id'] = $auth->clientId();
        }
        

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
        

        if($is_pagination)
            return ['last_page'=>$pages, 'data'=>$results, 'total'=>$total];
        else
            return send_json_response(true, 200, $this->success['success'], ['data' => $results]);
        
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
        global $auth, $clientsTable, $upload_dir_association;

        if(!empty($_FILES['profile_picture'])){
            $image_upload = handle_file_upload($_FILES['profile_picture'], $upload_dir_association);
            // Upload the file
            if ($image_upload['success'] === true) {
                $image_path = $image_upload['path'];
                $data['media'] = $image_path;
            }
        }

        $checkFor = ['phone', 'zip', 'city', 'state'];

        if($this->type == 'client')array_push($checkFor, 'association');

        $isNew = !is_valid($data, 'id');
    
        if($isNew){
            $checkFor = array_merge($checkFor, ['admin_fn', 'admin_ln', 'admin_email']);            
            
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

        if($isNew){

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
                return $ret;
            }

        }
        
        

        return send_json_response(true, 200, $this->success['saved'], ['client_id'=>$client_id]);
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


    private $errors = [
                        "not_allowed" => "Unauthorized operation",
                        "association" => "<b>Association Name</b> invalid !",
                        "wrong_type" => "<b>Type</b> invalid !",
                        "admin_fn" => "<b>Administrator First Name</b> invalid !",
                        "admin_ln" => "<b>Administrator Last Name</b> invalid !",
                        "admin_email" => "<b>Administrator Email</b> invalid !",
                        "phone" => "<b>Phone</b> invalid !",
                        "email" => "<b>Email</b> invalid !",
                        "zip" => "<b>Zip Code</b> invalid !",
                        "city" => "<b>City</b> invalid !",
                        "state" => "<b>State</b> invalid !",
                        ];
                        
    private $success = [
                        "saved" => "Client saved !",
                        "updated" => "Client updated !",
                        'success' => "Success !",
                        "deleted" => "Client deleted !",
                        ];

}