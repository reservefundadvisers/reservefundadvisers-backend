<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
class Simulation
{

    private $module_name = 'simulation';

    private $config = [];
    private $ltim_strategy = [];

    private $inv_strategies = [];

    private $default_invest_strategy = ['type' => 'bbp', 'dur' => 3, 'rate' => 0.55, 'year' => 0, 'terms' => 1, 'hold' => 0, 'end_of_year' => 0, 'parent' => -1, 'index' => -1]; // $model['inv_strategy']; //0.0055;


    /*
        deficit "inv_strategy" object (used in to add/edit strategy):

            "type" => string            // type of inv. see $inv_strategies for available startegies
            "year" => >= 0               // Year it started
            "rate" => 0 -> 100          // inv intrest rate
            "dur"  => >= 1              // inv terms duration
            "amount" => >= 1            // amount invested
            "perc" => 0 -> 100          // percentage of available funds to invest
            "name" => string            // inv title
            "note" => string            // short description of inv
            "pd"=> >= 1                 // number of days of intrest penalty
            "pm"=> >= 1                 // amount of minimum penalty
            "rpt"=> >= 0                // repeat strategy for the next X years
            "y_wth" => >= 0             // year in which the investment should be withrawn (used in Time Terms Investment)


        calculated "is" object (used to display strategy details)
            
            "type" => string                // type of inv. see $inv_strategies for available startegies
            "type_name" => string           // full name of inv. 
            "note" => string                // short description of inv
            "rate" => 0 -> 1                // inv intrest rate
            "principal" => int              // inv principal
            "total" => int                  // inv principal
            "earned" => int                 // inv principal
            "wthd" => 0/1                   // is inv has been withdrawn this year
            "no_fund" => 0/1                // when this strategy is lower in priority compared to other available strategies, 1= no sufficient funds so not applied

            "parent" => >= 0                // parent year of strategy, -1 (no parent), -2 (existing)
            "index" => >= 0                 // index of parent strategy in deficit of 'parent' year
            "existing" => 0/1               // if it's an existing or not, to avoid delete and edit

        
        
        how to get parent strategy in 'simulation.js' using property 'parent' and 'index':
            var parent_strategy = context.simulation_data.calculated[parent].deficit.inv_strategy[index]
        
            
    */

    function __construct()
    {

        $this->init_config();

        $this->inv_strategies = get_investment_strategies();
    }


    public function process($cmd, $data)
    {
        global $configTable;

        $response = "";


        switch ($cmd) {
            case 'get':
                $response = $this->simulation($data);
                break;
            case 'get_association':
                $response = $this->list_association($data);
                break;
            case 'get_fiscal':
                $response = $this->list_fiscal($data);
                break;
            case 'get_model':
                $response = $this->list_model($data);
                break;

            case 'set':
                $response = $this->set($data);
                break;

            case 'deficit':
                $response = $this->deficit($data);
                break;
            case 'unsplit':
                $response = $this->unsplit($data);
                break;
            case 'update':
                $response = $this->update($data);
                break;
            case 'reset':
                $response = $this->reset($data);
                break;
            case 'rule':
                $response = $this->rules($data);
                break;

            case 'compare':
                $response = $this->compare($data);
                break;

            case 'get_version':
                $response = $this->get_version($data);
                break;
            case 'save_version':
                $response = $this->save_version($data);
                break;
            case 'edit_version':
                $response = $this->edit_version($data);
                break;
            case 'delete_version':
                $response = $this->delete_version($data);
                break;
            case 'load_version':
                $response = $this->load_version($data);
                break;
            case 'compare_version':
                $response = $this->compare_version($data);
                break;

            case 'load_settings':
                $response = view($this->module_name, 'settings.php');
                break;
            case 'get_settings':
                $response = $this->config;
                break;
            case 'set_settings':
                $response = $this->set_config($data);
                break;

            case 'get_months_items':
                $response = $this->get_monthly_items($data);
                break;
            case 'get_yearly_items':
                $response = $this->get_yearly_items($data);
                break;
            case 'add_monthly_item':
                $response = $this->add_monthly_item($data);
                break;
            case 'update_monthly_item':
                $response = $this->update_monthly_item($data);
                break;
            case 'delete_monthly_item':
                $response = $this->delete_monthly_item($data);
                break;
            case 'split_item_monthly':
                $response = $this->split_item_monthly($data);
                break;
        }

        return $response;
    }

    public function get_monthly_items($data)
    {
        global $simMonthlyItems;

        if (!is_valid($data, 'model_id')) {
            return send_json_response(false, 400, "model_id is required");
        }

        $conds = ['model_id' => intval($data['model_id'])];

        if (isset($data['year']))  $conds['year'] = intval($data['year']);
        if (isset($data['month'])) $conds['month'] = intval($data['month']);

        $items = get_elements($simMonthlyItems, $conds);

        return send_json_response(true, 200, "Monthly items fetched", ['data' => $items]);
    }

    public function get_yearly_items($data)
    {
        global $simMonthlyItems;

       if (!is_valid($data, 'year') || !is_valid($data, 'model_id')) {
           return send_json_response(false, 400, "year and model_id is required");
        }

        $year = intval($data['year']);
        $model_id = $data['model_id'];

        // Run simulation to get spending items
        $simulation = $this->simulation($data, true);
        $spendings = $simulation['spendings'];

        if (empty($spendings)) {
            return send_json_response(false, 404, "No spending items found for the specified year");
        }
        $items_in_year = $spendings[$year];

        $item_name_lookup = [];

        foreach ($items_in_year as $item) {
            $occurrence = isset($item['occurrence']) ? $item['occurrence'] : ($item['redundancy_at'] ?? 0);
            $item_id = !empty($item['parent_id']) ? $item['parent_id'] : $item['id'];
            $key = "{$item_id}|{$occurrence}";
            $item_name_lookup[$key] = $item['name'];
        }

        // Load monthly allocations from DB
        $monthly = get_elements($simMonthlyItems, ['model_id' => $model_id, 'year' => $year]);

        $grouped_allocated = [];
        foreach ($monthly as $entry) {
            $key = "{$entry['item_id']}|{$entry['occurrence']}";
            if (!isset($grouped_allocated[$key])) {
                $grouped_allocated[$key] = [
                    'item_id' => $entry['item_id'],
                    'occurrence' => $entry['occurrence'],
                    'year' => $entry['year'],
                    'parent_id' => $entry['parent_id'] ?? null,
                    'name' => $item_name_lookup[$key] ?? null,
                    'monthly' => []
                ];
            }
            $grouped_allocated[$key]['monthly'][] = [
                'allocated_item_id' => $entry['id'], 
                'month' => intval($entry['month']),
                'amount' => floatval($entry['amount'])
            ];
        }

        // Collect keys for allocated items
        $allocated_keys = array_keys($grouped_allocated);

        $unallocated_items = [];
        foreach ($items_in_year as $item) {
           $occurrence = isset($item['occurrence']) 
                ? $item['occurrence'] 
                : ($item['redundancy_at'] ?? 0);

            $item_id = !empty($item['parent_id']) ? $item['parent_id'] : $item['id'];
            $key = "{$item_id}|{$occurrence}";
            if (!in_array($key, $allocated_keys)) {
                $unallocated_items[] = $item;
            }
            // Find total amount allocated for this key
            // $total_allocated = 0;
            // if (isset($grouped_allocated[$key])) {
            //     foreach ($grouped_allocated[$key]['monthly'] as $alloc) {
            //         $total_allocated += $alloc['amount'];
            //     }
            // }

            // // Exclude only if fully allocated
            // if (!in_array($key, $allocated_keys) || $total_allocated < floatval($item['cost'])) {
            //     $unallocated_items[] = $item;
            // }
        }

        return send_json_response(true, 200, "Yearly items fetched", [
            'allocated' => array_values($grouped_allocated),
            'unallocated' => $unallocated_items
        ]);
    }

    public function add_monthly_item($data)
    {
        global $auth, $simMonthlyItems;

        if (!is_valid($data, 'model_id') || !is_valid($data, 'item_id') || !is_valid($data, 'month') || !is_valid($data, 'year') || !is_valid($data, 'amount') || !is_valid($data, 'occurrence')) {
            return send_json_response(false, 400, 'Model ID, Item ID, Month, Year, Occurrence and Amount are required');
        }

        $data['created_by'] = $auth->uid();

        // Check if an item for the same model_id, item_id, occurrence, month and year already exists, to avoid duplicates
        $existing = get_elements($simMonthlyItems, [
            'model_id' => $data['model_id'],
            'item_id' => $data['item_id'],
            'month' => $data['month'],
            'year' => $data['year'],
        ]);

        if (!empty($existing)) {
            return send_json_response(false, 409, 'A monthly item allocation for the specified model, item, month, and year already exists');
        }

        // Generate a new ID if none is provided
        if (!isset($data['id'])) {
            $data['id'] = generate_id();
        }

        $saved = save_element($simMonthlyItems, $data);

        if ($saved === false) {
            return send_json_response(false, 500, 'Failed to allocate monthly item');
        }

        return send_json_response(true, 200, 'Monthly item allocated successfully', $data);
    }

    public function update_monthly_item($data)
    {
        global $simMonthlyItems;

        if (!is_valid($data, 'allocated_item_id')) {
            return send_json_response(false, 400, "monthly allocation allocated item_id required");
        }

        $existing = get_element($simMonthlyItems, ['id' => $data['allocated_item_id']]);
        if (!$existing) {
            return send_json_response(false, 404, "Allocation not found");
        }

        $update = ['id' => $data['allocated_item_id']];

        if (isset($data['amount'])) $update['amount'] = floatval($data['amount']);
        if (isset($data['month']))  $update['month'] = intval($data['month']);
        if (isset($data['year']))   $update['year'] = intval($data['year']);
        if (isset($data['occurrence'])) $update['occurrence'] = intval($data['occurrence']);

        if (!save_element($simMonthlyItems, $update)) {
            return send_json_response(false, 500, "Update failed");
        }

        return send_json_response(true, 200, "Monthly allocation updated", $update);
    }

    public function delete_monthly_item($data)
    {
        global $simMonthlyItems;

        if (!is_valid($data,'allocated_item_id')) {
            return send_json_response(false, 400, "allocated_item_id is required");
        }

        $existing = get_element($simMonthlyItems,['id'=>$data['allocated_item_id']]);
        if (!$existing) {
            return send_json_response(false, 404, "Record not found");
        }

        if (!empty(delete_elements_by_id($simMonthlyItems, $data['allocated_item_id']))) {
            return send_json_response(false, 500, "Delete failed");
        }

        return send_json_response(true, 200, "Monthly allocation deleted");
    }

    public function split_item_monthly($data){

    }

    private function init_config()
    {
        global $configTable;

        $config = get_elements($configTable);

        foreach ($config as $c) {

            if ($c['param'] == 'ltim') {
                $this->ltim_strategy = parse_json(check_val($c, 'value', []));
            }

            $this->config[$c['param']] = parse_json($c['value'], true);
        }
    }

    private function set_config($data)
    {
        global $configTable;

        if (!is_valid($data, 'param'))
            return ['error' => $this->errors['param']];


        $data['value'] = json_encode(check_val($data, 'value', NULL));


        return set_property($configTable, $data, 'param');
    }



