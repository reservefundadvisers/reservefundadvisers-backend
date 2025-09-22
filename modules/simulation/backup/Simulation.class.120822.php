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
        }

        return $response;

	}


    

    public function simulation($data){
        global $auth, $modelItemsTable, $modelsTable, $simSplitsTable, $simDeficitTable, $auth;
        
        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];

        if(!belongs_to_client($modelsTable, $data['model_id']))return ['error'=>$this->errors['not_allowed']];
        
        $model = get_element($modelsTable, ['id'=>$data['model_id']]);                  // get model        
        $model_items = get_elements($modelItemsTable, ['model_id'=>$data['model_id']]); // get model items
        $deficits  = get_elements($simDeficitTable, ['model_id'=>$data['model_id'], 'user_id'=>$auth->uid()]); // get deficits

        // parse inv_startegy to JSON
        $model['inv_strategy'] = parse_json($model['inv_strategy'], []);

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
                $splits = get_elements_join( "$simSplitsTable s1", ['s1.parent_id' => $item['id'], 's1.redundancy_at' => $i], 
                                             "LEFT JOIN $simSplitsTable s2 ON s2.split_of = s1.id", 
                                             "s1.*, count(s2.id) as splits ", "GROUP BY s1.id");

                $at = $from + ($i * $redundancy);
                

                // if the current redundancy has no splits, add original data
                if(empty($splits) && $at < $period){

                    $spendings[$at] += -1 * $cost;      // add cost to total year's spendings

                    // add inflated spending to be used in Years Of Cash calculation before hand
                    $calculated[$at]['sp'] = $spendings[$at];
                    $calculated[$at]['lp'] = ceil($spendings[$at] * $inflation_rate);
                    
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
                        $calculated[$at]['sp'] = $spendings[$split_spending_year];
                        $calculated[$split_spending_year]['lp'] = ceil($spendings[$split_spending_year] * $inflation_rate);
                        
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
        
        $monthly_fees = floatval($model['monthly_fees']);   
        $monthly_fees_inc = floatval($model['monthly_fees_rate']) / 100.0; if($monthly_fees_inc < 0){ $monthly_fees_inc = 0;  }
        $yearly_collections = floatval($monthly_fees * $housing * 12);
        $default_monthly_fees = $monthly_fees;       
        $default_monthly_fees_inc = $monthly_fees_inc;
        $default_yearly_collections = $yearly_collections;
        $monthly_fees_changed = false;

            
        
        $bank_rate = floatval($model['bank_rate']) / 100.0;
        $loan_years = floatval($model['loan_years']);


        $client_invest_strategy = $model['inv_strategy']; //0.0055;
        $invest_strategy = $model['inv_strategy'];
        $invest_strategy_changed = false;
        /* ********** */
        



        /* calculation vars */
        $final_amount = 0;      // used for final calulated amount to be used as new starting_amount    
        $loan_payments = array_fill(0, $period, 0);     // different loan to take for next year
        $remaining_loan_payments = 0;
        /* ************** */
      
        
        


        /* Investment Strategy */

        // prepare inverstmen strategy rate array
        /*
        $investment_rates = array_fill(0, $period + 1, 0); 
        $inv_starting_amount = $starting_amount;
        $current_year = 0;

        foreach($this->inv_startegy as $strategy){
            $from = check_val($strategy, 'from', $current_year);    // get first investment strategy and check starting from
            $to = $from + check_val($strategy, 'dur', 1);           // end of investment strategy by duration
            $rate = check_val($strategy, 'rate', 0);                // investment strategy rate

            if($rate == 0 || $from >= $period){ $current_year = $to; continue; }             // if end > period, make it period

            if($to > $period){ $to = $period; }

            //log_info("$from -> $to : $rate");

            // fill investment strategy rate array
            for($i=$from; $i<$to; $i++){ $investment_rates[$i] = $rate; }

            // set current year
            $current_year = $to;

        }
        */
        //log_info($investment_rates);
        /* ************* */

        /* Erase Deficit Data */
        /*
        $deficit_array = array_fill(0, $period + 1, []);
        foreach($deficits as $deficit){

            $deficit_data = parse_json($deficit['data']); //log_info($deficit_data);
            $first_deficit_data = array_extract($deficit_data, ['bank_rate', 'loan_years', 'loan_amount', 'assessment', 'ltim'], true);

            $first = true;
            for($i = $deficit['year']; $i <= $deficit['to']; $i++){
                $deficit_array[$i] = $deficit_data;
                if($first){
                    $deficit_array[$i] = array_merge($deficit_array[$i], $first_deficit_data);
                    $first = false;

                    // calculate LTIM inv from begining of block
                    $ltim_array[$i] = check_val($first_deficit_data, 'ltim', 0);
                    for($j=$i + 1; $j<=$period; $j++){
                        $ltim_array[$j] += $ltim_array[$j - 1] * (1 + ($investment_rates[$i] < 0 ? 0 : $investment_rates[$i]));                                                 
                    }
                }
                //log_info($deficit_array["y_$i"]);
            }
        }   
        for($i=0; $i<=$period; $i++){
            if(empty($deficit_array[$i]) && $i > 0 ){
                $deficit_array[$i]['monthly_fees_inc'] = check_val($deficit_array[$i - 1], 'monthly_fees_inc', 0);
            }
        }
        */

        //log_info($ltim_array);
        /* ************** */

        //--------------------
        // LTIM
        //-------------------- 
        

        /* Erase Deficit Data */
        $deficit_array = [];
        foreach($deficits as $deficit){
            $deficit_array[$deficit['year']] = parse_json($deficit['data']);
            //if($deficit['year'] < $ltim_rates_first_year)$ltim_rates_first_year = $deficit['year']; 
        }       
        /* ************** */

        // for($i=0; $i<$period; $i++)
        //     $this->calculateLTIM($spendings, $i);
        
        //log_info($ltim_strategy);
        

        //--------------------
        // Base Calculations
        //--------------------
        $use_increased_mf = false;
        for($i=0; $i<$period; $i++){

            
            // erase deficit
            $erase_deficit = check_val($deficit_array, $i, []); 



            
            //log_info("(1 + $monthly_fees_inc) ^ $i = ".pow(1 + $monthly_fees_inc, $i));


            
            $year_calculations = $calculated[$i];
            $year_calculations["year"] = $i;
            
            
            // current year spendings, array index starts at 0
            $spending = $year_calculations["sp"]; // $spendings[$i];

            $default_monthly_fees = $i == 0 ? $default_monthly_fees : ceil($default_monthly_fees * (1 + $default_monthly_fees_inc));

            // base calculations
            $this->calculateYearData(   $starting_amount_o,                                         
                                        $default_monthly_fees, $housing,
                                        $client_invest_strategy,
                                        $year_calculations["lp"],
                                        0,
                                        0,
                                        $spending, 
                                        [],
                                        0, 0, 0, [], false,
                                        $year_calculations, $i,
                                        "_o" );

                                    
            
            $loan_amount = 0; 

            $ltim_amount = 0;   
            $ltim_i = 0;  

            // $tmp_monthly_fees = $monthly_fees;

            // erase deficit
            if(!empty($erase_deficit)){
                 

                // client investment rate
                if(isset($erase_deficit['inv_strategy'])){
                    $invest_strategy = $erase_deficit['inv_strategy'];
                }


                // Monthly fees
                if(isset($erase_deficit['monthly_fees'])){
                    $monthly_fees = $erase_deficit['monthly_fees'];
                    $monthly_fees_changed = true;
                }
                // $monthly_fees = check_val($erase_deficit, 'monthly_fees', $default_monthly_fees);

                // loan
                $loan_amount = check_val($erase_deficit, 'loan_amount', 0);
                $loan_calc = $this->calculateLoan($loan_payments, $loan_amount, check_val($erase_deficit, 'bank_rate', 0), check_val($erase_deficit, 'loan_years', 1), $i, $period);
                
                $year_calculations['loan_i'] = $loan_calc['total_i'];
                $year_calculations['loan_pi'] = $loan_calc['total'];
                //$year_calculations['loan_y'] = $loan_calc['yearly'];

                
            }else{
                
                if(!$monthly_fees_changed)
                    $monthly_fees = $default_monthly_fees;

                //if(!$use_increased_mf)$monthly_fees = $i == 0 ? $monthly_fees : ceil($monthly_fees * (1 + $default_monthly_fees_inc));
            }

            //log_info("$i -> $monthly_fees");

            
            // get previous yer loan payment
            $loan_payment = $loan_payments[$i] * -1;
            $year_calculations['loan_y'] = $loan_payment;

            $ltim_perc = floatval(check_val($erase_deficit, 'ltim_perc', 0));
            if($ltim_perc > 1)$ltim_perc = 1; else if($ltim_perc < 0)$ltim_perc = 0;

            $ltim_withdrawn = check_val($erase_deficit, 'ltim_wth', 0); 
            if($ltim_withdrawn < 0 || $ltim_withdrawn > check_val($year_calculations, 'ltim_acc', 0))$ltim_withdrawn = 0; 
                        
            // $starting_amount += check_val($erase_deficit, 'ltim_wth', 0);

            
            // LTIM Strategy 
            $ltim_str = $this->calculateLTIM($spendings, $i);
            
            // LTIM Years Of Cash
            $ltim_yoc = $this->calculateYearsOfCash($calculated, $i, 3);

            // managed calculations
            $this->calculateYearData(   $starting_amount,                                         
                                        $monthly_fees, $housing,
                                        $invest_strategy,
                                        $year_calculations["lp"],
                                        $loan_amount,
                                        $loan_payment,
                                        $spending, 
                                        $erase_deficit,
                                        $ltim_perc, $ltim_withdrawn, $ltim_yoc, $ltim_str, true,
                                        $year_calculations, $i );
            
            
            
            
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
            
            // if($i > 0)  $year_calculations['ltim_acc'] += check_val($calculated[$i - 1], 'ltim_acc', 0) + check_val($calculated[$i - 1], 'deficit/ltim_amount', 0);
            // else        $year_calculations['ltim_acc'] = 0;

            // $year_calculations['ltim_r'] = $ltim_rates[$i] * 100;
            $calculated[$i] = $year_calculations;

            
            //log_info("$starting_amount -> $final_amount");

            // next year starting amount as current year final amout, 0 if negatif
            $starting_amount_o = $year_calculations['fa_o'] < 0 ? 0 : $year_calculations['fa_o'];
            $starting_amount = ($year_calculations['fa'] < 0 ? 0 : $year_calculations['fa']);

            // REMOVE LTIM ALLOCATED AMOUNT FROM NEXT YEAR'S STARTING AMOUNT
            $starting_amount -= $year_calculations['ltim_p'];
            
            // next year inv starting amount as current year final amout, 0 if negatif
            //$inv_starting_amount = $inv_final_amount < 0 ? 0 : $inv_final_amount;

            // apply yearly increase on monthly fees
            //$yearly_collections *= (1 + $monthly_fees_inc);


            //log_info("$i -> $final_amount");

            
            //if($i == 2)break;
        }


        // to prevent infinite loop
        set_time_limit(10);

        // add Years Of Cash
        
        
        
        


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
                                        "$modelsTable.id, $modelsTable.name, $modelsTable.fiscal_year, $modelsTable.created_at", "ORDER BY $modelsTable.name ASC".check_val($pagination, 'query'));

        
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
            $ltim_p = $impl_ltim ? $ltim_perc * $ltim_spl : 0;


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


    private function calculateLoan(&$arr, $amount = 0, $bank_rate = 0, $loan_years = 1, $from = 0, $period = 1){

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
            for($i=$from; $i<$to; $i++){
                $arr[$i] += $yearly_payment;

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

    private $errors = [ "client_id" => "Please choose an <b>Association</b> !",
                        "model_id" => "Please choose a <b>Model</b> !",
                        "not_allowed" => "Unauthorized Access",
                        "missing" => "This <b>Model</b> doesn't exist !"];


}