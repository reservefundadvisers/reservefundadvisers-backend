<?php

class Models
{  
	
    private $module_name = 'models';
	
	function __construct()
	{
	

	}

	
	public function process($cmd, $data)
	{
        $response = "";

        switch($cmd){
            case 'load': $response = view($this->module_name, 'models.php'); break;
            case 'get': $response = $this->list($data); break;
            case 'edit': $response = $this->edit($data); break;
            case 'save': $response = $this->save($data); break;     
            case 'delete': $response = $this->delete($data); break;  
            case 'set': $response = $this->set($data); break;   
            case 'reset_simulations': $response = $this->reset_simulations($data); break;          
            
            case 'edit_inv': $response = $this->editInv($data); break;
            case 'save_inv': $response = $this->saveInv($data); break;       
        }

        return $response;

	}


    public function list($data){
        global $auth, $modelsTable, $clientsTable, $simDeficitTable;

        $filters = array();
        if(is_valid($data, 'filters'))
        $filters = format_filters(  $data['filters'], 
                                    ['association'=>["value"=>'%?%', "query"=> "( $clientsTable.association LIKE :? )"] ]);
                   


        // check if an id is given
        $conds = format_conds($data, "( $modelsTable.name LIKE :search OR $clientsTable.association LIKE :search )");
        

        append_filter($conds, $filters);

        if($auth->checkRoleType('client')){
            $conds['client_id'] = $auth->clientId();
        }else if($auth->checkRoleType('company')){
            $conds["$clientsTable.company_id"] = $auth->clientId();
        }


        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);
        

        if($is_pagination){
            
            $total = get_last_found_rows();
            $pages = ceil($total/check_val($pagination, 'size', 1));
            
        }


