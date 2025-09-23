function init_simulation(e){

    if(!e)return;

	
    var simulation = new Simulation(e);
    e = $(e);

    //view_modal(e.find('#deficit_box_template').html(), 'Erase Deficit', null, false, 'xl', false);

    
    init_ui(e);

    var chart = echarts.init(e.find('.simulation-chart')[0]);

    var association_select = e.find('select.associations'),
        model_select = e.find('select.models');

    var model_edit_btns = e.find('.model-edit');
    var model = new Models(), model_items = new ModelItems();


    var model_info = e.find('.model-info');
        model_info.reset = function(){  };

        $("html, body").scrollTop(0);

    // get model info Y on init then toggle d-none to make it FIXED TOP
    var model_info_y = Math.floor(model_info[0].getBoundingClientRect().y),
        model_info_l = Math.floor(model_info[0].getBoundingClientRect().x),
        model_info_r = $(window).width() - model_info_l - Math.floor(model_info[0].getBoundingClientRect().width),
        model_info_height = model_info.height();
        model_info.addClass('d-none').removeClass('invisible');

        

    

    model_edit_btns.click(function(){
            var action = $(this).data('action'), model_id = model_select.val();
            
            if(!model_id)return;

            if(action == "model"){
                model.edit(model_id, function(resp){ if(checkError(resp, true))return; simulation.run(); });
            }else if(action == "items"){
                model_items.edit(model_id, function(resp){ if(checkError(resp, true))return; simulation.run(); });
            }
    });

    if(association_select.length > 0){
        simulation.associations(function(list){

            association_select.html('<option value="" disabled selected>--- Associations ----</option>');
            model_select.html('<option value="" disabled selected>Choose Association First</option>');
            
            if(!list || list.length == 0)return;
            
            for(const a of list){
                
                fillSelect(association_select, {v:a.id, t:a.association}, true);
            }

            association_select.change(function(){
                var client_id = $(this).val();


                var l = loading('', e);
                
                // reset simultaion list
                simulation.reset();
                model_edit_btns.toggleClass('d-none', true);
                model_info.toggleClass('d-none', true);
                

                simulation.models(client_id, function(list){
                    
                    l.remove();

                    model_select.html('<option value="" disabled selected>--- Models ('+list.length+') ----</option>');
                    

                    if(!list || list.length == 0)return;
                    
                    var grouped_model = {};
                    for(const m of list){
                        
                        var year = m.fiscal_year+"" || "Other";
                        
                        if(!grouped_model[year])grouped_model[year] = [];
                        grouped_model[year].push({v:m.id, t:m.name});
                    

                        //var mod = {}; mod[m.id] = m.name + (m.fiscal_year ? ' - ('+m.fiscal_year+')' : '');
                        //fillSelect(model_select, mod, true);
                    }
                    
                    // start with element w/o fiscal year
                    if(grouped_model['Other'] && grouped_model['Other'].length > 0){                         
                        fillSelect(model_select, {t: "Other", h:true}, true);
                        fillSelect(model_select, grouped_model["Other"], true);
                        delete grouped_model['Other']; 
                    }      

                    for(const k in grouped_model){              
                        fillSelect(model_select, {t: k, h:true}, true);
                        fillSelect(model_select, grouped_model[k], true);
                    }

                    
                    model_select.change(function(){
                        var model_id = $(this).val();
                        
                        simulation.run(model_id, function(event){

                            // model edit btn
                            model_edit_btns.toggleClass('d-none', !event.data);
                            
                            // model infos
                            model_info.toggleClass('d-none', !event.data).reset();
                        });
                    });

                    l.remove();

                });
            });
        });
        
    }else{

    }

    e.find('.reset-simulation').click(function(){
        confirm('Are you sure you want to <b>Reset the simulation data</b> ? This will revert back to the Default Data and the simulation data will be <b>Lost<b> <br><b><u>This Action is irreversible</u></b> !', 'Reset Simulation', null,
                function(action){
                        if(action == 'ok'){
                            var utils = new ModelItems();

                            utils.reset(simulation.model_id, function(resp){
                                simulation.init();
                            })
                        }
                });
    });

    return simulation;

}


