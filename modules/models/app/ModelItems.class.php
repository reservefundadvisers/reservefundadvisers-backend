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
            case 'get': $response = $this->list($data); break;
            case 'edit': $response = $this->edit($data); break;
            case 'save': $response = $this->save($data); break;     
            case 'delete': $response = $this->delete($data); break;  
            case 'set': $response = $this->set($data); break;    

            case 'edit_actual': $response = $this->edit_actual($data); break; 
            case 'save_actual': $response = $this->save_actual($data); break;  
            case 'reset_actual': $response = $this->reset_actual($data); break;           
        }

        return $response;

	}


    public function list($data){
        global $auth, $modelItemsTable, $modelsTable;
        
        if(!is_valid($data, 'model_id')) {
            return send_json_response(false, 400, $this->errors['model_id']);
        }

        if(!exists($modelsTable, ['id'=>$data['model_id']])){
            return send_json_response(false, 400, $this->errors['missing']);
        }

        if(!belongs_to_client($modelsTable, $data['model_id'], false, true)){ 
            return send_json_response(false, 400, $this->errors['not_allowed']);
        }
        
        $results = get_elements($modelItemsTable, ['model_id'=>$data['model_id']]);

        return send_json_response(true, 200, 'Success', ['data' => $results ]);
        
    }


    public function edit($data){
        global $modelItemsTable, $modelsTable, $clientsTable, $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable, $simActualTable;

        if(!is_valid($data, 'model_id')){ 
            return ['error'=>$this->errors['model_id']];
        }
        if(!exists($modelsTable, ['id'=>$data['model_id']])) { 
            return ['error'=>$this->errors['missing']];
        }
        
        if(!belongs_to_client($modelsTable, $data['model_id'], false, true)){ 
            return ['error'=>$this->errors['not_allowed']];
        }

        
        
        $element['items'] = get_elements($modelItemsTable, ['model_id'=>$data['model_id']]);
        $element['model'] = get_element_join($modelsTable, ['id'=>$data['model_id']], 
                                             "LEFT JOIN $clientsTable ON $clientsTable.id = $modelsTable.client_id",
                                             format_select($modelsTable, "*") . ", $clientsTable.association");
        
        // check if model is being used in simulation
        // return modal to enter actual cost
        if( check_val($data, 'force', 0) == 0){

            if( exists($simDeficitTable, ['model_id'=>$data['model_id']]) ||
                exists($simSplitsTable, ['model_id'=>$data['model_id']]) ||
                exists($simDeficitLTIMTable, ['model_id'=>$data['model_id']]) ||
                exists($simSplitsLTIMTable, ['model_id'=>$data['model_id']]) ) {

                    return ['modal' => 'used'];
                    // return ['error' => $this->errors['used']];

            }else if(exists($simActualTable, ['model_id'=>$data['model_id']])){
                
                return ['modal' => 'has_actual'];

            }else if(count($element['items']) > 0){
                return ['modal' => 'update'];
            }
            
        }
                
        

        /*
        foreach($element['items'] as &$item){
            $splits = get_element($simSplitsTable, [ 'parent_id' => $item['id'] ], "COUNT(id) AS count");
            
            $item['has_splits'] = $splits['count'] > 0;
            
        }
        */

        view($this->module_name, 'model_items.edit.php', $element);

    }

    
    
    public function save($data){
        global $auth, $modelItemsTable, $modelsTable, $simActualTable, $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable;

        if(!is_valid($data, 'model_id')) {
            // return ['error'=>$this->errors['model_id']];
            return send_json_response(false, 400, $this->errors['model_id']);
        } 
        if(!exists($modelsTable, ['id'=>$data['model_id']])) {
            // return ['error'=>$this->errors['missing']];
            return send_json_response(false, 400, $this->errors['missing']);
        } 

        // if(!belongs_to_client($modelsTable, $data['model_id'], false, true)) {
        //     // return ['error'=>$this->errors['not_allowed']];
        //     rfa_create_log("Models::save - Unauthorized access attempt by user " . $auth->uid() . " for model_id " . $data['model_id']);
        //     return send_json_response(false, 400, $this->errors['not_allowed']);
        // } 


        
        $model_id = $data['model_id'];

        // Check if file is uploaded
        if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $fileType = $_FILES['file']['type'];

            // Validate file type (Excel or CSV)
            $allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/csv'];
            if(!in_array($fileType, $allowedTypes) && !preg_match('/\.(xlsx?|csv)$/i', $fileName)){
                return send_json_response(false, 400, 'Invalid file type. Please upload an Excel or CSV file.');
            }

            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($fileTmpPath);
                $sheet = $spreadsheet->getActiveSheet();
                $rows = $sheet->toArray();

                $model_items = [];
                for($i = 1; $i < count($rows); $i++){
                    $cells = $rows[$i];
                    if(count($cells) < 7) continue;
                    if(empty(trim($cells[0])) && empty(trim($cells[1])) && empty(trim($cells[2])) && empty(trim($cells[3]))) continue;
                    $item = [
                        'name' => trim($cells[0]),
                        'redundancy' => intval(preg_replace('/\D/', '', $cells[1])),
                        'remaining_life' => intval(preg_replace('/\D/', '', $cells[2])),
                        'cost' => floatval(preg_replace('/[^\d.]/', '', $cells[3])),
                        'item_type' => intval(preg_replace('/\D/', '', $cells[4])),
                        'estimated_cost' => floatval(preg_replace('/[^\d.]/', '', $cells[5])),
                        'actual_cost' => floatval(preg_replace('/[^\d.]/', '', $cells[6]))
                    ];
                    if(!empty($item['name']) || $item['expected_life'] > 0 || $item['remaining_life'] > 0 || $item['cost'] > 0){
                        $model_items[] = $item;
                    }
                }

                // Delete old items
                $old_items = get_elements($modelItemsTable, ['model_id'=>$model_id]);
                $items_to_delete = array();
                foreach($old_items as $item){
                    array_push($items_to_delete, $item['id']);
                }

                $ret_id = true;

                // Save new items
                foreach($model_items as $item){
                    $item['model_id'] = $model_id;
                    $ret_id = save_element($modelItemsTable, $item);
                }

                // Delete removed items
                delete_elements_by_id($modelItemsTable, $items_to_delete);

                if($ret_id === false)
                    return send_json_response(false, 404, $this->errors['not_found']);

                set_element($modelsTable, ['updated_at'=>time(), 'id'=>$model_id]);
                return send_json_response(true, 200, 'Model Items Created Successfully', ['data' =>['model_id' => $model_id ] ]);

            } catch (Exception $e) {
                return send_json_response(false, 400, 'Error processing file: ' . $e->getMessage());
            }

        }else{
            // Original logic for JSON data
            $items = check_val($data, 'items', []);

        
        $old_items = get_elements($modelItemsTable, ['model_id'=>$data['model_id']]);
        $items_to_delete = array();
        foreach($old_items as $item){
            array_push($items_to_delete, $item['id']);
        }

        $ret_id = true;

        foreach($items as $item){
            
            // if item already exist, remove from delete array
            if(is_valid($item, 'id')){
                $item_exists = array_has($items_to_delete, $item['id'], true);
                if($item_exists >= 0)array_splice($items_to_delete, $item_exists, 1);
            }
            
            $item['model_id'] = $model_id;
            if(!is_numeric($item['redundancy']))$item['redundancy'] = 0;
            if(!is_numeric($item['remaining_life']))$item['remaining_life'] = 0;
            if(!is_numeric($item['cost']))$item['cost'] = 0;

            if (isset($item['estimated_cost'])) {
                if(!is_numeric($item['estimated_cost']))$item['estimated_cost'] = 0;
            }
            if (isset($item['actual_cost'])) {
                if(!is_numeric($item['actual_cost']))$item['actual_cost'] = 0;
            }


            $ret_id = save_element($modelItemsTable, $item);
        }


        delete_elements_by_id($modelItemsTable, $items_to_delete);
        
        

        if($ret_id === false){
            // return ['error' => ''];    
            return send_json_response(false, 404, $this->errors['not_found']);
        }
        
        $response = set_element($modelsTable, ['updated_at'=>time(), 'id'=>$model_id]);
        
        return send_json_response(true, 200, 'Model Items Created Successfully', ['data' =>['model_id' => $model_id ] ]);
        }
    }

    
    

    


    public function edit_actual($data){
        global $modelItemsTable, $modelsTable, $clientsTable, $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable, $simActualTable;

        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];
        
        if(!belongs_to_client($modelsTable, $data['model_id'], false, true))return ['error'=>$this->errors['not_allowed']];
        
        
        $element['items'] = get_elements($modelItemsTable, ['model_id'=>$data['model_id']]);
        $element['model'] = get_element_join($modelsTable, ['id'=>$data['model_id']], 
                                             "LEFT JOIN $clientsTable ON $clientsTable.id = $modelsTable.client_id",
                                             format_select($modelsTable, "*") . ", $clientsTable.association");
        

        $element['actual_costs'] = [];
        $actual_costs = get_elements($simActualTable, ['model_id'=>$data['model_id']]);
        
        foreach($actual_costs as $item){
            
            $element['actual_costs'][$item['redundancy_at']][$item['item_id']] = $item['actual_cost'];
        }
        

        view($this->module_name, 'model_items_actual.edit.php', $element);
              

    }

    public function save_actual($data){
        global $auth, $modelItemsTable, $modelsTable, $simActualTable, $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable;

        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];

        if(!belongs_to_client($modelsTable, $data['model_id'], false, true))return ['error'=>$this->errors['not_allowed']];


        
        $model_id = $data['model_id'];
        $items = check_val($data, 'items', []);


        
        $items_org_costs = [];
        foreach(get_elements($modelItemsTable, ['model_id'=>$data['model_id']]) as $org_item){
            $items_org_costs[$org_item['id']] = intval($org_item['cost']);
        }
        

        // use redundancy_at instead of actual year so that no matter
        // the original item's redundancy and remaining life when changed, the actual cost will always follow
        
        // get old actual_cost to see if it changed
        $old_actual_costs = [];
        foreach(get_elements($simActualTable, ['model_id'=>$data['model_id']]) as $old_actual){
            $old_actual_costs[$old_actual['redundancy_at']][$old_actual['item_id']] = intval($old_actual['actual_cost']);
        }
        
        // delete all old actual_cost
        delete_elements_by_cond($simActualTable, 'model_id = :model_id', ['model_id'=>$model_id]);

        foreach($items as $redundancy_at => $costs){
            foreach($costs as $item_id => $actual_cost){
                if(intval($actual_cost) <= 0)continue;
                
                
                // delete all splits and keep moves
                if(isset($old_actual_costs[$redundancy_at][$item_id]) && ($old_actual_costs[$redundancy_at][$item_id] != $actual_cost)){
                    delete_elements_by_cond($simSplitsTable, 'model_id = :model_id AND parent_id = :parent_id AND redundancy_at = :redundancy_at AND LENGTH(split_of) > 5', ['model_id'=>$model_id, 'parent_id'=>$item_id, 'redundancy_at'=>$redundancy_at]);
                    delete_elements_by_cond($simSplitsLTIMTable, 'model_id = :model_id AND parent_id = :parent_id AND redundancy_at = :redundancy_at AND LENGTH(split_of) > 5', ['model_id'=>$model_id, 'parent_id'=>$item_id, 'redundancy_at'=>$redundancy_at]);    
                }
                

                // $org_cost = $items_org_costs[$item_id];
                // $reduce_by = $actual_cost / $org_cost;
                
                // update splitted costs
                /*
                foreach(get_elements($simSplitsTable, ['model_id'=>$data['model_id'], 'parent_id'=>$item_id, 'redundancy_at'=>$redundancy_at, 'raw'=>'LENGTH(split_of) > 5']) as $splitted_item){
                    // if item at specific redundancy has a split, update split by ratio
                    if(isset($items_org_costs[$splitted_item['parent_id']])){
                        $org_cost = $items_org_costs[$splitted_item['parent_id']];
                        $split_item_cost = intval($splitted_item['cost']);
                        $reduce_by = $actual_cost / $org_cost;

                        set_element($simSplitsTable, ['cost'=>floor(($split_item_cost/$org_cost)*$reduce_by*$org_cost), 'id'=>$splitted_item['id']]);
                    }
                }
                */
                
                save_element($simActualTable, ['model_id'=>$model_id, 'item_id'=>$item_id, 'redundancy_at'=>$redundancy_at, 'actual_cost'=>$actual_cost]);
            }
        }
        
        
        return ['success' => ''];
        // return ['error' => $this->errors['used']];
    }


    public function reset_actual($data){
        global $auth, $modelItemsTable, $modelsTable, $simActualTable, $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable;

        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];

        if(!belongs_to_client($modelsTable, $data['model_id'], false, true))return ['error'=>$this->errors['not_allowed']];


        
        $model_id = $data['model_id'];
        
        delete_elements_by_cond($simActualTable, 'model_id = :model_id', ['model_id'=>$model_id]);

        
        return ['success' => ''];
        // return ['error' => $this->errors['used']];
    }
    
        
    
    public function delete($data){
        global $auth, $modelItemsTable;


        
        $res = array();

        if(!is_valid($data, 'id')){
            return send_json_response(false, 400, 'Please specify Model Items to delete');
        }
            
        $trans_started = start_transaction();

                                
        // and delete only for this company_id
        $res = delete_elements_by_id($modelItemsTable, $data['id']);
    
        if($trans_started)end_transaction();

        
        $count = count($data['id']) - count($res);
        

        if(!empty($res)){
            return send_json_response(false, 400, 'Some items could not be deleted', ['data' => $res ]);
        }else{
            return send_json_response(true, 200, "$count Model Items deleted successfully", ['data' => $data['id']]);
        } 
    }
    
    
    
    public function set($data){
        global $auth, $modelItemsTable , $modelsTable;

        if(!is_valid($data, 'id')){
            return ['error' => ''];
        }
        
        return set_property($modelsTable, $data);

    }



    private $errors = [ "model_id" => "Please choose a Model !",
                        "not_allowed" => "Unauthorized Access",
                        'not_found' => "Model Items not found !",
                        "missing" => "This Model doesn't exist !",
                        "used" => "This Model cannot be edited because it is used in the Simulation !",
                        "items" => "Please specify Model Items to edit !",
                        "deficit_year" => "Invalid Deficit Year !",
                        "deficit_year" => "Invalid Deficit Last Year !",
                        "item_id" => "Please specifiy Item !",
                        "split_exists"=> "An item belonging to the same Master item already exists in this Year !"];


}