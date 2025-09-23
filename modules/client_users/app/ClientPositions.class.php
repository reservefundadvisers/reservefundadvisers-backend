<?php

class ClientPositions
{  
	
    private $module_name = 'client_users';
	
	function __construct()
	{
	

	}

	
	public function process($cmd, $data)
	{
        $response = "";

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
        global $auth, $clientPositionsTable;


        $conds = ['raw'=>'(client_id IS NULL OR client_id = :client_id)', '*client_id'=>$auth->clientId()];

        if(is_valid($data, 'id')){
            if(!belongs_to_client($clientPositionsTable, $data['id'], true))return ['error'=>'not_allowed'];
            $conds['id'] = $data['id'];

            return get_elements($clientPositionsTable, $conds, "*", "ORDER BY row ASC");

        }else{

            return get_elements($clientPositionsTable, $conds, "id, value, IF(client_id IS NULL, 0, 1) AS can_edit", "ORDER BY row ASC");
        
        }
        
        
    }


    public function edit($data){
        global $clientsTable;

        $element = [];
        if(is_valid($data, 'id'))
            $element = get_element($clientsTable, ['id' => $data['id']]);


        view($this->module_name, 'client.edit.php', $element);

    }

    public function save($data){
        global $auth, $clientPositionsTable;

        $checkFor = ['value'];

        if(is_valid($data, 'id') && !belongs_to_client($clientPositionsTable, $data['id']))
                return ['error'=>'not_allowed'];
    
        $ret = check_missing($checkFor, $data, $this->errors); if($ret !== true)return $ret;

        $data['client_id'] = $auth->clientId();
        $data['value'] = trim($data['value']);


        if(exists($clientPositionsTable, ['value' => trim($data['value']), 'client_id' => $data['client_id']]))
            return ['error'=>$this->errors['value_exists']];
        
        $ret_id = save_element($clientPositionsTable, $data);

        if($ret_id === false)
            return ['error' => ''];    
        else
            return ['success' => $ret_id];
    }


    
        
    
    public function delete($data){
        global $auth, $clientPositionsTable;

        
        $res = array();

        if(!is_valid($data, 'id'))
            return ['error' => ''];

            
        $trans_started = start_transaction();

        $ids = [];
        foreach($data['id'] as $id){
            if(!belongs_to_client($clientPositionsTable, $id, true) )continue;

            array_push($ids, $id);
        }                        

        // and delete only for this client_id
        $res = delete_elements_by_id($clientPositionsTable, $ids);
    
        if($trans_started)end_transaction();

        
        $count = count($data['id']) - count($res);
        

        if(!empty($res))return ['error' => $res];
        else return ['success' => ''];

    }
    
    
    
    public function set($data){
        global $auth, $clientsTable;

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }

        return set_property($clientsTable, $data);

    }


    private $errors = [ 
                        "not_allowed" => "Unauthorized Access",
                        "value" => "<b>Position</b> invalid !",
                        "value_exists" => "This <b>Position</b> exists already !"
                        ];

}