    public function simulation($data, $internal_call = false)
    {
        global $auth, $modelItemsTable, $modelsTable, $clientsTable,
            $simSplitsTable, $simDeficitTable,
            $simSplitsLTIMTable, $simDeficitLTIMTable,
            $simRulesTable, $simActualTable, $simVersionTable, $simMonthlyItems;

        if (!is_valid($data, 'model_id'))
            // return ['error' => $this->errors['model_id']];
            return send_json_response(false, 400, $this->errors['model_id']);
        if (!exists($modelsTable, ['id' => $data['model_id']]))
            // return ['error' => $this->errors['missing']];
            return send_json_response(false, 404, $this->errors['missing']);

        if (!belongs_to_client($modelsTable, $data['model_id'], false, true))
            // return ['error' => $this->errors['not_allowed']];
            return send_json_response(false, 403, $this->errors['not_allowed']);

        // get model
        $model = get_element_join(
            $modelsTable,
            ['id' => $data['model_id']],
            "LEFT JOIN $clientsTable ON $clientsTable.id = $modelsTable.client_id",
            format_select($modelsTable, '*') . ", $clientsTable.association AS association_name"
        );
        // $model['fiscal_year'] = $model['fiscal_year'] - 1;
        // get model items
        $model_items = get_elements($modelItemsTable, ['model_id' => $data['model_id']]);

        // parse inv_startegy to JSON
        $model['inv_strategy'] = parse_json($model['inv_strategy'], []);

        // Add simulation_rules
        $simulation_rules = $this->get_rules($data['model_id']);
        // var_dump($simulation_rules);
        /* General Rules */
        // $is_ltim_enabled = check_val($simulation_rules, 'ltim_enabled', 0);
        // echo "Cash Reserve Threshold: " . $cash_reserve_threshold . "<br>";
        $is_ltim_enabled = false;
        $rule_ltim_perc = check_val($simulation_rules, 'ltim_perc', 0);
        $rule_ltim_cover = check_val($simulation_rules, 'ltim_cover', 1);
        $rule_ltim_lower = check_val($simulation_rules, 'ltim_lower', 0);
        $rule_ltim_yoc = check_val($simulation_rules, 'ltim_yoc', 3);
        if ($rule_ltim_yoc < 1)
            $rule_ltim_yoc = 1;
        $rule_used_ltim_strategy = check_val($simulation_rules, 'ltim_used', 'FL');

        $rule_mf_auto = check_val($simulation_rules, 'mf_auto', 0) == 1;

        // $disable_auto_fee_reduction = check_val($simulation_rules, 'disable_auto_fee_reduction', 0) == 1;
        $rule_mf_perc = check_val($simulation_rules, 'mf_perc', 0);

        $disable_auto_fee_reduction = check_val($simulation_rules, 'disable_auto_fee_reduction', 0);

        $rule_inf_rate = check_val($simulation_rules, 'inf_rate', 0);
        if (!is_numeric($rule_inf_rate)) {
            $rule_inf_rate = 0;
        }
        $rule_cushion_fund = floatval(check_val($simulation_rules, 'cushion_fund', 0));
        // Adnan Saleem..........
        $cushion_fund_thre = floatval(check_val($simulation_rules, 'cushion_fund_thre', 0));
        // Adnan Saleem..........

        //Rushi Patel..........
        $cash_reserve_threshold = floatval(check_val($simulation_rules, 'cash_reserve_threshold', 0));
        //Rushi Patel..........
        $rule_inv_keep = check_val($simulation_rules, 'inv_keep', 0) == 1;

        $rule_use_inflation = check_val($simulation_rules, 'use_infl', 1) == 1;

        /* ********** */


        // force calculation mode: managed or ltim
        $mode = check_val($data, 'mode');

        // log_info("Mode: $mode");

        $deficitTable = /* $is_ltim_enabled ? $simDeficitLTIMTable : */ $simDeficitTable;
        $splitsTable = /* $is_ltim_enabled ? $simSplitsLTIMTable : */ $simSplitsTable;


        // deficit data
        $deficits = get_elements($deficitTable, ['model_id' => $data['model_id'], 'user_id' => $auth->uid()], "*", "ORDER BY year ASC"); // get deficits


        // get calculation period
        $period = check_val($simulation_rules, 'period', check_val($model, 'period', 29)); // check_val($model, 'period', 29);

        // $period = $period + 1;
        //log_info($data);



        // log_info($model);


        $inflation_rate = $rule_use_inflation ? $rule_inf_rate /* floatval($model['inflation_rate']) / 100.0 */ : 0;
        $lopp_rate = round($inflation_rate * 100, 2);

        // set default bank intrest rate 
        $this->default_invest_strategy['rate'] = check_val($model, 'bank_int_rate', 0.55);


        /* Returned vars */
        $calculated = array_fill(0, $period, []);   // calculated rows for the given period
        $default_calculated = array_fill(0, $period, []);

        /* ************* */



        // calculate how many more years to add to have all buckets in the last year
        $extra_years_to_add = 0;
        foreach (check_val($this->ltim_strategy, "$rule_used_ltim_strategy/buckets", []) as $bucket) {
            $extra_years_to_add += intval(check_val($bucket, "dur", 0));
        }


        // Prepare model item spendings
        $spendings = array_fill(0, $period + $extra_years_to_add, 0);  // Sum of spending for each year
        $spending_data = array_fill(0, $period + $extra_years_to_add, []); // items per year

        // prepare actual costs
        $spendings_actuals = [];
        $actual_costs = get_elements($simActualTable, ['model_id' => $data['model_id']]);
        foreach ($actual_costs as $item) {
            $spendings_actuals[$item['item_id']][$item['redundancy_at']] = $item['actual_cost'];
        }



        //log_info($period);

        //log_info(count($spendings));
        //log_info(count($spending_data));


        // Actual Spendings period
        $spendings_period = count($spendings);

        // forech item in the model
        foreach ($model_items as $item) {

            // get informations
            $item_id = $item['id'];
            $name = $item['name'];
            $remaining_life = intval($item['remaining_life']);
            $redundancy = intval($item['redundancy']);
            if ($redundancy == 0)
                $redundancy = 1;
            $cost = floatval($item['cost']);
            $is_sirs = intval($item['is_sirs']);
            $actual_cost = $cost;

            // skip if remaining time is over the current period
            if ($remaining_life > $spendings_period)
                continue;


            // array index starts at 0
            $from = $remaining_life;

            // calculate how many redundancies left
            $redundancies = floor(($spendings_period - $from) / $redundancy) + 1;

            $to = $redundancies * $redundancy;

            //log_info("$name  = $from -> $to + $redundancy x $redundancies -> ".($from + $redundancies * $redundancy));
            // log_info("$name =  $from -> $redundancy ($redundancies) - ".($from + $redundancies * $redundancy));

            // for each redundancy
            for ($i = 0; $i < $redundancies; $i++) {

                // get splits from simulation for the redundancy, 0 -> First occurence
                $splits = get_elements_join(
                    "$splitsTable s1",
                    ['s1.parent_id' => $item['id'], 's1.redundancy_at' => $i],
                    "LEFT JOIN $splitsTable s2 ON s2.split_of = s1.id",
                    "s1.*, count(s2.id) as splits ",
                    "GROUP BY s1.id"
                );

                $at = $from + ($i * $redundancy);

                $adjusted_inflation_rate = 0; // $inflation_rate > 0 ? pow(1 + $inflation_rate, $at) - 1 : 0;

                // log_info("$name -> $at: infl: " . $adjusted_inflation_rate );

                // update actual cost and keep the same until new value
                if (isset($spendings_actuals[$item_id][$i]))
                    $actual_cost = $spendings_actuals[$item_id][$i];
                // $cost_ratio = $actual_cost / $cost;

                // log_info("$name -> $i = $actual_cost");

                // if the current redundancy has no splits, add original data
                if (empty($splits) && $at < $spendings_period) {

                    $spendings[$at] += -1 * $actual_cost;      // add cost to total year's spendings

                    // add inflated spending to be used in Years Of Cash calculation before hand
                    $default_calculated[$at]['sp'] = $spendings[$at];
                    // $default_calculated[$at]['lp'] = $at > 0 ? ceil($spendings[$at] * $adjusted_inflation_rate) : 0;

                    // log_info($item['name']." -> $at = $actual_cost");

                    // Generate new id of the master so it can be stored in model_sims instead of master one
                    array_push(
                        $spending_data[$from + ($i * $redundancy)],
                        [
                            'id' => generate_id(),
                            'name' => $item['name'],
                            'cost' => $actual_cost,
                            'is_sirs' => $is_sirs,
                            'year' => $at,
                            'redundancy' => $redundancy,
                            'redundancy_at' => $i,
                            'parent_id' => $item['id'],
                            'split_of' => '',
                            'splits' => 0
                        ]
                    );
                } else if (!empty($splits)) {

                    $splits_total_cost_without_org = 0;
                    $org_item_year = -1;
                    $org_item_index = -1;

                    // else if has splits, add each split with corresponding redundancy and year
                    foreach ($splits as $split) {


                        $split_spending_year = $split['year'] < $spendings_period ? $split['year'] : $spendings_period;



                        // skip original split to deduct all other splits value from it                        
                        if (empty($split['split_of'])) {
                            $org_item_index = count($spending_data[$split_spending_year]);
                            $org_item_year = $split_spending_year;
                        } else {
                            $splits_total_cost_without_org += $split['cost'];
                            $spendings[$split_spending_year] += -1 * $split['cost'];      // add cost to total year's spendings
                        }

                        // add inflated spending to be used in Years Of Cash calculation before hand
                        $default_calculated[$at]['sp'] = $spendings[$split_spending_year];
                        // $default_calculated[$split_spending_year]['lp'] = ceil($spendings[$split_spending_year] /* * $inflation_rate */);

                        // log_info("$split_spending_year (Split)");

                        array_push(
                            $spending_data[$split_spending_year],
                            [
                                'id' => $split['id'],
                                'name' => $item['name'],
                                'cost' => $split['cost'],
                                'is_sirs' => $is_sirs,
                                'year' => $split_spending_year,
                                'redundancy' => $redundancy,
                                'redundancy_at' => $i,
                                'parent_id' => $split['parent_id'],
                                'split_of' => $split['split_of'],
                                'splits' => $split['splits']
                            ]
                        );
                    }


                    // update original cost by deducing total splits from item's original cost
                    if ($org_item_year >= 0 && $org_item_index >= 0) {


                        $org_item_split_cost = ($actual_cost - $splits_total_cost_without_org);
                        if ($org_item_split_cost < 0)
                            $org_item_split_cost = 0;

                        // update org item cost
                        $spending_data[$org_item_year][$org_item_index]['cost'] = $org_item_split_cost;

                        // update year total spendings
                        $spendings[$org_item_year] += -1 * $org_item_split_cost;
                    }
                }
            }
        }

        // log_info($spendings);

        /* MODEL INFOS */
        $init_starting_amount = floatval($model['starting_amount']);
        $starting_amount = floatval($model['starting_amount']);
        $starting_amount_o = $starting_amount;
        $housing = floatval($model['housing']);
        if ($housing < 1)
            $housing = 1;

        // Monthly Fees Collection
        $monthly_fees = floatval($model['monthly_fees']); // if($monthly_fees < 1)$monthly_fees = 1;  
        $yearly_collections = floatval($monthly_fees * $housing * 12);
        $new_monthly_fees = $monthly_fees;
        $auto_monthly_fees = array_fill(0, $period, $monthly_fees);     // used to track auto-fees calculated
        $manual_monthly_fees = array_fill(0, $period, $monthly_fees);
        $used_manual_monthly_fees = array_fill(0, $period, false);

        $custom_range_years = array_fill(0, $period, false);
        $custom_gradual_range_years = array_fill(0, $period, false);
        // print_r($custom_range_years);
        // exit;


        $apply_auto_fees = array_fill(0, $period, false);


        $bank_rate = floatval(check_val($model, 'bank_rate', 0)) / 100.0;
        $loan_years = floatval(check_val($model, 'loan_years', 1));



        // $invest_strategy = $model['inv_strategy'];
        $invest_strategies = array_fill(0, $period, []);
        $propagated_inv_strategy = array_fill(0, $period, []);  // used when inv strategy takes up period
        $existing_inv_strategy = array_fill(0, $period, []);     // propagated existing inv strategies
        /* ********** */




        /* calculation vars */
        $final_amount = 0;      // used for final calulated amount to be used as new starting_amount    
        $loan_payments = array_fill(0, $period, 0);     // different loan to take for next year
        $remaining_loan_payments = 0;
        $loan_balance = 0; // Track remaining loan principal
        /* ************** */


        /* Auto Monthly Fees Vars */
        $processed_deficits = array();

        // Auto MF increase % of each year to check when max is reached
        $auto_monthly_fees_inc = array_fill(0, $period, 0);
        // $auto_monthly_fees_stop_year = -1;
        // $auto_monthly_fees_last_used = -1;


        // Used in Auto MF Mode, to avoid taking the amount used to cover remaining deficit
        $auto_monthly_fees_ltim_amount = array_fill(0, $period, -1);

        // Used in Auto MF Mode, to avoid widthdrawi
        $auto_monthly_fees_ltim_wth = array_fill(0, $period, -1);


        $last_deficit = -1;

        /* ********************** */




        // log_info($existing_inv_strategy);


        /* Erase Deficit Data */
        $deficit_array = [];
        foreach ($deficits as $deficit) {

            if ($deficit['year'] > $period)
                break;

            $year = intval($deficit['year']);


            $deficit_array[$year] = parse_json($deficit['data']);


            // only implement manual fees if auto-fees is not active
            if (is_valid($deficit_array[$year], "monthly_fees_auto") == 1) {


                // reset fees before auto-mf
                $new_monthly_fees = $monthly_fees;


                for ($i = intval($year); $i < $period; $i++) {
                    // $manual_monthly_fees = array_fill(0, $period, $new_monthly_fees);
                    // $used_manual_monthly_fees = array_fill(0, $period, false);
                    $used_manual_monthly_fees = array_fill($i, $period - $i, false);

                    $auto_monthly_fees[$i] = $new_monthly_fees;

                    // AVOID DIV BY 0, WHEN MF = 0
                    // if first year
                    if ($i == $year) {
                        $auto_monthly_fees_inc[$i] = $monthly_fees == 0 ? 0 : ($auto_monthly_fees[$i] - $monthly_fees) / $monthly_fees;
                    } else {
                        $auto_monthly_fees_inc[$i] = $auto_monthly_fees[$i - 1] == 0 ? 0 : ($auto_monthly_fees[$i] - $auto_monthly_fees[$i - 1]) / $auto_monthly_fees[$i - 1];
                    }


                    // add auto fee to all previous years
                    $apply_auto_fees[$i] = true;
                }
                 } else if (

                isset($deficit_array[$year]['monthly_fees_auto_range']) &&
                is_array($deficit_array[$year]['monthly_fees_auto_range'])

            ) {
                $range = $deficit_array[$year]['monthly_fees_auto_range'];
                $start = intval($range['start']);
                $end = intval($range['end']);
                $max_perc = isset($range['max_perc']) ? floatval($range['max_perc']) : $rule_mf_perc;
                $new_monthly_fees = $monthly_fees;



                // Reset all years to false so only the range is affected
                $apply_auto_fees = array_fill(0, $period, false);


                // --- Update simulation_rules['mf_perc_per_year'] as array for selected years ---
                if (!isset($simulation_rules['mf_perc_per_year']) || !is_array($simulation_rules['mf_perc_per_year'])) {
                    $simulation_rules['mf_perc_per_year'] = [];
                }

                for ($i = $start; $i <= $end && $i < $period; $i++) {
                    $simulation_rules['mf_perc_per_year'][$i] = $max_perc;
                    // Only apply auto logic for selected range
                    $used_manual_monthly_fees[$i] = false;
                    $auto_monthly_fees_inc[$i] = $max_perc;
                    $custom_gradual_range_inc[$i] = $max_perc;
                    $custom_gradual_range_years[$i] = true;

                    if ($i == $start) {
                        // Always apply the increase for the first year in range
                        $auto_monthly_fees[$i] = $new_monthly_fees * (1 + $max_perc);
                        $auto_monthly_fees_inc[$i] = $max_perc;
                    } else {
                        $auto_monthly_fees[$i] = $auto_monthly_fees[$i - 1] * (1 + $max_perc);
                        $auto_monthly_fees_inc[$i] = $max_perc;
                    }

                }

                // Now optimize all years, skipping custom range years

                if ($rule_mf_auto == 1) {
                    for ($year_idx = 0; $year_idx < $period; $year_idx++) {
                        if (!isset($custom_range_fees))
                            $custom_range_fees = [];
                        if (!isset($custom_gradual_range_inc))
                            $custom_gradual_range_inc = [];
                        
                        $this->updateMonthlyFeeInc(
                            $year_idx,
                            $deficit_per_unit,
                            $auto_monthly_fees,
                            $auto_monthly_fees_inc,
                            $this->get_mf_perc($simulation_rules, $year_idx), // Use year-wise mf_perc
                            $monthly_fees,
                            $rule_cushion_fund,
                            $inflation_rate,
                            $rule_mf_auto,
                            $used_manual_monthly_fees,
                            $cash_reserve_threshold,
                            $simulation_rules,
                            $custom_range_years,
                            $custom_range_fees,
                            $custom_gradual_range_inc,
                            $custom_gradual_range_years,
                            $disable_auto_fee_reduction
                        );
                    }
                }

                $custom_gradual_range_inc = [];
                foreach ($custom_gradual_range_years as $year => $is_customs) {
                    if ($is_customs) {
                        $custom_gradual_range_inc[$year] = $auto_monthly_fees_inc[$year];
                    }
                }

                // After custom gradual range is set

                if ($rule_mf_auto == 1) {
                    for ($i = 0; $i < $period; $i++) {
                        if (!isset($simulation_rules['mf_perc_per_year'][$i]) && isset($simulation_rules['mf_perc'])) {
                            $simulation_rules['mf_perc_per_year'][$i] = $rule_mf_perc;
                        }
                    }
                }

            } else if (isset($deficit_array[$year]['monthly_fees_range']) && is_array($deficit_array[$year]['monthly_fees_range'])) {

                $range = $deficit_array[$year]['monthly_fees_range'];
                $fee = floatval($range['fee']);
                $start = intval($range['start']);
                $end = intval($range['end']);

                for ($i = $start; $i <= $end && $i < $period; $i++) {
                    $used_manual_monthly_fees[$i] = true;
                    $auto_monthly_fees[$i] = $fee;
                    $custom_range_years[$i] = true;
                    $custom_range_fees[$i] = $fee;

                    if ($i == $start) {
                        $auto_monthly_fees_inc[$i] = $monthly_fees == 0 ? 0 : ($auto_monthly_fees[$i] - $monthly_fees) / $monthly_fees;
                    } else {
                        $auto_monthly_fees_inc[$i] = $auto_monthly_fees[$i - 1] == 0 ? 0 : ($auto_monthly_fees[$i] - $auto_monthly_fees[$i - 1]) / $auto_monthly_fees[$i - 1];
                    }
                }

                // Now optimize all years, skipping custom range years

                if ($rule_mf_auto == 1) {

                    for ($opt_year = 0; $opt_year < $period; $opt_year++) {

                        if (!isset($custom_range_fees))
                            $custom_range_fees = [];

                        if (!isset($custom_gradual_range_inc))
                            $custom_gradual_range_inc = [];

                        $this->updateMonthlyFeeInc(
                            $opt_year,
                            $deficit_per_unit,
                            $auto_monthly_fees,
                            $auto_monthly_fees_inc,
                            $rule_mf_perc,
                            $monthly_fees,
                            $rule_cushion_fund,
                            $inflation_rate,
                            $rule_mf_auto,
                            $used_manual_monthly_fees,
                            $cash_reserve_threshold,
                            $simulation_rules,
                            $custom_range_years,
                            $custom_range_fees,
                            $custom_gradual_range_inc,
                            $custom_gradual_range_years,
                            $disable_auto_fee_reduction
                        );

                    }

                }

                $custom_range_fees = [];

                foreach ($custom_range_years as $year => $is_custom) {
                    if ($is_custom) {
                        $custom_range_fees[$year] = $auto_monthly_fees[$year];
                    }
                }

            } else if (isset($deficit_array[$year]['disable_auto_fee_reduction'])) {
                $disable_auto_fee_reduction = $deficit_array[$year]['disable_auto_fee_reduction'];


                $this->updateMonthlyFeeInc(
                    $i,
                    $deficit_per_unit,
                    $auto_monthly_fees,
                    $auto_monthly_fees_inc,
                    $rule_mf_perc,
                    $monthly_fees,
                    $rule_cushion_fund,
                    $inflation_rate,
                    $rule_mf_auto,
                    $used_manual_monthly_fees, /* $auto_monthly_fees_stop_year */
                    $cash_reserve_threshold,
                    $simulation_rules,
                    $custom_range_years,
                    $custom_range_fees,
                    $custom_gradual_range_inc,
                    $custom_gradual_range_years,
                    $disable_auto_fee_reduction
                );
                
            } else if (is_valid($deficit_array[$year], 'monthly_fees')) {

                $new_monthly_fees = floatval($deficit_array[$year]['monthly_fees']);

                // log_info($year, $new_monthly_fees);
                // set the where to stop using auto MF
                // if($auto_monthly_fees_stop_year == -1)
                //     $auto_monthly_fees_stop_year = intval($year);


                $used_manual_monthly_fees[intval($year)] = true;
                $i = intval($year);

                // update new monthly fees
                // for ($i = intval($year); $i < $period; $i++) {
                $manual_monthly_fees[$i] = $new_monthly_fees;
                $auto_monthly_fees[$i] = $new_monthly_fees;

                if ($i == 0) {
                    $auto_monthly_fees_inc[$i] = $monthly_fees == 0 ? 0 : ($auto_monthly_fees[$i] - $monthly_fees) / $monthly_fees;
                } else {
                    $auto_monthly_fees_inc[$i] = $auto_monthly_fees[$i - 1] == 0 ? 0 : ($auto_monthly_fees[$i] - $auto_monthly_fees[$i - 1]) / $auto_monthly_fees[$i - 1];
                }
                // }

                /***** New Code RUSHI Start *****/
                // $new_monthly_fees = floatval($deficit_array[$year]['monthly_fees']);
                // $used_manual_monthly_fees[intval($year)] = true;

                // // update new monthly fees only for the selected year
                // $manual_monthly_fees[$year] = $new_monthly_fees;
                // $auto_monthly_fees[$year] = $new_monthly_fees;

                // if ($year == 0) {
                //     $auto_monthly_fees_inc[$year] = $monthly_fees == 0 ? 0 : ($auto_monthly_fees[$year] - $monthly_fees) / $monthly_fees;
                // } else {
                //     $auto_monthly_fees_inc[$year] = $auto_monthly_fees[$year - 1] == 0 ? 0 : ($auto_monthly_fees[$year] - $auto_monthly_fees[$year - 1]) / $auto_monthly_fees[$year - 1];
                // }

                // log_info("set mf (".$year.")", json_encode($auto_monthly_fees));
                /***** New Code RUSHI End *****/
            } else if (isset($deficit_array[$year]['inv_existing_wthd'])) {

                /*
                // if an existing investment is withdrawn
                foreach($deficit_array[$year]['inv_existing_wthd'] as $existing_inv_id){
                    
                    foreach($model['inv_strategy'] as &$existing_inv){
                        if(check_val($existing_inv, 'id') == $existing_inv_id){
                            
                            // check if exitsing inv_strategy is before fiscal year
                            $start_year = intval($existing_inv['sy']) - intval($model['fiscal_year']);
                            if($start_year > 0)continue;

                            $withraw_in_year = intval($model['fiscal_year']) + $year - intval($existing_inv['sy']);

                            // check if year to withdraw is withing terms of inv
                            if($withraw_in_year > intval($existing_inv['dur']))continue;

                            // set withraw year
                            $existing_inv['y_wth'] = $withraw_in_year;

                            log_info("withdrawing ".($existing_inv['sy'])." $existing_inv_id in year $withraw_in_year");
                        }
                    }
                }
                */
            } else if (isset($deficit_array[$year]['inv_strategy'])) {

                if (is_array($deficit_array[$year]['inv_strategy'])) {

                    $tmp_inv_strategies = $deficit_array[$year]['inv_strategy'];

                    for ($n = count($tmp_inv_strategies) - 1; $n >= 0; $n--) {

                        $strategy = $tmp_inv_strategies[$n];

                        // get strategy duration
                        $dur = check_val($strategy, 'dur', 1);
                        if ($dur <= 0)
                            $dur = 1;


                        // set strategy type name to deficit data for UI display
                        $strategy['type_name'] = check_val($this->inv_strategies, check_val($strategy, 'type') . "/name", 'Investment Startegy');

                        // is type is holdable and terms are > 1
                        $hold = check_val($this->inv_strategies, check_val($strategy, 'type') . "/hold", 0) == 1;


                        // not holding, spread same strategy into the whole period,
                        // to generate intrest in the same year
                        if (!$hold) {
                            for ($i = 0; $i < $dur; $i++) {

                                // don't overflow the period
                                if ($year + $i > $period)
                                    break;

                                // put current year to top
                                array_unshift($invest_strategies[$year + $i], ['dur' => 1, 'year' => $i, 'terms' => $dur, 'parent' => $year, 'index' => $n] + $strategy);  // change dur to 1 to pay intrests yearly                                
                            }

                            // holding funds, compound, then withdraw on maturity
                        } else {
                            array_unshift($invest_strategies[$year], ['terms' => $dur, 'parent' => $year, 'index' => $n] + $strategy);
                        }
                    }
                }
            }

            // if(is_valid($deficit_array[$deficit['year']], 'monthly_fees_auto')){
            //     $auto_monthly_fees_last_used = intval($deficit['year']);
            // }


        }



        // Add Existing investmen strategies
        foreach ($model['inv_strategy'] as $existing_inv) {

            // log_info($existing_inv);

            // echo "<pre>";
            // print_r($existing_inv['sy']);
            // echo "</pre>";

            $start_year = intval($existing_inv['sy']) - intval($model['fiscal_year']);
            if ($start_year > 0)
                continue;


            $existing_inv['year'] = 0;
            $existing_inv['parent'] = -2;
            $existing_inv['index'] = -2;

            $this->calculateClientInvStrategy($existing_inv, $existing_inv['amount'], $ret_strategies, $amount_to_deduce, $early_penalty);

            // log_info($existing_inv['sy'] . " - " . $model['fiscal_year'] . " = " .$start_year, $existing_inv, $ret_strategies);
            // $existing_inv['existing'] = 1;
            // $process_inv_strategies($existing_inv, $start_year, -1);

            // add as existing propagated
            foreach ($ret_strategies as $ret_strategy) {

                // strategy total P+I
                $ret_strategy_p = $ret_strategy['total'];

                // strategy total Earning
                $ret_strategy_ne = $ret_strategy['earned'];


                foreach ($ret_strategy['yearly'] as $y => $yearly_details) {

                    $inv_y = $start_year + $yearly_details['year'];
                    if ($inv_y < 0)
                        continue;

                    // $yearly_details['year'] = $inv_y;
                    $yearly_details['sy'] = $existing_inv['sy'];
                    $yearly_details['id'] = $existing_inv['id'];

                    // log_info($inv_y, $yearly_details);

                    // merge parent infos with child infos
                    array_push($existing_inv_strategy[$inv_y], $yearly_details + ['total_pi' => $ret_strategy_p, 'total_ne' => $ret_strategy_ne]);
                }
            }
        }

        // log_info($existing_inv);
        $propagated_inv_strategy = $existing_inv_strategy;


        // log_info("Init", $invest_strategies);


        // log_info("auto_monthly_fees_inc", $auto_monthly_fees_inc);
        // log_info("invest_strategies", $invest_strategies);
        /* ************** */

        //--------------------
        // Base Calculations
        //--------------------

        $deficit_years = [];
        $processed_loans = [];



        // init original unmanaged data
        $this->initOriginalCalculation(
            $calculated,
            $default_calculated,
            $deficit_years,
            $starting_amount_o,
            $monthly_fees,
            $housing,
            [],
            $propagated_inv_strategy,
            $inflation_rate,
            $spendings,
            $period,
            0,
            $simulation_rules
        );
        // var_dump($calculated);
        // log_info($calculated);



        // to prevent infinite loop
        set_time_limit(10);


        $is_lowering_fees = false;
        $current_auto_fee = -1;
        $auto_fee_remaining_loop_count = 0; // used to count how many time auto-fee loops in same year when still deficit to avoid infinit loop
        $auto_fee_remaining_loop_max = 5;



        $is_calculating = true;
        $is_adjusting = false;
        $adjustment_operations = ['cushion'];
        $current_adjustment = '';

        $is_deficit_check = false;

        // then managed calculations
        for ($i = 0; $i < $period; $i++) {

            // log_info("$i/$period");


            // get erase deficit data
            $erase_deficit = check_val($deficit_array, $i, []);


            // get any calculated as year_calculations      
            $year_calculations = $calculated[$i];
            
            $year_calculations['lopp_rate'] = $lopp_rate;

            // current year spendings, array index starts at 0
            $spending = $spendings[$i];


            // Year Deficit Management variables


            // Investment Strategies Used
            // $invest_strategy = check_val($erase_deficit, 'inv_strategy', $default_invest_strategy);
            // $invest_strategy = !empty($invest_strategies[$i]) ? $invest_strategies[$i] : $default_invest_strategy;
            $invest_strategy = $invest_strategies[$i];


            // auto monthly fees
            $is_auto_mf = $apply_auto_fees[$i]; //check_val($erase_deficit, 'monthly_fees_auto', false);




            // Loan
            $loan_amount = 0;
            if (isset($erase_deficit['loan_amount'])) {
                $loan_amount = check_val($erase_deficit, 'loan_amount', 0);
                $loan_calc = $this->calculateLoan($loan_payments, $loan_amount, check_val($erase_deficit, 'bank_rate', $bank_rate), check_val($erase_deficit, 'loan_years', $loan_years), $i, $period, !array_has($processed_loans, $i));
                $loan_balance += $loan_amount;

                $year_calculations['loan_i'] = $loan_calc['total_i'];
                $year_calculations['loan_pi'] = $loan_calc['total'];

                array_push($processed_loans, $i);
            }

            // Get previous year loan payment
            $loan_payment = $loan_payments[$i] * -1;
            $year_calculations['loan_y'] = $loan_payment;

            if ($loan_payment < 0) {
                $loan_balance += $loan_payment; // subtract payment
                if ($loan_balance < 0) $loan_balance = 0;
            }
            $year_calculations['loan_balance'] = round($loan_balance, 2);

            // assessment
            $assessment = check_val($erase_deficit, 'assessment', 0);



            // LTIM Invest Surplus Percentage
            $is_ltim_enabled = isset($erase_deficit['ltim_enabled']) ? $erase_deficit['ltim_enabled'] == 1 : $is_ltim_enabled;
            $ltim_perc = 0;         // % to invest in LTIM
            $ltim_withdrawn = 0;    // Amount to withdraw from LTIM to cover deficit
            $ltim_yoc = 0;          // Amount of Years Of Cash
            $ltim_str = [];         // LTIM Strategy
            if ($is_ltim_enabled) {

                // use manual LTIM % to invest if specified
                if (is_valid($erase_deficit, 'ltim_perc')) {
                    $ltim_perc = floatval(check_val($erase_deficit, 'ltim_perc', 0));

                    // else use default LTIM %
                } else {
                    $ltim_perc = $rule_ltim_perc;
                }

                // correct LTIM %
                if ($ltim_perc > 1)
                    $ltim_perc = 1;
                else if ($ltim_perc < 0)
                    $ltim_perc = 0;
            }


            // LTIM Strategy 
            $ltim_str = $this->calculateLTIM($spendings, $i, $rule_used_ltim_strategy);
            // log_info($ltim_str);

            // LTIM Years Of Cash
            $ltim_yoc = $this->calculateYearsOfCash($calculated, $i, $rule_ltim_yoc);

            // LTIM Withdraw to cover deficit
            $ltim_withdrawn = $auto_monthly_fees_ltim_wth[$i];      // -1 when not previously assigned
            if ( /* $ltim_withdrawn < 0 && */isset($erase_deficit['ltim_wth'])) {
                $ltim_withdrawn = check_val($erase_deficit, 'ltim_wth', 0);
            }

            // if LTIM disabled and has accumulated
            if (!$is_ltim_enabled && check_val($year_calculations, 'ltim_acc', 0) > 0)
                $ltim_withdrawn = check_val($year_calculations, 'ltim_acc', 0);


            // adjust ltim_withdraw if negative
            if ($ltim_withdrawn < 0)
                $ltim_withdrawn = 0;
            // or greater than available funds
            else if ($ltim_withdrawn > check_val($year_calculations, 'ltim_acc', 0))
                $ltim_withdrawn = check_val($year_calculations, 'ltim_acc', 0);



            // log_info("Year: $i: $ltim_perc, $auto_monthly_fees_ltim_amount[$i], $ltim_withdrawn");

            // managed calculations
            // if auto monthly fee increase ON, don't add LOAN & ASSESSMENT until auto has been calculated
            // if ($year_calculations['fa']  >= 0 && $rule_mf_auto) {
            //     $this->adjustFeesForNoDeficitScenario($calculated, $auto_monthly_fees, $monthly_fees, $housing, $period, $simulation_rules);
            // }

            $this->calculateYearData(
                $starting_amount,
                $auto_monthly_fees[$i],
                $housing,
                $invest_strategy,
                $propagated_inv_strategy,
                // $year_calculations["lp"],
                $inflation_rate,
                $loan_amount,
                $loan_payment,
                $assessment,
                $spending,
                $erase_deficit,
                $ltim_perc,
                $auto_monthly_fees_ltim_amount[$i],
                $ltim_withdrawn,
                $ltim_yoc,
                $ltim_str,
                true,
                $year_calculations,
                $i,
                "",
                $simulation_rules
            );


            // add manually specifed monthly fee for reference
            $year_calculations['mf_m'] = $manual_monthly_fees[$i];

            // log_info($year_calculations);

            // log_info("Deficit Year ".$i.": ".$year_calculations['fa']." LTIM Ava: ".check_val($year_calculations, 'ltim_acc', 0));

            // get the available LTIM funds in this year
            $ltim_available = check_val($year_calculations, 'ltim_acc', 0) /* * 0.8 */; // only allow 80% f available funds
            $deficit_to_cover = $year_calculations['fa'] * (1 + $inflation_rate);  // abs() year deficit

            // log_info("$i -> LTIM AVAILABLE $ltim_available");


            // if year has a deficit, and no manually specified amount to withdraw from LTIM, 
            // and rule allows to cover from LTIM 
            if (!isset($erase_deficit['ltim_wth']) && $ltim_available > 0 && $rule_ltim_cover == 1 && $deficit_to_cover < 0) {

                $deficit_to_cover = abs($deficit_to_cover);

                // if allowed to cover deficit from LTIM when available funds are lower than deficit, and it's the case
                // then cover deficit from LTIM funds
                if ($rule_ltim_lower == 1 && $ltim_available > $deficit_to_cover) {

                    // add amount to cover to this year erase_deficit data
                    $deficit_array[$i]['ltim_wth'] = $deficit_to_cover;
                    $deficit_array[$i]['ltim_auto_wth'] = 1;


                    // log_info("LTIM deficit ($i): ". $year_calculations['fa'] ." Available: $ltim_available, Taking ".$deficit_array[$i]['ltim_wth']);

                    // remove this year processed investments in $propagated_inv_strategy
                    if (!empty($propagated_inv_strategy[$i])) {
                        for ($p = 0; $p < count($propagated_inv_strategy[$i]); $p++) {
                            if ($propagated_inv_strategy[$i][$p]['yproc'] == $i) {
                                unset($propagated_inv_strategy[$i][$p]);
                            }
                        }
                        $propagated_inv_strategy[$i] = array_values($propagated_inv_strategy[$i]); // used to reset array indexed when unset is used to avoid [0, 2, 3, 5 ...]
                    }

                    // recalculate current year after widthrawing amount
                    $i--;
                    continue;

                    // else cover no matter what
                } else if ($rule_ltim_lower == 0) {

                    // add amount to cover to this year erase_deficit data
                    $deficit_array[$i]['ltim_wth'] = $ltim_available > $deficit_to_cover ? $deficit_to_cover : $ltim_available;
                    $deficit_array[$i]['ltim_auto_wth'] = 1;

                    // log_info("LTIM deficit ($i): ". $year_calculations['fa'] ." Taking ".$deficit_array[$i]['ltim_wth']);

                    // remove this year processed investments in $propagated_inv_strategy
                    if (!empty($propagated_inv_strategy[$i])) {
                        for ($p = 0; $p < count($propagated_inv_strategy[$i]); $p++) {
                            if ($propagated_inv_strategy[$i][$p]['yproc'] == $i) {
                                unset($propagated_inv_strategy[$i][$p]);
                            }
                        }
                        $propagated_inv_strategy[$i] = array_values($propagated_inv_strategy[$i]); // used to reset array indexed when unset is used to avoid [0, 2, 3, 5 ...]
                    }

                    // recalculate current year after widthrawing amount
                    $i--;
                    continue;
                }
            } else {
                if (isset($deficit_array[$i]['ltim_auto_wth']))
                    unset($deficit_array[$i]['ltim_auto_wth']);
            }


            // log_info("$i -> ", $year_calculations);

            // if first year set LTIM funds to 0
            if ($i == 0) {
                $year_calculations['ltim_acc'] = 0;
                $year_calculations['ltim_acc_p'] = 0;
                $year_calculations['ltim_acc_ne'] = 0;
            }


            // for every other year
            if ($i < $period - 1) {

                // set next year's accumulated LTIM principal
                $calculated[$i + 1]['ltim_acc_p'] = floor($year_calculations['ltim_acc'] + $year_calculations['ltim_p']) - $ltim_withdrawn;
                if ($calculated[$i + 1]['ltim_acc_p'] < 0)
                    $calculated[$i + 1]['ltim_acc_p'] = 0;

                // set next year's LTIM net earning
                $ltim_avr_rate = check_val($year_calculations['ltim_str'], 'avr', 0);
                $calculated[$i + 1]['ltim_acc_ne'] = floor($calculated[$i + 1]['ltim_acc_p'] * $ltim_avr_rate);

                // set next year's Total Available LTIM funds
                $calculated[$i + 1]['ltim_acc'] = $calculated[$i + 1]['ltim_acc_p'] + $calculated[$i + 1]['ltim_acc_ne'];



                // $prev_ltim_acc = $i == 0 ? 0 : $calculated[$i - 1]['ltim_acc']; 
                // $year_calculations['ltim_acc'] =  $prev_ltim_acc + $year_calculations['ltim_p'] + $year_calculations['ltim_ne'];
                // if($year_calculations['ltim_acc'] < 0)$year_calculations['ltim_acc'] = 0;
                // else $year_calculations['ltim_acc'] *= $year_calculations['ltim_str']['rate'];

            }

            // remove LTIM Strategy
            // unset($year_calculations['ltim_str']);
            unset($year_calculations['ltim_str']['avr']);
            // $year_calculations['ltim_str'] = $year_calculations['ltim_str']['buckets'];



            $auto_monthly_fees_ltim_amount[$i] = $year_calculations['ltim_p'];
            $auto_monthly_fees_ltim_wth[$i] = $ltim_withdrawn;

             // $is_deficit_check = false;


            // when not lowering fees or applying cushion
            if ($is_calculating) {
                 if ($year_calculations['fa'] < 0) {
                    $is_deficit_check = true;
                }


                if (!$is_deficit_check) {
                    if ($year_calculations['fa'] > 0) {
                        // "increase 15% Logic";
                        $auto_monthly_fees_inc[$i] = $rule_mf_perc;

                        // Protect against accessing negative array index and division by zero

                        if ($i == 0) {
                            // First year - compare against original monthly fees
                            $auto_monthly_fees[$i] = $monthly_fees * (1 + $rule_mf_perc);
                            $auto_monthly_fees_inc[$i] = $monthly_fees == 0 ? 0 : ($auto_monthly_fees[$i] - $monthly_fees) / $monthly_fees;
                        } else {
                            // Subsequent years - compare against previous year
                            $auto_monthly_fees[$i] = $auto_monthly_fees[$i - 1] * (1 + $rule_mf_perc);
                            $auto_monthly_fees_inc[$i] = $auto_monthly_fees[$i - 1] == 0 ? 0 : ($auto_monthly_fees[$i] - $auto_monthly_fees[$i - 1]) / $auto_monthly_fees[$i - 1];
                        }
                    }
                }

                // if ($deficit == 0 && $rule_mf_auto) {
                //     $this->adjustFeesForNoDeficitScenario($calculated, $auto_monthly_fees, $monthly_fees, $housing, $period, $simulation_rules);
                // }

                // IF DEFICIT AUTO MF INCREASE
                if (
                    ($is_auto_mf || $rule_mf_auto) && $year_calculations['fa'] < 0 /* && array_has($deficit_years, $i) */
                    && (!array_has($processed_deficits, $i) || (array_has($processed_deficits, $i) && $current_auto_fee == $i))
                ) {

                    // log_info("Deficit Year ".$i." ($current_auto_fee): ".$year_calculations['fa']." INC%: " . $auto_monthly_fees_inc[$i]);

                    $deficit_per_unit = ($year_calculations['fa'] - $loan_amount - $assessment) / (12 * $housing);

                    // echo "Deficit Year $i ($current_auto_fee): " . $year_calculations['fa'] . " INC%: " . $auto_monthly_fees_inc[$i] . " Deficit/Unit: $deficit_per_unit\n";
                    // deal with extra deficit to year using auto-fee
                    // use auto-fee loop counter to avoid infinite loop
                    if ($current_auto_fee == $i && $auto_monthly_fees_inc[$i] < $rule_mf_perc && $auto_fee_remaining_loop_count < $auto_fee_remaining_loop_max) {

                        /*
                        // increase to cover remaining deficit
                        $auto_monthly_fees[$i] += abs($deficit_per_unit);
                        $auto_monthly_fees_inc[$i] = ($auto_monthly_fees[$i] - $auto_monthly_fees[$i - 1]) / $auto_monthly_fees[$i - 1];
                        if($auto_monthly_fees[$i] > $auto_monthly_fees[$i-1] * (1 + $rule_mf_perc)){
                            $auto_monthly_fees[$i] = $auto_monthly_fees[$i-1] * (1 + $rule_mf_perc);
                            $auto_monthly_fees_inc[$i] = $rule_mf_perc;
                        }
                        
                        // log_info("still have to cover: $deficit_per_unit => total % : " . $auto_monthly_fees_inc[$i]);

                        // set new monthly fee to future
                        for($j=$i+1; $j<$period; $j++){
                            
                            if($used_manual_monthly_fees[$j] == true) break;

                            // update all future until manual input
                            $auto_monthly_fees[$j] = $auto_monthly_fees[$i];
                        }

                        $current_auto_fee = -1;
                        $i--; continue;
                        */

                        // log_info("still have to cover: $deficit_per_unit => total % : " . $auto_monthly_fees_inc[$i]);
                        // var_dump("1");
                        if (!isset($custom_range_fees))
                            $custom_range_fees = [];

                        if (!isset($custom_gradual_range_inc))
                            $custom_gradual_range_inc = [];

                        $this->updateMonthlyFeeInc(
                            $i,
                            $deficit_per_unit,
                            $auto_monthly_fees,
                            $auto_monthly_fees_inc,
                            $rule_mf_perc,
                            $monthly_fees,
                            $rule_cushion_fund,
                            $inflation_rate,
                            $rule_mf_auto,
                            $used_manual_monthly_fees, /* $auto_monthly_fees_stop_year */
                            // $cash_reserve_threshold,
                            $cash_reserve_threshold,
                            $simulation_rules,
                            $custom_range_years,
                            $custom_range_fees,
                            $custom_gradual_range_inc,
                            $custom_gradual_range_years,
                            $disable_auto_fee_reduction
                        );



                        array_push($processed_deficits, $i);

                        // log_info("Adjusted MF Deficit -> $i", $auto_monthly_fees);

                        // reset inv_strategies propagation
                        $propagated_inv_strategy = $existing_inv_strategy;

                        $this->initOriginalCalculation(
                            $calculated,
                            $default_calculated,
                            $deficit_years,
                            $starting_amount_o,
                            $monthly_fees,
                            $housing,
                            [],
                            $existing_inv_strategy,
                            $inflation_rate,
                            $spendings,
                            $period
                        );

                        $starting_amount = $starting_amount_o;

                        // increase auto-fee loop counter
                        $auto_fee_remaining_loop_count++;

                        // start over
                        $i = -1;
                        continue;
                    } else if ($current_auto_fee >= 0 || $auto_fee_remaining_loop_count == $auto_fee_remaining_loop_max) {

                        $current_auto_fee = -1;
                        $auto_fee_remaining_loop_count = 0;
                    } else {


                        // var_dump("2");

                        if (!isset($custom_range_fees))
                            $custom_range_fees = [];

                        if (!isset($custom_gradual_range_inc))
                            $custom_gradual_range_inc = [];

                        $this->updateMonthlyFeeInc(
                            $i,
                            $deficit_per_unit,
                            $auto_monthly_fees,
                            $auto_monthly_fees_inc,
                            $rule_mf_perc,
                            $monthly_fees,
                            $rule_cushion_fund,
                            $inflation_rate,
                            $rule_mf_auto,
                            // $used_manual_monthly_fees, /* $auto_monthly_fees_stop_year */
                            $used_manual_monthly_fees, /* $auto_monthly_fees_stop_year */
                            $simulation_rules,
                            $custom_range_years,
                            $custom_range_fees,
                            $custom_gradual_range_inc,
                            $custom_gradual_range_years,
                            $disable_auto_fee_reduction
                        );



                        array_push($processed_deficits, $i);
                        // var_dump($processed_deficits);
                        // log_info("Adjusted MF Deficit -> $i", $auto_monthly_fees);


                        // reset inv_strategies propagation
                        $propagated_inv_strategy = $existing_inv_strategy;

                        $this->initOriginalCalculation(
                            $calculated,
                            $default_calculated,
                            $deficit_years,
                            $starting_amount_o,
                            $monthly_fees,
                            $housing,
                            [],
                            $existing_inv_strategy,
                            $inflation_rate,
                            $spendings,
                            $period
                        );

                        $starting_amount = $starting_amount_o;

                        $current_auto_fee = $i;

                        // init auto-fee loop counter
                        $auto_fee_remaining_loop_count = 0;

                        // start over
                        $i = -1;
                        continue;
                    }
                } else if ($current_auto_fee == $i) {

                    $current_auto_fee = -1;
                }
            }



            // add LOAN and ASSESSMENT seperatly when Auto MF Inc ON
            /*
            if($rule_mf_auto){
                $year_calculations['loan_t'] = $loan_amount;
                $year_calculations['assess'] = $assessment;
                $year_calculations['fa'] += $loan_amount + $assessment;
            }
            */

            // Ensure spending_year_amount is correctly calculated
            $spending_year_amount = $year_calculations['ltim_yoc'];

            // Add these values to the year_calculations array
            $year_calculations['sa'] = $starting_amount;
            $year_calculations['ltim_yoc'] = $spending_year_amount;

            // % Allocated to LTIM
            $year_calculations['ltim_p_perc'] = 0;
            if (!empty($year_calculations['inv']) && $year_calculations['inv'] > 0) {
                $year_calculations['ltim_p_perc'] = round(($year_calculations['ltim_p'] / $year_calculations['inv']) * 100, 2);
            }

            // $year_calculations['ltim_r'] = $ltim_rates[$i] * 100;
            $calculated[$i] = $year_calculations;

            // Add is_deficit boolean to indicate if year has deficit (fa < 0)
            $calculated[$i]['is_deficit'] = ($year_calculations['fa'] < 0);

            //log_info("$starting_amount -> $final_amount");

            // next year starting amount as current year final amout, 0 if negatif
            // $starting_amount = ($year_calculations['fa'] < 0 ? 0 : $year_calculations['fa']);
            $starting_amount = $year_calculations['fa'];

            // REMOVE LTIM ALLOCATED AMOUNT FROM NEXT YEAR'S STARTING AMOUNT
            // if($is_ltim_enabled)$starting_amount -= $year_calculations['ltim_p'];


            // when calculation are over
            if ($i == ($period - 1)) {

                // if all adjustment operation are done, exit calculations
                if ($is_adjusting && empty($adjustment_operations))
                    break;

                // set as adjusting when initial calculation are finished
                if (!$is_calculating) {
                    $is_calculating = false;
                    $is_adjusting = true;
                }

                // get the next adjustment operation
                if (empty($current_adjustment))
                    $current_adjustment = array_shift($adjustment_operations);
                // var_dump($current_adjustment);

                // log_info($current_adjustment);

                // apply cushion
                if ($current_adjustment == 'cushion') {
                    // var_dump($rule_mf_perc);var_dump($cushion_fund_thre);

                    // Adnan Saleem........
                    // if($rule_cushion_fund > 0 && $rule_cushion_fund < $rule_mf_perc && $rule_mf_perc > 0){
                   
                    //Rushi patel
                    // if (
                    //     $cash_reserve_threshold == 0 &&
                    //     (($rule_cushion_fund > 0 && $rule_cushion_fund < $rule_mf_perc && $rule_mf_perc > 0) ||
                    //         ($cushion_fund_thre > 0 && $cushion_fund_thre < $rule_mf_perc && $rule_mf_perc > 0))
                    // ) {

                    // if (
                    //     $cash_reserve_threshold == 0 &&
                    //     (($rule_cushion_fund > 0 && $rule_cushion_fund < $rule_mf_perc && $rule_mf_perc > 0) ||
                    //         ($cushion_fund_thre > 0 && $cushion_fund_thre < $rule_mf_perc && $rule_mf_perc > 0))
                    // ) {
                    if (
                        $cash_reserve_threshold == 0 &&
                        (($rule_cushion_fund > 0 && $rule_mf_perc > 0) ||
                            ($cushion_fund_thre > 0 && $rule_mf_perc > 0))
                    ) {
                        // echo "working";
                        // Adnan Saleem........
                        // reset inv_strategies propagation
                        $propagated_inv_strategy = $existing_inv_strategy;

                        // initialize calculations
                        $this->initOriginalCalculation(
                            $calculated,
                            $default_calculated,
                            $deficit_years,
                            $starting_amount_o,
                            $monthly_fees,
                            $housing,
                            [],
                            $existing_inv_strategy,
                            $inflation_rate,
                            $spendings,
                            $period
                        );



                        // apply cushion to monthly fees

                        for ($y = 0; $y < count($auto_monthly_fees); $y++) {


                            // skip if already reached limit
                            if ($auto_monthly_fees_inc[$y] >= $rule_mf_perc)
                                continue;

                            // if($auto_monthly_fees[$y - 1] >= $rule_mf_perc)

                            $prev_mf = ($y == 0 ? floatval($monthly_fees) : floatval($auto_monthly_fees[$y - 1]));
                            $max_mf = $prev_mf * (1 + floatval($rule_mf_perc));
                            // Adnan Saleem.......
                            // $new_mf =  $prev_mf * (1 + $rule_cushion_fund + $auto_monthly_fees_inc[$y]);
                            $new_mf = $prev_mf * (1 + floatval($rule_cushion_fund) + floatval($auto_monthly_fees_inc[$y]) - floatval($cushion_fund_thre));
                            // Adnan Saleem.......
                            // log_info("$prev_mf, $new_mf");
                            // var_dump($max_mf);var_dump($new_mf);
                            $auto_monthly_fees[$y] = ($new_mf > $max_mf) ? $max_mf : $new_mf;
                        }


                        // unset current_adjustment
                        $current_adjustment = '';

                        // reset starting amount
                        $starting_amount = $starting_amount_o;

                        // start over
                        $i = -1;
                    }

                    // } else if ($cash_reserve_threshold > 0 || ($cash_reserve_threshold > 0 && $rule_cushion_fund > 0)) {
                    //     // echo "working cash reserve threshold";
                    //     // Reset and initialize same as above
                    //     $propagated_inv_strategy = $existing_inv_strategy;

                    //     $this->initOriginalCalculation(
                    //         $calculated,
                    //         $default_calculated,
                    //         $deficit_years,
                    //         $starting_amount_o,
                    //         $monthly_fees,
                    //         $housing,
                    //         [],
                    //         $existing_inv_strategy,
                    //         $inflation_rate,
                    //         $spendings,
                    //         $period
                    //     );
                    //     // echo "starting_amount: " . $starting_amount . "<br>";
                    //     // echo "starting_amount_o: " . $starting_amount_o . "<br>";
                    //     // Apply cash reserve threshold calculations
                    //     for ($y = 0; $y < count($auto_monthly_fees); $y++) {
                    //         if ($auto_monthly_fees_inc[$y] >= $rule_mf_perc)
                    //             continue;

                    //         $prev_mf = ($y == 0 ? floatval($monthly_fees) : floatval($auto_monthly_fees[$y - 1]));
                    //         $cash_reserve_threshold_text = $cash_reserve_threshold * 100;
                    //         $cushion_fund_text = $rule_cushion_fund * 100;
                    //         $cash_reserve_threshold = $cash_reserve_threshold_text + $cushion_fund_text;
                    //         // echo "cash_reserve_threshold_text: " . $cash_reserve_threshold_text . "<br>";
                    //         // echo "cushion_fund_text: " . $cushion_fund_text . "<br>";
                    //         // echo "cash_reserve_threshold: " . $cash_reserve_threshold . "<br>";
                    //         // First adjust starting amount based on cash reserve threshold
                    //         if ($y == 0) {  // Only for first year
                    //             if (empty($cash_reserve_threshold) || preg_match('/^0+$/', $cash_reserve_threshold)) {
                    //                 // $starting_amount_o = $starting_amount_o; // Keep original value
                    //             } else {
                    //                 // Apply cash reserve threshold to starting amount
                    //                 $starting_amount_o = $starting_amount_o * ($cash_reserve_threshold / 100);
                    //             }
                    //         }
                    //         // echo "prev_mf: " . $prev_mf . "<br>";
                    //         // echo "cash_reserve_threshold: " . $cash_reserve_threshold . "<br>";
                    //         // exit();
                    //         if ($y == 0) {
                    //             // Check if cash reserve threshold is empty or zero
                    //             if (empty($cash_reserve_threshold) || preg_match('/^0+$/', $cash_reserve_threshold)) {
                    //                 $effective_monthly_fees = $prev_mf; // Keep the original value
                    //             } else {
                    //                 $effective_monthly_fees = $prev_mf * ($cash_reserve_threshold / 100);
                    //             }
                    //         }

                    //         $max_mf = $prev_mf * (1 + floatval($rule_mf_perc));
                    //         $auto_monthly_fees[$y] = ($effective_monthly_fees > $max_mf) ? $max_mf : $effective_monthly_fees;
                    //     }

                    //     $current_adjustment = '';
                    //     $starting_amount = $starting_amount_o;
                    //     $i = -1;
                    // }
                }
            }
        }



        // sort 'propagated_inv_strategy' by highest parent then lowest index 
        // this way the newest startegy is at the top and oldest at the bottom, each year
        foreach ($calculated as $year => $c) {
            usort($calculated[$year]['is'], function ($a, $b) {

                if ($a['parent'] == $b['parent']) {
                    // parent is the same, sort by index
                    if ($a['index'] > $b['index']) {
                        return 1;
                    }
                }

                // sort the higher score first:
                return $a['parent'] < $b['parent'] ? 1 : -1;
            });
        }


        if ($last_deficit >= 0)
            log_info("Last Deficit LTIM " . ($is_ltim_enabled ? "ON" : "OFF") . " @ " . ($model['fiscal_year'] + $last_deficit));



        $saved_versions = check_val(get_element($simVersionTable, ['model_id' => $model['id'], 'user_id' => $auth->uid()], "COUNT(id) AS count"), 'count', 0);


        $results = [
            "model" => $model,
            "simulation_rules" => $simulation_rules,
            "items" => $model_items,
            "spendings" => $spending_data,
            "calculated" => $calculated,
            "saved_versions" => $saved_versions,
            "remaining_loans" => $remaining_loan_payments,
            "last_deficit" => $last_deficit
        ];

        $results['ltims'] = [];
        foreach ($this->ltim_strategy as $state => $infos) {
            $results['ltims'][$state] = $infos['name'];
        };
        $results['inv_strategies'] = $this->inv_strategies;
        if ($results['inv_strategies']['bbp'])
            unset($results['inv_strategies']['bbp']);

      // Prepare formatted_calculation with descriptive keys
        $key_mapping = [
            'sa'        => 'starting_amount',
            'yc'        => 'yearly_collections',
            'mf'        => 'monthly_fee',
            'mf_m'      => 'monthly_fee_manual',
            'ta'        => 'total_amount',
            'ih'        => 'held_amount',
            'ipn'       => 'early_penalty',
            'is'        => 'investment_strategies',
            'ip'        => 'investment_principal',
            'inv'       => 'total_invested',
            'ne'        => 'net_earnings',
            'pip'       => 'principal_withdrawn',
            'pne'       => 'principal_net_earnings',
            'cp'        => 'compound',
            'lp'        => 'loss_purchase_power',
            'sp'        => 'spending',

            //LOANS
            'loan_t'    => 'loan_total',
            'loan_pay'  => 'loan_payment',
            'loan_i'    => 'loan_interest',
            'loan_y'    => 'loan_remaining_balance',

            // FINALS
            'tx'        => 'total_expenses',
            'fa'        => 'final_amount',
            'deficit'   => 'deficit',
            'assess'    => 'assessment',

            // LTIM
            'ltim_acc'  => 'ltim_accumulated',
            'ltim_p'    => 'ltim_principal',
            'ltim_ne'   => 'ltim_net_earnings',
            'ltim_str'  => 'ltim_strategy',
            'ltim_yoc'  => 'ltim_years_of_cash',
            'ltim_spl'  => 'ltim_surplus',

            'ltim_p_perc' => 'ltim_percent_allocated',
            'lopp_rate'   => 'loss_of_purchase_power_rate',
            'loan_balance'=> 'remaining_loan_balance',
        ];

        $results['formatted_calculation'] = [];

        foreach ($calculated as $year_idx => $year_data) {
            $formatted_year = [];
            foreach ($year_data as $key => $value) {
                if (array_key_exists($key, $key_mapping)) {
                    $formatted_year[$key_mapping[$key]] = $value;
                } else {
                    // preserve unmapped keys
                    $formatted_year[$key] = $value;
                }
            }
            $results['formatted_calculation'][$year_idx] = $formatted_year;
        }

        // return $results;
        if($internal_call){
            return $results;
        }
        return send_json_response(true, 200, $this->success['getted'], ['data' => $results]);
    }

