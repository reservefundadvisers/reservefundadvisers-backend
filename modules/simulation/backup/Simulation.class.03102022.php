<?php

class Simulation
{  
	
    private $module_name = 'simulation';

    private $ltim_strategy = [  "start_year"=>3,
                                
                                "buckets"=>[
                                    ["dur"=>5, "rate"=>0.0771],
                                    ["dur"=>5, "rate"=>0.0862],
                                    ["dur"=>5, "rate"=>0.0942],
                                    ["dur"=>5, "rate"=>0.0963],
                                    ["dur"=>5, "rate"=>0.0977],
                                    ["dur"=>5, "rate"=>0.0988],
                                    ["dur"=>10, "rate"=>0.0993]
                                ]
                            ];
	
	function __construct()
	{
	

	}

	
	public function process($cmd, $data)
	{
        $response = "";

        switch($cmd){
            case 'get': $response = $this->simulation($data); break;
            case 'get_association': $response = $this->list_association($data); break;
            case 'get_fiscal': $response = $this->list_fiscal($data); break;
            case 'get_model': $response = $this->list_model($data); break;
            
            case 'set': $response = $this->set($data); break;           
            
            case 'deficit': $response = $this->deficit($data); break;
            case 'unsplit': $response = $this->unsplit($data); break;
            case 'update': $response = $this->update($data); break;     
            case 'reset': $response = $this->reset($data); break;   
            case 'rule': $response = $this->rules($data); break;     
            
            case 'compare': $response = $this->compare($data); break;   
                        
        }

        return $response;

	}


    

    public function simulation($data){
        global $auth, $modelItemsTable, $modelsTable, $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable, $simRulesTable, $auth;
        
        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];

        if(!belongs_to_client($modelsTable, $data['model_id']))return ['error'=>$this->errors['not_allowed']];


        
        $model = get_element($modelsTable, ['id'=>$data['model_id']]);                  // get model        
        $model_items = get_elements($modelItemsTable, ['model_id'=>$data['model_id']]); // get model items
        
        // parse inv_startegy to JSON
        $model['inv_strategy'] = parse_json($model['inv_strategy'], []);

        // Add simulation_rules
        $model['simulation_rules'] = $this->get_rules($data['model_id']);


        // force calculation mode: managed or ltim
        $mode = check_val($data, 'mode');

        $is_ltim_enabled = !empty($mode) ? $mode == 'ltim' : check_val($model, 'simulation_rules/ltim_enabled', 0);
        $deficitTable = $is_ltim_enabled ? $simDeficitLTIMTable : $simDeficitTable;
        $splitsTable = $is_ltim_enabled ? $simSplitsLTIMTable : $simSplitsTable;


