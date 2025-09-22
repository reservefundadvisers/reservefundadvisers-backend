<?php

class Simulation
{  
	
    private $module_name = 'simulation';

    private $inv_startegy = [   /*["from"=>0, "dur"=>3, "rate"=>-1],*/
                                ["dur"=>5, "rate"=>0.096],
                                ["dur"=>5, "rate"=>0.1046],
                                ["dur"=>5, "rate"=>0.1119],
                                ["dur"=>10, "rate"=>0.116],
                                ["dur"=>5, "rate"=>0.1069]
                            ];
	
	function __construct()
	{
	

	}

	
	public function process($cmd, $data)
	{
        $response = "";

        switch($cmd){
            case 'get': $response = $this->list($data); break;
            case 'get_association': $response = $this->list_association($data); break;
            case 'get_fiscal': $response = $this->list_fiscal($data); break;
            case 'get_model': $response = $this->list_model($data); break;
            
            case 'set': $response = $this->set($data); break;            
        }

        return $response;

	}


    

    public function list($data){
        global $auth, $modelItemsTable, $modelsTable, $simSplitsTable, $simDeficitTable, $auth;
        
        if(!is_valid($data, 'model_id'))return ['error'=>$this->errors['model_id']];
        if(!exists($modelsTable, ['id'=>$data['model_id']]))return ['error'=>$this->errors['missing']];

        if(!belongs_to_client($modelsTable, $data['model_id']))return ['error'=>'not_allowed'];
        
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
            $redundancies = floor( ( $period - $from ) / $redundancy);
           
            $to = $redundancies * $redundancy;
            
            //log_info("$from -> $to + $redundancy x $redundancies -> ".($redundancies * $redundancy));

            // for each redundancy
            for($i=0; $i<$redundancies; $i++){                
                                    
                // get splits from simulation for the redundancy, 0 -> First occurence
                $splits = get_elements_join( "$simSplitsTable s1", ['s1.parent_id' => $item['id'], 's1.redundancy_at' => $i], 
                                             "LEFT JOIN $simSplitsTable s2 ON s2.split_of = s1.id", 
                                             "s1.*, count(s2.id) as splits ", "GROUP BY s1.id");

                // if the current redundancy has no splits, add original data
                if(empty($splits)){

                    $at = $from + ($i * $redundancy);
                    $spendings[$at] += -1 * $cost;      // add cost to total year's spendings

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

                }else{
                    
                    // else if has splits, add each split with corresponding redundancy and year
                    foreach($splits as $split){

                        $split_spending_year = $split['year'] < $period ? $split['year'] : $period;
                        $spendings[$split_spending_year] += -1 * $split['cost'];      // add cost to total year's spendings

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
        $default_yearly_collections = $yearly_collections;
        
        
        $inflation_rate = floatval($model['inflation_rate']) / 100.0;        
        
        $bank_rate = floatval($model['bank_rate']) / 100.0;
        $loan_years = floatval($model['loan_years']);


        $client_invest_rate = $this->calculateClientInvStrategy($model['inv_strategy']); //0.0055;
        /* ********** */
        

        /* Returned vars */
        $calculated = array_fill(0, $period, 0);   // calculated rows for the given period
        /* ************* */


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
        $ltim_rates_first_year = 3;   
        
        /* Erase Deficit Data */
        $deficit_array = [];
        foreach($deficits as $deficit){
            $deficit_array[$deficit['year']] = parse_json($deficit['data']);
            if($deficit['year'] < $ltim_rates_first_year)$ltim_rates_first_year = $deficit['year']; 
        }       
        /* ************** */
        if($ltim_rates_first_year > 3)$ltim_rates_first_year = 3;

        $ltim_strategy = array_fill(0, $period, 0);  
        $ltim_rates = array_fill(0, $period, 0); 
        foreach($this->inv_startegy as $rate){
            
            $to = $ltim_rates_first_year + $rate['dur'];
            if($to > $period)$to = $period;

            for($ltim_rates_first_year; $ltim_rates_first_year < $to; $ltim_rates_first_year++ ){

                $ltim_rates[$ltim_rates_first_year] = $rate['rate'];
                
                /*
                $amount = check_val($deficit_array, "$ltim_stategy_y/ltim", 0); 
                $intrest = $rate['rate'] * $amount;
                
                $ltim_strategy[$ltim_stategy_y]['amount'] = $amount;
                $ltim_strategy[$ltim_stategy_y]['rate'] = $rate['rate'];
                $ltim_strategy[$ltim_stategy_y + 1]['intrest'] = check_val($ltim_strategy, "$ltim_stategy_y/intrest", 0) + $intrest;
                */
            }
        }

        //log_info($ltim_strategy);
        

        //--------------------
        // Base Calculations
        //--------------------
        for($i=0; $i<$period; $i++){

            
            // erase deficit
            $erase_deficit = check_val($deficit_array, $i, []); 

            // client investment rate
            $client_invest_rate = !empty($erase_deficit) ? $this->calculateClientInvStrategy(check_val($erase_deficit, 'inv_strategy', [])) : $client_invest_rate;

            
            // current year spendings, array index starts at 0
            $spending = $spendings[$i];

            

            
            $year_calculations = ["year"=>$i];

            // base calculations
            $this->calculateYearData(   $starting_amount_o,                                         
                                        $monthly_fees, $monthly_fees_inc, $housing,
                                        $client_invest_rate,
                                        $inflation_rate,
                                        0,
                                        0,
                                        $spending, 
                                        [],
                                        0, 0,
                                        $year_calculations,
                                        "_o" );

                                        
            
            $loan_amount = 0; 

            $ltim_amount = 0;   
            $ltim_i = 0;  

            // erase deficit
            if(!empty($erase_deficit)){
                 
                // loan
                $loan_amount = check_val($erase_deficit, 'loan_amount', 0);
                $loan_calc = $this->calculateLoan($loan_payments, $loan_amount, check_val($erase_deficit, 'bank_rate', 0), check_val($erase_deficit, 'loan_years', 1), $i, $period);
                
                $year_calculations['loan_i'] = $loan_calc['total_i'];
                $year_calculations['loan_pi'] = $loan_calc['total'];
                //$year_calculations['loan_y'] = $loan_calc['yearly'];

                
                $ltim_amount = check_val($erase_deficit, 'ltim', 0);
                if($ltim_amount > 0){
                    $ltim_i =  $ltim_amount * $ltim_rates[$i];                    
                }
                
            }

            
            // get previous yer loan payment
            $loan_payment = $loan_payments[$i];
            $year_calculations['loan_y'] = $loan_payment;

            // managed calculations
            $this->calculateYearData(   $starting_amount,                                         
                                        $monthly_fees, $monthly_fees_inc, $housing,
                                        $client_invest_rate,
                                        $inflation_rate,
                                        $loan_amount,
                                        $loan_payment,
                                        $spending, 
                                        $erase_deficit,
                                        $ltim_amount, $ltim_i,
                                        $year_calculations );


            // $year_calculations['ltim_r'] = $ltim_rates[$i] * 100;
            $calculated[$i] = $year_calculations;

            
            //log_info("$starting_amount -> $final_amount");

            // next year starting amount as current year final amout, 0 if negatif
            $starting_amount_o = $year_calculations['fa_o'] < 0 ? 0 : $year_calculations['fa_o'];
            $starting_amount = ($year_calculations['fa'] < 0 ? 0 : $year_calculations['fa']);
            
            // next year inv starting amount as current year final amout, 0 if negatif
            //$inv_starting_amount = $inv_final_amount < 0 ? 0 : $inv_final_amount;

            // apply yearly increase on monthly fees
            //$yearly_collections *= (1 + $monthly_fees_inc);


            //log_info("$i -> $final_amount");

            
            //if($i == 2)break;
        }


        // to prevent infinite loop
        set_time_limit(10);

        


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


        return set_property($modelsTable, $data);

    }


    public function calculateYearData(  $starting_amount,                                         
                                        $monthly_fees, $monthly_fees_inc, $housing,
                                        $invest_rate,
                                        $inflation_rate,
                                        $loan,
                                        $prev_loan_payment,
                                        $spending, 
                                        $erase_deficit,
                                        $ltim_amount = 0, $ltim_i = 0,
                                        &$year_calculations,                                        
                                        $suffix = ""){
  
            //$starting_amount -= $ltim_amount;
            
            // yearly collections
            $monthly_fees *= 1 + $monthly_fees_inc;
            $yearly_collections = $monthly_fees * 12 * $housing;
            
            // monthly fees increase
            if(isset($erase_deficit['monthly_fees'])){
                $monthly_fees = $erase_deficit['monthly_fees']; 
                $yearly_collections = $monthly_fees * 12 * $housing;
            }


            // assessment
            $assessment = check_val($erase_deficit, 'assessment', 0);



            // total available strating amount
            $total_amount = floor($starting_amount -  + $yearly_collections - $ltim_amount); 

            // net eargnings
            $net_earning = floor(($total_amount) * $invest_rate);  if($net_earning < 0)$net_earning = 0;

            // compound starting amount from investment
            $compound = floor($total_amount + $net_earning);
            
            // loss in purchase power due to inflation
            $loss_purchase = -1 * floor($yearly_collections * ($inflation_rate)); /*$compound - ceil($total_amount * (1 + $inflation_rate)); */ if($loss_purchase > 0) $loss_purchase = 0;
            //$inv_loss_purchase = $investment - ceil($inv_total_amount * ($inflation_rate)); if($inv_loss_purchase > 0) $inv_loss_purchase = 0;
            
            // remaining amount after spending
            $final_amount = $compound + $loss_purchase + $spending + $loan - $prev_loan_payment;


            $final_amount += $assessment + $ltim_amount + $ltim_i; // - check_val($erase_deficit, 'ltim', 0);

            if($final_amount >= -1 && $final_amount < 0)$final_amount = 0;


            // total expenses
            $total_expenses = $loss_purchase + $spending + $prev_loan_payment;


            $tmp_year_calculations = [  

                                        "sa$suffix"=>$starting_amount, 
                                        "yc$suffix"=>$yearly_collections,
                                        "mf$suffix"=>$monthly_fees,
                                        

                                        "ta$suffix"=>$total_amount, 
                                        
                                        "is$suffix"=>$invest_rate,
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

                                        "ltim"=>$ltim_amount,
                                        "ltim_i"=>$ltim_i
                                                                        
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

    private $errors = [ "client_id" => "Please choose an <b>Association</b> !",
                        "model_id" => "Please choose a <b>Model</b> !",
                        "not_allowed" => "Unauthorized Access",
                        "missing" => "This <b>Model</b> doesn't exist !"];


}