    public function list_association($data)
    {
        global $auth, $clientsTable;


        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);

        $conds = ['type' => 'client'];

        // if company
        if ($auth->clientType() == 'company') {
            $conds['company_id'] = $auth->clientId();
        } else if ($auth->clientType() == 'client')
            $conds['id'] = $auth->clientId();

        if ($is_pagination) {
            $count = get_element($clientsTable, $conds, "COUNT($clientsTable.id) AS count");
            if (isset($count['count']))
                $total = (int) (intval($count['count']) / $pagination['size']) + 1;
        }


        $results = get_elements(
            $clientsTable,
            $conds,
            "$clientsTable.id, $clientsTable.association",
            "ORDER BY $clientsTable.association ASC" . check_val($pagination, 'query')
        );


        if ($is_pagination)
            // return ['last_page' => $total, 'data' => $results, 'total' => $count['count']];
            return send_json_response(true, 200, $this->success['getted'], ['last_page' => $total, 'data' => $results, 'total' => $count['count']]);
        else
            return send_json_response(true, 200, $this->success['getted'], ['data' => $results]);
    }


    public function list_model($data)
    {
        global $auth, $modelsTable;


        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);

        $conds = array();
        $conds['client_id'] = $auth->checkRole('client_admin') || $auth->checkRole('client_user') ? $auth->clientId() : check_val($data, 'client_id', $auth->clientId());

        if ($is_pagination) {
            $count = get_element($modelsTable, $conds, "COUNT($modelsTable.id) AS count");
            if (isset($count['count']))
                $total = (int) (intval($count['count']) / $pagination['size']) + 1;
        }

        $results = get_elements(
            $modelsTable,
            $conds,
            "$modelsTable.id, $modelsTable.name, $modelsTable.fiscal_year, $modelsTable.created_at",
            "ORDER BY $modelsTable.fiscal_year DESC" . check_val($pagination, 'query')
        );


        for ($i = 0; $i < count($results); $i++) {
            if (strlen($results[$i]['fiscal_year']) < 3) {
                $results[$i]['fiscal_year'] = date("Y", intval($results[$i]['created_at']));
            }
        }

        if ($is_pagination)
            // return ['last_page' => $total, 'data' => $results, 'total' => $count['count']];
            return send_json_response(true, 200, $this->success['getted'], ['last_page' => $total, 'data' => $results, 'total' => $count['count']]);
        else
            return send_json_response(true, 200, $this->success['getted'], ['data' => $results]);
    }

    public function set($data)
    {
        global $auth, $modelsTable;

        if (!is_valid($data, 'id')) {
            return ['error' => ''];
        }


        if (!belongs_to_client($modelsTable, $data['id'], false, true))
            return ['error' => $this->errors['not_allowed']];


        return set_property($modelsTable, $data);
    }

    private function initOriginalCalculation(
        &$calculated,
        &$default_calculated,
        &$deficit_years,
        $starting_amount_o,
        $monthly_fees,
        $housing,
        $invest_strategies,
        $propagated_inv_strategy,
        $inflation_rate,
        &$spendings,
        $period,
        $start_year = 0,
        $simulation_rules = []
    ) {

        // reset all calulated year data staring $start_year to $period
        $calculated = array_fill($start_year, $period, []);

        // init $propagated_inv_strategy
        // $propagated_inv_strategy = array_fill($start_year, $period, []);

        // fill original unmanaged data
        for ($i = $start_year; $i < $period; $i++) {

            // get default calcuated data (sp + lp)
            $calculated[$i] = $default_calculated[$i];

            // get any future year_calculations      
            $year_calculations = $calculated[$i];
            $year_calculations["year"] = $i;


            // current year spendings, array index starts at 0
            $spending = $spendings[$i];

            // investments
            $invest_strategy = check_val($invest_strategies, $i, []);

            // original unmanaged calculations
            $this->calculateYearData(
                $starting_amount_o,
                $monthly_fees,
                $housing,
                $invest_strategy,
                $propagated_inv_strategy,
                // $year_calculations["lp"],
                $inflation_rate,
                0,
                0,
                0,
                $spending,
                [],
                0,
                -1,
                0,
                0,
                [],
                false,
                $year_calculations,
                $i,
                "_o",
                $simulation_rules
            );

            // add to calculated                                        
            $calculated[$i] = $year_calculations;

            // calculate auto monthly fees inc

            // add unmanaged year with deficit to deficit_years
            if ($year_calculations['fa_o'] < 0)
                array_push($deficit_years, $i);


            // $starting_amount_o = $year_calculations['fa_o'] < 0 ? 0 : $year_calculations['fa_o'];
            $starting_amount_o = $year_calculations['fa_o'];

            // Check if there are no deficits in any year and adjust fees accordingly
            // Only when "Optimize All Monthly Fees" toggle is ON
            // if ($rule_mf_auto) {
            // $this->adjustFeesForNoDeficitScenario($calculated, $auto_monthly_fees, $monthly_fees, $housing, $period, $simulation_rules);
            // }
        }
    }

    /**
     * Adjust monthly fees when there are no deficits in any year
     * This method finds the exact minimum fee that prevents deficits
     * Only works when "Optimize All Monthly Fees" toggle is ON
     */
    private function adjustFeesForNoDeficitScenario(&$calculated, &$auto_monthly_fees, $original_monthly_fees, $housing, $period, $simulation_rules){
        // Check if there are any deficit years
        $has_deficits = false;

        for ($i = 0; $i < $period; $i++) {
            $final_amount = isset($calculated[$i]['fa']) ? $calculated[$i]['fa'] :
                (isset($calculated[$i]['fa_o']) ? $calculated[$i]['fa_o'] : 0);

            if ($final_amount < 0) {
                $has_deficits = true;
                break;
            }
        }

        // If there are no deficits, find individual optimal fees for each year
        if (!$has_deficits) {

            // Calculate total expenses for all years
            $total_expenses = 0;
            $final_amount = 0; // Track starting amount

            for ($i = 0; $i < $period; $i++) {

                $spending = isset($calculated[$i]['sp']) ? abs($calculated[$i]['sp']) : 0;
                $loss_purchase = isset($calculated[$i]['lp']) ? abs($calculated[$i]['lp']) : 0;

                // CRITICAL: Include ALL income sources that reduce required fees
                $investment_income = isset($calculated[$i]['inv_ne']) ? abs($calculated[$i]['inv_ne']) : 0;
                $ltim_income = isset($calculated[$i]['ltim_ne']) ? abs($calculated[$i]['ltim_ne']) : 0;
                $assessments = isset($calculated[$i]['assessment']) ? abs($calculated[$i]['assessment']) : 0;
                $loans = isset($calculated[$i]['loan_amount']) ? abs($calculated[$i]['loan_amount']) : 0;
                $other_income = isset($calculated[$i]['other_income']) ? abs($calculated[$i]['other_income']) : 0;

                // NEW: Include starting amount for first year
                if ($i == 0) {
                    $final_amount = isset($calculated[$i]['sa']) ? $calculated[$i]['sa'] :
                        (isset($calculated[$i]['sa_o']) ? $calculated[$i]['sa_o'] : 0);
                }

                // Calculate NET expenses (expenses minus income)
                $net_expenses = $spending + $loss_purchase - $investment_income - $ltim_income - $assessments - $loans - $other_income;

                // Only add positive net expenses
                if ($net_expenses > 0) {
                    $total_expenses += $net_expenses;
                }
            }

            // Calculate initial minimum required monthly fee
            $total_months = $period * 12;
            $min_monthly_fee = $total_months > 0 ? ceil($total_expenses / ($total_months * $housing)) : $original_monthly_fees;

            // NEW: Calculate individual fees for each year based on their specific expenses
            $individual_fees = [];
            $test_starting = $final_amount;

            for ($i = 0; $i < $period; $i++) {

                // Get year-specific expenses and income
                $spending = isset($calculated[$i]['sp']) ? abs($calculated[$i]['sp']) : 0;
                $loss_purchase = isset($calculated[$i]['lp']) ? abs($calculated[$i]['lp']) : 0;
                $investment_income = isset($calculated[$i]['inv_ne']) ? abs($calculated[$i]['inv_ne']) : 0;
                $ltim_income = isset($calculated[$i]['ltim_ne']) ? abs($calculated[$i]['ltim_ne']) : 0;
                $assessments = isset($calculated[$i]['assessment']) ? abs($calculated[$i]['assessment']) : 0;
                $loans = isset($calculated[$i]['loan_amount']) ? abs($calculated[$i]['loan_amount']) : 0;
                $other_income = isset($calculated[$i]['other_income']) ? abs($calculated[$i]['other_income']) : 0;

                // Calculate year-specific net expenses
                $year_net_expenses = $spending + $loss_purchase - $investment_income - $ltim_income - $assessments - $loans - $other_income;

                // Calculate year-specific monthly fee
                if ($year_net_expenses > 0) {
                    // This year needs fees to cover expenses
                    $year_monthly_fee = ceil($year_net_expenses / (12 * $housing));

                    // Ensure it's not less than minimum
                    if ($year_monthly_fee < $min_monthly_fee) {
                        $year_monthly_fee = $min_monthly_fee;
                    }
                } else {
                    // This year has surplus, use minimum fee or previous year's fee
                    $year_monthly_fee = $i > 0 ? $individual_fees[$i - 1] : $min_monthly_fee;
                }

                // Apply progressive fee adjustment based on year position
                if ($i > 0) {

                    $prev_fee = $individual_fees[$i - 1];
                    // Allow some variation but maintain reasonable progression
                    $max_increase = $prev_fee * 0.15; // Max 15% increase
                    $max_decrease = $prev_fee * 0.10; // Max 10% decrease

                    if ($year_monthly_fee > $prev_fee + $max_increase) {
                        $year_monthly_fee = $prev_fee + $max_increase;
                    } elseif ($year_monthly_fee < $prev_fee - $max_decrease) {
                        $year_monthly_fee = $prev_fee - $max_decrease;
                    }
                }
                $individual_fees[$i] = $year_monthly_fee;
            }

            // NEW: Binary search to find exact minimum fees that prevent deficits for each year
            $optimal_fees = [];

            for ($i = 0; $i < $period; $i++) {

                $test_fee = $individual_fees[$i];
                $optimal_fee = $test_fee;

                // Test fees from calculated minimum down to $1
                while ($test_fee > 0) {
                    $test_fee--; // Decrease by $1

                    // Test if this fee creates any deficits
                    $creates_deficit = false;
                    $test_starting = $final_amount;

                    for ($j = 0; $j < $period; $j++) {
                        // Use the test fee for current year, optimal fees for other years
                        $current_year_fee = ($j == $i) ? $test_fee : ($optimal_fees[$j] ?? $individual_fees[$j]);

                        // Calculate what the final amount would be with this test fee
                        $monthly_collection = $current_year_fee * 12 * $housing;
                        $spending = isset($calculated[$j]['sp']) ? abs($calculated[$j]['sp']) : 0;
                        $loss_purchase = isset($calculated[$j]['lp']) ? abs($calculated[$j]['lp']) : 0;

                        // Include income sources
                        $investment_income = isset($calculated[$j]['inv_ne']) ? abs($calculated[$j]['inv_ne']) : 0;
                        $ltim_income = isset($calculated[$j]['ltim_ne']) ? abs($calculated[$j]['ltim_ne']) : 0;
                        $assessments = isset($calculated[$j]['assessment']) ? abs($calculated[$j]['assessment']) : 0;
                        $loans = isset($calculated[$j]['loan_amount']) ? abs($calculated[$j]['loan_amount']) : 0;
                        $other_income = isset($calculated[$j]['other_income']) ? abs($calculated[$j]['other_income']) : 0;

                        // Calculate test final amount
                        $test_final = $test_starting + $monthly_collection + $assessments + $loans + $investment_income + $ltim_income + $other_income - $spending - $loss_purchase;

                        if ($test_final < 0) {
                            $creates_deficit = true;
                            break;
                        }

                        $test_starting = $test_final; // Next year's starting amount
                    }

                    // If this fee creates a deficit, use the previous fee as optimal
                    if ($creates_deficit) {
                        $optimal_fee = $test_fee + 1; // Go back to the fee that didn't create deficit
                        break;
                    }
                    $optimal_fee = $test_fee;
                }
                $optimal_fees[$i] = $optimal_fee;
            }

            // Only adjust if any of the optimal fees are less than the original fees
            $should_adjust = false;
            for ($i = 0; $i < $period; $i++) {
                if ($optimal_fees[$i] < $original_monthly_fees) {
                    $should_adjust = true;
                    break;
                }
            }

            if ($should_adjust) {
                // Update each year with its individual optimal fee
                for ($i = 0; $i < $period; $i++) {
                    $auto_monthly_fees[$i] = $optimal_fees[$i];
                }

                // CRITICAL: Also update the calculated array to ensure frontend displays correctly
                for ($i = 0; $i < $period; $i++) {
                    if (isset($calculated[$i])) {
                        $calculated[$i]['monthly_fee'] = $optimal_fees[$i];
                        $calculated[$i]['mf'] = $optimal_fees[$i];
                    }
                }
            }

        }
    }

    public function calculateYearData(
        $starting_amount,
        $monthly_fees,
        $housing,
        $invest_strategy,
        &$propagated_inv_strategy,
        //$loss_purchase,
        $inflation_rate,
        $loan,
        $prev_loan_payment,
        $assessment,
        $spending,
        $erase_deficit,
        $ltim_perc = 0,
        $ltim_amount = -1,
        $ltim_wth = 0,
        $ltim_yoc = 0,
        $ltim_str = [],
        $impl_ltim = false,
        &$year_calculations,
        $year,
        $suffix = "",
        $simulation_rules = []
    ) {
        // Ensure inflation rate is a valid number
        if (!is_numeric($inflation_rate)) {
            $inflation_rate = 0;
        }
        // add LTIM withdrawn amout to starting amount
        $starting_amount += $ltim_wth;

        // Apply safety net to spending
        $cushion_fund = floatval(check_val($simulation_rules, 'cushion_fund', 0));
        $cushion_fund = $cushion_fund * 100;

        // Apply cash_reserve_threshold to spending
        $cash_reserve_threshold = floatval(check_val($simulation_rules, 'cash_reserve_threshold', 0));
        // echo "spending: " . $spending . "<br>";

        if ($cash_reserve_threshold > 0 || $cushion_fund > 0) {

            // Check if cushion_fund exceeds 15%
            // if ($cushion_fund > 15 && $cash_reserve_threshold == 0) {
            //     $cushion_fund = 0; // Ignore cushion_fund if it exceeds 15%
            // }

            $cash_reserve_threshold = $cash_reserve_threshold * 100;
            $cash_reserve_threshold = $cushion_fund + $cash_reserve_threshold;
            // echo "cash_reserve_threshold: " . $cash_reserve_threshold . "<br>";
            // $spending = $spending * ($cash_reserve_threshold / 100);
            if ($cash_reserve_threshold > 100) {
                $cash_reserve_threshold = 100;
            }
            $spending = $spending * ((100 - $cash_reserve_threshold) / 100);
        }
        // echo "spending after cash reserve threshold: " . $spending . "<br>";

        // withdraw investments from existing strategy 
        $withdrawn_principal = 0;
        $withdrawn_net_earnings = 0;


        // get early withdrawn investments
        if (!empty($propagated_inv_strategy[$year])) {
            foreach ($propagated_inv_strategy[$year] as $s) {
                if (check_val($s, 'wthd', 0) == 1) {
                    $withdrawn_principal += check_val($s, 'amount', 0);
                    $withdrawn_net_earnings += check_val($s, 'principal', 0) - check_val($s, 'amount', 0) - check_val($s, 'penalty', 0);
                }
            }
        }


        /*
        // get matured investments
        if($year - 1 >= 0 && !empty($propagated_inv_strategy[$year - 1])){
            foreach($propagated_inv_strategy[$year - 1] as $s){
                if(check_val($s, 'wthd', 0) == 1 && check_val($s, 'early', -1) == -1){
                    $withdrawn_principal += check_val($s, 'amount', 0);
                    $withdrawn_net_earnings += check_val($s, 'earned', 0) - check_val($s, 'penalty', 0);
                }
            }
        }   

        // get early withdrawn investments
        if(!empty($propagated_inv_strategy[$year])){
            foreach($propagated_inv_strategy[$year] as $s){
                if(check_val($s, 'wthd', 0) == 1 && check_val($s, 'early', -1) > -1){
                    $withdrawn_principal += check_val($s, 'amount', 0);
                    $withdrawn_net_earnings += check_val($s, 'earned', 0) - check_val($s, 'penalty', 0);
                }
            }
        }
        */




        // yearly collections
        $yearly_collections = $monthly_fees * 12 * $housing;

        // loss in purchase power due to inflation
        // $loss_purchase = /* -1 * */ ceil($spending * ($inflation_rate)); /*$compound - ceil($total_amount * (1 + $inflation_rate)); */ 
        // if($loss_purchase > 0) $loss_purchase = 0;

        // expenses
        $total_expenses = /* $loss_purchase + */ $spending;


        // total available strating amount
        $total_amount = ceil($starting_amount + $yearly_collections + $assessment + $withdrawn_principal + $withdrawn_net_earnings /* - $ltim_amount */);


        // loss in purchase power
        $loss_purchase = $inflation_rate * ($starting_amount > 0 ? -1 * $starting_amount : 0);

        //log_info("$total_amount  * $invest_rate");


        // Investment Strategies
        // var_dump('1 '. $total_amount . ' 2 '. $total_expenses. ' 3 '. $prev_loan_payment . ' 4 ' . $loss_purchase);

        // Adnan saleem.........
        // $inv_principal = ($total_amount < 0 ? 0 : $total_amount) + ($total_expenses + $prev_loan_payment + $loss_purchase); if($inv_principal < 0)$inv_principal = 0;

        // $inv_principal = ($starting_amount < 0 ? 0 : $starting_amount) + $yearly_collections /* - $ltim_amount */; if($inv_principal < 0)$inv_principal = 0;
        $inv_principal = ($starting_amount < 0 ? 0 : $starting_amount) + ($spending + $loss_purchase) /* - $ltim_amount */;

        if ($inv_principal < 0)
            $inv_principal = 0;
        // var_dump('1 '. $starting_amount . ' 2 '. $spending. ' 3 '. $loss_purchase . ' 4 ' . $inv_principal);
        // Adnan saleem.........
        $total_invested = 0;
        $invest_rate = 0;
        $net_earning = 0;
        $this->calculateClientInvStrategy($invest_strategy, $inv_principal, $ret_strategies, $amount_to_deduce, $early_penalty);

        // deduce held principal for investments
        $total_amount -= $amount_to_deduce;

        // printf("Total Amount: %.2f", $total_amount);
        // print_r($total_amount);
        // log_info("$total_amount - $inv_principal ", $ret_strategies);

        // add propagated inv strategy
        // $ret_strategies = array_reverse($ret_strategies); // reverse order to prepend current year strategy over old ones
        // var_dump($ret_strategies);
        foreach ($ret_strategies as $ret_strategy) {

            // log_info($ret_strategy);

            $total_invested += $ret_strategy['amount'];

            // if has yearly values, spread across period
            if (isset($ret_strategy['yearly']) && !empty($ret_strategy['yearly'])) {

                // if($ret_strategy['type'] != 'bbp')log_info($ret_strategy);

                // the current year as the startegy start
                $inv_y = $year;

                // strategy total P+I
                $ret_strategy_p = $ret_strategy['total'];

                // strategy total Earning
                $ret_strategy_ne = $ret_strategy['earned'];

                // for each year of the strategy
                foreach ($ret_strategy['yearly'] as $y => $yearly_details) {

                    // if current year has an amount available to withdraw, take the intrests to current year
                    if ($y == 0 && check_val($yearly_details, 'wthd', 0) == 1) {
                        $net_earning += check_val($yearly_details, 'earned', 0);
                    }

                    // IMPORTANT: When LTIM withraws money, it repeats the year's calculations
                    // and that made a crazy bug where investments processed that year, pile up in the
                    // $propagated_inv_strategy. So to fix it, we'll add year processed to $propagated_inv_strategy
                    // so we'll be able to remove them and avoid double processing

                    // merge parent infos with child infos
                    array_push($propagated_inv_strategy[$inv_y], $yearly_details + ['yproc' => $year, 'total_pi' => $ret_strategy_p, 'total_ne' => $ret_strategy_ne]);

                    if (++$inv_y >= count($propagated_inv_strategy))
                        break;
                }
            }
        }

        // log_info("$year -> ad= $amount_to_deduce, ne = $net_earning, prev_ne = $withdrawn_net_earnings");



        // LTIM Surplus
        $ltim_spl = $total_amount - $ltim_yoc + $loss_purchase;
        if ($ltim_spl < 0)
            $ltim_spl = 0;

        // LTIM Principal, choose $ltim_amount instead of $ltim_perc if >= 0, 
        // and then $ltim_spl instead of $ltim_amount if > $ltim_spl
        $ltim_p = $impl_ltim && $ltim_spl > 0 ? ($ltim_amount >= 0 ? ($ltim_amount < $ltim_spl ? $ltim_amount : $ltim_spl)
            : $ltim_perc * $ltim_spl)
            : 0;


        // LTIM Earnings            
        $ltim_ne = $ltim_p * check_val($ltim_str, 'avr', 0);



        // compound starting amount from investment
        // $compound = ceil(($total_amount < 0 ? 0 : $total_amount) + $net_earning + $loan  /* + $assessment */);
        $compound = ceil($total_amount + $net_earning + $loan  /* + $assessment */);


        //$inv_loss_purchase = $investment - ceil($inv_total_amount * ($inflation_rate)); if($inv_loss_purchase > 0) $inv_loss_purchase = 0;

        // remaining amount after spending
        // log_info($compound , $total_expenses , $prev_loan_payment, $loss_purchase);
        $final_amount = $compound + $total_expenses + $prev_loan_payment + $loss_purchase - $ltim_p;
        // var_dump('1 '. $compound . ' 2 '. $total_expenses. ' 3 '. $prev_loan_payment . ' 4 ' . $loss_purchase . ' 5 ' . $ltim_p);

        // log_info("$total_amount ($starting_amount + $yearly_collections) + $net_earning  + $assessment + $loan = $compound");


        // $final_amount += $assessment + $ltim_amount + $ltim_i; // - check_val($erase_deficit, 'ltim', 0);

        if ($final_amount >= -1 && $final_amount < 0)
            $final_amount = 0;

        $tmp_year_calculations = [

            "sa$suffix" => $starting_amount,
            "yc$suffix" => $yearly_collections,
            "mf$suffix" => $monthly_fees,


            "ta$suffix" => $total_amount,

            "ih$suffix" => $amount_to_deduce * -1,
            "ipn$suffix" => $early_penalty * -1,

            "is$suffix" => $propagated_inv_strategy[$year],
            "ip$suffix" => $inv_principal,
            "inv$suffix" => $total_invested,

            "ne$suffix" => $net_earning,
            "pip$suffix" => $withdrawn_principal,
            "pne$suffix" => $withdrawn_net_earnings,
            "cp$suffix" => $compound,

            "lp$suffix" => $loss_purchase,
            "sp$suffix" => $spending,

            "loan_t" => $loan,
            "loan_pay" => $prev_loan_payment,

            "tx$suffix" => $total_expenses,

            "fa$suffix" => $final_amount,

            "deficit" => $erase_deficit,

            "assess" => $assessment,

            "ltim_yoc" => $impl_ltim ? $ltim_yoc : 0,
            "ltim_spl" => $impl_ltim ? $ltim_spl : 0,
            "ltim_p" => $impl_ltim ? $ltim_p : 0,
            "ltim_ne" => $impl_ltim ? $ltim_ne : 0,
            "ltim_str" => $impl_ltim ? $ltim_str : 0,
            "loan_i" => isset($year_calculations['loan_i']) ? $year_calculations['loan_i'] : 0

        ];

        $year_calculations = array_merge($year_calculations, $tmp_year_calculations);

        return $tmp_year_calculations;
    }

    public function loanMonthlyPayment($amount, $interest, $numOfMonths)
    {

        if ($numOfMonths < 1)
            return $numOfMonths = 12;

        $rate = $interest / 12;
        $rate = round($rate, 7);

        if (empty($interest) || $rate <= 0)
            return $amount / $numOfMonths;

        // $monthlyPayment = ($rate + $rate / (pow($rate + 1, $numOfMonths) - 1)) * $amount;
        $monthlyPayment = $amount * $rate * pow(1 + $rate, $numOfMonths) / (pow(1 + $rate, $numOfMonths) - 1);
        $monthlyPayment = round($monthlyPayment, 4);

        return $monthlyPayment;
    }


    private function calculateLoan(&$arr, $amount = 0, $bank_rate = 0, $loan_years = 1, $from = 0, $period = 1, $addPaymentsToArr = true)
    {

        // if($amount < 0){
        //     $remaining_payments = 0;
        //     $loan = 0;
        //     $yearly_payment = 0;
        //     return;
        // }

        // log_info('LOAN', $amount, $bank_rate, $loan_years, $from);


        // get monthly payment of the given amount
        $payment = abs($this->loanMonthlyPayment($amount, $bank_rate, $loan_years * 12));


        $remaining_payments = 0;        // calculate remaining payments if loan years exceeds calculation period      
        $to = ($loan_years + $from + 1);    // get the last payment

        if ($to > $period) {
            $remaining_payments = $to - $period - 1;
            $to = $period;
        } // if exceeds period, set period as limit


        // start loan payemnt in the next year
        $from++;
        if ($from > $to)
            $from = $to;

        //log_info("$amount ($bank_rate) -> $payment : [$from -> $to] / $tmp_remaining_payments");

        // add loan yearly payment as reference
        $yearly_payment = $payment * 12;
        $loan = $yearly_payment * $loan_years;

        // add current payment to calculated loan_pay per calculated year
        if ($addPaymentsToArr) {
            for ($i = $from; $i < $to; $i++) {
                $arr[$i] += $yearly_payment;
            }
        }

        $remaining_payments *= $yearly_payment;

        return ['total' => $loan, 'total_i' => $loan - $amount, 'yearly' => $yearly_payment, 'remaining' => $remaining_payments];


        //log_info("$amount $loan, $yearly_payment $remaining_payments");

    }

    private function calculateClientInvStrategy($strategies, $inv_principal, &$ret_strategies, &$amount_to_deduce = 0, &$early_penalty = 0)
    {
        $net_earning = 0;

        // if unvalid strategy
        if (!is_array($strategies)) {
            $strategies = [];
        }

        // if single strategy 
        if (is_assoc($strategies))
            $strategies = [$strategies];


        $ret_strategies = [];

        $perc_used = 0;

        // process strategy
        $cb = function ($s, &$pu, &$ad, &$pn) use ($inv_principal) {


            // inv type
            $type = check_val($s, 'type');
            $type_name = check_val($s, 'type_name', check_val($this->inv_strategies, "$type/name", 'Investment Startegy'));

            // does strategy hold funds
            $hold = check_val($this->inv_strategies, "$type/hold", 0) == 1;

            // are intrests paid by end of the year, or during the year. Used typically in Simple intrest when they're paid monthly
            $end_of_year = check_val($this->inv_strategies, "$type/end_of_year", 1) == 1;


            // inv perc and rate
            $rate = check_val($s, 'rate', 0) / 100;
            if ($rate < 0) {
                $rate = 0;
            }
            $inv_amount = intval(check_val($s, 'amount', 0));
            if ($inv_amount < 1) {
                $inv_amount = 0;
            }
            if ($inv_principal > 0) {
                $perc = $inv_amount >= 1 ? ($inv_amount / $inv_principal) : check_val($s, 'perc', 0) / 100;
                if ($perc > 1) {
                    $perc = 1;
                } else if ($perc < 0) {
                    $perc = 0;
                }
            } else {
                $perc = 0;
            }

            // duration to run the calculations for
            $dur = $hold ? check_val($s, 'dur', 1) : 1;
            if ($dur < 1) {
                $dur = 1;
            } // how long to run the calculations

            // inv actual terms
            $terms = check_val($s, 'terms', $dur); // used in 'yearly'
            $not_hold_year = check_val($s, 'year', 0);

            // startegy name
            $name = check_val($s, 'name');
            $note = check_val($s, 'note');



            $compounding_frequency = 1;

            // percentage to amount, when less than 100
            $enough_funds = $pu + $perc <= 1;

            // amount actually invested
            $amount = round($enough_funds ? $inv_principal * $perc : 0);

            // parent strategy index
            $extra = ['early' => -1, 'penalty' => 0];
            if (isset($s['parent']))
                $extra['parent'] = $s['parent'];  // parent year
            if (isset($s['index']))
                $extra['index'] = $s['index'];  // parent inv_stratgery index


            $final = $amount;   // final P+I 
            $ne = 0;            // Total Earned
            $yearly = [];       // Yearly details of investment

            // HOLD type
            if ($hold) {

                // when funds available
                if ($perc > 0 && $amount > 0) {

                    // inc pecentage used to avoid over allocation
                    $pu += $perc;


                    // early withdrawl
                    $y_wth = intval(check_val($s, 'y_wth', -1));
                    if ($y_wth > $dur)
                        $y_wth = $dur;


                    // change duration to year_withrawl
                    $inv_dur = $y_wth < 0 ? $dur : $y_wth;

                    // early withdrawl penalty
                    $pen = 0;


                    // on early with drawn
                    if ($y_wth >= 0 && $y_wth < $dur) {

                        // add early withrawl penalty
                        $pen_days = check_val($s, 'pd', 0);
                        if ($pen_days < 1)
                            $pen_days = 0;
                        $pen_min = check_val($s, 'pm', 0);
                        if ($pen_min < 0)
                            $pen_min = 0;

                        // $withrawn = round($amount * pow(1 + ($rate/$compounding_frequency), $y_wth * $compounding_frequency));

                        $pen = ($rate / 365) * $pen_days * $amount;  // penalty
                        if ($pen_min > 0 && $pen < $pen_min)
                            $pen = $pen_min;

                        // add to total penalties
                        $pn += $pen;


                        // set year of early withdraw and penalty
                        $extra['early'] = $y_wth;
                        $extra['penalty'] = $pen;
                    }

                    // log_info("$note -> $inv_dur");

                    // for each year
                    for ($i = 0; $i < $inv_dur; $i++) {


                        // calculate year's Principal
                        $new_principal = round($amount * pow(1 + ($rate / $compounding_frequency), $i * $compounding_frequency));

                        // calculate year's P+I
                        $final = round($amount * pow(1 + ($rate / $compounding_frequency), ($i + 1) * $compounding_frequency));


                        // add to yearly details
                        array_push($yearly, [
                            'type' => $type,
                            'type_name' => $type_name,
                            'year' => $i,
                            'terms' => $terms,
                            'hold' => 1,
                            'rate' => $rate,
                            'perc' => $perc,
                            'no_fund' => 0,
                            'amount' => $amount,
                            'principal' => $new_principal,
                            'total' => $final,
                            'earned' => ($final - $amount),
                            'end_of_year' => $end_of_year ? 1 : 0,
                            'wthd' => 0,
                            'note' => $note,
                            'name' => $name
                        ] + $extra);
                    }

                    // add to yearly last year of withdraw
                    array_push($yearly, [
                        'type' => $type,
                        'type_name' => $type_name,
                        'year' => intval($inv_dur),
                        'terms' => $terms,
                        'hold' => 1,
                        'rate' => $rate,
                        'perc' => $perc,
                        'no_fund' => 0,
                        'amount' => $amount,
                        'principal' => $final,
                        'total' => 0,
                        'earned' => 0,
                        'end_of_year' => $end_of_year ? 1 : 0,
                        'wthd' => 1,
                        'note' => $note,
                        'name' => $name
                    ] + $extra);


                    // calculate total net earnings
                    $ne = $final - $amount;


                    // add amount to amount_to_deduce when the investment strategy hold the amount when terms > 1
                    // if($inv_dur > 1)$ad += $amount;
                    $ad += $amount;
                } else {
                    array_push($yearly, [
                        'type' => $type,
                        'type_name' => $type_name,
                        'year' => 0,
                        'terms' => $terms,
                        'hold' => 1,
                        'rate' => $rate,
                        'perc' => 0,
                        'no_fund' => 0,
                        'amount' => 0,
                        'principal' => 0,
                        'total' => 0,
                        'earned' => 0,
                        'end_of_year' => $end_of_year ? 1 : 0,
                        'early' => 0,
                        'wthd' => 1,
                        'note' => $note,
                        'name' => $name
                    ] + $extra);
                }
            }


            // NOT HOLD Type
            else {
                // when funds available
                if ($perc > 0 && $amount > 0) {

                    // inc pecentage used to avoid over allocation
                    $pu += $perc;

                    // calculate year's P+I
                    $final = round($amount * pow(1 + ($rate / $compounding_frequency), $compounding_frequency));

                    // calculate total net earnings
                    $ne = $final - $amount;


                    // if intrests are paid by the end of the year, 
                    // deduct invested amount and add
                    // add to yearly details
                    array_push($yearly, [
                        'type' => $type,
                        'type_name' => $type_name,
                        'year' => $not_hold_year,
                        'terms' => $terms,
                        'hold' => 0,
                        'rate' => $rate,
                        'perc' => $perc,
                        'no_fund' => 0,
                        'amount' => $amount,
                        'principal' => $amount,
                        'total' => $final,
                        'earned' => $ne,
                        'end_of_year' => $end_of_year ? 1 : 0,
                        'wthd' => 1,
                        'note' => $note,
                        'name' => $name
                    ] + $extra);

                    if ($end_of_year) {

                        // set last one as unwithdraw
                        $yearly[count($yearly) - 1]['wthd'] = 0;

                        // and withdraw next year
                        array_push($yearly, [
                            'type' => $type,
                            'type_name' => $type_name,
                            'year' => $not_hold_year,
                            'terms' => $terms,
                            'hold' => 0,
                            'rate' => $rate,
                            'perc' => $perc,
                            'no_fund' => 0,
                            'amount' => $amount,
                            'principal' => $final,
                            'total' => 0,
                            'earned' => 0,
                            'end_of_year' => $end_of_year ? 1 : 0,
                            'wthd' => 1,
                            'note' => $note,
                            'name' => $name
                        ] + $extra);

                        // add amount to amount_to_deduce when the investment strategy hold the amount when intrest are paid by end of the year
                        // if($inv_dur > 1)$ad += $amount;
                        $ad += $amount;
                    } else {
                    }
                } else {
                    array_push($yearly, [
                        'type' => $type,
                        'type_name' => $type_name,
                        'year' => $not_hold_year,
                        'terms' => $terms,
                        'hold' => 0,
                        'rate' => $rate,
                        'perc' => 0,
                        'no_fund' => 1,
                        'amount' => 0,
                        'principal' => 0,
                        'total' => 0,
                        'earned' => 0,
                        'end_of_year' => $end_of_year ? 1 : 0,
                        'wthd' => 1,
                        'note' => $note,
                        'name' => $name
                    ] + $extra);
                }
            }


            // log_info("$type_name -> early withdrawl ", $yearly);

            // if($type != 'bbp')log_info($yearly);


            return [
                'type' => $type,
                'type_name' => $type_name,
                'rate' => $rate,
                'perc' => $perc,
                'dur' => $dur,
                'amount' => $amount,
                'earned' => $ne,
                'total' => $final,
                'hold' => $hold ? 1 : 0,
                'note' => $note,
                'name' => $name,
                'no_fund' => !$enough_funds ? 1 : 0,
                'yearly' => $yearly
            ] + $extra;
        };

        $ad = 0;
        $pn = 0;

        foreach ($strategies as $s) {

            if (!is_assoc($s))
                continue;

            array_push($ret_strategies, $cb($s, $perc_used, $ad, $pn));
        }

        // when money is left, add default_invest_strategy
        if ($perc_used < 1 && check_val($this->default_invest_strategy, 'rate', 0) > 0) {
            // parent = -1, index = -1 to be at bottom after sorting
            array_push($ret_strategies, $cb($this->default_invest_strategy + ['perc' => (1 - $perc_used) * 100], $perc_used, $ad, $pn));
        }


        // log_info("Strategies", $ret_strategies);

        $amount_to_deduce = $ad;
        $early_penalty = $pn;
    }


    private function calculateInvType() {}

    private function calculateLTIM($spendings, $startAt = 0, $whichLTIM = "FL")
    {
        global $log_file, $log_dir;


        if (empty($this->ltim_strategy) || !isset($this->ltim_strategy[$whichLTIM]))
            return;

        // log_info($this->ltim_strategy);

        $ltim_strategy = $this->ltim_strategy[$whichLTIM];

        $buckets = [];
        $deficit_sum = 0;
        $from = check_val($ltim_strategy, 'start_year', 0) + $startAt;
        $num_spendings = count($spendings);

        $ltim_strategy_period = 0;
        foreach ($ltim_strategy['buckets'] as $bucket)
            $ltim_strategy_period += $bucket['dur'];


        // if(file_exists("$log_dir/ltim.csv"))
        //     unlink("$log_dir/ltim.csv");


        if ($from >= $num_spendings)
            return $buckets;


        // log_info("LTIM Period: $ltim_strategy_period");
        // log_info("Num Of Spendings: $num_spendings");
        // log_info("from: $from");

        $content = "";

        foreach ($ltim_strategy['buckets'] as $bucket) {

            // $content = "Bucket From $from To ";

            $to = $from + $bucket['dur'] < $num_spendings ? $bucket['dur'] : ($num_spendings - $from);

            // log_info("$from $to");

            if ($from >= $num_spendings)
                break;
            // else if($to >= $ltim_strategy_period)$to = $ltim_strategy_period;

            $bucketSum = 0;

            // $content .= "[;";
            for ($i = 0; $i < $to; $i++) {
                // log_info("$from ");

                $bucketSum += -1 * $spendings[$from + $i];

                $content .= (-1 * $spendings[$from + $i]) . ";";
            }

            // $content .= "SUM BUCKET;$bucketSum;=ARRONDI($bucketSum*100/B". (1 + $startAt * 2) .");%;;";

            $from += $to;
            // $content .= $from;
            // log_info($content);

            $deficit_sum += $bucketSum;
            array_push($buckets, ['sum' => $bucketSum, 'rate' => $bucket['rate'], 'dur' => $bucket['dur']]);
        }


        $avr_rate = 0;
        foreach ($buckets as &$bucket) {
            $bucket['ratio'] = $deficit_sum > 0 ? $bucket['sum'] / $deficit_sum : 0;
            $avr_rate += $bucket['ratio'] * $bucket['rate'];

            unset($bucket['rate']);
        }

        // log_info($bucketSum, $avr_rate, $buckets);

        //file_put_contents($log_file, "SUM Spendings;$deficit_sum;\n" . $content . "\n", FILE_APPEND);

        //file_put_contents("$log_dir/ltim.csv", "SUM Spendings;$deficit_sum;\n" . $content . "\n", FILE_APPEND);

        return ['sum' => $deficit_sum, 'avr' => $avr_rate, 'buckets' => $buckets];
        // return ['sum'=>$deficit_sum, 'avr'=>$avr_rate];


    }


    private function calculateYearsOfCash($calculated, $startAt = 0, $years = 3)
    {

        $yoc = 0;
        $period = count($calculated);

        $to = $startAt + $years;
        if ($to > $period)
            $to = $period;

        for ($j = $startAt; $j < $to; $j++) {
            $yoc += -1 * (check_val($calculated[$j], 'sp', 0) + check_val($calculated[$j], 'lp', 0));
        }

        return $yoc;
    }

    private function getStartIncreaseYear(&$mfs, $max_inc)
    {

        $y = 0;
        for ($y; $y < count($mfs) - 1; $y++) {
            $diff = ($mfs[$y + 1] - $mfs[$y]) / $mfs[$y];
            // log_info("$diff / $max_inc");
            if ($diff < $max_inc)
                break;
        }

        return $y;
    }

    private function estimateMonthlyFeeInc($years, $mf, $deficit, $max_inc, $inflation_rate)
    {

        $deficit = abs($deficit); // * ($inflation_rate > 0 ? pow(1 + $inflation_rate, $years - 2) : 1);

        if ($mf == 0)
            return 0;

        // log_info("estimateMonthlyFeeInc($years, $mf, $deficit, $max_inc, $inflation_rate)");

        if ($max_inc <= 0) {
            // return $deficit / ($mf * $years);
            $max_inc = 200;
        }

        $max = $max_inc;
        $max_val = 0;
        $min = 0;
        $min_val = 0;

        // $years++;

        // log_info("($years, $mf, $deficit, $max_inc)");

        // get positive & negative when > 1
        for ($i = $max_inc; $i > 0; $i -= 0.01) {

            // 
            $inc_multi = ((pow(1 + $i, $years + 1) - 1) / $i) - 1; // DONT CHANGE
            $cumul = ($mf * $inc_multi) - ($mf * $years);
            $diff = $cumul - $deficit;

            // log_info("(".($years).") -> [$i] -> $inc_multi, $cumul, $diff");

            if ($diff < 0) {
                $min = $i;
                $min_val = $diff;
                break;
            } else {
                $max = $i;
                $max_val = $diff;
            }
        }

        // log_info("$max($max_val) > $min($min_val)");

        $inc = 0;
        // get positive & negative when < 1 and step by 0.05%
        if ($min == 0) {
            for ($i = $max_inc; $i > 0; $i -= 0.00005) {
                $inc_multi = ((pow(1 + $i, $years + 1) - 1) / $i) - 1; // DONT CHANGE
                $cumul = ($mf * $inc_multi) - ($mf * $years);
                $diff = $cumul - $deficit;

                if ($diff < 0) {
                    $min = $i;
                    $min_val = $diff;
                    break;
                } else {
                    $max = $i;
                    $max_val = $diff;
                }
            }
            $inc = 0.0002 + $max - ($max_val * ($max - $min) / ($max_val - $min_val));
        } else {
            $inc = 0.001 + $max - ($max_val * ($max - $min) / ($max_val - $min_val));
        }

        // log_info("$max($max_val) > $min($min_val");

        // log_info("Montly Fee Increase Needed to cure \$$deficit in $years years is: ".($inc*100)."%");

        return $inc;
        // }else{
        // log_info("Montly Fee Increase CANNOT cure \$$deficit in $years years with Limit of ".($max_inc*100)."%");
        // }

    }
    //     private function estimateMonthlyFeeInc($years, $mf, $deficit, $max_inc, $inflation_rate) {
    //     $deficit = abs($deficit); 

    //     if ($mf == 0) {
    //         var_dump('Monthly Fee (mf) is 0, returning 0.');
    //         return 0;
    //     }

    //     var_dump("Initial Parameters: Years: $years, MF: $mf, Deficit: $deficit, Max Increase: $max_inc, Inflation Rate: $inflation_rate");

    //     if ($max_inc <= 0) {
    //         $max_inc = 200;
    //         var_dump("Max Increase was <= 0, set to: $max_inc");
    //     }

    //     $max = $max_inc; 
    //     $max_val = 0;
    //     $min = 0; 
    //     $min_val = 0;

    //     var_dump("Starting Iteration with Max: $max, Min: $min");

    //     for ($i = $max_inc; $i > 0; $i -= 0.01) {
    //         $inc_multi = ((pow(1 + $i, $years + 1) - 1) / $i) - 1; 
    //         $cumul = ($mf * $inc_multi) - ($mf * $years);
    //         $diff = $cumul - $deficit;

    //         var_dump("Iteration for > 1: i = $i, Inc Multi: $inc_multi, Cumulative: $cumul, Diff: $diff");

    //         if ($diff < 0) { 
    //             $min = $i; 
    //             $min_val = $diff; 
    //             var_dump("Breaking Loop > 1: Min: $min, Min Val: $min_val");
    //             break; 
    //         } else { 
    //             $max = $i; 
    //             $max_val = $diff; 
    //         }
    //     }

    //     var_dump("After > 1 Iteration: Max: $max ($max_val), Min: $min ($min_val)");

    //     $inc = 0;

    //     if ($min == 0) {
    //         for ($i = $max_inc; $i > 0; $i -= 0.00005) {
    //             $inc_multi = ((pow(1 + $i, $years + 1) - 1) / $i) - 1; 
    //             $cumul = ($mf * $inc_multi) - ($mf * $years);
    //             $diff = $cumul - $deficit;

    //             var_dump("Iteration for < 1: i = $i, Inc Multi: $inc_multi, Cumulative: $cumul, Diff: $diff");

    //             if ($diff < 0) { 
    //                 $min = $i; 
    //                 $min_val = $diff; 
    //                 var_dump("Breaking Loop < 1: Min: $min, Min Val: $min_val");
    //                 break; 
    //             } else { 
    //                 $max = $i; 
    //                 $max_val = $diff; 
    //             }
    //         }
    //         $inc = 0.0002 + $max - ($max_val * ($max - $min) / ($max_val - $min_val));
    //         var_dump("Calculated Inc for < 1: $inc");
    //     } else {
    //         $inc = 0.001 + $max - ($max_val * ($max - $min) / ($max_val - $min_val));
    //         var_dump("Calculated Inc for > 1: $inc");
    //     }

    //     var_dump("Final Increment: $inc");
    //     return $inc;
    // }

    private function get_closest_inc_year($year, $start_year = 0, &$auto_monthly_fees, &$auto_monthly_fees_inc, $max_inc, $base_mf, $deficit, $inflation_rate)
    {

        // if first year skip
        if ($year == 0)
            return ['year' => $year, 'start_year' => 0, 'total_years' => 1, 'inc' => $this->estimateMonthlyFeeInc(0, $auto_monthly_fees[0], $deficit, $max_inc, $inflation_rate)];

        if ($max_inc > 0)
            for ($i = $year - 1; $i > 0; $i--) {
                if ($auto_monthly_fees_inc[$i] < $max_inc)
                    continue;
                $start_year = $i + 1;
                break;
            }

        // log_info($auto_monthly_fees_inc);

        $total_years = $year - $start_year + 1;
        $period = count($auto_monthly_fees_inc);

        if (empty($base_mf))
            $base_mf = $auto_monthly_fees[$start_year];

        // get necessary increase
        $inc = $this->estimateMonthlyFeeInc($total_years, $base_mf, $deficit, $max_inc, $inflation_rate);

        // log_info("deficit: $deficit, total covered: $total_covered, remain: " . ($diff > 0 ? 0 : $diff));

        return ['year' => $year, 'start_year' => $start_year, 'total_years' => $total_years, 'inc' => $inc];
    }

    private function updateMonthlyFeeInc(
        $year,
        $deficit,
        &$auto_monthly_fees,
        &$auto_monthly_fees_inc,
        $max_inc,
        $default_monthly_fee,
        $cushion_fund = 0,
        $inflation_rate = 0,
        $rule_mf_auto = false,
        &$used_manual_monthly_fees,
        $cash_reserve_threshold = 0,
        $simulation_rules = [],
        $custom_range_years = [],
        $custom_range_fees = [],
        $custom_gradual_range_inc = [],
        $custom_gradual_range_years = [],
        $disable_auto_fee_reduction = false
    ) {
        // print_r($disable_auto_fee_reduction);
        // exit;

        $is_optimize_all = $rule_mf_auto;
        $start_year = $is_optimize_all ? 0 : $year;
        if ($disable_auto_fee_reduction == "1" && $rule_mf_auto) {



            $is_optimize_all = $rule_mf_auto;

            $start_year = $is_optimize_all ? 0 : $year;



            //  var_dump('START HERE');var_dump($auto_monthly_fees);



            // get closest year with % to increase by and total years to increase

            $inc_infos = $this->get_closest_inc_year($year, $start_year, $auto_monthly_fees, $auto_monthly_fees_inc, $max_inc, '', $deficit, $inflation_rate);



            $start_year = $inc_infos['start_year'];     // year to start increasing

            $total_years = $inc_infos['total_years'];   // number of years to increase

            $inc = $inc_infos['inc'];                   // % to increase each year



            // log_info($inc_infos);





            $adjust_inflation = ($inflation_rate > 0 ? 1 + $inflation_rate : 1);



            /*

            // if no increase return, that means that the inital mf is $0

            // create new monthly fee for all previous years

            if($inc == 0){

                $n_mf = ceil(abs($deficit)) / $total_years;

                for($y=$start_year; $y<$total_years; $y++){

                    $auto_monthly_fees[$y] = $n_mf * $adjust_inflation;        

                    $auto_monthly_fees_inc[$y] = 0;

                }         

                //return;

            }

            */





            // base monthly fee to start the increase from auto_monthly_fees previously increased

            $base_mf = $auto_monthly_fees[$start_year];





            // log_info("deficit: $deficit, year: ".($year).", start_year: ".($start_year).", total_years: $total_years, incBy: $inc");



            // period to process

            $period = count($auto_monthly_fees); /* $stop_at_year >= 0 ? $stop_at_year : count($auto_monthly_fees); */



            // new monthly fee used as base of increase

            $new_mf = $base_mf;





            // var_dump($auto_monthly_fees);

            // blindly apply to whole period

            $total_covered = 0;

            for ($y = $start_year; $y < $period; $y++) {



                // reached the year to auto-calc

                // fill with the manual monthly fees

                if ($y > $year) {



                    // if manually entered mf, break

                    if ($used_manual_monthly_fees[$y] == true)

                        break;



                    // else update

                    $auto_monthly_fees[$y] = $new_mf;

                    // var_dump($y);

                    // var_dump($default_monthly_fee);

                    continue;

                }



                // get 'n' of equation

                $n = $y - $start_year + 1;



                // calculate by how much current fees should be increased

                $inc_ratio = pow(1 + $inc, $n);

                $inc_by_amount = $base_mf * $inc_ratio - ($base_mf);

                // var_dump($inc_ratio);

                // adjusted to inflation

                $inc_by_amount *= $adjust_inflation;





                // calculate new mf

                $current_mf = $auto_monthly_fees[$y];

                $new_mf = $current_mf + $inc_by_amount;

                // var_dump($new_mf);

                // compare increase to prev value

                $prev_mf = $y == 0 ? $default_monthly_fee : $auto_monthly_fees[$y - 1];

                $diff_mf = ($new_mf - $prev_mf);

                $inced_by = $prev_mf > 0 ? $diff_mf / $prev_mf : 0;





                // if increase for more than max, set to max, else keep 

                if ($max_inc > 0 && $inced_by > $max_inc) {

                    // log_info("Exeeds $inced_by > $max_inc -> $new_mf");

                    $new_mf = $prev_mf * (1 + $max_inc);

                    // log_info("Adjust $new_mf");

                    $diff_mf = ($new_mf - $prev_mf);

                    $inced_by = $max_inc;

                    // var_dump($max_inc);

                }

                // var_dump($y);

                $total_covered += $new_mf - $current_mf;



                // log_info("Year $y ($inced_by): $new_mf ($diff_mf), covered: $total_covered");



                // add it to the lf array

                // if($inced_by == 0){

                //     $auto_monthly_fees[$y] = 0;

                //     var_dump("Here ITS NOW");

                // } else {

                $auto_monthly_fees[$y] = $new_mf;

                //}





                $auto_monthly_fees_inc[$y] = $inced_by;





                // log_info("Year $y: increase by $inc_by_amount -> $new_mf (".($inced_by*100).")");



            }

            // for($x=21; $x<=29; $x++){

            //     $auto_monthly_fees[$x] = 10;

            // }

            // var_dump($auto_monthly_fees);



            // if remaining deficit to cover, add max remaining to last year 

            $remain_to_cover = abs($deficit) - $total_covered;

            if ($remain_to_cover < 0)

                $remain_to_cover = 0;



            if ($remain_to_cover > 0 && $auto_monthly_fees_inc[$year] < $max_inc) {

                $prev_mf = $y == 0 ? $default_monthly_fee : $auto_monthly_fees[$y - 1];

                $curr_mf = $auto_monthly_fees[$year];

                $max_mf = $prev_mf * (1 + $max_inc);

                // log_info("Covered: $total_covered - $deficit = $remain_to_cover");



                $auto_monthly_fees[$year] = $curr_mf + ceil($remain_to_cover);

                if ($auto_monthly_fees[$year] > $max_mf)

                    $auto_monthly_fees[$year] = $max_mf;



                // log_info("Added $remain_to_cover to $curr_mf = $auto_monthly_fees[$year]");



            }



        } else if ($deficit > 0) {
            // Get closest year with % to increase by and total years to increase
            $inc_infos = $this->get_closest_inc_year(
                $year,
                $start_year,
                $auto_monthly_fees,
                $auto_monthly_fees_inc,
                $max_inc,
                '',
                $deficit,
                $inflation_rate
            );

            $start_year = $inc_infos['start_year'];
            $total_years = $inc_infos['total_years'];
            $inc = $inc_infos['inc'];

            $adjust_inflation = ($inflation_rate > 0 ? 1 + $inflation_rate : 1);

            // Base monthly fee to start the increase
            $base_mf = $auto_monthly_fees[$start_year] ?? $default_monthly_fee;

            $period = count($auto_monthly_fees);
            $new_mf = $base_mf;
            $total_covered = 0;
            $deficit_cleared_year = -1;

            for ($y = $start_year; $y < $period; $y++) {
                if (!empty($custom_range_years[$y]) && $custom_range_years[$y] === true) {
                    continue;
                }

                if (!empty($custom_gradual_range_years[$y]) && $custom_gradual_range_years[$y] === true) {
                    $auto_monthly_fees_inc[$y] = $custom_gradual_range_inc[$y];
                    continue;
                }

                // Skip years with manual monthly fees
                if ($y > $year && !empty($used_manual_monthly_fees[$y]) && $used_manual_monthly_fees[$y] === true) {
                    break;
                }

                // Calculate the increase
                $n = $y - $start_year + 1;
                $inc_ratio = pow(1 + $inc, $n);
                $inc_by_amount = $base_mf * $inc_ratio - $base_mf;
                $inc_by_amount *= $adjust_inflation;

                // Calculate new monthly fee
                $current_mf = $auto_monthly_fees[$y] ?? $default_monthly_fee;
                $new_mf = $current_mf + $inc_by_amount;

                // Enforce maximum increase
                $prev_mf = $y == 0 ? $default_monthly_fee : $auto_monthly_fees[$y - 1];
                $diff_mf = $new_mf - $prev_mf;
                $inced_by = $prev_mf > 0 ? $diff_mf / $prev_mf : 0;

                if ($max_inc > 0 && $inced_by > $max_inc) {
                    $new_mf = $prev_mf * (1 + $max_inc);
                    $diff_mf = $new_mf - $prev_mf;
                    $inced_by = $max_inc;
                }

                $total_covered += $new_mf - $current_mf;

                // Check if deficit is cleared
                if ($deficit_cleared_year < 0) {
                    $deficit_cleared_year = $y;
                }

                // Update arrays
                $auto_monthly_fees[$y] = $new_mf;
                $auto_monthly_fees_inc[$y] = $inced_by;
            }
        } else {
            // $disable_auto_reduction = isset($simulation_rules['disable_auto_fee_reduction']) &&
            //     $simulation_rules['disable_auto_fee_reduction'] == 1;
            // Handle years after deficit is clearedion);
            // if (isset($simulation_rules['disable_auto_fee_reduction']) && $simulation_rules['disable_auto_fee_reduction'] == 1) {
            
            for ($y = $year + 1; $y < count($auto_monthly_fees); $y++) {

                if (!empty($custom_range_years[$y]) && $custom_range_years[$y] === true) {
                    $auto_monthly_fees[$y] = $custom_range_fees[$y];
                    continue;
                }

                if (!empty($custom_gradual_range_years[$y]) && $custom_gradual_range_years[$y] === true) {
                    $auto_monthly_fees_inc[$y] = $custom_gradual_range_inc[$y];
                    continue;
                }

                if (!empty($used_manual_monthly_fees[$y]) && $used_manual_monthly_fees[$y] === true) {
                    continue;
                }

                $prev_mf = $auto_monthly_fees[$y - 1];
                $new_mf = $prev_mf * (1 - $max_inc); // Decrease by %

                if ($new_mf < $default_monthly_fee) {
                    $new_mf = $default_monthly_fee; // Ensure it doesn't go below the default fee
                }

                // if ($new_mf < 0) {
                //     $new_mf = 0; // Ensure it doesn't go below the default fee
                // }

                $auto_monthly_fees[$y] = $new_mf;
                $auto_monthly_fees_inc[$y] = -$max_inc; // Negative increment for decrease
            }
            // } else {
            //     // When auto reduction is ENABLED, use the OLD LOGIC approach
            //     // Based on your old logic, when deficit <= 0, there's no special handling
            //     // Just continue with the current fees without any auto-reduction

            //     for ($y = $year + 1; $y < count($auto_monthly_fees); $y++) {
            //         if (!empty($custom_range_years[$y]) && $custom_range_years[$y] === true) {
            //             $auto_monthly_fees[$y] = $custom_range_fees[$y];
            //             continue;
            //         }
            //         if (!empty($custom_gradual_range_years[$y]) && $custom_gradual_range_years[$y] === true) {
            //             $auto_monthly_fees_inc[$y] = $custom_gradual_range_inc[$y];
            //             continue;
            //         }
            //         if (!empty($used_manual_monthly_fees[$y]) && $used_manual_monthly_fees[$y] === true) {
            //             continue;
            //         }
            //         // Keep the fee at the same level (no reduction) - like in old logic
            //         $auto_monthly_fees[$y] = $auto_monthly_fees[$y - 1];
            //         $auto_monthly_fees_inc[$y] = 0; // No change
            //     }
            // }
        }

        if (empty($custom_range_years[$year]) && empty($custom_gradual_range_years[$year])) {
            // Handle remaining deficit
            $remain_to_cover = abs($deficit) - $total_covered ? $total_covered : 0;
            if ($remain_to_cover > 0 && $auto_monthly_fees_inc[$year] < $max_inc) {
                $prev_mf = $year == 0 ? $default_monthly_fee : $auto_monthly_fees[$year - 1];
                $curr_mf = $auto_monthly_fees[$year];
                $max_mf = $prev_mf * (1 + $max_inc);

                $auto_monthly_fees[$year] = $curr_mf + ceil($remain_to_cover);
                if ($auto_monthly_fees[$year] > $max_mf) {
                    $auto_monthly_fees[$year] = $max_mf;
                    }
            }
        }
        // print_r($auto_monthly_fees_inc);
        // exit;
    }

    public function reset($data)
    {
        global $auth, $modelsTable, $simRulesTable, $simDeficitTable, $simSplitsTable, $simSplitsLTIMTable, $simDeficitLTIMTable;


        if (!is_valid($data, 'model_id'))
            return ['error' => $this->errors['model_id']];
        if (!exists($modelsTable, ['id' => $data['model_id']]))
            return ['error' => $this->errors['missing']];

        if (!belongs_to_client($modelsTable, $data['model_id'], false, true))
            return ['error' => $this->errors['not_allowed']];

        $model_id = $data['model_id'];

        $which = check_val($data, 'which', 'current');

        // check if LTIM is enabled in the simulation
        // $is_ltim_enabled = check_val($this->get_rules($model_id), 'ltim_enabled', 0);


        $deficitTable = /* $is_ltim_enabled ? $simDeficitLTIMTable : */ $simDeficitTable;
        $splitsTable = /* $is_ltim_enabled ? $simSplitsLTIMTable : */ $simSplitsTable;




        // reset by year
        if (is_valid($data, "year")) {

            $year = $data["year"];

            // delete erase deficit data
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year = :year', ['model_id' => $model_id, 'user_id' => $auth->uid(), 'year' => $year]);
        } else if (is_valid($data, "from_year")) {

            $year = $data["from_year"];

            // delete erase deficit data
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year >= :year', ['model_id' => $model_id, 'user_id' => $auth->uid(), 'year' => $year]);
        } else {

            // delete_elements_by_cond($simRulesTable, 'model_id = :model_id AND user_id = :user_id', ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid()] );

            if ($which == 'current') {

                delete_elements_by_cond($splitsTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);
                delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);
            } else {

                delete_elements_by_cond($simSplitsTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);
                delete_elements_by_cond($simDeficitTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);
                // delete_elements_by_cond($simSplitsLTIMTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);
                // delete_elements_by_cond($simDeficitLTIMTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);
                delete_elements_by_cond($simRulesTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);
            }
        }

        return ['success' => ''];
    }

    // handles erase deficit data
    public function deficit($data)
    {
        global $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable, $modelsTable, $clientsTable, $auth;

        // get model info and year to erase deficit for
        if (!is_valid($data, 'model_id')){
            // return ['error' => $this->errors['model_id']];
            return send_json_response(false, 400 , $this->errors['model_id']);
        }
        if (!is_valid($data, 'year') || intval($data['year']) < 0){
            // return ['error' => $this->errors['deficit_year']];
            return send_json_response(false, 400 , $this->errors['deficit_year']);
        }

        //if(!is_valid($data, 'to') || intval($data['to']) < 0)return ['error'=>$this->errors['deficit_to'] ];


        $year = $data['year'];
        $model_id = $data['model_id'];

        $model = get_element($modelsTable, ['id' => $model_id]);

        if (empty($model)){
            // return ['error' => $this->errors['missing']];
            return send_json_response(false, 404 , $this->errors['missing']);
        }

        if (!belongs_to_client($modelsTable, $model_id, false, true)){
            // return ['error' => $this->errors['not_allowed']];
            return send_json_response(false, 403 , $this->errors['not_allowed']);
        }

        // check if LTIM is enabled in the simulation
        // $is_ltim_enabled = check_val($this->get_rules($model_id), 'ltim_enabled', 0);
        $deficitTable = /* $is_ltim_enabled ? $simDeficitLTIMTable : */ $simDeficitTable;
        $splitsTable = /* $is_ltim_enabled ? $simSplitsLTIMTable : */ $simSplitsTable;


        // log_info($data);

        // add erase deficit data
        if (isset($data['deficit'])) {
             if (
                isset($data['deficit']['monthly_fees_range']) &&
                is_array($data['deficit']['monthly_fees_range']) &&
                isset($data['deficit']['monthly_fees_range']['start']) &&
                isset($data['deficit']['monthly_fees_range']['end']) &&
                isset($data['deficit']['monthly_fees_range']['fee'])
            ) {
                $start = intval($data['deficit']['monthly_fees_range']['start']);
                $end = intval($data['deficit']['monthly_fees_range']['end']);
                $fee = floatval($data['deficit']['monthly_fees_range']['fee']);
                $period = check_val($model, 'period', 0);

                // $period = check_val($model, 'period', 0);

                for ($i = 0; $i < $period; $i++) {

                    $deficit_of_year = get_element($deficitTable, [
                        'model_id' => $model_id,
                        'user_id' => $auth->uid(),
                        'year' => $i
                    ]);

                    if (!empty($deficit_of_year)) {
                        $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                        unset($deficit_of_year['data']['monthly_fees_range']);
                        unset($deficit_of_year['data']['custom_range_toggle']);
                        $deficit_of_year['data'] = json_encode($deficit_of_year['data']);
                        save_element($deficitTable, $deficit_of_year, ['model_id', 'user_id', 'year']);
                    }
                }

                for ($i = $start; $i <= $end && $i < $period; $i++) {

                    $deficit_of_year = get_element($deficitTable, [
                        'model_id' => $model_id,
                        'user_id' => $auth->uid(),
                        'year' => $i
                    ]);

                    if (empty($deficit_of_year)) {

                        $deficit_of_year = [
                            'model_id' => $model_id,
                            'user_id' => $auth->uid(),
                            'year' => $i,
                            'data' => []
                        ];
                    } else {
                        $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                    }

                    $deficit_of_year['data']['custom_range_toggle'] = 1;

                    $deficit_of_year['data']['monthly_fees_range'] = [
                        'start' => $start,
                        'end' => $end,
                        'fee' => $fee,
                        'year' => $i,

                    ];
                    $deficit_of_year['data'] = json_encode($deficit_of_year['data']);
                    save_element($deficitTable, $deficit_of_year, ['model_id', 'user_id', 'year']);
                }
            }

            if (isset($data['deficit']['monthly_fees_range_reset'])) {

                $start = intval($data['deficit']['monthly_fees_range_reset']['start']);
                $end = intval($data['deficit']['monthly_fees_range_reset']['end']);
                $period = check_val($model, 'period', 0);

                for ($i = $start; $i <= $end && $i < $period; $i++) {
                    $deficit_of_year = get_element($deficitTable, [
                        'model_id' => $model_id,
                        'user_id' => $auth->uid(),
                        'year' => $i
                    ]);

                    if (!empty($deficit_of_year)) {
                        $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                        unset($deficit_of_year['data']['monthly_fees_range']);
                        unset($deficit_of_year['data']['custom_range_toggle']);
                        $deficit_of_year['data'] = json_encode($deficit_of_year['data']);
                        // echo "<pre>";
                        // print_r($deficit_of_year['data']);
                        // echo "</pre>";
                        save_element($deficitTable, $deficit_of_year, ['model_id', 'user_id', 'year']);
                    }
                }
            }

            if (

                isset($data['deficit']['monthly_fees_auto_range']) &&
                is_array($data['deficit']['monthly_fees_auto_range']) &&
                isset($data['deficit']['monthly_fees_auto_range']['start']) &&
                isset($data['deficit']['monthly_fees_auto_range']['end']) &&
                isset($data['deficit']['monthly_fees_auto_range']['max_perc'])
            ) {
                $start = intval($data['deficit']['monthly_fees_auto_range']['start']);
                $end = intval($data['deficit']['monthly_fees_auto_range']['end']);
                $max_perc = floatval($data['deficit']['monthly_fees_auto_range']['max_perc']);
                $period = check_val($model, 'period', 0);

                // Clear previous auto range data for all years
                for ($i = 0; $i < $period; $i++) {

                    $deficit_of_year = get_element($deficitTable, [
                        'model_id' => $model_id,
                        'user_id' => $auth->uid(),
                        'year' => $i
                    ]);

                    if (!empty($deficit_of_year)) {
                        $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                        unset($deficit_of_year['data']['monthly_fees_auto_range']);
                        unset($deficit_of_year['data']['custom_gradual_range_toggle']);
                        $deficit_of_year['data'] = json_encode($deficit_of_year['data']);
                        save_element($deficitTable, $deficit_of_year, ['model_id', 'user_id', 'year']);
                    }
                }

                // Set new auto range for selected years
                for ($i = $start; $i <= $end && $i < $period; $i++) {

                    $deficit_of_year = get_element($deficitTable, [
                        'model_id' => $model_id,
                        'user_id' => $auth->uid(),
                        'year' => $i
                    ]);

                    if (empty($deficit_of_year)) {
                        $deficit_of_year = [
                            'model_id' => $model_id,
                            'user_id' => $auth->uid(),
                            'year' => $i,
                            'data' => []
                        ];
                    } else {
                        $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                    }
                    $deficit_of_year['data']['custom_gradual_range_toggle'] = 1;
                    $deficit_of_year['data']['monthly_fees_auto_range'] = [
                        'start' => $start,
                        'end' => $end,
                        'max_perc' => $max_perc,
                        'year' => $i,
                    ];
                    $deficit_of_year['data'] = json_encode($deficit_of_year['data']);
                    save_element($deficitTable, $deficit_of_year, ['model_id', 'user_id', 'year']);
                }
            }

            if (isset($data['deficit']['gradual_fees_range_reset_btn'])) {
                $start = intval($data['deficit']['gradual_fees_range_reset_btn']['start']);
                $end = intval($data['deficit']['gradual_fees_range_reset_btn']['end']);
                $period = check_val($model, 'period', 0);

                for ($i = $start; $i <= $end && $i < $period; $i++) {

                    $deficit_of_year = get_element($deficitTable, [
                        'model_id' => $model_id,
                        'user_id' => $auth->uid(),
                        'year' => $i
                    ]);

                    if (!empty($deficit_of_year)) {
                        $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                        unset($deficit_of_year['data']['monthly_fees_auto_range']);
                        unset($deficit_of_year['data']['custom_gradual_range_toggle']);
                        $deficit_of_year['data'] = json_encode($deficit_of_year['data']);
                        // echo "<pre>";
                        // print_r($deficit_of_year['data']);
                        // echo "</pre>";
                        save_element($deficitTable, $deficit_of_year, ['model_id', 'user_id', 'year']);
                    }
                }
            }

            if (!empty($data['deficit']) && is_assoc($data['deficit'])) {


                // Apply These properties to the end of the period
                $apply_prop = [];
                $remove_prop = [];

                // get current deficit data
                $deficit_of_year = get_element($deficitTable, ['model_id' => $model_id, 'user_id' => $auth->uid(), 'year' => $year]);

                if (!empty($deficit_of_year)) {

                    $deficit_of_year['data'] = parse_json(check_val($deficit_of_year, 'data', []), []);


                    // check LTIM percentage
                    if (
                        isset($data['deficit']['ltim_perc']) &&
                        floatval(check_val($deficit_of_year, 'data/ltim_perc', 0)) > floatval($data['deficit']['ltim_perc'])
                    ) {

                        // remove all future ltim_wth
                        array_push($remove_prop, 'ltim_wth');
                    }


                    // check LTIM Withdraw
                    if (
                        isset($data['deficit']['ltim_wth']) &&
                        floatval(check_val($deficit_of_year, 'data/ltim_wth', 0)) < floatval($data['deficit']['ltim_wth'])
                    ) {

                        // remove all future ltim_wth
                        array_push($remove_prop, 'ltim_wth');
                    }

                    // check monthly_fees
                    if (
                        isset($data['deficit']['monthly_fees']) &&
                        floatval(check_val($deficit_of_year, 'data/monthly_fees', 0)) != floatval($data['deficit']['monthly_fees'])
                    ) {

                        // remove all future monthly_fees
                        array_push($remove_prop, 'monthly_fees');
                    }
                }



                save_element($deficitTable, ['model_id' => $model_id, 'user_id' => $auth->uid(), 'year' => $year, /* 'to'=>$to, */ 'data' => json_encode($data['deficit'])], ['model_id', 'user_id', 'year']);


                if (!empty($remove_prop)) {
                    $period = check_val($model, 'period', 0);

                    for ($i = $year + 1; $i < $period; $i++) {

                        // get year deficit
                        $deficit_of_year = get_element($deficitTable, ['model_id' => $model_id, 'user_id' => $auth->uid(), 'year' => $i]);

                        // if has a deficit data
                        if (!empty($deficit_of_year)) {
                            $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                            foreach ($remove_prop as $prop) {
                                if (isset($deficit_of_year['data'][$prop]))
                                    unset($deficit_of_year['data'][$prop]);
                            }
                        } else {
                            continue;
                        }

                        // Encode JSON
                        $deficit_of_year['data'] = json_encode(check_val($deficit_of_year, 'data', []));

                        // save
                        save_element($deficitTable, $deficit_of_year);
                    }
                }

                /*
                if(!empty($apply_prop)){
                    
                    $apply_for_next = check_val($model, 'period', 0);
                    
                    for($i=1; $i<=$apply_for_next; $i++){
                        
                        // get year deficit
                        $deficit_of_year = get_element($simDeficitTable, ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>($year + $i)]);
                        
                        // if not exist
                        if(empty($deficit_of_year)){
                            $deficit_of_year = ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>($year + $i), 'data'=>[] ];

                        // else if exists
                        }else{
                            $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                        }        

                        foreach($apply_prop as $prop){
                            $deficit_of_year['data'][$prop] = $data['deficit'][$prop];
                        }      

                        foreach($remove_prop as $prop){
                            if(isset($deficit_of_year['data'][$prop]))unset($deficit_of_year['data'][$prop]);
                        }

                        $deficit_of_year['data'] = json_encode($deficit_of_year['data']);
                                                
                        save_element($simDeficitTable, $deficit_of_year);
                    }
                }
                */

                // delete simulation deficit data
                // delete_elements_by_cond($simDeficitTable, 'model_id = :model_id AND user_id = :user_id AND year > :year', ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>$year]);

            } else {
                delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year = :year', ['model_id' => $model_id, 'user_id' => $auth->uid(), 'year' => $year]);
            }
        }


        $split_min_year = 999;

        // add splits
        if (is_valid($data, 'splits') && !empty($data['splits'])) {
            foreach ($data['splits'] as $split) {
                $split['user_id'] = $auth->uid();
                $split['model_id'] = $model_id;
                if ($split['year'] < $split_min_year)
                    $split_min_year = $split['year'];
                save_element($splitsTable, $split);
            }
        }

        // unsplit
        if (is_valid($data, 'unsplit') && !empty($data['unsplit'])) {
            foreach ($data['unsplit'] as $unsplit) {
                $res = $this->unsplit(['id' => $unsplit]);
                if (isset($res['success']) && $res['success'] < $split_min_year)
                    $split_min_year = $res['success'];
            }
        }

        // delete simulation deficit data
        delete_elements_by_cond(
            $deficitTable,
            'model_id = :model_id AND user_id = :user_id AND year >= :year',
            ['model_id' => $data['model_id'], 'user_id' => $auth->uid(), 'year' => $split_min_year]
        );



        // return ['success' => ''];
        return send_json_response(true, 200, 'success');
    }

    // unsplit item 
    public function unsplit($data)
    {
        global $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable, $modelsTable, $clientsTable, $auth;

        // get item's id  to unsplit
        if (!is_valid($data, 'id'))
            return ['error' => $this->errors['item_id']];

        // tables
        $deficitTable = $simDeficitTable;
        $splitsTable = $simSplitsTable;

        // check if item in LTIM split or other
        if (exists($simSplitsLTIMTable, ['id' => $data['id'], 'user_id' => $auth->uid()])) {

            $deficitTable = $simDeficitLTIMTable;
            $splitsTable = $simSplitsLTIMTable;
        } else if (!belongs_to_user($splitsTable, $data['id']))
            return ['error' => $this->errors['not_allowed']];

        $item = get_element($splitsTable, ['id' => $data['id']]);

        $year = 999;

        // add cost to split_of item
        if (!empty($item) && !empty($item['split_of'])) {
            $split_of = get_element($splitsTable, ['id' => $item['split_of']]);
            if (!empty($split_of)) {
                $split_of['cost'] += $item['cost'];
                save_element($splitsTable, $split_of);


                $year = $split_of['year'];
            }
        } else {
            $year = $item['year'];
        }

        // delete it
        delete_elements_by_id($splitsTable, $data['id']);

        // delete simulation deficit data
        if (is_valid($data, 'model_id'))
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year >= 0', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);

        return ['success' => $year];
    }


    public function rules($data)
    {
        global $auth, $modelsTable, $simRulesTable;


        if (!is_valid($data, 'model_id')) {
            // return ['error' => ''];
            return send_json_response(false, 400,  $this->errors['model_id_missing']);
        }

        if (!belongs_to_client($modelsTable, $data['model_id'], false, true))
            return ['error' => $this->errors['not_allowed']];

        /*
        $prev_rules = $this->get_rules($data['model_id']);

        // If asked to enable LTIM
        $ltim_enabled = check_val($data, 'rules/ltim_enabled', 0);
        $prev_ltim_enabled = check_val($prev_rules, 'ltim_enabled', 0);

        if($ltim_enabled == 1 && $prev_ltim_enabled == 0){
            $data['rules']['ltim_perc'] = check_val($prev_rules, 'ltim_perc', 1);
            $data['rules']['ltim_cover'] = check_val($prev_rules, 'ltim_cover', 1);
            $data['rules']['ltim_lower'] = check_val($prev_rules, 'ltim_lower', 0);
            $data['rules']['ltim_yoc'] = check_val($prev_rules, 'ltim_yoc', 3);
            $data['rules']['ltim_used'] = check_val($prev_rules, 'ltim_used', 'FL');
            $data['rules']['cushion_fund'] = check_val($prev_rules, 'cushion_fund', 0);
        }
        */

        foreach ($data as $k => $v) {
            if (is_array($v))
                $data[$k] = json_encode($v);
        }

        // reset rules
        if (!is_valid($data, 'rules')) {
            delete_elements_by_cond($simRulesTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);

            // save rules
        } else {
            save_element($simRulesTable, ['model_id' => $data['model_id'], 'user_id' => $auth->uid(), 'rules' => $data['rules']], ['model_id', 'user_id']);
        }

        // return ['success' => ''];
        return send_json_response(true, 200, 'success');
    }


    // update simulation model item data
    public function update($data)
    {
        global $simSplitsTable, $simSplitsLTIMTable, $modelsTable, $clientsTable, $auth;

        // the id is pregenerated, so if it doesnt exist just add it as is
        // and the parent_id is the master item id

        if (!is_valid($data, 'id'))
            return ['error' => $this->errors['item_id']];

        // check if LTIM is enabled in the simulation
        // $is_ltim_enabled = check_val($model, 'ltim_enabled', 0);
        $deficitTable = /* $is_ltim_enabled ? $simDeficitLTIMTable : */ $simDeficitTable;
        $splitsTable = /* $is_ltim_enabled ? $simSplitsLTIMTable : */ $simSplitsTable;

        $exists = exists($splitsTable, ['id' => $data['id']]);

        if ($exists && !belongs_to_user($splitsTable, $data['id']))
            return ['error' => $this->errors['not_allowed']];

        if (is_valid($data, 'split_of')) {
            $split_already_exist = get_element($splitsTable, ['parent_id' => $data['parent_id'], 'redundancy_at' => $data['redundancy_at'], 'split_of' => $data['split_of'], 'year' => $data['year']]);
            if (!empty($split_already_exist)) {
                return ['error' => $this->errors['split_exists']];
            }
        }


        $data['user_id'] = $auth->uid();

        save_element($splitsTable, $data);

        // delete simulation deficit data
        if (is_valid($data, 'model_id'))
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year >= 0', ['model_id' => $data['model_id'], 'user_id' => $auth->uid()]);


        return ['success' => ''];
    }

    private function get_rules($model_id)
    {
        global $auth, $simRulesTable, $modelsTable;

        if (empty($model_id) || !belongs_to_client($modelsTable, $model_id, false, true))
            return [
                "ltim_perc" => 1,
                "ltim_cover" => 1,
                "ltim_lower" => 0,
                "ltim_yoc" => 3,
                "ltim_used" => "FL",
                "mf_perc" => 0,
                "mf_auto" => 0,
                "inf_rate" => 0,
                "period" => 29,
                "cushion_fund" => 0,
                "inv_keep" => 1,
                "use_infl" => 1,
                "disable_auto_fee_reduction" => 0
            ];

        $rules = parse_json(check_val(get_element($simRulesTable, ['model_id' => $model_id, 'user_id' => $auth->uid()]), 'rules', []), []);

        // get defaults
        $model = get_element($modelsTable, ['id' => $model_id]);

        // values
        $max_mf_inc = check_val($model, "monthly_fees_rate", 0) / 100;
        $inf_rate = check_val($model, "inflation_rate", 0) / 100;
        $cushion_fund = check_val($model, "cushion_fund", 0) / 100;
        $cash_reserve_threshold = check_val($model, "cash_reserve_threshold", 0) / 100;
        $period = check_val($model, "period", 29);

        $default_rules = [
            "ltim_perc" => 1,
            "ltim_cover" => 1,
            "ltim_yoc" => 1,
            "ltim_used" => "FL",
            "ltim_lower" => 0,
            "mf_perc" => $max_mf_inc,
            "mf_auto" => 0,
            "inf_rate" => $inf_rate,
            "period" => $period,
            "cushion_fund" => $cushion_fund,
            "cash_reserve_threshold" => $cash_reserve_threshold,
            "inv_keep" => 1,
            "use_infl" => 1,
            "disable_auto_fee_reduction" => 0
        ];

        foreach ($default_rules as $k => $v) {
            $rules[$k] = check_val($rules, $k, $v);
        }

        return $rules;
    }

      // Helper: Get mf_perc for a specific year (array or single value supported)

    private function get_mf_perc($simulation_rules, $year){
        $mf_perc_mf_perc = check_val($simulation_rules, 'mf_perc_per_year', 0);
        if (is_array($mf_perc_mf_perc)) {
            return isset($mf_perc_mf_perc[$year]) ? $mf_perc_mf_perc[$year] : (is_array($mf_perc_mf_perc) && count($mf_perc_mf_perc) > 0 ? end($mf_perc_mf_perc) : 0);
        }
        return $mf_perc_mf_perc;
    }

    public function compare($model_id)
    {
        global $auth, $simRulesTable, $modelsTable;

        if (empty($model_id))
            return ["error" => $this->errors['model_id']];

        if (!belongs_to_client($modelsTable, $model_id, false, true))
            return ["error" => $this->errors['not_allowed']];

        $compare['managed'] = $this->simulation(['model_id' => $model_id, 'mode' => 'managed']);
        $compare['ltim'] = $this->simulation(['model_id' => $model_id, 'mode' => 'ltim']);
        $compare['rules'] = $this->get_rules($model_id);
        $compare['inv_strategies'] = $this->inv_strategies;
        if ($compare['inv_strategies']['bbp'])
            unset($compare['inv_strategies']['bbp']);
        $compare['ltims'] = [];
        foreach ($this->ltim_strategy as $state => $infos) {
            $compare['ltims'][$state] = $infos['name'];
        };

        return $compare;
    }


    // get versions
    public function get_version($data)
    {
        global $auth, $modelsTable, $simVersionTable, $usersTable;

        $model_id = check_val($data, 'model_id');
        $uid = $auth->uid();

        if (empty($model_id))
            return ["error" => $this->errors['model_id']];

        if (!belongs_to_client($modelsTable, $model_id, false, true))
            return ["error" => $this->errors['not_allowed']];

        $conds = ['model_id' => $model_id, '*user_id' => $uid];

        if (check_val($data, 'owned', 0) == 1) {
            $conds['raw'] = "user_id = :user_id";
        } else {
            $conds['raw'] = "(user_id = :user_id  OR ( (user_id != :user_id OR user_id IS NULL) AND (can_view = 1 OR can_load = 1)))";
        }

        $versions = get_elements_join(
            $simVersionTable,
            $conds,
            "LEFT JOIN $usersTable ON $usersTable.id = $simVersionTable.user_id",
            format_select($simVersionTable, '*', ['user_id', 'data']) .
                ", IF(user_id IS NOT NULL AND user_id = :user_id, 1, 0) owned, $usersTable.fn, $usersTable.ln",
            'ORDER BY row_id DESC'
        );

        return $versions;
    }

    // save versions
    public function save_version($data)
    {
        global $auth, $modelsTable, $simSplitsTable, $simDeficitTable, $simRulesTable, $simVersionTable;

        $model_id = check_val($data, 'model_id');

        if (empty($model_id))
            return ["error" => $this->errors['model_id']];

        if (!belongs_to_client($modelsTable, $model_id, false, true))
            return ["error" => $this->errors['not_allowed']];

        $uid = $auth->uid();







        // override existing version
        $version_id = check_val($data, 'version_id');
        if (!empty($version_id)) {

            if (!belongs_to_user($simVersionTable, $version_id))
                return ["error" => $this->errors['not_allowed']];

            $data = ['id' => $version_id];
        } else {

            // add uid
            $data['user_id'] = $uid;

            $name = trim(check_val($data, 'name'));
            if (empty($name))
                return ["error" => $this->errors['version_name']];
        }



        // create data
        $version_data = array();
        $version_data['rules'] = $this->get_rules($model_id);
        $version_data['deficit'] = get_elements($simDeficitTable, ['model_id' => $model_id, 'user_id' => $uid]); // get deficits;
        $version_data['splits'] = get_elements($simSplitsTable, ['model_id' => $model_id, 'user_id' => $uid]); // get deficits;
        $version_data['simulation'] = $this->simulation(['model_id' => $model_id]); // get deficits;

        $data['data'] = json_encode($version_data);



        $res = save_element($simVersionTable, $data);


        if ($res === false)
            return ['error' => ''];

        return ['success' => ''];
    }

    // edit version prop
    public function edit_version($data)
    {
        global $auth, $modelsTable, $simVersionTable;

        $version_id = check_val($data, 'version_id');
        $uid = $auth->uid();


        if (empty($version_id))
            return ["error" => $this->errors['version_id']];

        if (!belongs_to_user($simVersionTable, $version_id))
            return ["error" => $this->errors['not_allowed']];

        if (isset($data['model_id']))
            unset($data['model_id']);
        if (isset($data['user_id']))
            unset($data['user_id']);
        if (isset($data['data']))
            unset($data['data']);

        $data['id'] = $data['version_id'];
        unset($data['version_id']);

        $res = save_element($simVersionTable, $data);


        if ($res === false)
            return ['error' => ''];

        return ['success' => ''];
    }

    // delete version
    public function delete_version($data)
    {
        global $auth, $modelsTable, $simVersionTable;

        $version_id = check_val($data, 'version_id');
        $uid = $auth->uid();


        if (empty($version_id))
            return ["error" => $this->errors['version_id']];

        if (!belongs_to_user($simVersionTable, $version_id))
            return ["error" => $this->errors['not_allowed']];


        $res = delete_elements_by_cond($simVersionTable, 'id = :id AND user_id = :user_id', ['id' => $version_id, 'user_id' => $uid]);


        if (!empty($res))
            return ['error' => $res];
        else
            return ['success' => ''];
    }

    // load versions
    public function load_version($data)
    {
        global $auth, $modelsTable, $simSplitsTable, $simDeficitTable, $simRulesTable, $simVersionTable;

        $version_id = check_val($data, 'version_id');
        $uid = $auth->uid();


        if (empty($version_id))
            return ["error" => $this->errors['version_id']];


        $version = get_element($simVersionTable, ['id' => $version_id]);

        $model_id = $version['model_id'];

        if (!belongs_to_client($modelsTable, $model_id, false, true))
            return ["error" => $this->errors['not_allowed']];

        $version_data = parse_json($version['data']);

        // reset all data
        delete_elements_by_cond($simSplitsTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $model_id, 'user_id' => $uid]);
        delete_elements_by_cond($simDeficitTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $model_id, 'user_id' => $uid]);
        delete_elements_by_cond($simRulesTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $model_id, 'user_id' => $uid]);



        // add deficit data
        $deficits = check_val($version_data, 'deficit', []);
        if (!empty($deficits)) {
            foreach ($deficits as $deficit) {
                $deficit['user_id'] = $uid;     // set user id
                $deficit['id'] = '';        // unset id

                // log_info($deficit);

                save_element($simDeficitTable, $deficit);
            }
        }


        // change split_of ids before adding splits
        $splits = check_val($version_data, 'splits', []);
        if (!empty($splits)) {

            // change ids
            $old_ids = [];
            for ($i = 0; $i < count($splits); $i++) {

                // find only splitted items
                if (!empty($splits[$i]['split_of'])) {

                    // if not new id is attributed, do so 
                    if (!isset($old_ids[$splits[$i]['split_of']])) {
                        $old_ids[$splits[$i]['split_of']] = generate_id();
                    }

                    // change child split_of
                    $splits[$i]['split_of'] = $old_ids[$splits[$i]['split_of']];
                }
            }


            // save splits
            foreach ($splits as $split) {

                // change parent split_of id
                if (empty($split['split_of'])) {
                    $split['id'] = check_val($old_ids, $split['id'], generate_id());        // unset id
                } else {
                    $split['id'] = '';        // unset id
                }

                $split['user_id'] = $uid;     // set user id


                save_element($simSplitsTable, $split);
            }
        }



        // save rules 
        $rules = check_val($version_data, 'rules');
        if (!empty($rules)) {
            save_element($simRulesTable, ['model_id' => $model_id, 'user_id' => $uid, 'rules' => json_encode($rules)]);
        }


        return ['success' => ''];
    }

    // compare versions
    public function compare_version($data) {}

    //Missing func
    public function list_fiscal() {}


    private $errors = [
        "client_id" => "Please choose an Association !",
        "model_id" => "Please choose a Model !",
        "deficit_year" => "Please choose a Deficit Year !",
        "model_id_missing" => "Model ID is not valid",
        "not_allowed" => "Unauthorized Access",
        "missing" => "This Model doesn't exist !",
        "version_name" => "Version Name invalid !",
        "version_id" => "Version invalid !",
        "param" => "Setting Parameter missing !"
    ];

    private $success = [
        "saved" => "Saved successfully !",
        "deleted" => "Deleted successfully !",
        "updated" => "Updated successfully !",
        'getted' => "Data retrieved successfully !"
    ];
}
