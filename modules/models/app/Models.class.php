<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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
        global $auth, $modelsTable, $clientsTable, $simDeficitTable, $modelItemsTable;

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
                                         LEFT JOIN $simDeficitTable ON $simDeficitTable.model_id = $modelsTable.id
                                         LEFT JOIN $modelItemsTable ON $modelItemsTable.model_id = $modelsTable.id",
                                        "$modelsTable.*, IF(COUNT($modelsTable.id) - 1 > 0, 1, 0) AS has_simulation, CASE WHEN $clientsTable.association IS NOT NULL THEN $clientsTable.association ELSE 'unspecified' END AS association, COUNT($modelItemsTable.id) AS total_items, SUM(CASE WHEN $modelItemsTable.is_sirs = 1 THEN 1 ELSE 0 END) AS total_sirs, SUM(CASE WHEN $modelItemsTable.is_sirs = 0 THEN 1 ELSE 0 END) AS total_non_sirs ", " GROUP BY $modelsTable.id ORDER BY $modelsTable.row_id DESC".check_val($pagination, 'query'));

        
                                        rfa_create_log("Model List Retrieved");
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
        global $auth, $modelsTable, $modelItemsTable;

        // Check if file is uploaded
        if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
            // Handle file upload
            $fileTmpPath = $_FILES['file']['tmp_name'];
            $fileName = $_FILES['file']['name'];
            $fileSize = $_FILES['file']['size'];
            $fileType = $_FILES['file']['type'];

            // Validate file type (Excel or CSV)
            $allowedTypes = ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'application/csv'];
            if(!in_array($fileType, $allowedTypes) && !preg_match('/\.(xlsx?|csv)$/i', $fileName)){
                return send_json_response(false, 400, 'Invalid file type. Only Excel (.xlsx, .xls) and CSV files are allowed.');
            }

                // Parse the file
                try {
                    $spreadsheet = IOFactory::load($fileTmpPath);
                    $sheet = $spreadsheet->getActiveSheet();
                    $rows = $sheet->toArray();

                    // Validate file format
                    if (count($rows) < 20) {
                        return send_json_response(false, 400, 'Invalid file format. Please ensure the file matches the required template.');
                    }

                // Extract model data from actual sheet layout
                $model_data = ['client_id' => $data['client_id']];
                $label_to_key = [
                    'Model Name' => 'name',
                    'Housing Units' => 'housing',
                    'Starting Amount' => 'starting_amount',
                    'Monthly fees per unitInflaction rate (%)' => 'monthly_fees_rate',
                    'Inflation rate (%)' => 'inflation_rate',
                    'What is your Reserve funding year ?' => 'period',
                    'Fiscal Year To Start' => 'fiscal_year',
                    'Total Reserve Fees On Hand' => 'total_reserve_fees_onhand',
                    'Total SIRS Funds On Hand' => 'total_sirs_fund_onhand'
                ];

                for($i = 1; $i <= 11; $i++){
                    if($i >= count($rows)) break;
                    $cells = $rows[$i];
                    if(count($cells) < 2) continue;
                    $label = trim($cells[0]);
                    $value = $cells[1];
                    if(isset($label_to_key[$label])){
                        $key = $label_to_key[$label];
                        if($key == 'name'){
                            $model_data[$key] = trim($value);
                        }else{
                            $model_data[$key] = floatval(preg_replace('/[^\d.]/', '', $value));
                        }
                    }
                }

                // --- Calculate starting_amount from totals ---
                if (isset($model_data['total_reserve_fees_onhand']) && isset($model_data['total_sirs_fund_onhand'])) {
                    $model_data['starting_amount'] =
                        floatval($model_data['total_reserve_fees_onhand']) +
                        floatval($model_data['total_sirs_fund_onhand']);
                }

                // Extract model items from actual sheet layout
                $model_items = [];
                for($i = 14; $i < count($rows); $i++){
                    $cells = $rows[$i];
                    if(count($cells) < 6) continue;
                    // Skip only if ALL IMPORTANT FIELDS are blank
                    if (empty(trim($cells[0])) && empty(trim($cells[1])) && empty(trim($cells[2])) && empty(trim($cells[3]))) {
                        continue;
                    }

                     // Normalize dropdown text
                    $raw_type = trim($cells[4]);
                    $normalized_type = strtolower(str_replace(' ', '_', $raw_type));

                    // Optional strict mapping to prevent invalid enum entries
                    $valid_types = ['sirs_item', 'non_sirs_item', 'uncertain_sirs_item'];
                    if (!in_array($normalized_type, $valid_types)) {
                        $normalized_type = 'non_sirs_item';
                    }

                    // is_sirs boolean → only true when sirs_item
                    $is_sirs = ($normalized_type === 'sirs_item') ? 1 : 0;

                    $item = [
                        'name' => trim($cells[0]),
                        'expected_life' => intval(preg_replace('/\D/', '', $cells[1])),
                        'remaining_life' => intval(preg_replace('/\D/', '', $cells[2])),
                        'estimated_cost' => floatval(preg_replace('/[^\d.]/', '', $cells[3])),
                        'is_sirs' => $is_sirs,
                        'item_type' => $normalized_type,
                        'actual_cost' => floatval(preg_replace('/[^\d.]/', '', $cells[5])),
                        
                    ];
                    if(!empty($item['name']) || $item['expected_life'] > 0 || $item['remaining_life'] > 0 ){
                        $model_items[] = $item;
                    }
                }

                // Set auth client_id if needed
                if($auth->checkRoleType('client')){
                    $model_data['client_id'] = $auth->clientId();
                }

                // Validate model data
                $checkFor = ['client_id', 'housing', 'name'];
                $ret = check_missing($checkFor, $model_data, $this->errors);
                if($ret !== true) return $ret;

                $model_data['fiscal_year'] = check_val($model_data, 'fiscal_year', date('Y', time()));

                // Save model
                $model_id = save_element($modelsTable, $model_data);
                if($model_id === false){
                    return send_json_response(false, 500, 'Failed to save model.');
                }

                // Save model items
                if(!empty($model_items)){
                    foreach($model_items as &$item){
                        $item['model_id'] = $model_id;
                    }
                    unset($item); 
                    // 
                    foreach($model_items as $item){
                       $items_save =  save_element($modelItemsTable, $item);
                    }
                }

                return send_json_response(true, 200, $this->success['sucess'], ['data' =>['model_id' => $model_id ] ]);

            } catch (Exception $e) {
                return send_json_response(false, 500, 'Error processing file: ' . $e->getMessage());
            }

        }else{
            // Original logic for JSON data
            $checkFor = ['client_id', 'housing', 'total_reserve_fees_onhand','total_sirs_fund_onhand'];
            /**
             * allow null for these fields according to requirements(new figma)
             *
             * currently this array is not used
             */
            $allow_null = ['starting_amount', 'monthly_fees',];

            if(is_valid($data, 'id')){
                if(!belongs_to_client($modelsTable, $data['id'], false, true)){
                    return send_json_response(false, 400, $this->errors['not_allowed']);
                }
            }

        if($auth->checkRoleType('client') && empty($data['client_id'])){
            $data['client_id'] = $auth->clientId();
        }


        $ret = check_missing($checkFor, $data, $this->errors); 
        if($ret !== true){
            return send_json_response(false, 400, $ret['error']);
        }

        $data['fiscal_year'] = check_val($data, 'fiscal_year', date('Y', time()));
        $data['starting_amount'] = floatval(check_val($data, 'total_reserve_fees_onhand', 0)) + floatval(check_val($data, 'total_sirs_fund_onhand', 0));

        $ret_id = save_element($modelsTable, $data);

        if($ret_id === false)
            return send_json_response(false, 500, 'Failed to save model.');
        else
            return send_json_response(true, 200, $this->success['sucess'], ['data' =>['model_id' => $ret_id ] ]);
        }
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
            // return ['error' => ''];
            return send_json_response(false, 400, $this->errors['model_id']);
        }

        foreach($data['id'] as $id){
            if(!belongs_to_client($modelsTable, $id, false, true)){

                return send_json_response(false, 400, $this->errors['not_allowed']);
                // return ['error' => $this->errors['not_allowed'] ];
            }
        }

        
            
        $trans_started = start_transaction();

                                
        // and delete only for this company_id
        $res = delete_elements_by_id($modelsTable, $data['id']);
    
        if($trans_started)end_transaction();
        
        $count = count($data['id']) - count($res);
        

        if(!empty($res)){
            // return ['error' => $res];
            return send_json_response(false, 400, $res);
        } else {
            return send_json_response(true, 200, $this->success['deleted']);
            // return ['success' => ''];
        } 
    }
    
    
    
    public function set($data){
        global $auth, $modelsTable;

        if(!is_valid($data, 'id')){
            // return ['error' => ''];
            return send_json_response(false, 400, $this->errors['model_id']);
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


    private $errors = [ "client_id" => "Please choose anAssociation !",
                        "not_allowed" => "Unauthorized Access", 
                        "model_id" => "Please choose aModel !",
                        "missing" => "ThisModel doesn't exist !",
                        "used" => "ThisModel cannot be edited because it is used in theSimulation !",
                        "housing" => "Housing Units invalid !",
                        "starting_amount" => "starting_amount invalid !",
                        "monthly_fees" => "Monthly Fees invalid !"];

    private $success = [ "sucess" => "Operation completed successfully!",
                         "deleted" => "Selected model(s) deleted successfully!" ];


}