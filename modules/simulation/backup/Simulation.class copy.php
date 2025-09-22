<?php

class Simulation
{  
	
    private $module_name = 'simulation';

    private $inv_startegy = [   ["from"=>0, "dur"=>3, "rate"=>-1],
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
        $spendings = array_fill(0, $period + 1, 0);  // Sum of spending for each year
        $spending_data = array_fill(0, $period + 1, []); // items per year
        
        
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
            if($remaining_life >= $period)continue;
            

            // array index starts at 0
            $from = $remaining_life;

            // calculate how many redundancies left
            $redundancies = floor( ( $period - $from ) / $redundancy);
           
            $to = $redundancies * $redundancy;
            
            //log_info("$from -> $to + $redundancy x $redundancies -> ".($redundancies * $redundancy));

            // for each redundancy
            for($i=0; $i<=$redundancies; $i++){                
                                    
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
        $housing = floatval($model['housing']); if($housing < 1)$housing = 1;
        
        $monthly_fees = floatval($model['monthly_fees']);        
        $monthly_fees_inc = floatval($model['monthly_fees_rate']) / 100.0; if($monthly_fees_inc < 0){ $monthly_fees_inc = 0;  }
        $yearly_collections = floatval($monthly_fees * $housing * 12);
        
        
        $inflation_rate = floatval($model['inflation_rate']) / 100.0;        
        
        $bank_rate = floatval($model['bank_rate']) / 100.0;
        $loan_years = floatval($model['loan_years']);


        $client_invest_rate = $this->calculateClientInvStrategy($model['inv_strategy']); //0.0055;
        /* ********** */
        

        /* Returned vars */
        $calculated = array_fill(0, $period + 1, 0);   // calculated rows for the given period
        /* ************* */


        /* calculation vars */
        $final_amount = 0;      // used for final calulated amount to be used as new starting_amount    
        $loan_payments = array_fill(0, $period + 1, 0);     // different loan to take for next year
        $remaining_loan_payments = 0;
        /* ************** */
      
        
        /* Investment Strategy */

        // prepare inverstmen strategy rate array
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
        //log_info($investment_rates);
        /* ************* */
        
        /* Erase Deficit Data */
        $deficit_array = array_fill(0, $period + 1, []);
        $ltim_array = array_fill(0, $period + 1, 0);        
        foreach($deficits as $deficit){

            $deficit_data = parse_json($deficit['data']);
            $first_deficit_data = array_extract($deficit_data, ['bank_rate', 'loan_years', 'loan_amount', 'assessment', 'ltim'], true);

            $first = true;
            for($i = $deficit['year']; $i <= $deficit['to']; $i++){
                $deficit_array[$i] = $deficit_data;
                if($first){
                    $deficit_array[$i] = array_merge($deficit_array[$i], $first_deficit_data);
                    $first = false;

                    // calculate LTIM inv from begining of block
                    $ltim_array[$i] = $first_deficit_data['ltim'];
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

        log_info($ltim_array);
        /* ************** */
        




        for($i=0; $i<=$period; $i++){

            // erase deficit
            $erase_deficit = check_val($deficit_array, $i, []); 

            // total available strating amount
            $total_amount = ceil($starting_amount + $yearly_collections);
            
            // investment net earning
            //if($i < 300 )
                $used_client_invest_rate = !empty($erase_deficit) ? $this->calculateClientInvStrategy(check_val($erase_deficit, 'inv_strategy', [])) : $client_invest_rate;
            //else
            //  $used_client_invest_rate = $investment_rates[$i];

            $net_earning = ceil($total_amount * $used_client_invest_rate);

            // compound starting amount from investment
            $compound = ceil($total_amount + $net_earning);
            
            // compound starting amount using inv strategy
            $inv_total_amount = ceil($inv_starting_amount + $yearly_collections);
            $inv_rate = $investment_rates[$i]; if($inv_rate == -1)$inv_rate = 0; //$client_invest_rate;
            $investment = ceil($inv_total_amount * (1 + $inv_rate));
            
            // loss in purchase power due to inflation
            $loss_purchase = -1 * ceil($yearly_collections * ($inflation_rate)); /*$compound - ceil($total_amount * (1 + $inflation_rate)); */ if($loss_purchase > 0) $loss_purchase = 0;
            $inv_loss_purchase = $investment - ceil($inv_total_amount * ($inflation_rate)); if($inv_loss_purchase > 0) $inv_loss_purchase = 0;

            // current year spendings, array index starts at 0
            $spending = $spendings[$i];

            // get previous yer loan payment
            $prev_loan_payment = (-1 * $loan_payments[($i == 0 ? 0 : $i - 1)]);

            // remaining amount after spending
            $final_amount = $compound + $loss_purchase + $spending + $prev_loan_payment;

            // inv remaining amount after spending
            $inv_final_amount = $investment + $inv_loss_purchase + $spending;
            

            
            // Erase Deficit
            $reduced_final_amount = $final_amount;

            // mf increase
            $yc_inc = 0;
            $mf_new = $monthly_fees;
            $mf_inc_r = 0;

            // loan
            $yearly_payment = 0;
            $loan_pi = 0; $loan_i = 0;

            // assessment
            $assessment = 0;
            if(!empty($erase_deficit) /* && $final_amount < 0 */){

                // mf increase
                $mf_inc_r = floatval(check_val($erase_deficit, 'monthly_fees_inc', 0)); // mf inc rate
                $yc_inc = $yearly_collections * $mf_inc_r ; // yearly increased by
                $mf_new = $yc_inc / ($housing * 12);   // projected mf


                // loan
                $loan_amount = check_val($erase_deficit, 'loan_amount', 0);
                $loan_calc = $this->calculateLoan($loan_payments, $loan_amount, check_val($erase_deficit, 'bank_rate', 0), check_val($erase_deficit, 'loan_years', 1), $i, $period);
                
                $loan_pi = $loan_calc['total'];
                $loan_i = $loan_calc['total_i'];
                $yearly_payment = $loan_calc['yearly'];
                $remaining_loan_payments += $loan_calc['remaining']; // add up remaining payments

                // assessment
                $assessment = check_val($erase_deficit, 'assessment', 0);


                $reduced_final_amount += $yc_inc + $loan_amount + $assessment;
                //if($reduced_final_amount > -1)$reduced_final_amount = 0;
            }          
            

            // total expenses
            $total_expenses = $loss_purchase + $spending + $prev_loan_payment;


            $year_calculations = [  "year"=> $i, 

                                    "sa"=>$starting_amount, 
                                    "yc"=>$yearly_collections,
                                    "mf"=>($yearly_collections / (12 * $housing)),

                                    
                                    "ta"=>$total_amount, 
                                    "is"=>$used_client_invest_rate,
                                    "ne"=>$net_earning, 
                                    "cp"=>$compound, 
                                    
                                    "lp"=>$loss_purchase,
                                    "sp"=>$spending,
                                    
                                    "tx"=>$total_expenses,

                                    "fa"=>$final_amount,
                                    "rfa"=>$reduced_final_amount,
                                
                                    "loan_i"=>$loan_i,
                                    "loan_pr"=> $prev_loan_payment,
                                    "loan_pay"=> $loan_payments[$i],
                                    "loan_yearly"=> $yearly_payment,
                                    "loan_pi"=>$loan_pi,

                                    "asses"=>$assessment,

                                    "deficit"=>$erase_deficit,

                                    
                                    //"yc_cp" => $compound,
                                    //"yc_tx" => $total_expenses,
                                    //"yc_fa" => $final_amount,
                                    "yc_inc" => $yc_inc,
                                    "mf_new" => $mf_new,
                                    "mf_inc_r" => $mf_inc_r,

                                    "ltim"=>$ltim_array[$i],
                                    "ltim_r"=>$inv_rate,
                                    "rfa_ltim" => $ltim_array[$i] * (1 + $inv_rate),
                                
                                
                                    "inv" => $ltim_array[$i] * (1 + $inv_rate),
                                    "inv_f" => $inv_final_amount,
                                    "inv_s" => ($inv_final_amount - $final_amount ) 
                                
                                ];

            $calculated[$i] = $year_calculations;

            

            // next year starting amount as current year final amout, 0 if negatif
            $starting_amount = $final_amount < 0 ? 0 : $final_amount;
            
            // next year inv starting amount as current year final amout, 0 if negatif
            //$inv_starting_amount = $inv_final_amount < 0 ? 0 : $inv_final_amount;

            // apply yearly increase on monthly fees
            // $yearly_collections *= (1 + $monthly_fees_inc);


            //log_info("$i -> $final_amount");

            
            //if($i == 2)break;
        }


        // to prevent infinite loop
        set_time_limit(10);

        /* Yearly Collections Increase Calculation */
        /*
        for($i=0; $i<=$period; $i++){
            
            //if($i == 6)break;
            
            $compound = $calculated[$i]['yc_cp'];
            $final_amount = $calculated[$i]['yc_fa'];
            $expenses = $calculated[$i]['yc_tx'];

            
            // if final amount is negatif
            if($final_amount < 0){


                $increase_by_year = abs($final_amount / ($i + 1));
                
                //log_info("$i -> $increase_by_year");
                
                for($j=0; $j<=$i; $j++){

                    $mul = ($j + 1);

                    // increase compound savings
                    if($mul < count($calculated))$calculated[$mul]['yc_cp'] += $mul * $increase_by_year;

                    // increase remaining amount
                    $calculated[$j]['yc_fa'] += $mul * $increase_by_year;

                    // set yearly increase
                    $calculated[$j]['yc_inc'] += $increase_by_year;
                    // set monthly new monthly increase
                    $calculated[$j]['mf_new'] += $increase_by_year / ( 12 * $housing);
                    // set yearly increase
                    $calculated[$j]['mf_inc_r'] = $monthly_fees > 0 ? ($calculated[$j]['mf_new'] - $monthly_fees ) / $monthly_fees : 0;
                    
                }

                $i = -1;
                continue;
            }

        }
        */
        /* **************************************** */




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