var Simulation = function(){

    function Simulation(parent){

        this.parent = parent;
        this.root = $(parent);


        this.interface = null;
        this.simulation_data = null;

        this.erase_deficit = {};

        this.chart = null;
        this.chartInit();

        this.model_id = null;
        
        
        this.table = null; 
    }

    
    
    Simulation.prototype.data_formatter = function(items){
            
        console.log(items);

        for(const item of items){
            

            item.name = formatName(item.fn, item.ln);
            item.date = formatDate(item.date, 'm/d/y');    
            
            //console.log(item);           

        }

        return items;
    };
    
    Simulation.prototype.reset = function(){ 
        this.model_id = null;

        
        if(this.table)this.table.setData([]);


        this.chartInit();

        this.erase_deficit = {};
    }

    
    Simulation.prototype.chartInit = function(){ 
        if(this.root.find('.simulation-chart').length < 1)return;

        if(this.chart)this.chart.clear();

        this.chart =  echarts.init(this.root.find('.simulation-chart')[0]);
        
        var option = {
            title: { text: "Simulation" }, tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, legend: {top: 50},
            grid: { top: '25%', left: '3%', right: '4%', bottom: '3%', containLabel: true }, xAxis: [ { type: 'category', data: [0]} ],
            yAxis: [ { type: 'value' } ], series: [{ type: 'bar', barMaxWidth:15,
                                                     itemStyle: {color: '#495266'}, emphasis: { focus: 'series' }, data: [0] }],
                                                     
            dataZoom: [ { type: 'inside', start: 0 }, { start: 0 } ],
            
        };
        

        option && this.chart.setOption(option);

        var context = this;
        
        $(window).on('resize', function(){
            if(context.chart != null && context.chart != undefined){
                context.chart.resize();
            }
        });
        
    }

                     
    Simulation.prototype.toggleChart =   function (search){ 
        this.root.find('.simulation-chart').toggleClass('d-none');
        if(this.chart)this.chart.resize();
    }
    
    
    Simulation.prototype.eraseDeficit = function(at, amount = 0, yearlyCollections = 0, units = 1, original_spendings_amount = 0, total_amount = 0, original_yearlyCollections = 0, original_fa = 0, cb, has_deficits = false){

        // which year
        if(at == undefined)return false; 
        
        var context = this;
        

        // Year and Deficit item
        var deficit_erase = cloneObj(context.simulation_data.calculated[at].deficit);
        if(deficit_erase.fa)amount = parseInt(deficit_erase.fa);


        var original_surplus_amount = amount > 0 ? amount : 0;
        var original_deficit_amount = Math.abs(amount < 0 ? amount : 0);
        
        
        var inflation_rate = parseFloat(context.simulation_data.model.inflation_rate) / 100;

        // amount =  Math.abs(amount);

        // Deficit Data
        var deficit_amount = original_deficit_amount;
        var surplus_amount = original_surplus_amount;
        var is_deficit = deficit_amount > 0;

        // Net Earning
        var original_net_earning_amount = 0;
        var net_earning_amount = 0;

        // Spendings
        var spendings_amount = original_spendings_amount;

        // fill model infos
        var deficit_box = $(this.root.find('#deficit_box_template').html());
        
        // set title 
        deficit_box.find('.deficit-title').html(at == 0 ? 'Current Year' : 'Year '+at);
        


        // Initial data
        
        var loan_intrests = 0;

        // Monthly Fees

        // Deficit Data
        var deficit_data = {}

        
        var init_deficit_data = function(clear = false){ 
                                    var inv_strategy = !objEmpty(deficit_erase) && deficit_erase.inv_strategy ? deficit_erase.inv_strategy : context.simulation_data.model.inv_strategy;
                                    var monthly_fees = !objEmpty(deficit_erase) && deficit_erase.monthly_fees && !clear ? parseFloat(deficit_erase.monthly_fees) : (yearlyCollections / (12 * units));
                                    var bank_rate = !objEmpty(deficit_erase) && deficit_erase.bank_rate ? deficit_erase.bank_rate : parseFloat(context.simulation_data.model.bank_rate)/100.0;
                                    var loan_years = !objEmpty(deficit_erase) && deficit_erase.loan_years ? deficit_erase.loan_years : parseFloat(context.simulation_data.model.loan_years); if(loan_years < 1)loan_years = 1;
                                    var loan_amount = !objEmpty(deficit_erase) && deficit_erase.loan_amount && !clear? parseInt(deficit_erase.loan_amount) : 0;                                
                                    var assessment = !objEmpty(deficit_erase) && deficit_erase.assessment && !clear ? parseInt(deficit_erase.assessment) : 0;

                                    deficit_data = { "inv_strategy": inv_strategy, 
                                                    "bank_rate":bank_rate, 
                                                    "loan_years": loan_years, 
                                                    "loan_amount": loan_amount, 
                                                    "monthly_fees":monthly_fees, 
                                                    "assessment":assessment };
                                                          
                                };
                                
                                      
        init_deficit_data();
        
        var calculate_loan_intrests = function(amount = 0, intrestOnly = true){ 
                                        
                                        if(!deficit_data.loan_years || !deficit_data.bank_rate)return 0;
                                        

                                        var rate = floatDecimals(deficit_data.bank_rate / 12, 7);
                                            //rate = Math.round( rate, 7); 

                                        if(rate > 0){
                                
                                            var months = deficit_data.loan_years * 12;
                                    
                                            var total = ( rate +  rate / (Math.pow( rate + 1,  months) - 1)) *  amount;
                                                total = floatDecimals(total, 0) * months;
                                            
                                        }else{
                                            total = amount;
                                        }
                                        
                                        loan_intrests = total - amount;
                                        
                                        if(intrestOnly)return total - amount;
                                        else return total;
                            }
        var yearly_intrest = function(){
                                    //console.log(loan_intrests, deficit_data.loan_years);
                                    return deficit_data.loan_years > 0 ? (loan_intrests / deficit_data.loan_years) : 0;
                             }


        // details
        var surplus_label = deficit_box.find('.surplus-amount');
        var deficit_label = deficit_box.find('.deficit-amount');

        var spendings_amount_label = deficit_box.find('.spendings-amount');
        var red_spendings_amount_label = deficit_box.find('.red-spendings-amount');

        var net_earning_label = deficit_box.find('.net-earning-amount');
        var yearly_inc_label = deficit_box.find('.yearly-increase-amount');
        var pi_label = deficit_box.find('.pi-amount');
        var immediate_assessment_label = deficit_box.find('.immediate-assessment-amount');
        
        var loss_purchase_power_label = deficit_box.find('.lp-amount');
        
        
        var remaining_deficit_amount_label = deficit_box.find('.remaining-deficit-amount');
        var remaining_surplus_amount_label = deficit_box.find('.remaining-surplus-amount');


        // LTIM
        var ltim_input = deficit_box.find('.ltim-input');

        
        var deficit_chart = echarts.init(deficit_box.find('.model-info-deficit-chart')[0]);

        if(original_deficit_amount <= 0)deficit_box.find('.model-info-deficit-chart').addClass('invisible')
        
        deficit_chart.setOption({
            title: { text: '' }, tooltip: { trigger: 'item', position: 'inside', formatter: (p) => { return '<b>'+ucwords(p.seriesName, true)+' <br> <span class="w3-text-indigo">'+formatMoney(p.value, 0)+'</span></b>' } }, 
            legend: {left: 'right', top: 'center', type: 'scroll', orient: 'verical'}, 
            grid: {left: '120px', width:100, top: '5px' },
            xAxis: { type: 'category', show: false },
            yAxis: { show: false }

        });

        var calculate_remaining_deficit = function(type = ''){
                var remaining = 0;
                
                var new_deficit_amount = deficit_amount + yearlyCollections + original_net_earning_amount - net_earning_amount;
                var yc = 12 * units * deficit_data["monthly_fees"];

                var predicted_inflation = yc * inflation_rate;
                //new_deficit_amount += predicted_inflation;


                // new_deficit_amount *= 1 + inflation_rate;

                if(type == 'mf'){
                    remaining = new_deficit_amount;
                }else if(type == 'loan'){
                    remaining = new_deficit_amount - yc;//(yearlyCollections * deficit_data["monthly_fees_inc"]);
                }else if(type == 'assessment'){
                    remaining = new_deficit_amount - yc - deficit_data['loan_amount'];
                }else {
                    remaining = new_deficit_amount - yc - deficit_data['loan_amount']  - deficit_data["assessment"];
                } 
                
                if(remaining < 0)remaining = 0;

                return parseInt(remaining);
        }

        var calculate_remaining_surplus = function(){

            var new_deficit_amount = deficit_amount + yearlyCollections + original_net_earning_amount - net_earning_amount;
            var yc = 12 * units * deficit_data["monthly_fees"];

            var deficit_surplus = new_deficit_amount - yc - deficit_data['loan_amount'] - deficit_data["assessment"];
            
            var predicted_inflation = yc * inflation_rate; console.log(predicted_inflation);
            deficit_surplus += predicted_inflation;
            
            return original_surplus_amount + ((deficit_surplus > 0) ? 0 : -1 * deficit_surplus);
        }

        


        deficit_chart_update = function(){
                                            
            var new_yc = 12 * units * deficit_data["monthly_fees"];//yearlyCollections * (deficit_data.monthly_fees_inc);
            var remaining = calculate_remaining_deficit(); 

            // Bar chart
            deficit_chart.setOption({series: [                                                
                { data: [remaining], name:'Remaining Deficit', itemStyle: {color: '#fd4032'}, type: 'bar', stack: 'Bar', showBackground: false },
                { data: [deficit_data["assessment"]], name:'Immediate Assessment', itemStyle: {color: '#4CAF50'}, type: 'bar', stack: 'Bar', showBackground: false },
                { data: [new_yc], name:'Yearly Collections Inc', itemStyle: {color: '#ffc107'}, type: 'bar', stack: 'Bar', showBackground: false },
                { data: [deficit_data.loan_amount], name:'Loan Amount', itemStyle: {color: '#673ab7'}, type: 'bar', stack: 'Bar', showBackground: false },
                { data: [yearly_intrest()], name:'Current Year Loan Intrest', itemStyle: {color: '#ff5722'}, type: 'bar', stack: 'Bar', showBackground: false },
                { data: [net_earning_amount], name:'Inv Strategy', itemStyle: {color: '#e301d0'}, type: 'bar', stack: 'Bar', showBackground: false }
              ]});

                                    
        }

        var update_remaining_deficit = function(){
            
                                            // update right chart infos
                                            var totalYearly = 12 * units * deficit_data["monthly_fees"]; //(yearlyCollections * deficit_data["monthly_fees_inc"]);
                                            var pi = deficit_data["loan_amount"] ; //+ yearly_intrest();
                                            
                                            var remaining_deficit_amount = calculate_remaining_deficit();
                                                surplus_amount = calculate_remaining_surplus();
                                                
                                            var predicted_inflation = totalYearly * inflation_rate;
                                            
                                            
                                            surplus_label.html( formatMoney(original_surplus_amount, 0) );
                                            deficit_label.html( formatMoney(original_deficit_amount, 0) );
                                            
                                            spendings_amount_label.html(formatMoney(original_spendings_amount, 0)) ;
                                            red_spendings_amount_label.html(formatMoney(spendings_amount, 0)) ;
 
                                            net_earning_label.html(formatMoney(net_earning_amount, 0));
                                            yearly_inc_label.html(formatMoney(totalYearly, 0)) ;
                                            pi_label.html(formatMoney(pi, 0)) ;
                                            immediate_assessment_label.html(formatMoney(deficit_data["assessment"], 0)) ;
                                            
                                            loss_purchase_power_label.html(formatMoney(predicted_inflation, 0)) ;

                                            remaining_deficit_amount_label.html(formatMoney(remaining_deficit_amount, 0)) ;
                                            remaining_surplus_amount_label.html(formatMoney(surplus_amount, 0)) ;


                                            // monthmy fees deficit clear btn
                                            monthly_fees_reset_btn.toggleClass('d-none', deficit_data["monthly_fees"] == default_monthly_fees /*!deficit_data["monthly_fees_inc"]*/);
                                            monthly_fees_clear_btn.toggleClass('d-none', remaining_deficit_amount == 0);
                                            
                                            // update loan options max loan to take or disable if 0
                                            var loan_remaining = calculate_remaining_deficit('loan'); if(loan_remaining < 0)loan_remaining = 0;
                                            deficit_box.find('.deficit-loan-remaining').html(formatMoney(loan_remaining, 0)) ;
                                            deficit_box.find('.deficit-loan-disable').toggleClass('d-none', (loan_remaining > 0));
                                            
                                            deficit_chart_update();

                                            deficit_box.find('.deficit-almost-erased').toggleClass('d-none', !(remaining_deficit_amount > 0 && remaining_deficit_amount / deficit_amount < 0.1) );
                                            deficit_box.find('.deficit-erased').toggleClass('d-none', !(remaining_deficit_amount == 0));

                                            // LTIM
                                            deficit_box.find('.ltim-option').toggleClass('d-none', surplus_amount <= 0);
                                            
                                        }



        // Loan Options
        var loan_slider = deficit_box.find('.loan-slider'),
            //loan_deficit_amount_label = deficit_box.find('span.deficit-loan-remaining'),
            loan_amount_input = deficit_box.find('input.loan-amount'),
            loan_total_label = deficit_box.find('span.loan-total'),
            loan_intrests_label = deficit_box.find('span.loan-intrests'),
            bank_rate_input = deficit_box.find('[data-key="bank_rate"]'),
            loan_years_input = deficit_box.find('[data-key="loan_years"]');

            
            loan_amount_input.val(deficit_data['loan_amount'], 0);

            
            bank_rate_input.val(deficit_data['bank_rate'] * 100).on('input', function(){ if(this.value < 0)this.value = 0; deficit_data['bank_rate'] = this.value; loan_update_value(); });
            loan_years_input.val(deficit_data['loan_years']).on('input', function(){ if(this.value < 0)this.value = 0; deficit_data['loan_years'] = this.value; loan_update_value();  });;
            
            calculate_loan_intrests(deficit_data['loan_amount']);

        


            loan_update_value = function(value){

                                            
                                            if(!value)value = loan_slider.slider('value');
                                            if(value > 100)value = 100;
                                            if(isNaN(value))value = 0;
                                            
                                            var deficit_remaining = calculate_remaining_deficit('loan');

                                            if(deficit_remaining < 0)deficit_remaining = 0;

                                            deficit_data["loan_amount"] = parseInt(value * deficit_remaining / 100.0);

                                            loan_amount_input.val(deficit_data["loan_amount"]);
                                
                                            calculate_loan_intrests(deficit_data["loan_amount"]);   
                                            loan_total_label.html(formatMoney(deficit_data["loan_amount"] + loan_intrests, 0));
                                            loan_intrests_label.html(formatMoney(loan_intrests, 0));

                                            //assessment_update_value();

                                            update_remaining_deficit();
                                            
                                }

            loan_amount_input.unbind().on('change', function(){     var value = Math.ceil(this.value); if(value < 0)value = 0;
                                                                    deficit_data['loan_amount'] = value;
                                                                    var deficit_remaining = calculate_remaining_deficit('loan');                                            
                                                                    var r = value * 100 / deficit_remaining;
                                                                    loan_slider.slider('value', r);
                                                                    loan_update_value(r);
                                                                });

        // Monthly Fees Options
        var monthly_fees_slider = deficit_box.find('.monthly-fees-slider'),
            monthly_fees_label = deficit_box.find('input.monthly-fees'),
            monthly_fees_reset_btn = deficit_box.find('.monthly-fees-reset-btn'),
            monthly_fees_clear_btn = deficit_box.find('.monthly-fees-clear-btn'),
            monthly_fees = deficit_data['monthly_fees'] ,
            default_monthly_fees = original_yearlyCollections / (units * 12);
            
            deficit_box.find('span.monthly-fees-old').html(formatMoney(default_monthly_fees, 0)); // init current monthly fees

            // reset slider with new limits
            // var monthly_fees_diff = parseInt(yearlyCollections - original_yearlyCollections) / (units * 12),
            //     monthly_fees_diff_inc = monthly_fees_diff * 100 /default_monthly_fees;
            
            // if(monthly_fees_diff_inc > 0){
            //     console.log(monthly_fees_diff_inc);
            //     //monthly_fees = default_monthly_fees;
            //     //yearlyCollections = original_yearlyCollections;
            //     //deficit_data['monthly_fees_inc'] = 0;
            //     monthly_fees_slider.data('min',  1 );
            //     monthly_fees_slider.data('max',  default_monthly_fees * 100);
            //     //monthly_fees_slider.data('max',  monthly_fees * 2);
            // }else{                
            //     //monthly_fees_slider.data('min', (yearlyCollections > 0 ? -100 : 0));
            //     //monthly_fees_slider.data('max', (is_deficit ?  Math.floor(((original_deficit_amount) * 100 / yearlyCollections )) +  200 : 300) );
            //     monthly_fees_slider.data('min', 1);
            //     monthly_fees_slider.data('max', default_monthly_fees * 100);
            // }

            //monthly_fees_label.val(Math.ceil(monthly_fees * (1 + deficit_data['monthly_fees_inc'])), 0);  // init new monthly fees
            monthly_fees_slider.data('min', 1);
            monthly_fees_slider.data('max', 1000);
            monthly_fees_label.val(monthly_fees);  // init new monthly fees


            //ui_slider(deficit_box.find('.monthly-fees-slider'), null, true);
            
            monthly_fees_clear_btn.click(function(){  
                                                //var remaining_to_cover = Math.floor((calculate_remaining_deficit('mf')) * 100 / yearlyCollections )) + 1;
                                                var remaining_to_cover = Math.ceil(calculate_remaining_deficit('mf') * (1 + inflation_rate) / (12 * units) )  ;
                                                monthly_fees_slider.slider('value', remaining_to_cover);
                                                monthly_fee_increase_update_value( remaining_to_cover ); 
                                    });
                                    
            monthly_fees_reset_btn.click(function(){ 
                                                monthly_fees_slider.slider('value', default_monthly_fees);
                                                monthly_fee_increase_update_value( default_monthly_fees ); 
                                            });
                                                                
            monthly_fee_increase_update_value = function(value){

                                                        //if(value != undefined)deficit_data['monthly_fees_inc'] = value / 100.0;
                                                        if(value != undefined)deficit_data['monthly_fees'] = value;

                                                        var deficit_remaining = calculate_remaining_deficit('mf');
                                                        // var deficit_to_coverby_increase = Math.ceil(((deficit_remaining) * 100 / yearlyCollections ));
                                                        
                                                                                                               
                                                           
                                                        /*                                                     
                                                        if((deficit_data.monthly_fees_inc * 100) > deficit_to_coverby_increase ){
                                                            deficit_data['monthly_fees_inc'] = deficit_to_coverby_increase / 100.0;
                                                            monthly_fees_slider.slider('value', deficit_to_coverby_increase);
                                                            //return;
                                                        }
                                                        
                                                        */
                                                        
                                                        //var increase = (monthly_fees * (1 + deficit_data.monthly_fees_inc));
                                                        //if(increase < 0)increase = 0;
                                                        

                                                        // monthly_fees_label.val(Math.ceil(increase));                                                
                                                        monthly_fees_label.val(value);                                                
                                                                          
                                                        
                                                        //loan_update_value();
                                                        //assessment_update_value();
                                                        update_remaining_deficit();
                                }

           

            monthly_fees_label.unbind().change(function(){      
                                                             var value = Math.ceil(this.value); if(value < 1 /* monthly_fees*/)value = 1 /*monthly_fees*/;
                                                            
                                                            // deficit_data['monthly_fees_inc'] = parseInt((value - monthly_fees ) * 100 / monthly_fees) / 100;                                                            
                                                            // if(deficit_data['monthly_fees_inc'] < -1)deficit_data['monthly_fees_inc'] = -1;
                                                            
                                                            // var r = deficit_data['monthly_fees_inc'] * 100;
                                                            // monthly_fees_slider.slider('value', r);
                                                            monthly_fees_slider.slider('value', value);
                                                            

                                                            monthly_fee_increase_update_value(value);
                                                        });


        // Immediate Assessment
        var assessment_input = deficit_box.find('input.assessment-amount'),
            assessment_total_label = deficit_box.find('span.assessment-total'),
            assessment_unit_label = deficit_box.find('span.assessment-unit');

            assessment_input.attr('max', deficit_amount);
            
        var assessment_update_value = function(value = 0){

                                                //var remaining = calculate_remaining_deficit('assessment');

                                                //if(value > remaining)value = remaining;
                                                /* else */ if(value < 0)value = 0;
                                                
                                                deficit_data['assessment'] = value;
                                                assessment_input.val(value);

                                                assessment_total_label.html(formatMoney(value, 0));
                                                assessment_unit_label.html(formatMoney(value/units, 0));
                                        }

            assessment_input.val(deficit_data["assessment"]).unbind().on('input', function(){
                

                                                                        var value = Math.ceil(this.value);
                                                                        
                                                                        assessment_update_value(value);
                                                                        
                                                                        // update monthly fees increase before
                                                                        update_remaining_deficit();
                                                                    });
        

        var context = this;

        

        // Spendings
        var spendings = this.simulation_data.spendings[at];         // data before editing
            spendings_table = deficit_box.find('.spendings-table');
            spendings_table.html(''),
            spendings_edit = [],    // edited data
            spendings_unsplit = []; // unsplitted items
            
            // prepare items to edit
            for(const s of spendings){ 
                var tmp = cloneObj(s);

                // set edited flage to false
                tmp.edited = false; // used to check if original item has been edited
                tmp.cost = parseInt(tmp.cost);
                tmp.year = parseInt(tmp.year);
                tmp.org = true;         // is original item
                tmp.org_cost = parseInt(tmp.cost);
                tmp.split_of = tmp.split_of || '';      // item's splitted parent
                tmp.splits = parseInt(tmp.splits);      // item's splits

                spendings_edit.push(tmp); 
                
            }

        var update_spendings_amount = function(){
                var new_spendings_amount = 0;
                for(const s of spendings_edit){
                    if(s.year == at){
                        new_spendings_amount += s.cost;
                    }
                }


                spendings_amount = new_spendings_amount;
                
                deficit_amount = original_deficit_amount - original_spendings_amount + spendings_amount;
                    //if(deficit_amount < 0)deficit_amount = 0;

                init_deficit_data(true);

                monthly_fees_slider.slider('value', 0);  monthly_fee_increase_update_value(0);
                //assessment_input.val(0); assessment_update_value(0);
                loan_slider.slider('value', 0); loan_update_value(0);

                update_remaining_deficit();
        }

        var spending_has_splits = function(item){
                                        if(!item || !item.org)return false;                                        
                                        return spendings_edit.findIndex((obj)=>{return obj.split_of == item.id}) >= 0;
                                    }
        var spendings_check_edit = function(){
                                        var has_edit = false;
                                        for(const s of spendings_edit){
                                            if(s.org && s.edited){
                                                has_edit = true;
                                                break;
                                            }
                                        }

                                        //deficit_box.find('.apply').toggleClass('d-none', !has_edit);

                                        return has_edit;
                                    }

        var spendings_fill = function(){

            spendings_table.html('');

            // data-index: the index witin the spendings array

            var spendings_tmpl = '<div class="col-1 p-2 pt-3 border-bottom border-dark "><label class="w3-indigo px-2 py-2 rounded pointer split font-weight-bold" style="font-size:0.8rem;" data-index="%INDEX%">Split</label></div>   \
                                  <div class="col-4 p-2 pt-3 pl-3 border-bottom border-dark name"></div> \
                                  <div class="col-2 p-2 pt-3 border-bottom border-dark"><input type="number" min="0" step="1" class="app-input year" value=""  data-index="%INDEX%" /></div>   \
                                  <div class="col-3 p-2 pt-3 border-bottom border-dark"><input type="number" min="1" step="1" class="app-input d-none cost" value=""  data-index="%INDEX%" data-split="" /><span class="float-right formatted-cost font-weight-bold d-none" data-index="%INDEX%"></span></div>    \
                                  <div class="col-2 p-2 pt-2 border-bottom border-dark">    \
                                    <i class="d-none pointer p-2 pt-3 fa fa-times w3-text-red del"  style="font-size:1.2rem;"  data-index="%INDEX%"></i>    \
                                    <label class="d-none mt-2 btn btn-sm btn-danger unsplit" data-index="%INDEX%">Unsplit</label>    \
                                    <label class="d-none mt-2 btn btn-sm w3-blue text-nowrap view-splits" data-index="%INDEX%"><i class="mr-1 fa fa-eye"></i>Splits</label>    \
                                  </div>';

            //for(var i=0; i<10; i++)
            for(var i=0; i<spendings_edit.length; i++){
                var s = spendings_edit[i];

                var element = $(replaceAll(spendings_tmpl, '%INDEX%', i));
                element.filter('.name').html(s.split_of && !s.org ? '<i class="ml-5 fa fa-level-up-alt fa-rotate-90"></i>' : (ucwords(s.name, true)  + ('<b class="badge ml-1 w3-pink">'+(s.redundancy_at+1)+'</b>') + (s.split_of ? '<b class="badge ml-1 w3-deep-purple">Split</b>' : '') ) );
                element.find('.year').val(s.year)
                        .on('input', function(){ 
                            var index = $(this).data('index');
                            var year = parseInt(this.value || 0);
                            var tmp = spendings_edit[index];

                            tmp.year = year;

                            // if it's orgiinal item, only set to unedited if different year and has no splits
                            if(tmp.org){
                                var org = spendings.find((obj)=>{return obj.id == tmp.id});
                                tmp.edited = (tmp.year != org.year || 
                                              tmp.cost != org.cost ||
                                              spending_has_splits(tmp));
                            }


                            //spendings_check_edit();
                            update_spendings_amount();

                         });
                
                // cost input
                element.find('.cost').val(s.cost).toggleClass('d-none', s.org)
                        .on('input', function(){ 
                            var index = $(this).data('index');
                            var new_cost = parseInt(this.value || 1);
                            var tmp = spendings_edit[index];

                            var split_index = spendings_edit.findIndex((obj)=>{return obj.id == tmp.split_of});
                            var split_of = spendings_edit[split_index];

                            // check remaining          
                            var remaining = tmp.org_cost - 1;                            
                            for(var i=0; i<spendings_edit.length; i++){
                                if(i != index && spendings_edit[i].split_of == tmp.split_of){
                                    remaining -= spendings_edit[i].cost;
                                }
                            }

                            remaining = parseInt(remaining);

                            // if over remaining or under $1 update input value
                            if(new_cost > remaining){
                                new_cost = remaining;
                                this.value = new_cost;
                            }else if(new_cost < 1){
                                new_cost = 1;
                                this.value = new_cost;
                            }
                            
                            // update rremaining and current split value
                            remaining -= new_cost;
                            tmp.cost = new_cost;
                            
                            // update data
                            split_of.cost = remaining + 1;
                            spendings_table.find('.formatted-cost[data-index="'+split_index+'"]').html(formatMoney(remaining + 1, 0));
                            
                            update_spendings_amount();

                         });

                // cost formatted
                element.find('.formatted-cost').html(formatMoney(s.cost, 0)).toggleClass('d-none', !s.org);
                
                element.find('.del').toggleClass('d-none', s.org);
                element.find('.unsplit').toggleClass('d-none', !(s.split_of && s.org));
                //element.find('.view-splits').toggleClass('d-none', !(s.splits > 0 && s.org));

                
                if(!s.org)element.find('.split').remove();

                // splits item and adds a new row 
                element.find('.split').click(function(){
                    var index = $(this).data('index');
                    var tmp = cloneObj(spendings_edit[index]);
                    
                    // clone the splitted item and change cost and year
                    tmp.id = '';
                    tmp.year++;
                    tmp.cost = parseInt(tmp.cost/2);
                    tmp.split_of = spendings_edit[index].id;
                    
                    tmp.org = false;
                    tmp.edited = true;

                    spendings_edit.splice(index+1, 0, tmp); //add right splite right after splitted item

                    // ecit orginal item                    
                    spendings_edit[index].cost -= tmp.cost;
                    spendings_edit[index].edited = true;
                    
                    spendings_fill();

                    //spendings_check_edit();
                    update_spendings_amount();
                });


                // removes split done in modal and adds cost to split_of item
                element.find('.del').click(function(){
                    var index = $(this).data('index');
                    var tmp = spendings_edit[index];

                    confirm('Are you sure you want to remove this split?', '', null, function(action){
                        
                        var split_of = spendings_edit.find((obj) => {return obj.id == tmp.split_of} );

                        spendings_edit.splice(index, 1);   
    
                        if(!tmp.org && !objEmpty(split_of)){
                            split_of.cost += tmp.cost;
                            
                            var org = spendings.find((obj)=>{return obj.id == split_of.id});
                            split_of.edited = (split_of.year != org.year || 
                                                                split_of.cost != org.cost ||
                                                                spending_has_splits(split_of));
                        }
                        //spendings[index].cost += s.cost;
                        
                        spendings_fill();
    
                        //spendings_check_edit();
                        update_spendings_amount();

                    });

                    
                });
                

                // removes splitted element from edits and adds it to unsplit array
                element.find('.unsplit').click(function(){
                    var index = $(this).data('index');
                    var tmp = spendings_edit[index];

                    if(!tmp.org){
                        return;
                    }

                    confirm("Unsplitting this item will add its <b>Cost</b> to the item from which it has been splitted, would you like to continue ?", 
                                        "Warning", 
                                        null, function(action){
                                            if(action == 'ok'){

                                                spendings_unsplit.push(tmp.id);
                                                spendings_edit.splice(index, 1);   

                                                spendings_fill();

                                                //spendings_check_edit();
                                                update_spendings_amount();
                                            }
                                        });
                });

                spendings_table.append(element);
            }

            init_ui(spendings_table);

            
        }


        spendings_fill();


        // Investment Strategy

        var inv_strategy_inputs = deficit_box.find('input.inv-startegy');

        var inv_strategy_update = function(initOriginal = false){
            
            var avr = 0;
            for(const st of deficit_data['inv_strategy']){
                avr += (st.rate || 0 ) * (st.perc || 0) / 100;
            }

            avr /= 100;

            if(initOriginal)original_net_earning_amount = avr * total_amount;
            else net_earning_amount = avr * total_amount;

        }

        fill_editor(deficit_box.find('.inv-strategy-box'), deficit_data);

        inv_strategy_inputs.on('input', function(){
                                var item = $(this),
                                    key = item.data('key'),
                                    value = item.val();


                                    if(key.indexOf('perc') >= 0){
                                        var perc_remaining = 100;
                                        for(const i in deficit_data['inv_strategy']){
                                            if(key.indexOf('inv_strategy/'+i+'/') >= 0)continue;
                                            perc_remaining -= parseInt(deficit_data['inv_strategy'][i]['perc']);

                                        }

                                        if(value > perc_remaining){
                                            item.val(perc_remaining);
                                            value = perc_remaining;
                                        }
                                    }

                                    objectSetDataToPath(deficit_data, key, value, true);

                                    inv_strategy_update();
                                    

                                    init_deficit_data(true);
                                    monthly_fees_slider.slider('value', 0);  monthly_fee_increase_update_value(0);
                                    assessment_input.val(0); assessment_update_value(0);
                                    loan_slider.slider('value', 0); loan_update_value(0);

                                    update_remaining_deficit();
                            })
        
        inv_strategy_update(true); // set original
        inv_strategy_update();      // set edited
        

        // Modal
                
        var deficit_modal = view_modal(deficit_box, '<span class="h4">Erase Deficit <b>'+(at == 0 ? 'Current Year' : 'Year '+at)+'</b></span>', null, false, 'xl', false);
        if(deficit_chart != null && deficit_chart != undefined){
            deficit_chart.resize();
        }

        deficit_box.find('.reset').click(function(){

                            confirm('Are you sure you want to <b>Reset</b> ? <br><b><u>This Action is irreversible</u></b> !', 'Reset', null, 
                                                        function(action){
                                                            if(action != 'ok')return;
                                                            
                                                            utils = new ModelItems();
                                                            utils.deficit(context.model_id, {"year": at, "reset":true}, 
                                                                                        function(resp){
                                                                                            deficit_modal.close();
                                                                                            execfunc(cb);
                                                                                        });

                                                        })
                    
        });

        deficit_box.find('.apply').click(function(){
                var tmp = [];
                for(var i=0; i<spendings_edit.length; i++){
                    
                    var s = spendings_edit[i];
                    
                    if(!s.org){
                        var similar = spendings_edit.findIndex((obj)=>{ return obj.split_of == s.split_of && obj.year == s.year});
                        var split_of = spendings_edit.find((obj)=>{ return obj.id == s.split_of }); // item from which it's splitted

                        if(similar >= 0 && similar != i){
                            error('<br> Splits of the item <b>'+s.name+'</b> must have different <b>Years</b> !<br><br> Error at Year <b>'+s.year+'</b> ', 'Splitting');
                            return;
                        }else if(split_of.year == s.year ){
                            error('<br> Splits of the item <b>'+s.name+'</b> Cannot have the same <b>Year</b> as parent item ! <br><br> Year <b>'+s.year+'</b> ', 'Splitting');
                            return;
                        }

                        // if the item from which is has been splitted is already a split
                        // set it's split_of id instead
                        if(split_of.split_of)s.split_of = split_of.split_of;
                    }

                    if(s.edited)tmp.push(s);
                }

                var total_perc = 0, total_rate = 0;
                for(const st of deficit_data['inv_strategy']){
                    total_perc += parseInt(st.perc || 0);
                    total_rate += parseInt(st.rate || 0);
                }
                if(total_rate > 0 && total_perc != 100){
                    error('The <b>Total % of Wallet</b> must be <b>100%</b> !<br> Total % of Wallet: <b>'+total_perc+'%</b>');                                                        
                    return;
                }

                //deficit_data = {...deficit_data, ...{"fa":amount, "yc": yearlyCollections, "ta": total_amount}};

                if(has_deficits && (tmp.length > 0 || spendings_unsplit.length > 0)){
                    confirm('Changing Priorities will <b>Reset All Managed Years</b>, Would you like to continue ?', 'Warning', null, 
                                                        function(action){ 
                                                                if(action != 'ok')return;


                                                                utils = new ModelItems();
                                                                utils.deficit(  context.model_id, 
                                                                                {"year": at, "splits":tmp, "deficit":deficit_data, "unsplit":spendings_unsplit}, 
                                                                                function(resp){
                                                                                    deficit_modal.close();
                                                                                    execfunc(cb);
                                                                                });
                                                        });
                }else{

                    deficit_data['fa'] = original_fa;

                    utils = new ModelItems();
                    utils.deficit(  context.model_id, 
                                    {"year": at, "splits":tmp, "deficit":deficit_data, "unsplit":spendings_unsplit}, 
                                    function(resp){
                                        deficit_modal.close();
                                        execfunc(cb);
                                    });
                }
                
        });

        deficit_box.find('#deficit-box-tabContent').tabs('show');
        
            
        init_ui(deficit_box, function(event, value){ 
            
            
            if(event.key == "loan_ratio"){        
                
                if(event.event != "stop")return;

                loan_update_value(value.value);

            }else if(event.key == 'monthly_fees_inc'){

                if(event.event != "stop")return;

                monthly_fee_increase_update_value(value.value);

                

            }else if(event.event == "change"){

                deficit_data[event.key] = floatDecimals(parseFloat(event.type == 'slider' ? value.value : value ) / (event.element.data('percent') ? 100 : 1), 4);
            
                if(event.key == "bank_rate" || event.key == "loan_years")loan_update_value();
            }


        });
            

        monthly_fees_slider.slider('value', deficit_data['monthly_fees']);  
        assessment_input.val(deficit_data['assessment']);

        var init_loan =  deficit_data['loan_amount'] * 100 / calculate_remaining_deficit('loan'); if(isNaN(init_loan))init_loan = 0;
        loan_slider.slider('value', init_loan);  loan_update_value();
        

        $(window).on('resize', function(){
            if(deficit_chart != null && deficit_chart != undefined){
                deficit_chart.resize();
            }
        });
        

        
        update_remaining_deficit();



        return true;
        
    };
    
    
    
    Simulation.prototype.removeLoan = function(at){

        if(at == undefined || !this.erase_deficit["y_"+at] == -1)return false;
        delete this.erase_deficit["y_"+at];
        
        return true;
        
    };

    Simulation.prototype.init = function(model_id, callbacks){ 
        
        
            if(this.root.length == 0)return;
            //var table = this.root.find('.simulation-calculation-table tbody');
            

            
            var chart_series = {"remaining_amount":[], "compound":[], "loan":[], "loan_payments":[], "collections":[], "collectionsInc":[], "investment":[], "inv_remaining":[]};
            var chart_xaxis = [];
            //chart_element.html('');



            // fill model infos
            var model_infos = this.root.find('.model-info-val');

            if(!model_id)model_id = this.model_id;
            else { if(this.model_id != model_id){ this.reset(); } this.model_id = model_id; }

            var context = this;
            var l = loading('', this.root);

            this.simulation(model_id, function(data){
                
                l.remove();
                
                context.simulation_data = data;
                console.log(data);

                var currency = '$';

                
                
                
                /* TABLE */

                
                var calculated = data.calculated, spendings = data.spendings;
                var monthly_fees = parseFloat(data.model.monthly_fees);


                var has_deficits = function(at = 0){

                                            var has_deficit = false;
                                            var j =  at;
                                                                                        
                                            if(j < context.simulation_data.calculated.length){
                                                for( j; j<context.simulation_data.calculated.length; j++){

                                                    var d = context.simulation_data.calculated[j];
                                                    
                                                    if(d.deficit && !objEmpty(d.deficit)){
                                                        has_deficit = true; 
                                                        break;
                                                    }
                                                }
                                            }

                                            return has_deficit;
                                        }
                
                var view_spendings = function(index){
                    var sp = context.simulation_data.spendings[index] || {};

                    if(objEmpty(sp))return;

                    var content = $('<div class="w-100 my-3" style="height:500px; overflow:auto;"><div class="row m-0 py-2"></div></div>');
                    var tmpl = '<div class="col-sm-12 pt-3 name h5" data-id="%ID%"></div>   \
                                <div class="col-5 pb-2 border-bottom border-dark " data-id="%ID%"><b>Year</b><br><input type="number" class="app-input year" data-id="%ID%" min="0" data-icon="check button w3-green apply d-none" /></div>   \
                                <div class="col-5 pb-2 border-bottom border-dark text-right" data-id="%ID%"><b>Cost</b><br><span class="cost h6"></span></div>   \
                                <div class="col-2 pb-2 border-bottom border-dark " data-id="%ID%"><label class="w-100 mt-2 del btn btn-sm btn-danger d-none" >Unspit</label></div>';

                    var sp_ids = [];
                    var edited = false;

                    var move_spending = function(item_id){
                            if(!item_id)return;

                            var item = modal.find('.year[data-id="'+item_id+'"]')
                            var new_year = item.val(),
                                s = context.simulation_data.spendings[index][item.data('index')];


                            if(new_year < 0){
                                error('Year must greater or egal to 0 !', '', default_error_alert_timeout);
                                return;
                            }

                            s.year = new_year;

                            confirm("Are you sure you want to change the <b>Year</b> of this item, This will <b>Reset Simulation Managed Data</b> ?", 
                                        "Warning", 
                                        null, function(action){
                                            if(action == 'ok'){

                                                edited = true;

                                                utils = new ModelItems();
                                                utils.update(s, function(resp){
                                                    
                                                    if(!checkError(resp)){
                                                        content.find('[data-id="'+item_id+'"]').remove();
                                                        
                                                        var elements_remaining = content.find('.name');
                                                        if(elements_remaining.length == 0){
                                                            modal.close();
                                                            context.init();
                                                        }else{
                                                            modal.title('Spendings ('+elements_remaining.length+')');
                                                        }
                                                    }
                                                });
                                            }
                                        });
                    }


                    for(var i=0; i<sp.length; i++){
                        var s = sp[i];
                        var line = $(replaceAll(tmpl, '%ID%', s.id));


                        line.filter('.name').html(ucwords(s.name) + ('<b class="badge ml-1 w3-pink">'+(s.redundancy_at+1)+'</b>') + (s.split_of ? '<b class="badge ml-1 w3-deep-purple">Split</b>' : '') );
                        line.find('.year').val(s.year)
                            .data('value', s.year)
                            .data('index', i)
                            .on('input', function(event){ 
                                                if(this.value < 0)this.value=0; 
                                                var val = parseInt(this.value), 
                                                    old = $(this).data('value'),
                                                    item_id = $(this).data('id'); 
                                                    
                                                if(val < 0)this.value=0; 
                                                

                                                $(this).siblings('i.apply').toggleClass('d-none', val == old);


                                            })
                            .on('keyup', function(event){ if(event.keyCode == 13)move_spending($(this).data('id')); });

                        line.find('.cost').html(formatMoney(s.cost, 0));
                        line.find('.del').toggleClass('d-none', !s.split_of).data('id', s.id)
                            .click(function(){
                                var item = $(this), item_id = item.data('id');
                                if(!item_id)return;

                                confirm("Unsplitting this item will <b>Reset Simulation Managed Data</b> and add its <b>Cost</b> to the item from which it has been splitted, would you like to continue ?", 
                                        "Warning", 
                                        null, function(action){
                                            if(action == 'ok'){

                                                utils = new ModelItems();
                                                utils.unsplit(item_id, function(resp){
                                                    
                                                    if(!checkError(resp)){
                                                        edited = true;
                                                        content.find('[data-id="'+item_id+'"]').remove();
                                                        
                                                        var elements_remaining = content.find('.name');
                                                        if(elements_remaining.length == 0){
                                                            modal.close();
                                                            context.init();
                                                        }else{
                                                            modal.title('Spendings ('+elements_remaining.length+')');
                                                        }
                                                    }
                                                });
                                            }
                                        });

                            });
                        content.find('.row').append(line);

                        sp_ids.push(s.id);
                    }


                    var modal = view_modal(content, 'Spendings ('+sp.length+')', 
                                            function(action){
                                                if(action.action == 'cancel' && edited){
                                                    context.init();
                                                }
                                            }, true, 'lg', false);

                    modal.find('i.apply').click(function(){
                            var item = $(this), item_id = item.siblings('input').data('id'); 
                            if(!item_id)return;
                            
                            move_spending(item_id);
                            
                    });
                    
                }


                var view_spendings2 = function(index, cb){
                    

                    var content = $('<div class="w-100 mt-1 mb-4 p-3" style="height:500px; overflow:auto;">  \
                                        <div class="w-100 mt-2 mb-3 text-right actions invisible"> \
                                            <label class="btn btn-sm btn-danger cancel"><i class="fa fa-times mr-2"></i> Cancel</label> \
                                            <label class="btn btn-sm btn-success apply"><i class="fa fa-check mr-2"></i> Apply</label> \
                                        </div> \
                                        <div class="w-100 mt-3">  \
                                            <div class="row m-0  h6">  \
                                                <div class="col-1"></div>  \
                                                <div class="col-4">Item</div>  \
                                                <div class="col-2 text-center">Year</div>  \
                                                <div class="col-3 text-right">Cost</div>  \
                                                <div class="col-2 text-center"></div>  \
                                            </div>  \
                                            <div class="w-100 overflow-auto"  style="height:380px;">  \
                                                <div class="row m-0 spendings-table pt-3  h6"></div>  \
                                            </div>  \
                                        </div>  \
                                    </div>');
                                    
                    
                    var spendings, spendings_edit, spendings_final_edit, spendings_unsplit, spendings_unsplit_years, 
                        spendings_table = content.find('.spendings-table');

                    var spending_init = function(){

                        // Spendings
                        spendings = context.simulation_data.spendings[index];         // data before editing
                        spendings_edit = [];    // edited data
                        spendings_final_edit = [];    // final edited data
                        spendings_unsplit = []; // unsplitted items
                        spendings_unsplit_years = {}; // unsplitted items years
                        
                        spendings_table.html('');
                        
                        // prepare items to edit
                        for(const s of spendings){ 
                            var tmp = cloneObj(s);

                            // set edited flage to false
                            tmp.edited = false; // used to check if original item has been edited
                            tmp.cost = parseInt(tmp.cost);
                            tmp.year = parseInt(tmp.year);
                            tmp.org = true;         // is original item
                            tmp.org_cost = parseInt(tmp.cost);
                            tmp.split_of = tmp.split_of || '';      // item's splitted parent
                            tmp.splits = parseInt(tmp.splits);      // item's splits

                            spendings_edit.push(tmp); 
                            
                        }

                    
                    }

                    spending_init();

                    
                    var modal = view_modal(content, 'Spendings ('+(spendings.length)+')', null, true, 'lg', false);
                    

                    var spending_has_splits = function(item){
                                                    if(!item || !item.org)return false;                                        
                                                    return spendings_edit.findIndex((obj)=>{return obj.split_of == item.id}) >= 0;
                                                }

                    var spendings_check = function(withAlert = false){
                        
                        spendings_final_edit = [];

                        for(var i=0; i<spendings_edit.length; i++){
                    
                            var s = spendings_edit[i];
                            
                            if(!s.org){
                                var similar = spendings_edit.findIndex((obj)=>{ return obj.split_of == s.split_of && obj.year == s.year});
                                var split_of = spendings_edit.find((obj)=>{ return obj.id == s.split_of }); // item from which it's splitted
        
                                if(similar >= 0 && similar != i){
                                    if(withAlert)error('<br>Splits of this item must have different <b>Years</b> : <br><br><b>'+ucwords(s.name)+'</b><br> Error at Year <b>'+s.year+'</b> ', 'Splitting');
                                    return false;
                                }else if(split_of.year == s.year ){
                                    if(withAlert)error('<br>Splits of this item must have different <b>Years</b> : <br><br><b>'+ucwords(s.name)+'</b><br> Year <b>'+s.year+'</b> ', 'Splitting');
                                    return false;
                                }
        
                                // if the item from which is has been splitted is already a split
                                // set it's split_of id instead
                                if(split_of.split_of)s.split_of = split_of.split_of;
                            }
        
                            if(s.edited)spendings_final_edit.push(s);
                        }

                        var ok = (spendings_final_edit.length > 0 || spendings_unsplit.length > 0);

                        content.find('.actions').toggleClass('invisible', !ok);

                        return ok;
                    }

                    var spendings_fill = function(){
                        spendings_table.html('');
            
                        // data-index: the index witin the spendings array
            
                        var spendings_tmpl = '<div class="col-1 p-2 pt-3 border-bottom border-dark "><label class="w3-indigo px-2 py-2 rounded pointer split font-weight-bold" style="font-size:0.8rem;" data-index="%INDEX%">Split</label></div>   \
                                            <div class="col-4 p-2 pt-3 pl-3 border-bottom border-dark name"></div> \
                                            <div class="col-2 p-2 pt-3 border-bottom border-dark"><input type="number" min="0" step="1" class="app-input year" value=""  data-index="%INDEX%" /></div>   \
                                            <div class="col-3 p-2 pt-3 border-bottom border-dark"><input type="number" min="1" step="1" class="app-input d-none cost" value=""  data-index="%INDEX%" data-split="" /><span class="float-right formatted-cost font-weight-bold d-none" data-index="%INDEX%"></span></div>    \
                                            <div class="col-2 p-2 pt-2 border-bottom border-dark">    \
                                                <i class="d-none pointer p-2 pt-3 fa fa-times w3-text-red del"  style="font-size:1.2rem;"  data-index="%INDEX%"></i>    \
                                                <label class="d-none mt-2 btn btn-sm btn-danger unsplit" data-index="%INDEX%">Unsplit</label>    \
                                                <label class="d-none mt-2 btn btn-sm w3-blue text-nowrap view-splits" data-index="%INDEX%"><i class="mr-1 fa fa-eye"></i>Splits</label>    \
                                            </div>';
            
                        //for(var i=0; i<10; i++)
                        for(var i=0; i<spendings_edit.length; i++){
                            var s = spendings_edit[i];
            
                            var element = $(replaceAll(spendings_tmpl, '%INDEX%', i));
                            element.filter('.name').html(s.split_of && !s.org ? '<i class="ml-5 fa fa-level-up-alt fa-rotate-90"></i>' : (ucwords(s.name, true)  + ('<b class="badge ml-1 w3-pink">'+(s.redundancy_at+1)+'</b>') + (s.split_of ? '<b class="badge ml-1 w3-deep-purple">Split</b>' : '') ) );
                            element.find('.year').val(s.year)
                                    .on('input', function(){ 
                                        var index = $(this).data('index');
                                        var year = parseInt(this.value || 0);
                                        var tmp = spendings_edit[index];
            
                                        tmp.year = year;
            
                                        // if it's orgiinal item, only set to unedited if different year and has no splits
                                        if(tmp.org){
                                            var org = spendings.find((obj)=>{return obj.id == tmp.id});
                                            tmp.edited = (tmp.year != org.year || 
                                                        tmp.cost != org.cost ||
                                                        spending_has_splits(tmp));
                                        }
            
            
                                        spendings_check();
            
                                    });
                            
                            // cost input
                            element.find('.cost').val(s.cost).toggleClass('d-none', s.org)
                                    .on('input', function(){ 
                                        var index = $(this).data('index');
                                        var new_cost = parseInt(this.value || 1);
                                        var tmp = spendings_edit[index];
            
                                        var split_index = spendings_edit.findIndex((obj)=>{return obj.id == tmp.split_of});
                                        var split_of = spendings_edit[split_index];
            
                                        // check remaining          
                                        var remaining = tmp.org_cost - 1;                            
                                        for(var i=0; i<spendings_edit.length; i++){
                                            if(i != index && spendings_edit[i].split_of == tmp.split_of){
                                                remaining -= spendings_edit[i].cost;
                                            }
                                        }
            
                                        remaining = parseInt(remaining);
            
                                        // if over remaining or under $1 update input value
                                        if(new_cost > remaining){
                                            new_cost = remaining;
                                            this.value = new_cost;
                                        }else if(new_cost < 1){
                                            new_cost = 1;
                                            this.value = new_cost;
                                        }
                                        
                                        // update rremaining and current split value
                                        remaining -= new_cost;
                                        tmp.cost = new_cost;
                                        
                                        // update data
                                        split_of.cost = remaining + 1;
                                        spendings_table.find('.formatted-cost[data-index="'+split_index+'"]').html(formatMoney(remaining + 1, 0));
                                        
                                        spendings_check();
                                        
                                    });
            
                            // cost formatted
                            element.find('.formatted-cost').html(formatMoney(s.cost, 0)).toggleClass('d-none', !s.org);
                            
                            element.find('.del').toggleClass('d-none', s.org);
                            element.find('.unsplit').toggleClass('d-none', !(s.split_of && s.org));
                            //element.find('.view-splits').toggleClass('d-none', !(s.splits > 0 && s.org));
            
                            
                            if(!s.org)element.find('.split').remove();
            
                            // splits item and adds a new row 
                            element.find('.split').click(function(){
                                var index = $(this).data('index');
                                var tmp = cloneObj(spendings_edit[index]);
                                
                                // clone the splitted item and change cost and year
                                tmp.id = '';
                                tmp.year++;
                                tmp.cost = parseInt(tmp.cost/2);
                                tmp.split_of = spendings_edit[index].id;
                                
                                tmp.org = false;
                                tmp.edited = true;
            
                                spendings_edit.splice(index+1, 0, tmp); //add right splite right after splitted item
            
                                // ecit orginal item                    
                                spendings_edit[index].cost -= tmp.cost;
                                spendings_edit[index].edited = true;
                                
                                spendings_fill();
                            });
            
            
                            // removes split done in modal and adds cost to split_of item
                            element.find('.del').click(function(){
                                var index = $(this).data('index');
                                var tmp = spendings_edit[index];
            
                                confirm('Are you sure you want to remove this split?', '', null, function(action){
                                    
                                    var split_of = spendings_edit.find((obj) => {return obj.id == tmp.split_of} );
            
                                    spendings_edit.splice(index, 1);   
                
                                    if(!tmp.org && !objEmpty(split_of)){
                                        split_of.cost += tmp.cost;
                                        
                                        var org = spendings.find((obj)=>{return obj.id == split_of.id});
                                        split_of.edited = (split_of.year != org.year || 
                                                                            split_of.cost != org.cost ||
                                                                            spending_has_splits(split_of));
                                    }
                                    //spendings[index].cost += s.cost;
                                    
                                    spendings_fill();
                
            
                                });
            
                                
                            });
                            
            
                            // removes splitted element from edits and adds it to unsplit array
                            element.find('.unsplit').click(function(){
                                var index = $(this).data('index');
                                var tmp = spendings_edit[index];
            
                                if(!tmp.org){
                                    return;
                                }
            
                                confirm("Unsplitting this item will add its <b>Cost</b> to the item from which it has been splitted, would you like to continue ?", 
                                                    "Warning", 
                                                    null, function(action){
                                                        if(action == 'ok'){
            
                                                            spendings_unsplit.push(tmp.id);
                                                            spendings_unsplit_years[tmp.id] = tmp.year;
                                                            spendings_edit.splice(index, 1);   
            
                                                            spendings_fill();
            
                                                        }
                                                    });
                            });
            
                            spendings_table.append(element);
                        }
            
                        init_ui(spendings_table);

                        spendings_check();
                        
                    }


                    spendings_fill();

                    content.find('.cancel').click(function(){ 
                        spending_init();
                        spendings_fill();
                    });
                    
                    content.find('.apply').click(function(){ 
                        
                        if(!spendings_check(true))return;

                        var first_year = 999;
                        for(const s of spendings_final_edit)
                            if(s.year < first_year)first_year = s.year;
                        for(const s of spendings_unsplit)
                            if(spendings_unsplit_years[s] < first_year)first_year = spendings_unsplit_years[s];


                        if(has_deficits(first_year)){
                            confirm('Changing Spendings will <b>Reset All Simulation Managed Data After Year <span class="w3-text-red">'+first_year+'</span></b>, Are you sure you want to apply ?', 'Warning', null, 
                                        function(action){
                                            if(action != 'ok')return;
                                            utils = new ModelItems();
                                            utils.deficit(  context.model_id, 
                                                            {"year": index, "splits":spendings_final_edit, "unsplit":spendings_unsplit}, 
                                                            function(resp){
                                                                modal.close();
                                                                execfunc(cb);
                                                            });
                    
                                        });
                        }else{
                            utils = new ModelItems();
                            utils.deficit(  context.model_id, 
                                            {"year": index, "splits":spendings_final_edit, "unsplit":spendings_unsplit}, 
                                            function(resp){
                                                modal.close();
                                                execfunc(cb);
                                            });
    
                        }
                        
                    });
        
                    
                }
                
                for(var i=0; i<calculated.length; i++){

                    c = calculated[i];
                    

                    
                    // fill chart series first
                    chart_series.compound.push((c.fa < 0 ? c.cp : c.fa).toFixed(2));
                    chart_series.remaining_amount.push((c.fa < 0 ? c.fa : 0).toFixed(2));
                    //chart_series.loan.push((-1 * Math.abs(c.loan_t)) /* * loan_collection_ratio */ );
                    //chart_series.loan_payments.push((-1 * Math.abs(c.loan_pay).toFixed(2)) /* * loan_collection_ratio */ );
                    //chart_series.collections.push((c.yc).toFixed(2) /* * (1 - loan_collection_ratio) */);
                    //chart_series.collectionsInc.push((c.yc_inc).toFixed(2) /* * (1 - loan_collection_ratio) */);
                    //chart_series.investment.push((c.inv).toFixed(2));
                    //chart_series.inv_remaining.push((c.inv_f < 0 ? c.inv_f : 0).toFixed(2));
                    chart_xaxis.push(c.year);

                    
                }

                var formatter = function(row){

                    var data = row.getData();
                    var cells = row.getCells();

                    var i = parseInt(data.year); 


                    var first = true;


                    for(var c of cells){

                        var k = c.getField();
                        var cell = $(c.getElement());
                        
                        if(i == 2) cell.addClass('border-bottom border-dark pb-3');

                        var decimals = cell.hasClass('decimals') ? 2 : 0; 
                        var negative = cell.hasClass('negative'); 

                        if(k == "year"){
                            if(data.year == 0)cell.html('Current');
                            continue;
                        }


                        var value = decimals > 0 || k == "mf_inc_r" ? data[k] : Math.ceil(data[k]);
                        var formattedValue = formatMoney(value, decimals, undefined, undefined, '$');
                        

                        
                        if(k == "loan_pi" ){ if( value > 0) { cell.addClass('w3-purple'); } else{ formattedValue = ""; } }

                        else if(k == "loan_i"){ if( value > 0) { formattedValue = formatMoney(value, decimals); cell.addClass('font-weight-bold w3-text-deep-orange'); }else formattedValue = '';  }

                        else if(k == "loan_y"){ if( value > 0) { formattedValue = formatMoney(-1 * value, decimals) ; cell.addClass('font-weight-bold w3-text-red'); }else formattedValue = '';  }

                        
                        else if(k == 'yc_inc'){ if(value > 0)cell.addClass('w3-amber'); }

                        else if(k == 'is'){ formattedValue = formatNumber(value * 100, 2) + '%'; cell.addClass('font-weight-bold'); }
                        
                        else if(k == 'mf_new'){ cell.addClass('w3-text-'+(data.mf_inc_r  > 0 ? 'red' : 'green')); }
                        
                        
                        else if(k == 'mf_inc_r'){ formattedValue = '<i class="fa fa-'+(value > 0.01  ? 'chevron-up' : '')+' mr-1"></i>'+formatPercent(value, true, decimals); cell.addClass('w3-text-'+(value > 0.01  ? 'red' : 'green'));  }

                        
                        else if(k == 'fa' && value < 0){ cell.addClass('w3-red');}
                        else if(k == 'fa_o' && value < 0){ cell.addClass('w3-red');}
                                                
                        else if(k == 'inv_f' && value < 0){ cell.addClass('w3-red'); }
                                                
                        else if(k == 'inv_s'){ cell.addClass('w3-text-'+(value > 0 ? 'green' : 'red') ); }
                         
                        
                        else if(k == 'assess' && data.deficit.assessment){ 
                                        formattedValue = formatMoney(data.deficit.assessment, decimals);
                                        cell.addClass('w3-green');
                        }

                        else if(k == 'erase_deficit'){ 
                                
                                formattedValue = $('<span class="w3-'+(objEmpty(data.deficit) ? 'pink' : 'deep-orange')+' w3-hover-deep-purple px-1 rounded"><i class="fa fa-cog ml-1"></i> Manage</span>');

                                cell.addClass('pt-1');
                                formattedValue.addClass('erase-deficit')
                                    .data('at', i)
                                    .data('fa', data.fa)
                                    .data('fa_o', data.fa_o)
                                    .data('yc', Math.abs(data.yc))
                                    .data('sp', Math.abs(data.sp))
                                    .data('ta', Math.abs(data.ta))
                                    .data('yc_o', Math.abs(data.yc_o))
                                        .click(function(){
                                            
                                            var at = parseInt($(this).data('at')),
                                                units = context.simulation_data.model.housing;

                                            var has_deficit = has_deficits(at);
 

                                            var fa = /* deficit.fa || */ $(this).data('fa'), 
                                                fa_o = /* deficit.fa || */ $(this).data('fa_o'), 
                                                yc = /* deficit.yc || */ $(this).data('yc'), 
                                                sp = $(this).data('sp'), 
                                                ta = /* deficit.ta || */ $(this).data('ta'), 
                                                yc_o = /* deficit.ta || */ $(this).data('yc_o');

                                            /*
                                            if(has_deficits){
                                                confirm('All the <b>Years</b> managed after this one will be resetted, would you like to continue ?', 'Warning', null, 
                                                        function(action){ 
                                                                if(action != 'ok')return;

                                                                context.eraseDeficit(   at, 
                                                                                        fa, 
                                                                                        yc, 
                                                                                        units, 
                                                                                        sp, 
                                                                                        ta, 
                                                                                        function(){ 
                                                                                            context.init(); 
                                                                                        }, has_deficits()); 
                                                            
                                                        });
                                            }else{
                                            */
                                                    context.eraseDeficit(   at, 
                                                                            fa, 
                                                                            yc, 
                                                                            units, 
                                                                            sp, 
                                                                            ta,
                                                                            yc_o,
                                                                            fa_o,
                                                                            function(){ 
                                                                                context.init(); 
                                                                            }, has_deficits()); 
                                            //}
                                        }); 

                                        

                        }
                        
                        else{ if(negative)cell.addClass('w3-text-red');  }
                        
                        
                        if(!cell.hasClass('empty'))cell.html(formattedValue);


                        if(k == 'sp'){
                            cell.append('<i class="fa fa-cog mr-2 p-1 rounded w3-pink w3-hover-deep-purple float-left"></i>')
                                .addClass('link').data('year', data.year).click(function(){
                                
                                /*
                                if(has_deficits()){
                                    confirm('Changing Priorities of the <b>Spendings</b> will <b>Reset all Years Management Data</b>, Are you sure you want to continue ?', 'Warning', null,
                                            function(action){
                                                if(action != 'ok')return;

                                                var year = $(this).data('year');
                                                view_spendings(year);

                                            });
                                }else{
                                    */
                                
                                    var year = $(this).data('year');
                                    view_spendings2(year, function(){ context.init(); });
                                //}
                                
                            });
                        }
                        

                    }

                }

                
                /* ***** */




                if(!context.table)
                    context.table = list_local(context.parent, {'rowFormatter': formatter}, null, calculated);
                else
                    context.table.setData(calculated);
                
    







                /* CHART */
                

                if(!context.chart)context.chartInit();

                var char_title = "Simulation";
                var chart_initial_data = [	
                                            { name: 'Compound', type: 'bar', barMaxWidth:15, stack: 'amount',
                                                        itemStyle: {color: '#495266'}, emphasis: { focus: 'series' }, data: chart_series.compound },
                                            
                                            { name: 'Remaining Amount', type: 'bar', barMaxWidth:15, stack: 'amount',
                                                        itemStyle: {color: '#aa0000'}, emphasis: { focus: 'series' }, data: chart_series.remaining_amount },
                                            /*
                                            { name: 'Loan', type: 'bar', barMaxWidth:15,
                                                        itemStyle: {color: '#9c27b0'}, emphasis: { focus: 'series' }, data: chart_series.loan },
                                            */
                                            { name: 'Loan Payments', type: 'bar', barMaxWidth:15,
                                                        itemStyle: {color: '#9c27b0'}, emphasis: { focus: 'series' }, data: chart_series.loan_payments },
                                            
                                            { name: 'Collections', type: 'bar', barMaxWidth:15, stack: 'Collections',
                                                        itemStyle: {color: '#ffc107'}, emphasis: { focus: 'series' }, data: chart_series.collections },
                                            { name: 'Collections Increase', type: 'bar', barMaxWidth:15, stack: 'Collections',
                                                        itemStyle: {color: '#eb4545'}, emphasis: { focus: 'series' }, data: chart_series.collectionsInc },


                                            { name: 'Investment', type: 'bar', barMaxWidth:15, stack: 'inv', 
                                                        itemStyle: {color: '#4CAF50'}, emphasis: { focus: 'series' }, data: chart_series.investment },
                                            { name: 'Investment Remaining', type: 'bar', stack: 'inv', barMaxWidth:15, 
                                                        itemStyle: {color: '#ee0000'}, emphasis: { focus: 'series' }, data: chart_series.inv_remaining }];
                
                                                        
                var option = {
                    title: { text: char_title }, tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, 
                    legend: {top: 50, selected:{'Loan':false, 'Investment':false, 'Investment Remaining':false}},
                    grid: { top: '25%', left: '3%', right: '4%', bottom: '3%', containLabel: true }, xAxis: [ { type: 'category', data: chart_xaxis} ],
                    yAxis: [ { type: 'value' } ], series: chart_initial_data
                    
                };
        
                option && context.chart.setOption(option);
                
                /* ***** */



                execfunc(callbacks, {'data': data});

            }, function(){ l.remove(); execfunc(callbacks, {'data': null}); });
    };

    
       

    Simulation.prototype.run = function(model_id, callbacks){  this.init(model_id, callbacks); }
    
                        
    Simulation.prototype.search =   function (search){ 
        if(this.table)this.table.remoteSearch(search);
    }

    Simulation.prototype.filter =   function (filters){
                                    if(this.table)this.table.remoteFilter(filters);
                                }


    Simulation.prototype.edit = function(id, cb, beforeCb){
                                edit_element("modules/simulation/app/simulation.php?cmd=edit", { "id":id}, "modules/simulation/app/simulation.php?cmd=save",
                                                function(e, context){
                                                    
                                                    context.run();

                                                },
                                                null,
                                                function(data){
                                                    
                                                    console.log(data);                                                   
                                                    
                                                    //return false;
                                                },
                                                function(action){}, this);
    }

    
    Simulation.prototype.associations =   function(cb, errCb){
                        
        ajax_get_no_loading("modules/simulation/app/simulation.php?cmd=get_association", {},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){ execfunc(errCb, []); error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    Simulation.prototype.models =   function(id, cb, errCb){
                        
        ajax_get_no_loading("modules/simulation/app/simulation.php?cmd=get_model", {client_id:id},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){ execfunc(errCb, []); error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    
    Simulation.prototype.simulation =   function(id, cb, errCb, years){
                        
        ajax_get_no_loading("modules/simulation/app/simulation.php?cmd=get", {model_id:id, erase_deficit: this.erase_deficit},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } else { execfunc(errCb, []); } }, function(resp){ execfunc(errCb, []); error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };

    
    Simulation.prototype.delete =   function(){
                        
        this.table.delete();
        
    };

    

    

    Simulation.prototype.toggleColumns =   function (){
        if(this.table)this.table.toggleColumns();
    }

    return Simulation;

}();