        $results = get_elements_join($modelsTable, $conds,
                                        "LEFT JOIN $clientsTable ON $clientsTable.id = $modelsTable.client_id
                                         LEFT JOIN $simDeficitTable ON $simDeficitTable.model_id = $modelsTable.id",
                                        "$modelsTable.*, IF(COUNT($modelsTable.id) - 1 > 0, 1, 0) AS has_simulation, CASE WHEN $clientsTable.association IS NOT NULL THEN $clientsTable.association ELSE 'unspecified' END AS association ", " GROUP BY $modelsTable.id ORDER BY $modelsTable.row DESC".check_val($pagination, 'query'));

        
        if($is_pagination)
            return ['last_page'=>$pages, 'data'=>$results, 'total'=>$total];
        else
            return $results;
        
    }


    public function edit($data){
        global $modelsTable, $simDeficitTable, $simDeficitLTIMTable, $simSplitsTable, $simSplitsLTIMTable;

        $element = [];
        if(is_valid($data, 'id')){
            if(!belongs_to_client($modelsTable, $data['id'], false, true))return ['error' => $this->errors['not_allowed'] ]; 
            
            // check if model is being used in simulation
            if( exists($simDeficitTable, ['model_id'=>$data['id']]) || 
                exists($simDeficitLTIMTable, ['model_id'=>$data['id']]) || 
                exists($simSplitsTable, ['model_id'=>$data['id']]) || 
                exists($simSplitsLTIMTable, ['model_id'=>$data['id']]))

                return ['modal' => 'has_simulation'];

            
            $element = get_element($modelsTable, ['id' => $data['id']]);
        }



        view($this->module_name, 'model.edit.php', $element);

    }

    public function save($data){
        global $auth, $modelsTable;

        $checkFor = ['client_id', 'housing', 'starting_amount', 'monthly_fees'];
        
        if(is_valid($data, 'id'))
            if(!belongs_to_client($modelsTable, $data['id'], false, true))return ['error' => $this->errors['not_allowed'] ]; 

        if($auth->checkRoleType('client')){
            $data['client_id'] = $auth->clientId();
        }


        $ret = check_missing($checkFor, $data, $this->errors); if($ret !== true)return $ret;


        $data['fiscal_year'] = check_val($data, 'fiscal_year', date('Y', time()));

        $ret_id = save_element($modelsTable, $data);

        if($ret_id === false)
            return ['error' => ''];    
        else
            return ['success' => $ret_id];
    }


    


    public function reset_simulations($data){
        global $modelsTable, $simDeficitTable, $simDeficitLTIMTable, $simSplitsTable, $simRulesTable, $simActualTable, $simVersionTable;

        if(!is_valid($data, 'id')){
            return ['error' => $this->errors['model_id']];
        }


        if(!belongs_to_client($modelsTable, $data['id'], false, true))return ['error' => $this->errors['not_allowed'] ]; 

        // log_info("reset_simulations", $data);
            

        delete_elements_by_cond($simDeficitTable, 'model_id = :model_id', ['model_id' => $data['id']]);
        delete_elements_by_cond($simDeficitLTIMTable, 'model_id = :model_id', ['model_id' => $data['id']]);
        delete_elements_by_cond($simSplitsTable, 'model_id = :model_id', ['model_id' => $data['id']]);
        delete_elements_by_cond($simRulesTable, 'model_id = :model_id', ['model_id' => $data['id']]);
        delete_elements_by_cond($simActualTable, 'model_id = :model_id', ['model_id' => $data['id']]);
        delete_elements_by_cond($simVersionTable, 'model_id = :model_id', ['model_id' => $data['id']]);



        return ['success' => ''];

    }
        
    
    
    public function delete($data){
        global $auth, $modelsTable;


        
        $res = array();

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }

        foreach($data['id'] as $id){
            if(!belongs_to_client($modelsTable, $id, false, true))
                return ['error' => $this->errors['not_allowed'] ];
        }

        
            
        $trans_started = start_transaction();

                                
        // and delete only for this company_id
        $res = delete_elements_by_id($modelsTable, $data['id']);
    
        if($trans_started)end_transaction();

        
        $count = count($data['id']) - count($res);
        

        if(!empty($res))return ['error' => $res];
        else return ['success' => ''];

    }
    
    
    
    public function set($data){
        global $auth, $modelsTable;

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }

        if(!belongs_to_client($modelsTable, $data['id'], false, true))return ['error' => $this->errors['not_allowed'] ];

        foreach($data as $k => $v){
            if(is_array($v))
                $data[$k] = json_encode($v);
        }

        return set_property($modelsTable, $data);

    }

    

    public function editInv($data){
        global $modelsTable;

        if(!is_valid($data, 'model_id')){
            return ['error' => $this->errors['model_id']];
        }
           
        if(!belongs_to_client($modelsTable, $data['model_id'], false, true))return ['error' => $this->errors['not_allowed'] ]; 

        $element = get_element($modelsTable, ['id' => $data['model_id']]);
        if(empty($element))return ['error' => $this->errors['missing'] ]; 


        view($this->module_name, 'model_inv.edit.php', [    'model_id'=>$element['id'], 
                                                            'fiscal_year'=>$element['fiscal_year'], 
                                                            'available_inv_strategies'=>get_investment_strategies(),  
                                                            'inv_strategy'=>parse_json($element['inv_strategy'], array()) ]);

    }

    public function saveInv($data){
        global $auth, $modelsTable;

        $checkFor = ['model_id'];
    
        $ret = check_missing($checkFor, $data, $this->errors); if($ret !== true)return $ret;

        
        $element = ['id'=>$data['model_id'], 'inv_strategy'=>json_encode(check_val($data, 'inv_strategy', NULL))];
        
        $ret_id = save_element($modelsTable, $element);

        if($ret_id === false)
            return ['error' => ''];    
        else
            return ['success' => ''];
    }


    private $errors = [ "client_id" => "Please choose an <b>Association</b> !",
                        "not_allowed" => "Unauthorized Access", 
                        "model_id" => "Please choose a <b>Model</b> !",
                        "missing" => "This <b>Model</b> doesn't exist !",
                        "used" => "This <b>Model</b> cannot be edited because it is used in the <b>Simulation</b> !",
                        "housing" => "<b>Housing Units</b> invalid !",
                        "starting_amount" => "<b>starting_amount</b> invalid !",
                        "monthly_fees" => "<b>Monthly Fees</b> invalid !"];


}