        // deficit data
        $deficits  = get_elements($deficitTable, ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid()]); // get deficits


        // get calculation period
        $period = check_val($model, 'period', 29);

        // take loan
        $take_loan = check_val($data, 'take_loan', []);

        //log_info($data);

        // Prepare model item spendings
        $spendings = array_fill(0, $period, 0);  // Sum of spending for each year
        $spending_data = array_fill(0, $period, []); // items per year
        
        
        $inflation_rate = floatval($model['inflation_rate']) / 100.0;    

        
        /* Returned vars */
        $calculated = array_fill(0, $period, []);   // calculated rows for the given period
        $default_calculated = array_fill(0, $period, []);
        /* ************* */


        
        //log_info($period);

        //log_info(count($spendings));
        //log_info(count($spending_data));
        
        // forech item in the model
        foreach($model_items as $item){

            // get informations
            $name = $item['name'];
            $remaining_life = intval($item['remaining_life']);
            $redundancy = intval($item['redundancy']); if($redundancy == 0)$redundancy = 1;
            $cost = floatval($item['cost']);

            // skip if remaining time is over the current period
            if($remaining_life > $period)continue;
            

            // array index starts at 0
            $from = $remaining_life;

            // calculate how many redundancies left
            $redundancies = floor( ( $period - $from ) / $redundancy) + 1;
           
            $to = $redundancies * $redundancy;
            
            //log_info("$name  = $from -> $to + $redundancy x $redundancies -> ".($from + $redundancies * $redundancy));
            // log_info("$name =  $from -> $redundancy ($redundancies) - ".($from + $redundancies * $redundancy));

            // for each redundancy
            for($i=0; $i<$redundancies; $i++){                
                                    
                // get splits from simulation for the redundancy, 0 -> First occurence
                $splits = get_elements_join( "$splitsTable s1", ['s1.parent_id' => $item['id'], 's1.redundancy_at' => $i], 
                                             "LEFT JOIN $splitsTable s2 ON s2.split_of = s1.id", 
                                             "s1.*, count(s2.id) as splits ", "GROUP BY s1.id");

                $at = $from + ($i * $redundancy);
                

                // if the current redundancy has no splits, add original data
                if(empty($splits) && $at < $period){

                    $spendings[$at] += -1 * $cost;      // add cost to total year's spendings

                    // add inflated spending to be used in Years Of Cash calculation before hand
                    $default_calculated[$at]['sp'] = $spendings[$at];
                    $default_calculated[$at]['lp'] = ceil($spendings[$at] * $inflation_rate);
                    
                    // log_info($at);

                    // Generate new id of the master so it can be stored in model_sims instead of master one
                    array_push($spending_data[$from + ($i * $redundancy)], 
                               ['id'=>generate_id() ,
                                'name'=>$item['name'], 
                                'cost'=>$cost, 
                                'year'=>$at, 
                                'redundancy'=>$redundancy, 
                                'redundancy_at'=>$i, 
                                'parent_id'=>$item['id'], 
                                'split_of' => '', 
                                'splits' => 0 ]);

                }else if(!empty($splits)){
                    
                    // else if has splits, add each split with corresponding redundancy and year
                    foreach($splits as $split){

                        $split_spending_year = $split['year'] < $period ? $split['year'] : $period;
                        $spendings[$split_spending_year] += -1 * $split['cost'];      // add cost to total year's spendings
                        
                        // add inflated spending to be used in Years Of Cash calculation before hand
                        $default_calculated[$at]['sp'] = $spendings[$split_spending_year];
                        $default_calculated[$split_spending_year]['lp'] = ceil($spendings[$split_spending_year] * $inflation_rate);
                        
                        // log_info("$split_spending_year (Split)");

                        array_push($spending_data[$split_spending_year], 
                                  ['id'=>$split['id'] ,
                                   'name'=>$item['name'], 
                                   'cost'=>$split['cost'], 
                                   'year'=>$split_spending_year, 
                                   'redundancy'=>$redundancy, 
                                   'redundancy_at'=>$i, 
                                   'parent_id'=>$split['parent_id'], 
                                   'split_of' => $split['split_of'], 
                                   'splits' => $split['splits'] ]);
                    }
                }
                
                
            }




        }

        /* MODEL INFOS */
        $init_starting_amount = floatval($model['starting_amount']);
        $starting_amount = floatval($model['starting_amount']);
        $starting_amount_o = $starting_amount;
        $housing = floatval($model['housing']); if($housing < 1)$housing = 1;
        
        // Monthly Fees Collection
        $monthly_fees = floatval($model['monthly_fees']);   
        $yearly_collections = floatval($monthly_fees * $housing * 12);
        $new_monthly_fees = $monthly_fees;
        $auto_monthly_fees = array_fill(0, $period, $monthly_fees);
                    
        
        $bank_rate = floatval(check_val($model, 'bank_rate', 0)) / 100.0;
        $loan_years = floatval(check_val($model, 'loan_years', 1));


        $client_invest_strategy = $model['inv_strategy']; //0.0055;
        $invest_strategy = $model['inv_strategy'];
        /* ********** */
        

        /* General Rules */

        $rule_ltim_perc = $is_ltim_enabled && check_val($model['simulation_rules'], 'ltim_perc', 0);
        $rule_ltim_cover = $is_ltim_enabled && check_val($model['simulation_rules'], 'ltim_cover', 0);
        $rule_mf_auto = check_val($model['simulation_rules'], 'mf_auto', 0) == 1;
        $rule_mf_perc = check_val($model['simulation_rules'], 'mf_perc', 0);
        $rule_cushion_fund = check_val($model['simulation_rules'], 'cushion_fund', 0);
        $rule_inv_keep = check_val($model['simulation_rules'], 'inv_keep', 0);
        
        /* ********** */


        /* calculation vars */
        $final_amount = 0;      // used for final calulated amount to be used as new starting_amount    
        $loan_payments = array_fill(0, $period, 0);     // different loan to take for next year
        $remaining_loan_payments = 0;
        /* ************** */
      
        
        


        
        /* Erase Deficit Data */
        $deficit_array = [];
        foreach($deficits as $deficit){
            $deficit_array[$deficit['year']] = parse_json($deficit['data']);
                        
        }       
        /* ************** */

        //--------------------
        // Base Calculations
        //--------------------

        $deficit_years = [];
        $processed_loans = [];

        

        // init original unmanaged data
        $this->initOriginalCalculation( $calculated, $default_calculated, $deficit_years, 
                                        $starting_amount_o, $monthly_fees, $housing, $client_invest_strategy, $period);

        // log_info($calculated);
        


        // to prevent infinite loop
        set_time_limit(10);


        $processed_deficits = array();

        // then managed calculations
        for($i=0; $i<$period; $i++){

            
            // get erase deficit data
            $erase_deficit = check_val($deficit_array, $i, []); 


            // get any calculated as year_calculations      
            $year_calculations = $calculated[$i];
            
            // current year spendings, array index starts at 0
            $spending = $year_calculations["sp"]; // $spendings[$i];

                                    
            // Year Deficit Management variables
            $loan_amount = 0; 


            // Client Inv Strategy
            if(isset($erase_deficit['inv_strategy'])){
                $invest_strategy = $erase_deficit['inv_strategy'];
            }else if(!$rule_inv_keep){
                $invest_strategy = $client_invest_strategy;
            }
            

            // Loan
            if(isset($erase_deficit['loan_amount'])){
                $loan_amount = check_val($erase_deficit, 'loan_amount', 0);
                $loan_calc = $this->calculateLoan($loan_payments, $loan_amount, $bank_rate, $loan_years, $i, $period, !array_has($processed_loans, $i));
                
                $year_calculations['loan_i'] = $loan_calc['total_i'];
                $year_calculations['loan_pi'] = $loan_calc['total'];

                array_push($processed_loans, $i);
            }

            // get previous year loan payment
            $loan_payment = $loan_payments[$i] * -1;
            $year_calculations['loan_y'] = $loan_payment;
            



            // LTIM Invest Surplus Percentage
            if($is_ltim_enabled && isset($erase_deficit['ltim_perc'])){
                $ltim_perc = floatval(check_val($erase_deficit, 'ltim_perc', 0));
            }else{
                $ltim_perc = $rule_ltim_perc;
            }            
            if($ltim_perc > 1)$ltim_perc = 1; else if($ltim_perc < 0)$ltim_perc = 0;

            
            // LTIM Strategy 
            $ltim_str = $this->calculateLTIM($spendings, $i);
            
            // LTIM Years Of Cash
            $ltim_yoc = $this->calculateYearsOfCash($calculated, $i, 3);


            // LTIM Withdraw to cover deficit
            $ltim_withdrawn = 0;
            if(isset($erase_deficit['ltim_wth'])){
                $ltim_withdrawn = check_val($erase_deficit, 'ltim_wth', 0); 
            }            
            if($ltim_withdrawn < 0 || $ltim_withdrawn > check_val($year_calculations, 'ltim_acc', 0))$ltim_withdrawn = 0; 
                        

            // managed calculations
            $this->calculateYearData(   $starting_amount,                                         
                                        $auto_monthly_fees[$i], $housing,
                                        $invest_strategy,
                                        $year_calculations["lp"],
                                        $loan_amount,
                                        $loan_payment,
                                        $spending, 
                                        $erase_deficit,
                                        $ltim_perc, $ltim_withdrawn, $ltim_yoc, $ltim_str, true,
                                        $year_calculations, $i );
            
            
            // LTIM Auto cover deficit
            if(!isset($erase_deficit['ltim_wth']) && $is_ltim_enabled && $rule_ltim_cover == 1 && $year_calculations['fa'] < 0){
                
                $ltim_available = check_val($year_calculations, 'ltim_acc', 0);
                $deficit_to_cover = -1 * $year_calculations['fa'];

                if($ltim_available > $deficit_to_cover){
                    $deficit_array[$i]['ltim_wth'] = $deficit_to_cover;
                    // recalculate current year after widthrawing amount
                    $i--; continue;
                }

            }

            // log_info("$i -> ", $year_calculations);
            
            
            if($i == 0){
                $year_calculations['ltim_acc'] = 0; 
                $year_calculations['ltim_acc_p'] = 0; 
                $year_calculations['ltim_acc_ne'] = 0; 
            }
            
            if($i < $period - 1){
                
               
                $calculated[$i+1]['ltim_acc_p'] = floor($year_calculations['ltim_acc'] + $year_calculations['ltim_p'])  - $ltim_withdrawn;
                if($calculated[$i+1]['ltim_acc_p'] < 0)$calculated[$i+1]['ltim_acc_p'] = 0;

                $calculated[$i+1]['ltim_acc_ne'] = floor($calculated[$i+1]['ltim_acc_p'] * check_val($year_calculations['ltim_str'], 'avr', 0));
                $calculated[$i+1]['ltim_acc'] = $calculated[$i+1]['ltim_acc_p'] + $calculated[$i+1]['ltim_acc_ne'];
                
                

                // $prev_ltim_acc = $i == 0 ? 0 : $calculated[$i - 1]['ltim_acc']; 
                // $year_calculations['ltim_acc'] =  $prev_ltim_acc + $year_calculations['ltim_p'] + $year_calculations['ltim_ne'];
                // if($year_calculations['ltim_acc'] < 0)$year_calculations['ltim_acc'] = 0;
                // else $year_calculations['ltim_acc'] *= $year_calculations['ltim_str']['rate'];
                
            }

            unset($year_calculations['ltim_str']);
            



            // IF DEFICIT AUTO MF INCREASE
            if($rule_mf_auto && $year_calculations['fa'] < 0 && array_has($deficit_years, $i) && !array_has($processed_deficits, $i) ){
                
                // log_info("Deficit Year ".$i.": ".$year_calculations['fa']);
                $this->updateMonthlyFeeInc($i, ($year_calculations['fa']/(12*$housing)), $auto_monthly_fees, $rule_mf_perc, $monthly_fees, $rule_cushion_fund);
                array_push($processed_deficits, $i);

                // log_info("Adjusted MF Deficit -> $i", $auto_monthly_fees);

                $this->initOriginalCalculation( $calculated, $default_calculated, $deficit_years,
                                                $starting_amount_o, $monthly_fees, $housing, $client_invest_strategy, $period);

                $starting_amount = $starting_amount_o;

                $i = -1;
                continue;
            }

            // $year_calculations['ltim_r'] = $ltim_rates[$i] * 100;
            $calculated[$i] = $year_calculations;

            
            //log_info("$starting_amount -> $final_amount");

            // next year starting amount as current year final amout, 0 if negatif
            $starting_amount = ($year_calculations['fa'] < 0 ? 0 : $year_calculations['fa']);

            // REMOVE LTIM ALLOCATED AMOUNT FROM NEXT YEAR'S STARTING AMOUNT
            $starting_amount -= $year_calculations['ltim_p'];

            
            
        }



        // add Years Of Cash
        
        
        
        // log_info("MF ", $auto_monthly_fees);
        


        //log_info($this->loanMonthlyPayment(1000, $bank_rate, $loan_years * 12));
        //log_info($this->loanMonthlyPayment(1000*0.36, $bank_rate, $loan_years * 12));
        //log_info($this->loanMonthlyPayment(1000*0.75, $bank_rate, $loan_years * 12));
        //log_info($this->loanMonthlyPayment(1000*0.01, $bank_rate, $loan_years * 12));


        $results = ["model"=>$model, "items"=>$model_items, "spendings"=>$spending_data, "calculated"=>$calculated, "other"=>['remaining_loans' => $remaining_loan_payments]];

        return $results;
        
    }

    public function list_association($data){
        global $auth, $clientsTable;


        $pagination = format_pagination($data);
        $is_pagination = !empty($pagination);

        $conds = array();
        
        if($auth->checkRole('client_admin') || $auth->checkRole('client_user'))
            $conds['id'] = $auth->clientId();

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
        $conds['client_id'] = $auth->checkRole('client_admin') || $auth->checkRole('client_user') ? $auth->clientId() : check_val($data, 'client_id', $auth->clientId());

        if($is_pagination){
            $count = get_element(  $modelsTable, $conds, "COUNT($modelsTable.id) AS count");            
            if(isset($count['count']))$total = (int)(intval($count['count'])/$pagination['size'])+1;
        }

        $results = get_elements($modelsTable, $conds,
                                        "$modelsTable.id, $modelsTable.name, $modelsTable.fiscal_year, $modelsTable.created_at", "ORDER BY $modelsTable.fiscal_year DESC".check_val($pagination, 'query'));

        
        for($i=0; $i<count($results); $i++){
            if(strlen($results[$i]['fiscal_year']) < 3){
                $results[$i]['fiscal_year'] = date("Y", intval($results[$i]['created_at']));
            }
        }

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

        
        if(!belongs_to_client($modelsTable, $data['id']))return ['error'=>$this->errors['not_allowed']];


        return set_property($modelsTable, $data);

    }

    private function initOriginalCalculation(   &$calculated, &$default_calculated, &$deficit_years,
                                                $starting_amount_o, $monthly_fees, $housing, $client_invest_strategy, 
                                                $period){
        
        $calculated = array_fill(0, $period, []);
        $default_calculated;
        $year_calculations = array_fill(0, $period, []);

        // fill original unmanaged data
        for($i=0; $i<$period; $i++){
            

            $calculated[$i] = $default_calculated[$i];

            // get any future year_calculations      
            $year_calculations = $calculated[$i];
            $year_calculations["year"] = $i;


            // current year spendings, array index starts at 0
            $spending = $year_calculations["sp"]; // $spendings[$i];

            // original unmanaged calculations
            $this->calculateYearData(   $starting_amount_o,                                         
                                        $monthly_fees, $housing,
                                        $client_invest_strategy,
                                        $year_calculations["lp"],
                                        0,
                                        0,
                                        $spending, 
                                        [],
                                        0, 0, 0, [], false,
                                        $year_calculations, $i,
                                        "_o" );

            // add to calculated                                        
            $calculated[$i] = $year_calculations;

            // calculate auto monthly fees inc
                        
            if($year_calculations['fa_o'] < 0)
                array_push($deficit_years, $i);
            
            
            $starting_amount_o = $year_calculations['fa_o'] < 0 ? 0 : $year_calculations['fa_o'];
        }
    }

    public function calculateYearData(  $starting_amount,                                         
                                        $monthly_fees, $housing,
                                        $invest_strategy,
                                        $loss_purchase,
                                        $loan,
                                        $prev_loan_payment,
                                        $spending, 
                                        $erase_deficit,
                                        $ltim_perc = 0, $ltim_wth = 0, $ltim_yoc = 0, $ltim_str = [], $impl_ltim = false,
                                        &$year_calculations, $year,                                        
                                        $suffix = ""){
  
            

            $starting_amount += $ltim_wth;

                       
            // yearly collections
            $yearly_collections = $monthly_fees * 12 * $housing;

            $invest_rate =  $this->calculateClientInvStrategy($invest_strategy);

            
            // log_info("MF: $monthly_fees ($monthly_fees_inc)");
            
            


            // assessment
            $assessment = check_val($erase_deficit, 'assessment', 0);

            // loss in purchase power due to inflation
            // $loss_purchase = /* -1 * */ ceil($spending * ($inflation_rate)); /*$compound - ceil($total_amount * (1 + $inflation_rate)); */ 
                // if($loss_purchase > 0) $loss_purchase = 0;
                        
            $total_expenses = $loss_purchase + $spending;


            // total available strating amount
            $total_amount = ceil($starting_amount + $yearly_collections /* - $ltim_amount */); 

            // LTIM Surplus
            $ltim_spl = $total_amount - $ltim_yoc; 
            if($ltim_spl < 0)$ltim_spl = 0;

            // LTIM Principal 
            $ltim_p = $impl_ltim && $ltim_spl > 0 ? $ltim_perc * $ltim_spl : 0;


            // LTIM Earnings            
            $ltim_ne = $ltim_p * check_val($ltim_str, 'avr', 0);

            //log_info("$total_amount  * $invest_rate");

            // net eargnings 
            $client_inv_principal = (($total_amount < 0 ? 0 : $total_amount) ) - $ltim_p;           
            $net_earning = ceil( $client_inv_principal  * $invest_rate);  
                if($net_earning < 0)$net_earning = 0;

            // compound starting amount from investment
            $compound = ceil( ($total_amount < 0 ? 0 : $total_amount) + $net_earning  + $assessment + $loan);
            
            //$inv_loss_purchase = $investment - ceil($inv_total_amount * ($inflation_rate)); if($inv_loss_purchase > 0) $inv_loss_purchase = 0;
            
            // remaining amount after spending
            $final_amount = $compound + $total_expenses + $prev_loan_payment;


            // log_info("$total_amount ($starting_amount + $yearly_collections) + $net_earning  + $assessment + $loan = $compound");


            // $final_amount += $assessment + $ltim_amount + $ltim_i; // - check_val($erase_deficit, 'ltim', 0);

            if($final_amount >= -1 && $final_amount < 0)$final_amount = 0;


            $tmp_year_calculations = [  

                                        "sa$suffix"=>$starting_amount, 
                                        "yc$suffix"=>$yearly_collections,
                                        "mf$suffix"=>$monthly_fees,
                                        

                                        "ta$suffix"=>$total_amount, 
                                        
                                        "ir$suffix"=>$invest_rate,
                                        "is$suffix"=>$invest_strategy,
                                        "ip$suffix"=>$client_inv_principal,

                                        "ne$suffix"=>$net_earning, 
                                        "cp$suffix"=>$compound, 
                                        
                                        "lp$suffix"=>$loss_purchase,
                                        "sp$suffix"=>$spending,

                                        "loan_t"=>$loan,
                                        "loan_pay"=>$prev_loan_payment,
                                        
                                        "tx$suffix"=>$total_expenses,

                                        "fa$suffix"=>$final_amount,

                                        "deficit"=>$erase_deficit,

                                        "assess"=>$assessment,

                                        "ltim_yoc"=>$ltim_yoc,
                                        "ltim_spl"=>$ltim_spl,
                                        "ltim_p"=>$ltim_p,
                                        "ltim_ne"=>$ltim_ne,
                                        "ltim_str"=>$ltim_str
                                                                        
                                    ];

            $year_calculations = array_merge($year_calculations, $tmp_year_calculations);

            return $tmp_year_calculations;

    }

    public function loanMonthlyPayment($amount, $interest, $numOfMonths){
        

        $rate = $interest / 12;
        $rate = round($rate, 7); 

        if(empty($interest) || $rate <= 0)return $amount / $numOfMonths;

        $monthlyPayment = ($rate + $rate / (pow($rate + 1, $numOfMonths) - 1)) * $amount;
        $monthlyPayment = round($monthlyPayment, 4);

        return $monthlyPayment;

    }


    private function calculateLoan(&$arr, $amount = 0, $bank_rate = 0, $loan_years = 1, $from = 0, $period = 1, $addPaymentsToArr = true){

            // if($amount < 0){
            //     $remaining_payments = 0;
            //     $loan = 0;
            //     $yearly_payment = 0;
            //     return;
            // }

            
            
            // get monthly payment of the given amount
            $payment = abs($this->loanMonthlyPayment($amount, $bank_rate, $loan_years * 12));
            
            
            $remaining_payments = 0;        // calculate remaining payments if loan years exceeds calculation period      
            $to = ($loan_years + $from);    // get the last payment
            
            if($to > $period){ $remaining_payments = $to - $period - 1; $to = $period; } // if exceeds period, set period as limit
            

            // start loan payemnt in the next year
            $from++;
            if($from > $to)$from = $to;
            
            //log_info("$amount ($bank_rate) -> $payment : [$from -> $to] / $tmp_remaining_payments");

            // add loan yearly payment as reference
            $yearly_payment = $payment * 12;
            $loan = $yearly_payment * $loan_years;

            // add current payment to calculated loan_pay per calculated year
            if($addPaymentsToArr){
                for($i=$from; $i<$to; $i++){
                    $arr[$i] += $yearly_payment;

                }
            }

            $remaining_payments *= $yearly_payment;

            return ['total'=>$loan, 'total_i'=>$loan-$amount, 'yearly'=>$yearly_payment, 'remaining'=>$remaining_payments];

            
            //log_info("$amount $loan, $yearly_payment $remaining_payments");
            
    }

    private function calculateClientInvStrategy($strategy){
        $avr = 0;

        if(!is_array($strategy))return $avr;

        foreach($strategy as $s){
            if(!is_assoc($s))continue;

            $avr += check_val($s, 'rate', 0) * check_val($s, 'perc', 0) / 100;
        }

        $avr /= 100;
        //log_info($avr);

        return $avr;
    }

    private function calculateLTIM($spendings, $startAt = 0){
        global $log_file, $log_dir;


        $buckets = []; $deficit_sum = 0;
        $from = check_val($this->ltim_strategy, 'start_year', 3) + $startAt;
        $num_spendings = count($spendings);

        $ltim_strategy_period = 0;
        foreach($this->ltim_strategy['buckets'] as $bucket)
            $ltim_strategy_period += $bucket['dur'];
        
        // log_info("LTIM Period: $ltim_strategy_period");
        // log_info("Num Of Spendings: $num_spendings");

        if(file_exists("$log_dir/ltim.csv"))
            unlink("$log_dir/ltim.csv");
        

        if($from >= $num_spendings)return $buckets;

        $content = "";

        foreach($this->ltim_strategy['buckets'] as $bucket){
            
            // $content = "Bucket From $from To ";

            $to = $from + $bucket['dur'] < $num_spendings ? $bucket['dur'] : ($num_spendings - $from);

            // log_info("$from $to");
                    
            if($from >= $num_spendings)break;
            // else if($to >= $ltim_strategy_period)$to = $ltim_strategy_period;

            $bucketSum = 0;
            
            // $content .= "[;";
            for($i=0; $i < $to; $i++ ){
                // log_info("$from ");

                $bucketSum += - 1 * $spendings[$from + $i];

                $content .= (- 1 * $spendings[$from + $i]) . ";";
                                
            }
            $content .= "SUM BUCKET;$bucketSum;=ARRONDI($bucketSum*100/B". (1 + $startAt * 2) .");%;;";
            
            $from += $to;
            // $content .= $from;
            // log_info($content);

            $deficit_sum += $bucketSum;            
            array_push($buckets, ['sum'=>$bucketSum, 'rate'=>$bucket['rate'] ]);
            
        }
        

        $avr_rate = 0;
        foreach($buckets as &$bucket){
            $bucket['ratio'] =  $deficit_sum > 0 ? $bucket['sum'] / $deficit_sum : 0;
            $avr_rate += $bucket['ratio'] * $bucket['rate'];
        }

        // log_info($bucketSum, $avr_rate, $buckets);

        //file_put_contents($log_file, "SUM Spendings;$deficit_sum;\n" . $content . "\n", FILE_APPEND);
        
        //file_put_contents("$log_dir/ltim.csv", "SUM Spendings;$deficit_sum;\n" . $content . "\n", FILE_APPEND);

        // return ['sum'=>$deficit_sum, 'avr'=>$avr_rate, 'buckets'=>$buckets];
        return ['sum'=>$deficit_sum, 'avr'=>$avr_rate];


    }


    private function calculateYearsOfCash($calculated, $startAt=0, $inflation_rate = 0, $years=3){
        
        $yoc = 0;
        $period = count($calculated);

        $to = $startAt + $years; if($to > $period)$to = $period; 
        
        for($j=$startAt; $j<$to; $j++){
            $yoc += -1 * ($calculated[$j]['sp'] +  $calculated[$j]['lp']);            
        }

        return $yoc;
    }

    private function getStartIncreaseYear(&$mfs, $max_inc){
        
        $y = 0;
        for($y; $y<count($mfs)-1; $y++){
            $diff = ($mfs[$y+1] - $mfs[$y]) / $mfs[$y];
            // log_info("$diff / $max_inc");
            if($diff < $max_inc)break;
        }

        return $y;
    }

    private function estimateMonthlyFeeInc($years, $mf, $deficit, $max_inc){

        $deficit = abs($deficit);

        if($max_inc <= 0){
            return $deficit / ($mf * $years);
        }

        $max = $max_inc; $max_val = 0;
        $min = 0; $min_val = 0;

        $years++;

        // log_info("($years, $mf, $deficit, $max_inc)");

        // get positive & negative when > 1
        for($i=$max_inc; $i>0; $i -= 0.01){
            $inc_multi = ((pow(1+$i, $years+1) - 1)/$i) - 1 ;
            $cumul = ($mf * $inc_multi) - ($mf * $years);
            $diff = $cumul - $deficit;

            // log_info("(".($years+1).") -> [$i] -> $inc_multi, $cumul, $diff");

            if($diff < 0){ $min = $i; $min_val = $diff; break; }
            else { $max = $i; $max_val = $diff; }
        }

        //  log_info("$max($max_val) > $min($min_val)");

        $inc = 0;
        // get positive & negative when < 1 and step by 0.05%
        if($min == 0){
            for($i=$max_inc; $i>0; $i -= 0.00005){
                $inc_multi = ((pow(1+$i, $years+1) - 1)/$i) - 1 ;
                $cumul = ($mf * $inc_multi) - ($mf * $years);
                $diff = $cumul - $deficit;
    
                if($diff < 0){ $min = $i; $min_val = $diff; break; }
                else { $max = $i; $max_val = $diff; }
            }
            $inc = 0.0002 + $max - ($max_val * ($max - $min) / ($max_val - $min_val));
        }else{
            $inc = 0.001 + $max - ($max_val * ($max - $min) / ($max_val - $min_val));
        }
        
        // log_info("$max($max_val) > $min($min_val");
        
        // log_info("Montly Fee Increase Needed to cure \$$deficit in $years years is: ".($inc*100)."%");
        
        return $inc;
        // }else{
            // log_info("Montly Fee Increase CANNOT cure \$$deficit in $years years with Limit of ".($max_inc*100)."%");
        // }

    }

    private function updateMonthlyFeeInc($year, $deficit, &$auto_monthly_fees, $max_inc, $default_monthly_fee, $cushion_fund = 0){
        

            $start_year = 0; 
            $total_years = $year - $start_year;

            $period = count($auto_monthly_fees);

            // log_info("deficit: $deficit, start_year: $start_year, total_years: $total_years");
            
            $base_mf = $auto_monthly_fees[$start_year];
            
            $inc = $this->estimateMonthlyFeeInc($total_years, $base_mf, $deficit, $max_inc) + $cushion_fund;

            $new_monthly_fees = $base_mf;
            
            // blindly apply to whole period
            for($y=0; $y<$period; $y++){
                if($y > $year){
                    $auto_monthly_fees[$y] = $new_monthly_fees;
                    continue;
                }


                $inc_by = $base_mf * pow(1 + $inc, $y + 1) - $base_mf;
                    
                // add it to the lf array
                $auto_monthly_fees[$start_year + $y] += $inc_by;  

                
                // set as new mf for all subsequent years
                $new_monthly_fees = $auto_monthly_fees[$start_year + $y];

            }

            // adjust to max fee inc allowed
            for($y=0; $y<$period; $y++){

                if($y==0){
                    $diff = ($auto_monthly_fees[0] - $default_monthly_fee) / $default_monthly_fee;
                    if($diff > $max_inc)$auto_monthly_fees[0] = $default_monthly_fee * (1 + $max_inc);
                    continue;
                }
                
                $diff = ($auto_monthly_fees[$y] - $auto_monthly_fees[$y-1]) / $auto_monthly_fees[$y-1];

                // log_info("inc by: $diff/$max_inc");
                if($diff > $max_inc){
                    $auto_monthly_fees[$y] = $auto_monthly_fees[$y-1] * (1 + $max_inc);
                }
            }

            /*
            // add Contingency Funds
            if($cushion_fund > 0){
                for($y=0; $y<$period; $y++){

                    $auto_monthly_fees[$y] *= 1 + $cushion_fund;
                   
                }
            }
            */

    }

    public function reset($data){
        global $auth, $modelsTable, $simRulesTable, $simDeficitTable, $simSplitsTable, $simSplitsLTIMTable, $simDeficitLTIMTable;


        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];
        
        if(!belongs_to_client($modelsTable, $data['model_id']))return ['error'=>$this->errors['not_allowed']];

        $model_id = $data['model_id'];

        $which = check_val($data, 'which', 'current');

        // check if LTIM is enabled in the simulation
        $is_ltim_enabled = check_val($this->get_rules($model_id), 'ltim_enabled', 0);

        
        $deficitTable = $is_ltim_enabled ? $simDeficitLTIMTable : $simDeficitTable;
        $splitsTable = $is_ltim_enabled ? $simSplitsLTIMTable : $simSplitsTable;
    


        
        // reset by year
        if(is_valid($data, "year")){    

            $year = $data["year"];      
            
            // delete erase deficit data
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year = :year', ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>$year]);

        }else if(is_valid($data, "from_year")){    

            $year = $data["from_year"];
          
            // delete erase deficit data
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year >= :year', ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>$year]);

        }else{
        
            // delete_elements_by_cond($simRulesTable, 'model_id = :model_id AND user_id = :user_id', ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid()] );

            if($which == 'current'){
            
                delete_elements_by_cond($splitsTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);
                delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);

            }else{

                delete_elements_by_cond($simSplitsTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);
                delete_elements_by_cond($simDeficitTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);
                delete_elements_by_cond($simSplitsLTIMTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);
                delete_elements_by_cond($simDeficitLTIMTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);
                delete_elements_by_cond($simRulesTable, 'model_id = :model_id AND user_id = :user_id', ['model_id' => $data['model_id'], 'user_id'=>$auth->uid()]);

            }
            
            

        }

        return ['success'=>''];

    }

    // handles erase deficit data
    public function deficit($data){
        global $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable, $modelsTable, $clientsTable, $auth;

        // get model info and year to erase deficit for
        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id'] ];
        if(!is_valid($data, 'year') || intval($data['year']) < 0)return ['error'=>$this->errors['deficit_year'] ];
        //if(!is_valid($data, 'to') || intval($data['to']) < 0)return ['error'=>$this->errors['deficit_to'] ];

        
        $year = $data['year'];        
        $model_id = $data['model_id'];

        $model = get_element($modelsTable, ['id'=>$model_id]);

        if(empty($model))return ['error'=>$this->errors['missing'] ];
        
        if(!belongs_to_client($modelsTable, $model_id))return ['error'=>$this->errors['not_allowed'] ];

        // check if LTIM is enabled in the simulation
        $is_ltim_enabled = check_val($this->get_rules($model_id), 'ltim_enabled', 0);
        $deficitTable = $is_ltim_enabled ? $simDeficitLTIMTable : $simDeficitTable;
        $splitsTable = $is_ltim_enabled ? $simSplitsLTIMTable : $simSplitsTable;


        // log_info($data);


        // add erase deficit data
        if(isset($data['deficit'])){            
            
            if(!empty($data['deficit']) && is_assoc($data['deficit'])){
                
                
                // Apply These properties to the end of the period
                $apply_prop = [];
                $remove_prop = [];                

                $deficit_of_year = get_element($deficitTable, ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>$year]);

                if(!empty($deficit_of_year)){ 

                    $deficit_of_year['data'] = parse_json(check_val($deficit_of_year, 'data', []), []);
                    if(isset($data['deficit']['ltim_perc'])){

                        // check LTIM percentage
                        if( floatval(check_val($deficit_of_year, 'data/ltim_perc', 0)) > floatval($data['deficit']['ltim_perc']) ){
                            array_push($remove_prop, 'ltim_wth');
                        }
                    } else if(isset($data['deficit']['ltim_wth'])){


                        // check LTIM Withdraw
                        if( floatval(check_val($deficit_of_year, 'data/ltim_wth', 0)) < floatval($data['deficit']['ltim_wth']) ){
                            array_push($remove_prop, 'ltim_wth');
                        }
                    } 
                }
                
                

                save_element($deficitTable, ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>$year, /* 'to'=>$to, */ 'data'=>json_encode($data['deficit'])], ['model_id', 'user_id', 'year']);
                

                if(!empty($remove_prop)){
                    $period = check_val($model, 'period', 0);
                    
                    for( $i = $year + 1; $i < $period; $i++ ){
                        
                        // get year deficit
                        $deficit_of_year = get_element($deficitTable, ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>$i]);

                        // if has a deficit data
                        if(!empty($deficit_of_year)){
                            $deficit_of_year['data'] = parse_json($deficit_of_year['data']);
                            foreach($remove_prop as $prop){
                                if(isset($deficit_of_year['data'][$prop]))unset($deficit_of_year['data'][$prop]);
                            }
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

            }else{
                delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year = :year', ['model_id'=>$model_id, 'user_id'=>$auth->uid(), 'year'=>$year]);
            }
        }

             
        $split_min_year = 999;

        // add splits
        if(is_valid($data, 'splits') && !empty($data['splits'])){   
            foreach($data['splits'] as $split){
                $split['user_id'] = $auth->uid();
                $split['model_id'] = $model_id;
                if($split['year'] < $split_min_year)$split_min_year = $split['year'];
                save_element($splitsTable, $split);
            }
            
            
        }

        // unsplit
        if(is_valid($data, 'unsplit') && !empty($data['unsplit'])){
            foreach($data['unsplit'] as $unsplit){
                $res = $this->unsplit(['id'=>$unsplit]);
                if(isset($res['success']) && $res['success'] < $split_min_year)$split_min_year = $res['success'];
            }                        
        }

        // delete simulation deficit data
        delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year >= :year', 
                                ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid(), 'year'=>$split_min_year]);
                    

        
        return ['success' => ''];        

    }

    // unsplit item 
    public function unsplit($data){
        global $simSplitsTable, $simDeficitTable, $simSplitsLTIMTable, $simDeficitLTIMTable, $modelsTable, $clientsTable, $auth;

        // get item's id  to unsplit
        if(!is_valid($data, 'id'))return ['error'=>$this->errors['item_id'] ];

        // tables
        $deficitTable = $simDeficitTable;
        $splitsTable  = $simSplitsTable;

        // check if item in LTIM split or other
        if(exists($simSplitsLTIMTable, ['id'=>$data['id'], 'user_id'=>$auth->uid()])){
            
            $deficitTable = $simDeficitLTIMTable;
            $splitsTable  = $simSplitsLTIMTable;

        }else if(!belongs_to_user($splitsTable, $data['id']))return ['error'=>$this->errors['not_allowed'] ];

        $item = get_element($splitsTable, ['id' => $data['id']]);

        $year = 999;
        
        // add cost to split_of item
        if(!empty($item) && !empty($item['split_of'])){
            $split_of = get_element($splitsTable, ['id' => $item['split_of']]);
            if(!empty($split_of)){
                $split_of['cost'] += $item['cost'];
                save_element($splitsTable, $split_of);

                
                $year = $split_of['year'];
            }            
        }else{
            $year = $item['year'];
        }
        
        // delete it
        delete_elements_by_id($splitsTable, $data['id']);
        
        // delete simulation deficit data
        if(is_valid($data, 'model_id'))
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year >= 0', ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid()]);
        
        return ['success' => $year];        

    }
    

    public function rules($data){
        global $auth, $modelsTable, $simRulesTable;


        if(!is_valid($data, 'model_id')){
            return ['error' => ''];
        }

        if(!belongs_to_client($modelsTable, $data['model_id']))return ['error' => $this->errors['not_allowed'] ];

        $prev_rules = $this->get_rules($data['model_id']);

        // If asked to enable LTIM
        $ltim_enabled = check_val($data, 'rules/ltim_enabled', 0);
        $prev_ltim_enabled = check_val($prev_rules, 'ltim_enabled', 0);

        if($ltim_enabled == 1 && $prev_ltim_enabled == 0){
            $data['rules']['ltim_perc'] = check_val($prev_rules, 'ltim_perc', 1);
            $data['rules']['ltim_cover'] = check_val($prev_rules, 'ltim_cover', 1);
        }

        foreach($data as $k => $v){
            if(is_array($v))
                $data[$k] = json_encode($v);
        }

        if(!is_valid($data, 'rules')){
            delete_elements_by_cond($simRulesTable, 'model_id = :model_id AND user_id = :user_id', ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid()] );
        }else{
            save_element($simRulesTable, ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid(), 'rules'=>$data['rules']], ['model_id', 'user_id']);
        }

        return ['success'=>''];

    }


    // update simulation model item data
    public function update($data){
        global $simSplitsTable, $simSplitsLTIMTable, $modelsTable, $clientsTable, $auth;

        // the id is pregenerated, so if it doesnt exist just add it as is
        // and the parent_id is the master item id

        if(!is_valid($data, 'id'))return ['error'=>$this->errors['item_id'] ];

        // check if LTIM is enabled in the simulation
        $is_ltim_enabled = check_val($model, 'ltim_enabled', 0);
        $deficitTable = $is_ltim_enabled ? $simDeficitLTIMTable : $simDeficitTable;
        $splitsTable = $is_ltim_enabled ? $simSplitsLTIMTable : $simSplitsTable;

        $exists = exists($splitsTable, ['id' => $data['id']]);
        
        if($exists && !belongs_to_user($splitsTable, $data['id']))return ['error'=>$this->errors['not_allowed'] ];
        
        if(is_valid($data, 'split_of')){
            $split_already_exist = get_element($splitsTable, ['parent_id'=>$data['parent_id'], 'redundancy_at'=>$data['redundancy_at'], 'split_of'=>$data['split_of'], 'year'=>$data['year']]);
            if(!empty($split_already_exist)){
                return ['error'=>$this->errors['split_exists']];
            }
        }
        

        $data['user_id'] = $auth->uid();
        
        save_element($splitsTable, $data);

        // delete simulation deficit data
        if(is_valid($data, 'model_id'))
            delete_elements_by_cond($deficitTable, 'model_id = :model_id AND user_id = :user_id AND year >= 0', ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid()]);
        
                
        return ['success' => ''];        

    }

    private function get_rules($model_id){
        global $auth, $simRulesTable, $modelsTable;
        
        if(empty($model_id) || !belongs_to_client($modelsTable, $model_id) )
            return ["ltim_enabled"=>0, "ltim_perc"=>1,"ltim_cover"=>1,"mf_perc"=>0, "mf_auto"=>0, "cushion_fund"=>0,"inv_keep"=>1];

        $rules = parse_json(check_val(get_element($simRulesTable, ['model_id'=>$model_id, 'user_id'=>$auth->uid()]), 'rules', []), []);

        // get default monthly fees increas
        $model = get_element($modelsTable, ['id'=>$model_id]);
        $max_mf_inc = check_val($model, "monthly_fees_rate", 0) / 100;
        $cushion_fund = check_val($model, "cushion_fund", 0) / 100;

        return !empty($rules) ? $rules : ["ltim_enabled"=>0, "ltim_perc"=>1,"ltim_cover"=>1,"mf_perc"=>$max_mf_inc, "mf_auto"=>0, "cushion_fund"=>$cushion_fund, "inv_keep"=>1];
    }

    

    public function compare($model_id){
        global $auth, $simRulesTable, $modelsTable;
        
        if(empty($model_id))
            return ["error"=>$this->errors['model_id']];
        
        if(!belongs_to_client($modelsTable, $model_id))
            return ["error"=>$this->errors['not_allowed']];

        $compare['managed'] = $this->simulation(['model_id'=>$model_id, 'mode'=>'managed']);
        $compare['ltim'] = $this->simulation(['model_id'=>$model_id, 'mode'=>'ltim']);
        $compare['rules'] = $this->get_rules($model_id);

        return $compare;
    }

    private $errors = [ "client_id" => "Please choose an <b>Association</b> !",
                        "model_id" => "Please choose a <b>Model</b> !",
                        "not_allowed" => "Unauthorized Access",
                        "missing" => "This <b>Model</b> doesn't exist !"];


}