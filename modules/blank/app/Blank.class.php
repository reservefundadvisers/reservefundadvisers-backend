<?php

class Simulation
{  
	
    private $module_name = 'simulation';
	
	function __construct()
	{
	

	}

	
	public function process($cmd, $data)
	{
        $response = "";

        switch($cmd){
            case 'get': $response = $this->list($data); break;
            case 'get_association': $response = $this->list_association($data); break;
            case 'get_model': $response = $this->list_model($data); break;
            
            case 'set': $response = $this->set($data); break;            
        }

        return $response;

	}


    

    public function list($data){
        global $auth, $modelItemsTable, $modelsTable;
        
        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];

        if(!belongs_to_client($modelsTable, $modelsTable$data['model_id']))return ['error'=>'not_allowed'];
        
        $model = get_element($modelsTable, ['id'=>$data['model_id']]);
        $model_items = get_elements($modelItemsTable, ['model_id'=>$data['model_id']]);

        $results = ["model"=>$model, "items"=>$model_items];

        return $results;
        
    }

    public function list_association($data){
        global $auth, $clientsTable;


        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);

        $conds = array();

        if($is_pagination){
            $count = get_element(  $clientsTable, $conds, "COUNT($clientsTable.id) AS count");            
            if(isset($count['count']))$total = (int)(intval($count['count'])/$pagination['size'])+1;
        }

        $results = get_elements($clientsTable, $conds,
                                        "$clientsTable.id, $clientsTable.association", "ORDER BY $clientsTable.association ASC".check_val($pagination, 'query'));

        
        if($is_pagination)
            return ['last_page'=>$total, 'data'=>$results, 'total'=>$count['count']];
        else
            return $results;
        
    }


    public function list_model($data){
        global $auth, $modelsTable;


        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);

        $conds = array();
        $conds['client_id'] = check_val($data, 'client_id', $auth->clientId());

        if($is_pagination){
            $count = get_element(  $modelsTable, $conds, "COUNT($modelsTable.id) AS count");            
            if(isset($count['count']))$total = (int)(intval($count['count'])/$pagination['size'])+1;
        }

        $results = get_elements($modelsTable, $conds,
                                        "$modelsTable.id, $modelsTable.name", "ORDER BY $modelsTable.name ASC".check_val($pagination, 'query'));

        
        if($is_pagination)
            return ['last_page'=>$total, 'data'=>$results, 'total'=>$count['count']];
        else
            return $results;
        
    }

    
    
    public function set($data){
        global $auth, $modelsTable;

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }


        return set_property($modelsTable, $data);

    }

    


    private $errors = [ "client_id" => "Please choose an <b>Association</b> !",
                        "model_id" => "Please choose a <b>Model</b> !",
                        "not_allowed" => "Unauthorized Access",
                        "missing" => "This <b>Model</b> doesn't exist !"];


}