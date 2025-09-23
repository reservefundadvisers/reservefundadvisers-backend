var simulation;

function init_simulation(e){

    if(!e)return;

    
    simulation = new Simulation(e);

    
    init_ui(e);
    
    var guidedTour = new GuidedTour(simulation.tour_guide);
    
    var tooltips = new Tooltips(simulation.tooltips);
    e = $(e);


    //view_modal(e.find('#deficit_box_template').html(), 'Erase Deficit', null, false, 'xl', false);

    

    var association_select = e.find('select.associations'),
        model_select = e.find('select.models'),
        
        model_select_template = $("#model_select_template");

    var model_edit_btns = e.find('.model-edit');
    var model = new Models(), model_items = new ModelItems();


    
    // Edit model Infos
    model_edit_btns.click(function(){
            var action = $(this).data('action'), model_id = model_select.val();
            
            if(!model_id)return;

            if(action == "model"){
                model.edit(model_id, function(resp){ if(checkError(resp, true))return; simulation.run(); });
            }else if(action == "items"){
                model_items.edit(model_id, function(resp){ if(checkError(resp, true))return; simulation.run(); });
            }
    });

    // change model btn
    var change_model_btn = $('simulation-change-model');


    // Association & Model with MODAL
    if(model_select_template.length > 0){

        var create_model_select_modal = function(){

            var model_select_content = $(model_select_template.html());
            var model_select_associations = model_select_content.find('.model-select-associations');
            var model_select_models = model_select_content.find('.model-select-models');
            
            var model_select_modal = view_modal(model_select_content, '', null, false, 'lg', false);

            var l = loading('', model_select_content);

            simulation.associations(function(list){
                            
                model_select_associations.html('');

                if(!list || list.length == 0){ 
                    model_select_associations.append('<div class="col-12 text-center h4 py-3"> <b class="h3 font-weight-bold">Sorry !</b><br> There are no <b class="text-primary">Association</b> Available. </div>');            
                    l.remove(); return; 
                }
                

                // list models 
                var list_models = function(client_id, association_name){
                    
                    // hide associations
                    model_select_associations.parent().addClass('d-none');

                    // show models 
                    model_select_models.parent().removeClass('d-none');
                    model_select_models.addClass('invisible');


                    l = loading('', model_select_content);
                    

                    // change association btn
                    model_select_models.siblings('.model-select-change-association').unbind().click(function(){

                        // hide associations
                        model_select_associations.parent().removeClass('d-none');

                        // show models 
                        model_select_models.parent().addClass('d-none');
                    });

                    // hide deficit box
                    // e.find('#deficit_box_template').addClass('invisible');

                    simulation.models(client_id, function(list){
                        
                        model_select_models.html('');

                        if(!list || list.length == 0){ 
                            model_select_models.append('<div class="col-12 text-center h4 py-3"> <b class="h3 font-weight-bold">Sorry !</b><br> There is no <b class="text-primary">Model</b> Available for <b class="text-primary">'+association_name+'</b>. </div>');                        
                            model_select_models.removeClass('invisible');
                            l.remove(); 
                            return; 
                        }

                        // add models
                        for(const m of list){

                            var year = m.fiscal_year+"" || "Other";
                            var name_year = ' ('+(m.fiscal_year+"" || " ? ")+')';
                            
                            model_select_models.append('<div class="col-md-6 p-2"><label class="simulation-model-btn" data-id="'+m.id+'" data-model="'+btoa(ucwords(m.name + name_year, true))+'">'+ucwords(m.name + name_year, true)+'</label></div>');
                        }

                        model_select_models.find('.simulation-model-btn').click(function(){
                            
                            model_select_modal.close();


                            var model_name = atob($(this).data('model'));
                            simulation.run($(this).data('id'), function(event){

                                // show model name and association
                                $('.simulation-curr-model').html(ucwords(model_name, true));
                                $('.simulation-curr-association').html(ucwords(association_name, true));
                                $('.simulation-curr-model').parent().removeClass('invisible');
                                $('.simulation-version-options').removeClass('invisible');

                                // show main tour btn
                                $('.tour-start[data-tour-name="simulation_main"]').removeClass('d-none');
                                

                            });

                        });

                        model_select_models.removeClass('invisible');
                        l.remove();

                    });


                }

                if(list.length == 1){
                    
                    l.remove();
                    
                    // remove model selet change association btn
                    model_select_models.siblings('.model-select-change-association').remove();
                    list_models(list[0].id, ucwords(list[0].association));

                }else{
                    
                    // show associations box
                    model_select_associations.parent().removeClass('d-none');

                    // add associations
                    for(const a of list){
                        model_select_associations.append('<div class="col-md-6 p-2"><label class="simulation-association-btn" data-id="'+a.id+'"  data-association="'+btoa(ucwords(a.association, true))+'">'+ucwords(a.association, true)+'</label></div>');
                    }

                    // add models
                    model_select_associations.find('.simulation-association-btn').click(function(){
        
                        
                        list_models($(this).data('id'), atob($(this).data('association') || btoa('This Association')));
        
                    });
                    
                    l.remove();
                }


            });

        }

        // create_model_select_modal();

        change_model_btn.unbind().click(function(){
            
            create_model_select_modal();
            
        });

    }

    
    var change_model_box = $('#simulation-change-model-box');


    // Association & Model with SELECT input
    if(association_select.length > 0){
        var get_associations = function(){
            simulation.associations(function(list){

                if(has(['client_admin', 'client_user'], global_role) === false){
                    association_select.html('<option value="" disabled selected>Choose</option>');
                    association_select.closest('.app-input-group').removeClass('d-none');
                }

                model_select.html('<option value="" disabled selected>Choose (0)</option>');
                
                if(!list || list.length == 0)return;
                
                for(const a of list){
                    
                    fillSelect(association_select, {v:a.id, t:a.association, s: (list.length == 1)}, true);
                }


                association_select.unbind().change(function(){
                    var client_id = $(this).val();

                    if(!client_id)return;


                    var l = loading('', e);
                    

                    // hide deficit box
                    // e.find('#deficit_box_template').addClass('invisible');
                    

                    simulation.models(client_id, function(list){
                        
                        l.remove();

                        model_select.html('<option value="" disabled selected>Choose ('+list.length+')</option>');
                        

                        if(!list || list.length == 0)return;
                        
                        var grouped_model = {};
                        for(const m of list){
                            
                            var year = m.fiscal_year+"" || "Other";
                            var name_year = ' ('+(m.fiscal_year+"" || " ? ")+')';
                            
                            if(!grouped_model[year])grouped_model[year] = [];
                            grouped_model[year].push({ v:m.id, t:m.name + name_year });
                        

                            //var mod = {}; mod[m.id] = m.name + (m.fiscal_year ? ' - ('+m.fiscal_year+')' : '');
                            //fillSelect(model_select, mod, true);
                        }
                        
                        // start with element w/o fiscal year
                        if(grouped_model['Other'] && grouped_model['Other'].length > 0){                         
                            //fillSelect(model_select, {t: "Other", h:true}, true);
                            fillSelect(model_select, grouped_model["Other"], true);
                            delete grouped_model['Other']; 
                        }      

                        for(const k in grouped_model){              
                            //fillSelect(model_select, {t: k, h:true}, true);
                            fillSelect(model_select, grouped_model[k], true);
                        }

                        
                        model_select.unbind().change(function(){
                            var model_id = $(this).val();

                            change_model_box.fadeOut('fast');
                            

                            simulation.run(model_id, function(event){

                                
                                
                                // model infos
                            });
                        });

                        l.remove();

                    });
                }).change();



                change_model_box.fadeIn('fast');
            });
        }

        get_associations();
        e.find('.associations-refresh').click(function(){ 
            association_select.html('<option value="" disabled selected>Loading...</option>'); 
            model_select.html('<option value="" disabled selected>Loading...</option>'); 
            setTimeout(get_associations, 500); 
        });
        e.find('.models-refresh').click(function(){ 
            model_select.html('<option value="" disabled selected>Loading...</option>'); 
            setTimeout(function(){ association_select.change(); }, 500); 
        });
        
    }

    e.find('.reset-simulation').click(function(){
        
            var which = $(this).data('action') || 'current';

            confirm('Are you sure you want to <b>Reset the simulation data</b> ? This will revert back to the Default Data and the simulation data will be <b>Lost<b> <br><b><u>This Action is irreversible</u></b> !', 'Reset Simulation', null,
                function(action){
                        if(action == 'ok'){
                            simulation.reset(simulation.model_id, function(resp){
                                simulation.init();
                            }, null, null, which);
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

        this.model_id = null;

        this.summary = {};
        
        
        this.table = null; 

        this.delay = 5000;

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
    

    
    Simulation.prototype.chartInit = function(){ 
        if(this.root.find('.simulation-chart').length < 1)return;

        if(this.chart)this.chart.clear();

        this.chart =  echarts.init(this.root.find('.simulation-chart')[0]);
        
        var option = {
            tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, legend: {top: 50},
            grid: { top: '25%', left: '3%', right: '4%', bottom: '3%', containLabel: true }, xAxis: [ { type: 'category', data: [0]} ],
            yAxis: [ { type: 'value' } ], series: [{ type: 'bar', barMaxWidth:15,
                                                     itemStyle: {color: '#495266'}, emphasis: { focus: 'series' }, data: [0] }],
                                                     
            dataZoom: [ { type: 'inside', start: 0 }, { start: 0 } ],
            
        };
        

        this.chart.setOption(option);

        var context = this;
        
        $(window).on('resize', function(){
            if(context.chart){
                context.chart.resize();
            }
        });
        
    }

                     
    Simulation.prototype.toggleChart =   function (search){ 
        this.root.find('.simulation-chart').toggleClass('d-none');
        if(this.chart)this.chart.resize();
    }       

    Simulation.prototype.toggleTable =   function (search){ 
        var table = this.root.find('#simulation_table');
        if(!this.simulation_data){
            table.addClass('d-none');
            return;
        }

        table.toggleClass('d-none');

        if(!table.hasClass('d-none')){
            $('html, body').animate({
                scrollTop: table.offset().top
            }, 1000);
        }   
        
    }
    
    
    Simulation.prototype.eraseDeficit = function(at, callback){

        // which year
        if(at == undefined || !this.simulation_data)return false; 
        

        var context = this;

        

        // fill model infos
        //var deficit_box = $(this.root.find('#deficit_box_template').html());
        var deficit_box = this.root.find('#deficit_box_template');

        deficit_box.removeClass('invisible');
        
        // set title 
        deficit_box.find('.deficit-title').html('Year '+ (parseInt(context.simulation_data.model.fiscal_year) + parseInt(at)));

        
        // simulation options closing
        var simulation_options_modal = deficit_box.find('.simulation-options');
        var handle_options_modal = function(toggle, navTo = ''){ 
                                            
                                            if(navTo)goto_nav(navTo);

                                            // toggle simulation options
                                            if(toggle) {
                                                simulation_options_modal.fadeIn('fast', 'linear');
                                                
                                                // toggle toolbox when first start
                                                if(!$('#simulation_option_dropdowns').hasClass('is-toggled') || $('.simulation-tab-nav.active').length == 0){
                                                    $('#simulation_option_dropdowns').addClass('is-toggled');
                                                    // setTimeout(() => {
                                                    //     $('#simulation_option_dropdowns').dropdown('toggle');
                                                    // }, 200);
                                                }
                                                
                                            }
                                            else { 
                                                simulation_options_modal.fadeOut('fast', 'linear'); 
                                                // select corresponding year in timeline
                                                deficit_box.find('.simulation-timeline-col').removeClass('active');
                                            }


                                            // deficit_box.find('#simulation_timeline').toggleClass('pad', toggle); 
                                            deficit_box.find('#simulation_timeline').css('padding-left', !toggle ? 0 : simulation_options_modal.width() + 20 + 'px'); 
                                            
                                            
                                        }
        deficit_box.find('.simulation-options-close').unbind().click(function(){
            simulation_options_modal.fadeOut('fast');
            handle_options_modal(false);
        });
        
        // simulation option nav left right
        var deficit_options_nav = deficit_box.find('.simulation-tab-nav-group');
        deficit_options_nav.find('a').on('shown.bs.tab', function (e) {
            update_nav();
            deficit_box.find('#simulation_timeline').css('padding-left', simulation_options_modal.width() + 20 + 'px'); 
        });
        
        // remaining amount list
        var fa_row = deficit_box.find('.simulation-fa');
        // Adnan Saleem........
        function toggleSpanVisibility() {
                                               
            if ($('#settings-tab').hasClass('active') || $('#settings-tab').attr('aria-selected') === 'true') {
                $('.deficit-title').addClass('d-none'); // Hide span if tab is active
            } else {
                $('.deficit-title').removeClass('d-none'); // Show span if tab is not active
            }
        }
        
        // Call the function to set initial state
        toggleSpanVisibility();
    
        // Add event listener to check when tab is clicked or changes
        $('#settings-tab, #spendings-tab, #summary-tab, #inv-strategy-tab, #monthly-fees-tab, #loan-tab, #assessment-tab').on('click', toggleSpanVisibility);
//         $(document).on('click', '.simulation-general-rules-btn', function () {
//             alert();
//     // Activate the "Simulation Settings" tab
//     $('#settings-tab').removeClass('d-none'); // Temporarily show the tab
//     $('#settings-tab').tab('show'); // Activate the tab
//     // setTimeout(function () {
//     //     $('#settings-tab').addClass('d-none'); // Hide it again from the dropdown
//     // }, 1000);
// });

        
        // Adnan Saleem........
        
        // select a corresponding year to show eraseDeficit data for
        var manageDeficitYear = function(year){
            
            var l = loading('', deficit_box.find('.simulation-options'));

            at = parseInt(year); 
            
            // show simulation option modal
            handle_options_modal(true);

            // select corresponding year in timeline
            deficit_box.find('#simulation_timeline').trigger('selectTimeline', (at+1));

            eraseDeficit_init();

            setTimeout(function(){ 
                            print_fa(); 
                            l.remove(); 
                        }, 100);
        }


        var generate_report = function(){

                var data = {    "managed":  context.all_data.managed.calculated,
                                "ltim":     context.all_data.ltim.calculated        } ,
                    fiscal_year = parseInt(context.all_data.managed.model.fiscal_year),
                    default_mf = parseInt(context.all_data.managed.model.monthly_fees),
                    period = data.managed.length,
                    
                    total_deficit = 0,
                    total_deficit_years = 0;

                var analysis =    {     "managed":{"deficit_remain":0, "deficit_remain_years":0, "mf":[], "loan":0, "assess":0, "earning":0 }, 
                                        "ltim":{"deficit_remain":0, "deficit_remain_years":0, "mf":[], "loan":0, "assess":0, "earning":0, "ltim_earning":0, "ltim_used":0} },
                    avr_delta = 30, 
                    tmp_data =    {   "managed":  {
                                                    avr_mf: 0,
                                                    prev_mf_inc: 0,
                                                    avr_mf_start: -1,
                                                    avr_mf_value: 0,
                                                    has_stat: false

                                                },
                                      "ltim":   {
                                                    avr_mf: 0,
                                                    prev_mf_inc: 0,
                                                    avr_mf_start: -1,
                                                    avr_mf_value: 0,
                                                    has_stat: false
                                                }
                                };

                let accumulate_deficit = function(i = 0, type = "managed", accTotal = true){
                    if(i == period)return;

                    var deficit = data[type][i]['fa'],
                        deficit_o = data[type][i]['fa_o'];
                    
                    if(deficit < 0){
                        analysis[type]['deficit_remain'] += Math.abs(deficit);
                        analysis[type]['deficit_remain_years']++;
                    }

                    if(accTotal && deficit_o < 0){
                        total_deficit += Math.abs(deficit_o);
                        total_deficit_years++;
                    }
                }
                
                let analyse_mf = function(i = 0, type = "managed"){
                    
                    let tmp = tmp_data[type],
                        prev_mf = i == 0 ? data[type][i].mf_o : data[type][i-1].mf;     // previous mf

                    // close last analysis
                    if(i == period){
                        if(tmp.has_stat){
                            analysis[type].mf.push({ y: fiscal_year + tmp.avr_mf_start, dur:(period - tmp.avr_mf_start), avr: tmp.avr_mf, start_mf:tmp.avr_mf_value, end_mf: prev_mf }); 
                        }
                        return ;
                    }


                    let curr_mf = data[type][i].mf,                                                 // current mf
                        mf_inc = floatDecimals((curr_mf - prev_mf)*100/prev_mf, 2),      // increase compared to previous mf
                        mf_inc_var = floatDecimals(Math.abs(mf_inc - tmp.prev_mf_inc), 2),                  // variation of current inc and previous inc
                        mf_inc_var_avr = Math.round((tmp.avr_mf == 0 ? mf_inc_var : Math.abs(mf_inc_var / tmp.avr_mf))*100);          // how much does the variation compare to average calculated increase

                    tmp.prev_mf_inc = mf_inc;   // save current mf increase as previous mf increase

                    // console.log(fiscal_year+i, mf_inc, mf_inc_var, tmp.avr_mf, mf_inc_var_avr);
                                        
                                                                   
                        // spike
                        if(mf_inc_var_avr > avr_delta){
                            // console.log(type+' -> Year spike '+ (fiscal_year + i));
                            // console.log('   mf_m increase: '+ mf_inc);
                            // console.log('   mf_spike: '+ mf_inc_m_var);
                                                    
                            // close prev analysis
                            if(tmp.has_stat){
                                analysis[type].mf.push({y: fiscal_year + tmp.avr_mf_start , dur:(i - tmp.avr_mf_start), avr: floatDecimals(tmp.avr_mf, 2), start_mf:tmp.avr_mf_value, end_mf: prev_mf}); 
                            }

                            tmp.avr_mf_start = i;
                            tmp.avr_mf = mf_inc;
                            tmp.avr_mf_value = i == 0 ? curr_mf : prev_mf;

                            tmp.has_stat = true;

                        // flattened
                        }else{
                            if(tmp.avr_mf < 0)tmp.avr_mf = mf_inc;
                            else tmp.avr_mf = (tmp.avr_mf + mf_inc)/2;
                            // console.log('Year '+ (fiscal_year + i)+' -> ('+mf_inc_m+')'+avr_mf_m);
                        }
                    
                   
                }

                let accumulate = function(i = 0, type = "managed", key, asKey){
                    if(i == period)return;

                    let amount = Math.abs(parseFloat(objectGetDataFromPath(data[type][i], key) || 0));
                    analysis[type][(asKey || key)] += amount;
                    
                }



                for(var i=0; i<=period; i++){

                    accumulate_deficit(i, "managed");
                    analyse_mf(i, "managed");
                    accumulate(i, "managed", "loan_pi", "loan");
                    accumulate(i, "managed", "assess");
                    accumulate(i, "managed", "ne", "earning");


                    accumulate_deficit(i, "ltim", false);
                    analyse_mf(i, "ltim");
                    accumulate(i, "ltim", "loan_pi", "loan");
                    accumulate(i, "ltim", "assess");
                    accumulate(i, "ltim", "ne", "earning");
                    accumulate(i, "ltim", "ltim_acc_ne", "ltim_earning");
                    // accumulate(i, "ltim", "deficit/ltim_wth", "ltim_used");

                    
                }

                let format_all_deficit = function(){ 
                        
                    var c = "<p>You have ";

                    if(total_deficit == 0){
                        c += '<b class="w3-text-green">No Deficit</b>';
                    }else{

                        c += 'a total deficit of <b class="w3-text-red text-nowrap">'+formatMoney(total_deficit, 0)+'</b> ';
                        c += total_deficit_years == 1 ? 'in <b class="w3-text-red">One Year</b>.' : 'spread accross <b class="w3-text-red text-nowrap">'+total_deficit_years+' Years</b>.';
                    }

                    return c + '<p>';
                }

                let format_deficit = function(type = "managed"){
                    var deficit_remain = analysis[type]['deficit_remain'],
                        deficit_remain_years = analysis[type]['deficit_remain_years'],
                        
                        reduced_by = (total_deficit - deficit_remain)*100/total_deficit;   

                    if(reduced_by == 0)return '';
                        
                    var c = '<p>';

                    if(deficit_remain == 0){
                        c += 'You have managed to <b class="w3-text-green text-nowrap">Completely Clear</b> your deficit.';
                    }else{

                        c +=    'You have managed to reduce it to <b class="text-nowrap"><span class="w3-text-red">'+formatMoney(deficit_remain, 0)+ '</span>'+
                                ' (<i class="fa fa-arrow-down"></i>'+floatDecimals(reduced_by, 0)+'%)</b>.';
                        
                    }

                    return c + '<p>';
                }

                let format_mf = function(type = "managed"){
                    var mfs = analysis[type].mf;

                    var c = '<p>';

                    if(mfs.length == 0){
                        c += 'Your <b style="color:#bd941a">Monthly fees</b> remains at <b style="color:#bd941a">'+formatMoney(default_mf, 0)+'</b>';
                    }else{
                        var last_mf = mfs[mfs.length - 1];
                        var peak_mf = 0, peak_y = 0;

                        c = 'Your <b style="color:#bd941a">Monthly fees</b> have been changed <b style="color:#bd941a">'+(mfs.length)+' time'+(mfs.length>1?'s':'' )+'</b>, ' +
                            'reaching a final value of <b style="color:#bd941a">'+formatMoney(last_mf.end_mf, 0)+'</b> by the year <b>'+(last_mf.y)+'</b>';
                        
                            var c2 = [];

                        for(const mf of mfs){
                            var avr_inc = mf.avr,
                                year = mf.y,
                                duration = mf.dur, 
                                start_mf = mf.start_mf,
                                end_mf = mf.end_mf;

                            // console.log(year, avr_inc, duration, start_mf, end_mf);

                            if(avr_inc == 0)continue;

                            if(peak_mf < end_mf){
                                peak_mf = end_mf;
                                peak_y = year;
                            }
                            
                            var tmp = '<li>';

                            tmp += '<b style="color:#bd941a">' + (avr_inc > 0 ? 'Raised' : 'Lowered') + '</b>';

                            if(duration == 1){
                                tmp += ' in <b style="color:#bd941a">' + year + '</b> by <b style="color:#bd941a">' + formatNumber(avr_inc, 2)+'%</b>';
                            }else{
                                tmp += ' between <b style="color:#bd941a">' + year + '</b> and <b style="color:#bd941a">' + (year + duration - 1) + '</b> <b>('+duration+' years)</b> by an average of <b style="color:#bd941a">' + formatNumber(avr_inc, 2)+'% a Year</b>';
                            }

                            tmp += ', going from <b style="color:#bd941a">'+formatMoney(start_mf, 0)+'</b> to <b style="color:#bd941a">'+formatMoney(end_mf, 0)+'</b>';

                            tmp += '</li>';

                            c2.push(tmp);

                        }

                        if(last_mf.end_mf != peak_mf)
                            c += ' and reaching a maximum of <b style="color:#bd941a">'+formatMoney(peak_mf, 0)+'</b> in the year <b>'+peak_y+'</b>: <br>';

                        c += '<ul>' + c2.join('') + '</ul>';
                    }

                    return c ;
                }

                let format_loan = function(type = "managed"){
                    var loan = analysis[type].loan;

                    var c = '';
                    if(loan == 0){
                        c += '<p>You have not taken <b class="w3-text-indigo">Any Loan</b></p>';
                    }else{
                        c += '<p>You have taken a <b>Total Loan of <span class="w3-text-indigo">'+formatMoney(loan, 0)+'</span> (P+I)</b></p>';
                    }

                    return c ;
                }

                let format_assess = function(type = "managed"){
                    var assess = analysis[type].assess;

                    var c = '';
                    if(assess == 0){
                        c += '<p>You have not taken <b class="w3-text-green">Any Assessment</b></p>';
                    }else{
                        c += '<p>You have taken a <b>Total Assessment of <span class="w3-text-green">'+formatMoney(assess, 0)+'</span></b></p>';
                    }

                    return c ;
                    
                }

                let format_earning = function(type = "managed"){
                    var earning = analysis[type].earning;

                    if(earning == 0){
                        return '<p>You did not make <b class="w3-text-blue">Any Earnings</b> using <b>Your Investment Strategy</b>.</p>';
                    }else{
                        return '<p>You have earned a <b>Total of <span class="w3-text-blue">'+formatMoney(earning, 0)+'</spa></b> using <b>Your investment strategy</b>.</p>';
                    }
                    
                }

                let format_ltim = function(){
                    var ltim_earning = analysis["ltim"].ltim_earning;
                    var earning = analysis["ltim"].earning;

                    if(ltim_earning == 0){
                        return '<p>You did not make <b class="w3-text-blue">Any Earnings</b> using <b class="w3-text-purple">LTIM Investment Strategy</b>.</p>';
                    }else{
                        return '<p>You have earned a <b>Total of <span class="w3-text-purple">'+formatMoney(ltim_earning, 0)+'</spa></b> using <b class="w3-text-purple">LTIM investment strategy</b> '
                                +(earning > 0 ? ', Which make the total earnings from both investment strategies of <b  class="w3-text-blue">'+formatMoney(ltim_earning+earning, 0)+'.' : '.')+'</b></p>';
                    }
                    
                }


                var client_report_box = deficit_box.find('.simulation-report-client'),
                    ltim_report_box =   deficit_box.find('.simulation-report-ltim');


                // console.log(analysis);

                // console.log(format_all_deficit());
                // console.log('\n\n');

                var managed_analysis =  format_all_deficit()+
                                        format_deficit("managed")+
                                        format_mf('managed')+
                                        format_loan('managed')+
                                        format_assess('managed')+
                                        format_earning('managed');
                // console.log("Using Your Investment Strategy, ", managed_analysis);

                client_report_box.html(managed_analysis);
                // for(const mf_analysis of analysis.managed.mf){
                //     console.log(mf_analysis);
                // }


                
                // console.log('--------------------------------------')
                
                var ltim_analysis = format_all_deficit()+
                                    format_deficit("ltim")+
                                    format_mf('ltim')+
                                    format_loan('ltim')+
                                    format_assess('ltim')+
                                    format_earning('ltim')+
                                    format_ltim();
                // console.log("Using LTIM Investment Strategy", ltim_analysis);

                ltim_report_box.html(ltim_analysis);
                // for(const mf_analysis of analysis.ltim.mf){
                //     console.log(mf_analysis);
                // }
                


                

        }
        

        // print all final amounts
        var print_fa = function(){


                                fa_row.html('');

                                for(const c of context.simulation_data.calculated){
                                    var bg_color = !objEmpty(c.deficit) ? 'w3-deep-orange' : '';
                                    var has_data = '';

                                    fa_row.append(' <div class="col-3 p-1  border-bottom border-dark '+(c.year == at ? 'bg-primary' : bg_color + ' year link underline')+'" data-year="'+c.year+'"> \
                                                            '+(''+(parseInt(context.simulation_data.model.fiscal_year) + parseInt(c.year)) ) + /* ' <span class="w3-green">'+formatMoney(c.mf, 0)+'</span>' + has_data + */ '   \
                                                    </div>  \
                                                    <div class="col-5 p-1 border-bottom border-left border-dark text-right text-nowrap '+(c.fa >= 0 ? 'w3-text-black' : (c.ltim_acc > 0 && c.ltim_acc >= ( -1 * c.fa ) ? 'w3-text-blue' : 'w3-text-red'))+'">    \
                                                        '+formatMoney(c.fa, 0)+'    \
                                                    </div>  \
                                                    <div class="col-4 p-1 border-bottom border-dark text-right text-nowrap '+(c.fa_o >= 0 ? 'w3-green' : 'w3-red')+'">    \
                                                        '+formatMoney(c.fa_o, 0)+'    \
                                                    </div>');
                                }

                                fa_row.find('.year').click(function(){
                                    
                                            manageDeficitYear($(this).data('year'));

                                });



                        }



        // disable specific or all navs
        var disable_nav = function(tab){

            var navs = deficit_box.find('.simulation-tab-nav'),
                fa_overlay = $('.simulation-fa-overlay'),
                option_close = deficit_box.find('.simulation-options-close');
            

            if(!tab){navs.removeClass('invisible'); fa_overlay.addClass('d-none'); option_close.removeClass('invisible'); return;}

            navs.addClass('invisible');
            navs.filter("#"+tab+"-tab").removeClass('invisible');

            fa_overlay.removeClass('d-none');
            option_close.addClass('invisible');
            
        }
        disable_nav();

        
        // select specific nav
        var update_nav = function(reset){

            var active_item = deficit_options_nav.children('.active');
            if(active_item.hasClass('d-none') || reset)active_item = deficit_options_nav.children().not('.d-none').first();

            active_item.click();

            // console.log(active_item, active_item.html())

            $('#simulation_option_dropdown').html(active_item.html() || 'Choose');

        }

        var goto_nav = function(nav){
            if(!nav)return;

            var item = deficit_options_nav.find('#'+nav+'-tab');            
            if(!item.legth == 0 /* || item.hasClass('d-none') */)return;

            item.click();
        }

        // goto_nav('summary');
        
        // disable specific or all navs
        var color_nav = function(tab, toggle = true){

            var navs = deficit_box.find('.simulation-tab-nav');
            

            if(!tab){navs.removeClass('w3-text-deep-orange font-weight-bold'); return;}

            navs.filter('[id^="'+tab+'"]').toggleClass('w3-text-deep-orange font-weight-bold', toggle);

        }
        color_nav();
        
        // Modal            
        var deficit_chart = echarts.init(deficit_box.find('.model-info-deficit-chart')[0]);
        if(deficit_chart != null && deficit_chart != undefined){
            deficit_chart.resize();
        }

        
        var has_deficits = function(j = 0, prop){
            
            // j :  what year to start 
            // prop: only check given property

            var has_deficit = false;
                                                                    
            if(j < context.simulation_data.calculated.length){
                for( j; j<context.simulation_data.calculated.length; j++){

                    var d = context.simulation_data.calculated[j];
                    
                    if(d.deficit && ( (prop && d.deficit[prop]) || (!prop && !objEmpty(d.deficit)) ) ){
                        has_deficit = true; 
                        break;
                    }
                }
            }

            return has_deficit;
        }


        var getNextDeficit = function(startAt){
            if(startAt == undefined)startAt = at;
            startAt++;

            if(startAt >= context.simulation_data.calculated.length)return -1;
            
            for( var j = startAt; j<context.simulation_data.calculated.length; j++){

                var d = context.simulation_data.calculated[j];
                
                if(d.fa < 0 ){
                    return j;
                }
            }

            return -1;
        }



        var getPrevDeficit = function(startAt){
            if(startAt == undefined)startAt = at;
            startAt--;

            if(startAt <= 0)return -1;
            
            for( var j = startAt; j>0; j--){

                var d = context.simulation_data.calculated[j];
                
                if(d.fa < 0 ){
                    return j;
                }
            }

            return -1;
        }
        

        // Add LTIM Strategy list
        var available_ltim_select = deficit_box.find('[data-key="ltim_used"]'),
            used_ltim = context.simulation_data.simulation_rules.ltim_used || '';
            
        available_ltim_select.html('');
        for(const state in context.simulation_data.ltims || []){
            available_ltim_select.append('<option value="'+state+'" '+(used_ltim && used_ltim == state ? 'selected' : '')+'>'+ucwords(context.simulation_data.ltims[state])+'</option>');
        }

        // Show LTIM Buckets
        var buckets_tmpl = deficit_box.find('#ltim_show_buckets_template').html();
        var showBuckets = function(ltim_str, startYear, ltim_p, ltim_ne, ltim_acc_p, ltim_acc_ne){
            
            var modal = $(buckets_tmpl);
            var list = modal.filter('.simulation-buckets');
            var i = 1;

            var current_year = startYear;

            modal.find('.principal').html(formatMoney(ltim_p || 0, 0));
            modal.find('.net-earnings').html(formatMoney(ltim_ne || 0, 0));

            

            modal.find('.acc-principal').html(formatMoney(ltim_acc_p || 0, 0));
            modal.find('.acc-net-earnings').html(formatMoney(ltim_acc_ne || 0, 0));


            for(const b of ltim_str.buckets){
                var bucket_ratio = parseInt(b.ratio*100);
                var bucket_dur = parseInt(b.dur);
                var bucket_period = current_year + "-" + (current_year + bucket_dur) + 
                                    '<br> <span class="w3-text-green font-weight-bold h5">' + formatMoney(Math.round(b.ratio*ltim_p), 0) + "</span>" +
                                    '<br> <span class="w3-text-purple font-weight-bold h5">' + formatMoney(Math.round(b.ratio*ltim_acc_p), 0) + "</span>";

                var bucket = $('<div class="simulation-bucket-col"> \
                                    <div class="simulation-bucket-bar"> \
                                        <div class="simulation-bucket" style="height:'+bucket_ratio+'%;">   \
                                            <span class="simulation-bucket-ratio">'+formatNumber(b.ratio*100, 2)+'%</span>  \
                                        </div>    \
                                    </div>    \
                                    <div class="text-nowrap font-weight-bold">Bucket <br>'+bucket_period+'</div>    \
                                </div>');
                
                list.append(bucket);

                current_year += bucket_dur;
            }

            var modal = view_modal(modal, 'LTIM Buckets ' + startYear, null, true, 'xl', false);
            
            if(ltim_str.buckets.length > 5)modal.find('.modal-dialog').css('cssText', 'max-width: 95% !important; width: 95% !important;');
        }



        var cb = function(){
                               
                context.simulation(context.model_id, function(data){ 
                    
                                                            context.simulation_data = data;
                                                            print_fa();
                                                            context.print_summary();
                                                            eraseDeficit_init();
                                                            execfunc(callback);
                                                    });

                
        }

        
        
        var timelineCb = function(year, isMF = false){

                                var l = loading('', deficit_box.find('.simulation-options')),
                                    l2 = loading('', deficit_box.find('.simulation-fa'));

                                at = year;

                                // show simulation option modal
                                handle_options_modal(true, isMF ? 'monthly-fees' : '');

                                eraseDeficit_init();

                                setTimeout(function(){ 
                                                print_fa(); 
                                                l.remove(); 
                                                l2.remove();
                                            }, 100);
                        };

        context.timelineDeficit(null, timelineCb, true);

        var eraseDeficit_init = function(){

            print_fa();

            // generate_report();



            var fiscal_year = parseInt(context.simulation_data.model.fiscal_year);

            
            // Next and Previous Year
            var next_deficit = getNextDeficit(), prev_deficit = getPrevDeficit();
            deficit_box.find('.simulation-next-deficit').toggleClass('invisible', next_deficit == -1).unbind().click(function(){
                manageDeficitYear(next_deficit);
            });
            deficit_box.find('.simulation-prev-deficit').toggleClass('invisible', prev_deficit == -1).unbind().click(function(){
                manageDeficitYear(prev_deficit);
            });

            // NOTE: deficit timeline is updated at the bottm to maintain smooth animation
            
            //deficit_modal.title('<span class="h4">Erase Deficit <b>'+(at == 0 ? 'Current Year' : 'Year '+(at + 1))+'</b></span>');
            
            deficit_box.find('.deficit-title').html('Year '+ (fiscal_year + parseInt(at)));

            var original_data = context.simulation_data.calculated[at];
            var next_original_data = context.simulation_data.calculated[at + 1] || {};
            console.log(context);
            var period = context.simulation_data.calculated.length;

            var amount = original_data.fa,
                yearlyCollections = original_data.yc,
                units = context.simulation_data.model.housing,
                total_amount = original_data.ta,
                original_yearlyCollections = original_data.yc_o;

            if(units <= 0)units = 1;


            // Use Deficit Amount managed
            var deficit_data = !objEmpty(original_data.deficit) ? cloneObj(original_data.deficit) : {};
                if(!deficit_data)deficit_data = {};


            // update nav color to show which tab has deficit_data         
            color_nav();
            for(const d in deficit_data){
                
                // show which tab has deficit_data by exploding key with '_' and giving only
                // first word
                color_nav(d.split('_')[0]);
            }

                
            var general_rules = context.simulation_data.simulation_rules || {};

            var inv_strategy = !objEmpty(deficit_data) && deficit_data.inv_strategy ? deficit_data.inv_strategy : []; // original_data.is;
            var inv_existing_wthd = !objEmpty(deficit_data) && deficit_data.inv_existing_wthd ? deficit_data.inv_existing_wthd : [];
            var ltim_perc = !objEmpty(deficit_data) && deficit_data.ltim_perc ? parseFloat(deficit_data.ltim_perc) : (original_data.ltim_spl > 0 && general_rules['ltim_perc'] ? general_rules['ltim_perc'] :  0) ;
            var ltim_withdrawed = !objEmpty(deficit_data) && deficit_data.ltim_wth ? parseFloat(deficit_data.ltim_wth) : 0;
            var monthly_fees = !objEmpty(deficit_data) && deficit_data.monthly_fees ? parseFloat(deficit_data.monthly_fees) : 0;
            var monthly_fees_is_auto = !objEmpty(deficit_data) && deficit_data.monthly_fees_auto == 1;
            var bank_rate = !objEmpty(deficit_data) && deficit_data.bank_rate ? parseFloat(deficit_data.bank_rate) : parseFloat(context.simulation_data.model.bank_rate)/100.0;
            var loan_years = !objEmpty(deficit_data) && deficit_data.loan_years ? parseFloat(deficit_data.loan_years) : parseFloat(context.simulation_data.model.loan_years); if(loan_years < 1)loan_years = 1;
            var loan_amount = !objEmpty(deficit_data) && deficit_data.loan_amount? parseInt(deficit_data.loan_amount) : 0;                                
            var assessment = !objEmpty(deficit_data) && deficit_data.assessment ? parseInt(deficit_data.assessment) : 0;

            var ltim_auto_withraw = !objEmpty(deficit_data) && deficit_data.ltim_auto_wth == 1;
                delete deficit_data.ltim_auto_wth;


            var update_deficit_data = function(key, value, remove = false){

                if(!key){
                    deficit_data = {};
                    return;
                }

                if(remove){
                    tmp = {};
                    for(const k in deficit_data){
                        if(k == key)continue;
                        tmp[k] = deficit_data[k];
                    }
                    deficit_data = tmp;
                }else{
                    deficit_data[key] = value;
                }

            }

            // if(deficit_data.fa !=  undefined)amount = parseInt(deficit_data.fa);
            // if(deficit_data.ta !=  undefined)total_amount = parseInt(deficit_data.ta);



            var original_surplus_amount = amount > 0 ? amount : 0;
            var original_deficit_amount = Math.abs(amount <= 0 ? amount : 0);


            
            
            var inflation_rate = parseFloat(context.simulation_data.model.inflation_rate) / 100;

            // amount =  Math.abs(amount);

            // Deficit Data
            var deficit_amount = original_deficit_amount;
            var surplus_amount = original_surplus_amount;

            // Net Earning
            var original_net_earning_amount = original_data.ne;
            var net_earning_amount = original_data.ne;



            // reset navigation to default
            deficit_box.find('.simulation-tab-nav.togglable').toggleClass('invisible', false);



            // Initial data
        
            var loan_intrests = 0;

            // Monthly Fees


            // var calculate_loan_intrests = function(amount = 0, bank_rate = 0, loan_years = 0, intrestOnly = true){ 
                                                                                        

            //                                 var rate = floatDecimals(bank_rate / 12, 7);
            //                                     //rate = Math.round( rate, 7); 

            //                                 if(rate > 0){
                                    
            //                                     var months = loan_years * 12;
                                        
            //                                     var total = ( rate +  rate / (Math.pow( rate + 1,  months) - 1)) *  amount;
            //                                         total = floatDecimals(total, 0) * months;
                                                
            //                                 }else{
            //                                     total = amount;
            //                                 }
                                            
            //                                 loan_intrests =  Math.ceil(total - amount);

                                            
            //                                 if(intrestOnly)return loan_intrests;
            //                                 else return Math.ceil(total);
            // }
            
            var calculate_loan_intrests = function(amount = 0, bank_rate = 0, loan_years = 0, intrestOnly = true){ 
                var months = loan_years * 12;
                var rate = bank_rate / 12;
                var total = 0;
                var interest = 0;

                if(rate > 0 && months > 0){
                    // Standard amortization formula
                    var monthlyPayment = amount * rate * Math.pow(1 + rate, months) / (Math.pow(1 + rate, months) - 1);
                    total = monthlyPayment * months;
                    interest = total - amount;
                } else {
                    total = amount;
                    interest = 0;
                }

                if(intrestOnly) return Math.round(interest);
                else return Math.round(total);
            }


            // details
            var surplus_label = deficit_box.find('.surplus-amount');
            var deficit_label = deficit_box.find('.deficit-amount');

            var spendings_label = deficit_box.find('.spendings-amount');

            var starting_amount_label = deficit_box.find('.starting-amount');
            var client_inv_p_label = deficit_box.find('.inv-strategy-p');
            var net_earning_label = deficit_box.find('.inv-strategy-ne');
            var yearly_inc_label = deficit_box.find('.yearly-increase-amount');
            var pi_label = deficit_box.find('.pi-amount');
            var immediate_assessment_label = deficit_box.find('.immediate-assessment-amount');
            
            var yearly_pi_label = deficit_box.find('.yearly-pi-amount');
            var loan_remaining_label = deficit_box.find('.loan-remaining-amount');
            var loss_purchase_power_label = deficit_box.find('.lp-amount');
            var used_infl_rate_label = deficit_box.find('.infl_rate');
            var yearly_loan_interest_label = deficit_box.find('.loan_i');
            
            
            var remaining_deficit_amount_label = deficit_box.find('.remaining-deficit-amount');
            var remaining_surplus_amount_label = deficit_box.find('.remaining-surplus-amount');



            // show compare results
            deficit_box.find('.simulation-compare').unbind().click(function(){

                    //context.compare(context.model_id, function(resp){
                        
                            var compare_chart_e = $('<div class="p-2 m-2 simulation-compare-chart" style="width:90%; height:600px;"></div>');

                            view_modal(compare_chart_e, 'Compare Results', null, false, 'xl', false);

                            var compare_chart = echarts.init(compare_chart_e[0]);


                            var data = context.simulation_data.calculated || {};

                            var chart_series = { "collections":[], "deficit":[], "cash":[] };
                            var chart_xaxis = [];

                            for(var i=0; i<data.length; i++){

                                m = data[i];

                                // managed
                                chart_series.collections.push((m.yc).toFixed(2));
                                chart_series.deficit.push((m.fa < 0 ? m.fa : 0).toFixed(2));
                                chart_series.cash.push((m.sa > 0 ? m.sa : 0).toFixed(2));


                                chart_xaxis.push(parseInt(m.year) + 1);
            
                                
                            }
                            

                            var chart_initial_data = []     
                                                
                            chart_initial_data.push({ name: 'Collections', type: 'bar', barMaxWidth:15, /*stack: 'Collections',*/
                                                            itemStyle: {color: '#ffc107'}, emphasis: { focus: 'series' }, data: chart_series.collections_m });

                            chart_initial_data.push({ name: 'Deficit', type: 'bar', barMaxWidth:15, /*stack: 'Collections',*/
                                                            itemStyle: {color: '#ff0000'}, emphasis: { focus: 'series' }, data: chart_series.deficit_m });

                                                
                            chart_initial_data.push({ name: 'Cash Balance', type: 'line', barMaxWidth:15, /*stack: 'Collections',*/
                                                            itemStyle: {color: '#00ff00'}, emphasis: { focus: 'series' }, data: chart_series.cash_m });

                            
                            var option = {
                                title: { text: "Compare Results" }, tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, legend: {top: 50},
                                grid: { top: '20%', left: '5%', right: '5%', bottom: '0%', containLabel: true }, xAxis: [ { type: 'category', data: chart_xaxis} ],
                                yAxis: [ { type: 'value' } ], series: chart_initial_data,
                                                                        
                                dataZoom: [ { type: 'inside', start: 0 }, { start: 0 } ],
                                
                            };
                            

                            option && compare_chart.setOption(option);
                            
                            $(window).on('resize', function(){
                                if(compare_chart != null && compare_chart != undefined){
                                    compare_chart.resize();
                                }
                            });






                    //});

            });


            


            // next prev years
            deficit_box.find('.simulation-prev-year').unbind().click(function(){
                manageDeficitYear(at - 1);
            }).toggleClass('d-none', at <= 0);

            deficit_box.find('.simulation-next-year').unbind().click(function(){
                manageDeficitYear(at + 1);
            }).toggleClass('d-none', at >= period - 1);

            

            //if(original_deficit_amount <= 0)deficit_box.find('.model-info-deficit-chart').addClass('invisible')
            //console.log("This wll load w",original_data);
            deficit_chart.setOption({
                title: { text: '' }, tooltip: { trigger: 'item', position: 'inside', formatter: (p) => { return '<b>'+ucwords(p.seriesName, true)+' <br> <span class="w3-text-indigo">'+formatMoney(p.value, 0)+'</span></b>' } }, 
                legend: {left: 'center', top: 'bottom', type: 'scroll', orient: 'horizontal'}, 
                // grid: {left: '120px', width:100, top: '50px' },
                xAxis: { type: 'category', show: false},
                yAxis: { show: false },
                series: [                                                
                    { data: [original_data.ip], name:'Reserve Fund Cash', itemStyle: {color: '#521f4e'}, type: 'bar', stack: 'inv', showBackground: true },
                    // { data: [original_data.ne], name:'Project Bank Earnings', itemStyle: {color: '#e301d0'}, type: 'bar', stack: 'inv', showBackground: true },

                    { data: [original_data.ltim_p], name:'Allocated to LTIM ', itemStyle: {color: '#115814'}, type: 'bar', stack: 'inv', showBackground: true },
                    // { data: [original_data.ltim_ne], name:'Project Bank Earnings', itemStyle: {color: '#4CAF50'}, type: 'bar', stack: 'inv', showBackground: true },

                    { data: [original_data.ltim_acc], name:'Projected Acc LTIM Funds', itemStyle: {color: '#4CAF50'}, type: 'bar', showBackground: true },
                    { data: [deficit_amount], name:'Remaining Deficit', itemStyle: {color: '#fd4032'}, type: 'bar', showBackground: true }
                ]

            });


            

            deficit_chart_update = function(){
                return;

                /*
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
                */

                                        
            }

            var update_remaining_deficit = function(){
                
                                                var loan_remaining = 0;
                                                if(at > 0){
                                                    for(var i=at+1; i<period; i++)
                                                        loan_remaining -= context.simulation_data.calculated[i].loan_pay;
                                                }
                                                // console.log(original_data);
                                                starting_amount_label.html(formatMoney(original_data.sa, 0));
                                                
                                                surplus_label.html( formatMoney(original_surplus_amount, 0) );
                                                deficit_label.html( formatMoney(original_deficit_amount, 0) );
                                                
                                                spendings_label.html(formatMoney(-1 * original_data.sp, 0));
                                               
                                                client_inv_p_label.html(formatMoney(original_data.ip, 0));
                                                net_earning_label.html(formatMoney(original_data.ne, 0));

                                                yearly_inc_label.html(formatMoney(original_data.yc, 0)) ;
                                                pi_label.html(formatMoney(original_data.loan_t, 0)) ;
                                                immediate_assessment_label.html(formatMoney(original_data.assess, 0)) ;
                                                
                                                
                                                yearly_pi_label.html(formatMoney(-1 * original_data.loan_pay, 0)) ;
                                                loan_remaining_label.html(formatMoney(loan_remaining, 0)) ;
                                                loss_purchase_power_label.html(formatMoney(-1 * original_data.lp, 0)) ;
                                                
                                                // used_infl_rate_label.html(floatDecimals(inflation_rate*100, 2) + '%') ;
                                                used_infl_rate_label.html(floatDecimals((general_rules.inf_rate || 0) * 100, 2) + '%') ;
                                                yearly_loan_interest_label.html(formatMoney(original_data.loan_i || 0, 0));
                                                
                                                remaining_deficit_amount_label.html(formatMoney(original_deficit_amount, 0)) ;
                                                remaining_surplus_amount_label.html(formatMoney(original_surplus_amount, 0)) ;


                                               
                                                deficit_box.find('.deficit-almost-erased').toggleClass('d-none', !(original_deficit_amount > 0 && original_deficit_amount / deficit_amount < 0.1) );
                                                deficit_box.find('.deficit-erased').toggleClass('d-none', !(original_deficit_amount == 0));

                                                // LTIM
                                                //deficit_box.find('.ltim-option').toggleClass('d-none', !(surplus_amount > 0 || deficit_data['ltim']) );
                                                //if(!(surplus_amount > 0 || deficit_data['ltim']))ltim_input.val(0);
                                                // Adnan Saleem..........
                                                condition_ava_invest = 'condition_ava_invest';
                                                inv_strategy_edit(undefined, undefined, condition_ava_invest);
                                                // Adnan Saleem..........
                                            }



            // Simultaion General 
            var update_general_rules_values = function(){
                deficit_box.find('.simulation-rule-value').each(function(){
                    var item = $(this)
                        key = item.data('key'),
                        value = general_rules[key] || undefined,
                        decimals = item.hasClass('decimal') ? 2 : 0,
                        is_perc = item.hasClass('percent');

                    if(!key || value == undefined)return;

                    item.html(is_perc ? value * 100 + '%' : formatNumber(value, decimals));

                });
            }

            var update_general_rules = function(key, value, callback){ 
                                                if(typeof key == 'object'){ general_rules = key; } 
                                                else { general_rules[key] = value; } 
                                                
                                                context.updateRules(context.model_id, general_rules, 
                                                                        function(){ 
                                                                            
                                                                            execfunc(cb); 

                                                                            execfunc(callback);

                                                                            update_general_rules_values();
                                                                        });
            }
            
            deficit_box.find('.simulation-general-rules-btn').unbind().click(function(){
                // alert();
                manageDeficitYear(at);
                goto_nav('settings');
                
            });

            // console.log('general_rules');
            // console.log(general_rules);
            deficit_box.find('.simulation-rule').each(function(){
                var item = $(this)
                    console.log('item');

                    key = item.data('key'),
                    value = general_rules[key] || 0,
                    is_perc = item.hasClass('percent');
                
                if(!key)return;

                item.unbind();

                if(item.is(':checkbox')){
                    item.prop('checked', value == 1).change(function(){
                        var item = $(this)
                            key = item.data('key'),
                            value = item.is(':checked') ? 1 : 0;


                        update_general_rules(key, value);
                        
                    });
                }else{
                    item.val(is_perc ? floatDecimals(parseFloat(value * 100)): value).change(function(){
                        var item = $(this)
                            key = item.data('key'),
                            is_perc = item.hasClass('percent'), 
                            min = parseFloat(item.attr('min')),
                            max = parseFloat(item.attr('max'));
                                
                        if(min != undefined && this.value < min)this.value = min;
                        else if(max != undefined && this.value < min)this.value = max;

                        var value = item.val();
                        console.log(key);console.log(value);console.log(item);
                        if(item.attr('type') == 'number'){
                            value = parseFloat(value);
                            min = item.attr('min');
                            max = item.attr('max');

                            if(min != undefined && min > value)
                                value = min;
                            else if(max != undefined && max < value)
                                value = max;

                            if(is_perc)value /= 100;
                        }


                        update_general_rules(key, value);
                        
                    });
                }
            });
            
            // update rules in simulation page
            update_general_rules_values();
            

            
            // Boost With LTIM
            var ltim_boost_btn = deficit_box.find('.simulation-boost-ltim');

            
            // used to check if ltim is previously active to remove from deficit data
            var clear_ltim_enabled_state = function(current_state){
                var ltim_prev_active = -1;
                for(var i=at-1; i>=0; i--){
                    var prev_ltim = context.simulation_data.calculated[i].deficit.ltim_enabled;
                    if(prev_ltim !== undefined){
                        ltim_prev_active = prev_ltim; 
                        break;
                    }
                }

                if(current_state == 'state')return ltim_prev_active == 1;

                return  (at == 0 && !current_state) || 
                        (!current_state && ltim_prev_active == '0');
            }

            var ltim_enabled = (clear_ltim_enabled_state('state') && deficit_data.ltim_enabled == undefined) || (!objEmpty(deficit_data) && deficit_data.ltim_enabled == 1);


            ltim_boost_btn.unbind().prop('checked', ltim_enabled)
                                .change(function(){

                                        var checked = $(this).is(':checked');
                                        update_deficit_data('ltim_enabled', checked ? 1 : 0, clear_ltim_enabled_state(checked));
                                        apply_changes();
                                            

                                });


            // LTIM Tab 
            var ltim_tab_btn = deficit_box.find('#ltim-tab-activate');
            ltim_tab_btn.unbind().click(function(){
                
            });
            


            // LTIM Strategy
            var ltim_years_cash_label = deficit_box.find('.ltim-yoc'),
                ltim_compound_label = deficit_box.find('.ltim-ta'),
                ltim_surplus_label = deficit_box.find('.ltim-surplus'),
                ltim_startegy_label = deficit_box.find('.ltim-used-strategy'),
                ltim_acc_label = deficit_box.find('.ltim-acc'),
                ltim_p_label = deficit_box.find('.ltim-p'),
                ltim_p_perc_label = deficit_box.find('.ltim-p-perc'),
                ltim_ne_label = deficit_box.find('.ltim-ne'),
                
                ltim_amount_chk = deficit_box.find('.ltim-amount-chk'),
                ltim_amount_apply = deficit_box.find('.ltim-amount-apply'),

                ltim_perc_input = deficit_box.find('input.ltim-perc'),
                ltim_perc_apply = deficit_box.find('.ltim-perc-apply'),
                ltim_perc_reset = deficit_box.find('.ltim-perc-reset'),

                ltim_show_buckets = deficit_box.find('.ltim-show-buckets');

            ltim_years_cash_label.html(formatMoney(original_data.ltim_yoc || 0));
            ltim_compound_label.html(formatMoney(original_data.ta || 0));
            ltim_surplus_label.html(formatMoney(original_data.ltim_spl || 0));

            ltim_p_perc_label.html((ltim_perc * 100) + '%')
            ltim_startegy_label.html(ucwords(general_rules && general_rules.ltim_used && context.simulation_data.ltims[general_rules.ltim_used] ? context.simulation_data.ltims[general_rules.ltim_used] : ''));
            
            
            ltim_acc_label.html(formatMoney(original_data.ltim_acc, 0));
            ltim_p_label.html(formatMoney(original_data.ltim_p, 0));
            ltim_ne_label.html(formatMoney(original_data.ltim_ne, 0));
            

            
                ltim_perc_reset.unbind().click(function(){ 
                                                    update_deficit_data('ltim_perc', 0, true);
                                                    apply_changes();
                                                }).toggleClass('invisible', !deficit_data['ltim_perc']);
                

                // Use Percentage
                ltim_perc_apply.unbind().click(function(){           
                                                var value = Math.floor(ltim_perc_input.val()) / 100; if(value < 0)value = 0; else if(value > 1)value = 1; 
                                                
                                                
                                                if(original_data.ltim_spl <= 0){
                                                    error('You have no Surplus', '', default_error_alert_timeout);
                                                    return;
                                                }


                                                if(ltim_perc > value && has_deficits(at, 'ltim_perc')){
                                                    confirm('Lowering the Amount Allocated to the LTIM Strategy will <b>Reset All LTIM withdraws Starting Year <span class="w3-text-red"> '+(fiscal_year + parseInt(at) + 1)+'</span></b>, Are you sure you want to apply ?', 'Warning', null, 
                                                    function(action){ 
                                                                                    if(action != 'ok')return;
            
                                                                                    update_deficit_data('ltim_perc', value, value == 0);
                                                                                    // update_deficit_data('ltim_amount_chk', ltim_amount_chk.is(':checked') ? 1 : 0, value == 0);
                                                                                
                                                                                    apply_changes();
                                                                            });
                                                    return;
                                                }else{
                                                    
                                                    update_deficit_data('ltim_perc', value, value == 0);
                                                    // update_deficit_data('ltim_amount_chk', ltim_amount_chk.is(':checked') ? 1 : 0, value == 0);
                                                
                                                    apply_changes();
                                                }


                                        }).addClass('invisible'); 

                ltim_perc_input.unbind().on('input', function(){   
                                                                
                                                                var value = Math.floor(this.value) / 100; if(value < 0)value = 0; else if(value > 1){ value = 1; this.value = 100; }


                                                                // var ratio = original_data.ltim_spl > 0 ? Math.ceil(value / original_data.ltim_spl) : 0;
                                                                // ltim_amount_slider.slider('value', ratio * 100);   
                                                                
                                                                var has_changed = value != $(this).data('old');
                                                                ltim_perc_apply.toggleClass('invisible', !has_changed);  

                                                                deficit_box.find('.ltim-amount-chk-txt').toggleClass('invisible', value <= 0);  

                                                                
                                                                disable_nav(has_changed ? 'ltim' : '' );                       
                                                                
                                                            }).data('old', ltim_perc)
                                            .keypress(function(e){ if(e.key == "Enter"){ ltim_perc_apply.click(); }})
                                            .val(ltim_perc * 100);

                                            
                ltim_amount_chk.unbind().change(function(){ltim_perc_apply.click()});

                                                        
                // show buckets
                ltim_show_buckets/*.toggleClass('invisible', original_data.ltim_p <= 0 && original_data.ltim_acc_p <= 0)*/.unbind().click(function(){

                            showBuckets(original_data.ltim_str, fiscal_year + parseInt(at), 
                                        original_data.ltim_p, original_data.ltim_ne, original_data.ltim_acc_p, original_data.ltim_acc_ne);

                });



                // show options
                // deficit_box.find('.ltim-amount-chk-txt').toggleClass('invisible', ltim_amount <= 0); 
                deficit_box.find('.ltim-amount-chk-txt').toggleClass('invisible', ltim_perc <= 0); 
                deficit_box.find('.ltim-off-warning').toggleClass('d-none', ltim_enabled);
                deficit_box.find('.ltim-amount-disable').parent().toggleClass('d-none', !ltim_enabled);
                deficit_box.find('.ltim-amount-disable').toggleClass('d-none', !(!original_data.ltim_spl || original_data.ltim_spl <= 0));


            // LTIM Withdraw
            var ltim_acc_label = deficit_box.find('.ltim-acc'),    
                ltim_acc_p_label = deficit_box.find('.ltim-acc-p'),
                ltim_acc_ne_label = deficit_box.find('.ltim-acc-ne'),                

                ltim_withdraw_slider = deficit_box.find('.ltim-withdraw-slider'),
                ltim_withdraw_input = deficit_box.find('input.ltim-withdraw'),
                ltim_withdraw_apply = deficit_box.find('.ltim-withdraw-apply'),
                ltim_withdraw_clear = deficit_box.find('.ltim-withdraw-clear'),
                ltim_withdraw_reset = deficit_box.find('.ltim-withdraw-reset'),
                
                ltim_wth_label = deficit_box.find('.ltim-wth'),
                ltim_next_acc_label = deficit_box.find('.ltim-next-acc');

                ltim_acc_label.html(formatMoney(original_data.ltim_acc, 0));
                ltim_acc_p_label.html(formatMoney(original_data.ltim_acc_p, 0));
                ltim_acc_ne_label.html(formatMoney(original_data.ltim_acc_ne, 0));

                ltim_withdraw_input.html(formatMoney(ltim_withdrawed, 0));
                ltim_wth_label.html(formatMoney(ltim_withdrawed, 0));

                ltim_next_acc_label.html(formatMoney(next_original_data.ltim_acc_p, 0));
                deficit_box.find('.ltim-next-acc-txt').toggleClass('invisible', !ltim_withdrawed);

            
                ltim_withdraw_reset.unbind().click(function(){ 
                                                    update_deficit_data('ltim_wth', 0, true);
                                                    apply_changes();
                                                }).toggleClass('invisible', deficit_data['ltim_wth'] == undefined || ltim_auto_withraw);


                                                                                               

                ltim_withdraw_clear.unbind().click(function(){  
                                                    //var remaining_to_cover = Math.floor((calculate_remaining_deficit('mf')) * 100 / yearlyCollections )) + 1;
                                                    // console.log(original_data.ltim_acc > deficit_amount ? deficit_amount : original_data.ltim_acc)                                                    
                                                    update_deficit_data('ltim_wth', original_data.ltim_acc > deficit_amount ? deficit_amount : original_data.ltim_acc);
                                                    apply_changes();

                                        }).toggleClass('invisible', original_data.ltim_acc <= 0 || general_rules.ltim_cover == 1);

                ltim_withdraw_apply.unbind().click(function(){           
                                                var value = Math.ceil(ltim_withdraw_input.val()); if(value < 0)value = 0;
                                                
                                                if(!original_data.ltim_acc || original_data.ltim_acc <= 0){
                                                    error('You have no money in LTIM', '', default_error_alert_timeout);
                                                    return;
                                                }
                                                if(Math.ceil(ltim_withdrawed) < value && has_deficits(at, 'ltim_perc')){
                                                    confirm('Raising the Amount to Withdraw from the LTIM Funds will <b>Reset All LTIM withdraws Starting Year <span class="w3-text-red"> '+(fiscal_year + parseInt(at) + 1)+'</span></b>, Are you sure you want to apply ?', 'Warning', null, 
                                                    function(action){ 
                                                                        if(action != 'ok')return;
                                                                        
                                                                        update_deficit_data('ltim_wth', Math.ceil(value));                                            
                                                                        apply_changes();

                                                                });
                                                    return;
                                                }else{                    

                                                    update_deficit_data('ltim_wth', Math.ceil(value));                                            
                                                    apply_changes();
                                                }

                                        }).addClass('invisible'); 

                ltim_withdraw_update_value = function(value){
                                                
                                                
                                                if(!value)value = Math.ceil(ltim_withdraw_slider.slider('value'));
                                                else if(value > 100)value = 100;
                                                else if(isNaN(value) || value < 0)value = 0;

                                                ltim_withdraw_input.val(Math.ceil(value * original_data.ltim_acc / 100));

                                                ltim_withdraw_apply.click();
                                    }

                                    
                ltim_withdraw_slider.slider('value', 0).slider('value', Math.floor(ltim_withdrawed * 100 / original_data.ltim_acc));
                ltim_withdraw_input.unbind().on('input', function(){   
                                                                
                                                                if(this.value > original_data.ltim_acc){
                                                                    this.value = Math.floor(original_data.ltim_acc);                                                                    
                                                                }   
                                                                
                                                                var value = Math.ceil(this.value); if(value < 0)value = 0;


                                                                // var ratio = original_data.ltim_spl > 0 ? Math.ceil(value / original_data.ltim_spl) : 0;
                                                                // ltim_amount_slider.slider('value', ratio * 100);   
                                                                
                                                                var has_changed = value != $(this).data('old');
                                                                ltim_withdraw_apply.toggleClass('invisible', !has_changed);  
                                                                
                                                                disable_nav(has_changed ? 'ltim' : '' );                       
                                                                
                                                            }).data('old', Math.ceil(ltim_withdrawed))
                                            .keypress(function(e){ if(e.key == "Enter"){ ltim_withdraw_apply.click(); }})
                                            .val(floatDecimals(ltim_withdrawed, 0));

                deficit_box.find('.ltim-withdraw-disable').toggleClass('d-none', !(!original_data.ltim_acc || original_data.ltim_acc <= 0));


            // Loan Options
            var loan_slider = deficit_box.find('.loan-slider'),
                //loan_deficit_amount_label = deficit_box.find('span.deficit-loan-remaining'),
                loan_amount_input = deficit_box.find('input.loan-amount'),
                loan_total_label = deficit_box.find('span.loan-total'),
                loan_intrests_label = deficit_box.find('span.loan-intrests'),
                bank_rate_input = deficit_box.find('[data-key="bank_rate"]'),
                loan_years_input = deficit_box.find('[data-key="loan_years"]'),
                
                loan_reset = deficit_box.find('.loan-reset'),
                loan_clear_btn = deficit_box.find('.loan-clear-btn'),
                loan_apply = deficit_box.find('.loan-apply');

                
                loan_amount_input.val(loan_amount, 0);
                bank_rate_input.val( bank_rate * 100).data('old', bank_rate * 100).on('input', function(){ if(this.value < 0)this.value = 0; update_loan_intrest();  /* deficit_data['bank_rate'] = this.value; loan_update_value(); */ });
                loan_years_input.val( loan_years).data('old', loan_years).on('input', function(){ if(this.value < 0)this.value = 0; update_loan_intrest(); /* deficit_data['loan_years'] = this.value; loan_update_value(); */  });;
                
                
                loan_apply.unbind().click(function(){           
                                                var value = Math.ceil(loan_amount_input.val()); if(value < 0)value = 0;          

                                                update_deficit_data('loan_amount', Math.ceil(value), value == 0);
                                                update_deficit_data('bank_rate', bank_rate_input.val() / 100, value == 0);
                                                update_deficit_data('loan_years', Math.ceil(loan_years_input.val()), value == 0);
                                            
                                                apply_changes();

                                        }).addClass('d-none'); 
                                        
                loan_reset.unbind().click(function(){ 
                                        
                                        update_deficit_data('loan_amount', 0, true);
                                        update_deficit_data('bank_rate', 0, true);
                                        update_deficit_data('loan_years', 0, true);
                                        apply_changes();

                                    }).toggleClass('d-none', !deficit_data['loan_amount']);
                                    
                loan_clear_btn.unbind().click(function(){           
                    
                                                if(deficit_amount <= 0)return;

                                                update_deficit_data('loan_amount', deficit_amount, deficit_amount <= 0);
                                                update_deficit_data('bank_rate', bank_rate_input.val() / 100, deficit_amount <= 0);
                                                update_deficit_data('loan_years', Math.ceil(loan_years_input.val()), deficit_amount <= 0);
                                            
                                                apply_changes();

                                        }).addClass('d-none'); 


                var update_loan_intrest = function(){

                        var value = Math.ceil(loan_amount_input.val()); if(value < 0)value = 0;
                                                                        
                        loan_total_label.html(formatMoney(calculate_loan_intrests(value, bank_rate_input.val() / 100, Math.ceil(loan_years_input.val()), false), 0));
                        loan_intrests_label.html(formatMoney(calculate_loan_intrests(value, bank_rate_input.val() / 100, Math.ceil(loan_years_input.val())), 0));

                        loan_apply.toggleClass('d-none',    ( loan_amount_input.data('old') == loan_amount_input.val() && 
                                                              bank_rate_input.data('old') == bank_rate_input.val() &&
                                                              loan_years_input.data('old') == loan_years_input.val() ) ||
                                                            
                                                            ( loan_amount_input.data('old') == loan_amount_input.val() && 
                                                              loan_amount_input.val() == 0 ));
                                                            
                        loan_clear_btn.toggleClass('d-none', (deficit_amount <= 0 || (deficit_amount > 0 && deficit_amount == value)));
                        
                        disable_nav(value != loan_amount ? 'loan' : '' );   
                }

                loan_update_value = function(value){
                                                
                                                if(!value)value = Math.ceil(loan_slider.slider('value'));
                                                else if(value > 100)value = 100;
                                                else if(isNaN(value) || value < 0)value = 0;

                                                loan_apply.click();
                                    }

                loan_amount_input.unbind().on('input', function(){     
                                                                                                
                                                                        update_loan_intrest();
                                                                        
                                                                    })
                                            .data('old', loan_amount)
                                            .keypress(function(e){ if(e.key == "Enter"){  loan_update_value(this.value < 0 ? 0 : Math.ceil(this.value)); }});
                    
                update_loan_intrest();
                

            // Monthly Fees Options
            var monthly_fees_slider = deficit_box.find('.monthly-fees-slider'),
                monthly_fees_label = deficit_box.find('input.monthly-fees'),
                monthly_fees_reset_btn = deficit_box.find('.monthly-fees-reset-btn'),
                monthly_fees_clear_btn = deficit_box.find('.monthly-fees-clear-btn'),
                monthly_fees_future_apply = deficit_box.find('.monthly-fees-future'),
                monthly_fees_apply = deficit_box.find('.monthly-fees-apply'),
                monthly_fees_auto_box = deficit_box.find('.monthly-fees-auto-box'),
                monthly_fees_auto_val = deficit_box.find('.monthly-fees-auto-val'),
                monthly_fees_auto_switch = deficit_box.find('.monthly-fees-auto'),
                default_monthly_fees = parseFloat(original_data.mf_o);
                
                deficit_box.find('span.monthly-fees-old').html(formatMoney(default_monthly_fees, 0)); // init current monthly fees

                // only make it editable when Auto-fees is off
                // monthly_fees_label.attr('disabled', general_rules && general_rules.mf_auto == 1);
                // monthly_fees_auto.toggleClass('d-none', !general_rules || (general_rules && general_rules.mf_auto == 0));
                monthly_fees_auto_val.html(formatMoney(original_data.mf, 0));
               
                monthly_fees_auto_box.unbind().click(function(e){

                                                if(default_monthly_fees > 0)return;
                                                
                                                error('<b>Original Monthly Fees</b> must be atleast <b>$1</b> in order to use <b>Optimal Monthly Fees<b>');

                                                e.preventDefault();
                                                e.stopPropagation();

                                            });
                monthly_fees_auto_switch.prop('checked', monthly_fees_is_auto).unbind().change(function(){ 
                                                                                    var checked = $(this).is(':checked');
                                                                                    update_deficit_data('monthly_fees_auto', checked ? 1 : 0, !checked);
                                                                                    apply_changes(); 
                                                                                });
                
               
                monthly_fees_slider.data('min', 1);
                monthly_fees_slider.data('max', default_monthly_fees < 1000 ? 1000 : default_monthly_fees * 2 );
                monthly_fees_label.val(Math.ceil(monthly_fees));  // init new monthly fees
                if(monthly_fees_label.val() < 1)monthly_fees_label.val('');
                monthly_fees_future_apply.val(0).attr('max', parseInt(period) - parseInt(at)).on('input change', function(){ var remaining_years = parseInt(period) - parseInt(at); if(this.value > remaining_years)this.value = remaining_years; });
                
                monthly_fees_slider.slider('value', Math.ceil(monthly_fees));

                
                monthly_fees_apply.unbind().click(function(){ 
                    
                                                        monthly_fee_increase_update_value(monthly_fees_label.val() < 1 ? 1 : Math.ceil(monthly_fees_label.val()));

                                                    }).addClass('d-none');                                                    

                monthly_fees_clear_btn.unbind().click(function(){  
                                                    //var remaining_to_cover = Math.floor((calculate_remaining_deficit('mf')) * 100 / yearlyCollections )) + 1;
                                                    var remaining_to_cover = Math.round(monthly_fees + (deficit_amount / (12 * units)) )  ;
                                                    update_deficit_data('monthly_fees', remaining_to_cover);
                                                    apply_changes();

                                        }).toggleClass('d-none', deficit_amount <= 0);
                                        
                monthly_fees_reset_btn.unbind().click(function(){ 
                                                    update_deficit_data('monthly_fees', 0, true);
                                                    apply_changes();
                                                }).toggleClass('d-none', !deficit_data['monthly_fees']);
                                                                    
                monthly_fee_increase_update_value = function(value){
                                                           
                                                            if(value != undefined){
                                                                update_deficit_data('monthly_fees', Math.ceil(value), Math.ceil(value) == 0 /* Math.ceil(value) == default_monthly_fees */);
                                                                var apply_for_next = parseInt(monthly_fees_future_apply.val());
                                                                if(apply_for_next > 0)
                                                                update_deficit_data('monthly_fees_future', apply_for_next);
                                                                apply_changes();
                                                            }
                                                                
                                                            return;

                                    }

            

                monthly_fees_label.unbind().on('input', function(){      
                                                                var value = Math.ceil(this.value); if(value < 1)value = 1;
                                                                monthly_fees_slider.slider('value', value);   
                                                                
                                                                var has_changed = value != $(this).data('old');
                                                                monthly_fees_apply.toggleClass('d-none', !has_changed);   
                                                                
                                                                disable_nav(has_changed ? 'monthly-fees' : '' );                       
                                                                
                                                            }).data('old', Math.ceil(monthly_fees))
                                            .keypress(function(e){ if(e.key == "Enter"){ monthly_fee_increase_update_value(this.value < 1 ? 1 : Math.ceil(this.value)); }});;

            // Monthly Fees Increase Prev Years
            var monthly_fees_year_label = deficit_box.find('input.monthly-fees-year'),
                monthly_fees_year_from = deficit_box.find('.monthly-fees-year-from'),
                monthly_fees_year_by = deficit_box.find('.monthly-fees-year-by'),
                monthly_fees_warning = deficit_box.find('.monthly-fees-warning'),
                monthly_fees_year_apply = deficit_box.find('.monthly-fees-year-apply'),
                monthly_fees_year_reset = deficit_box.find('.monthly-fees-year-reset-btn'),
                monthly_fees_year_box = deficit_box.find('.monthly-fees-year-box');
                
                monthly_fees_year_label.attr('max', at);


                monthly_fees_year_from.html(deficit_data['monthly_fees_year_from'] || 0);
                monthly_fees_year_by.html(formatMoney(deficit_data['monthly_fees_year'] || 0, 0));
                
                var monthly_fees_warning_update = function(inc_by){
                    
                    var is_over_mf_inc_limit = (inc_by / original_data.mf_o > 0.15);
                    
                    monthly_fees_warning.toggleClass('invisible', !is_over_mf_inc_limit);
                    

                }

                // first warning update
                monthly_fees_warning_update(deficit_data['monthly_fees_year']);
                
                if(deficit_data['monthly_fees_year'] || (deficit_amount > 0 && at > 0))
                    monthly_fees_year_box.removeClass('invisible');
                else 
                    monthly_fees_year_box.addClass('invisible');

                monthly_fees_year_reset.unbind().click(function(){ 
                                                    update_deficit_data('monthly_fees_year', 0, true);
                                                    update_deficit_data('monthly_fees_year_from', 0, true);
                                                    apply_changes();
                                                }).toggleClass('d-none', !deficit_data['monthly_fees_year']);
                
                monthly_fees_year_apply.unbind().click(function(){ 
                    
                                                var value = Math.round(monthly_fees_year_label.val()); if(value < 0)value = 0;
                                                if(value > at)return;          

                                                var inc_by = 0;
                                                if(value > 0){
                                                    inc_by = Math.round((deficit_data['monthly_fees_year_fa'] || deficit_amount) / ( (at - value + 2) * units * 12));
                                                    if(isNaN(inc_by) || inc_by < 0)inc_by = 0;
                                                }    
                                                
                                                update_deficit_data('monthly_fees_year', inc_by, inc_by == 0);
                                                update_deficit_data('monthly_fees_year_from', value, inc_by == 0);
                                                update_deficit_data('monthly_fees_year_fa', deficit_amount, inc_by == 0);
                                                
                                                apply_changes();

                }).addClass('invisible'); 

                monthly_fees_year_label.unbind().val(deficit_data['monthly_fees_year_from'] || 0).on('input', function(){      

                                                                if(this.value < 0)this.value = 0;
                                                                else if(this.value > at)this.value = at;

                                                                var value = parseInt(this.value); 
                                                                                                                                
                                                                var has_changed = value != $(this).data('old');
                                                                monthly_fees_year_apply.toggleClass('invisible', !has_changed);   

                                                                var inc_by = 0;
                                                                if(value > 0){                                                                    
                                                                    inc_by = Math.round((deficit_data['monthly_fees_year_fa'] || deficit_amount) / ( (at - value + 2) * units * 12));
                                                                    if(isNaN(inc_by) || inc_by < 0)inc_by = 0;
                                                                }

                                                                monthly_fees_year_from.html(value);

                                                                monthly_fees_year_by.html(formatMoney(inc_by, 0));
                                                            
                                                                monthly_fees_warning_update(inc_by);

                                                                
                                                                disable_nav(has_changed ? 'monthly-fees' : '' );                       
                                                                
                                                            }).data('old', deficit_data['monthly_fees_year_from'] || 0)
                                            .keypress(function(e){  if(e.key == "Enter"){ monthly_fees_year_apply.click(); } });
                                            
                                            // Adnan Saleem new........
                                            const checkbox = document.querySelector('.monthly-fees-auto');
                                              // Check if checkbox is checked
                                            
                                            // if (checkbox.checked) {
                                            //     // year = (original_data.year || 1) - 1,
                                            //     mf_inc = (original_data.mf - original_data.mf_o)*100/original_data.mf_o;
                                            //     // content = '<ul><li><b>Updated Monthly Fee: </b>'+formatMoney(original_data.mf)+'</li><li><b>Updated Percent Increase: </b>'+formatNumber(mf_inc, 1)+'%</li><li><b>Updated Amount Increase (New fee - Old fee): </b>'+formatMoney(original_data.mf - original_data.mf_o)+'</li>';
                                            //     content = '<ul><li><b>Updated Monthly Fee: </b>'+formatMoney(original_data.mf)+'</li><li><b>Previous Fee: </b>'+formatMoney(original_data.mf_o)+'</li><li><b>Updated Amount Increase (New fee - Old fee): </b>'+formatMoney(original_data.mf - original_data.mf_o)+'</li>';
                                            //     title = "Updated fee after applying 'Optimize Monthy Fee'";
                                            //     info(content, title);
                                            // }
                                            // Adnan Saleem new........


            // Immediate Assessment
            var assessment_input = deficit_box.find('input.assessment-amount'),
                assessment_total_label = deficit_box.find('span.assessment-total'),
                assessment_num_units_label = deficit_box.find('span.assessment-num-units'),
                assessment_unit_input = deficit_box.find('input.assessment-unit'),
                assessment_reset = deficit_box.find('.assessment-reset'),
                assessment_apply = deficit_box.find('.assessment-apply');
                // Adnan Saleem..........
                assessment_clear_btn = deficit_box.find('.assessment-clear-btn');
                // Adnan Saleem..........
                assessment_num_units_label.html(units);

                assessment_apply.unbind().click(function(){
                                                    update_deficit_data('assessment', assessment_input.val(), !parseInt(assessment_input.val()));
                                                    apply_changes();
                                                }).addClass('d-none');

                    
                                        
                assessment_reset.unbind().click(function(){ 
                                        
                                        update_deficit_data('assessment', 0, true);
                                        apply_changes();

                                    }).toggleClass('d-none', !deficit_data['assessment']);     
                // Adnan Saleem..........                    
                assessment_clear_btn.unbind().click(function(){           
                    
                                        if(deficit_amount <= 0)return;

                                        update_deficit_data('assessment', deficit_amount, deficit_amount <= 0);
                                    
                                        apply_changes();

                                }).addClass('d-none');
                // Adnan Saleem..........
            var assessment_update_value = function(value = 0, which = 'total'){

                                                    //var remaining = calculate_remaining_deficit('assessment');

                                                    //if(value > remaining)value = remaining;
                                                    /* else */ if(value < 0){value = 0; }
                                                    
                                                    assessment_total_label.html(formatMoney(value, 0));
                                                    
                                                    switch(which){
                                                        case 'total': assessment_unit_input.val(parseInt(value/units)); break;
                                                        case 'unit':  assessment_input.val(value); break;
                                                    }
                                                // Adnan Saleem..........
                                                assessment_clear_btn.toggleClass('d-none', (deficit_amount <= 0 || (deficit_amount > 0 && deficit_amount == value)));
                                                // Adnan Saleem..........
                                            }

                assessment_input.val(deficit_data["assessment"] || 0).unbind().on('input', function(e){
                    
                                                                            if(this.value < 0)this.value = 0;
                                                                            var value = Math.ceil(this.value);

                                                                            var has_changed = value != $(this).data('old');
                                                                            assessment_apply.toggleClass('d-none', !has_changed);                                                                            
                                                                            assessment_update_value(value, 'total');
                                                                            disable_nav(has_changed ? 'assess' : '' );
                                                                            
                                                                        }).data('old', deficit_data["assessment"] || 0)
                                                                          .keypress(function(e){ if(e.key == "Enter"){ assessment_apply.click(); }});

                                                                          

                assessment_unit_input.val(parseInt((deficit_data["assessment"] || 0)/units)).unbind().on('input', function(e){
                    
                                                                            if(this.value < 0)this.value = 0;
                                                                            var value = Math.ceil(this.value);

                                                                            var has_changed = value != $(this).data('old');
                                                                            assessment_apply.toggleClass('d-none', !has_changed);                                                                            
                                                                            assessment_update_value(value*units, 'unit');
                                                                            disable_nav(has_changed ? 'assess' : '' );
                                                                            
                                                                        }).data('old', parseInt((deficit_data["assessment"] || 0)/units))
                                                                          .keypress(function(e){ if(e.key == "Enter"){ assessment_apply.click(); }});
                                                                    
                assessment_update_value(deficit_data["assessment"] || 0);
            

            // Spendings
            var spendings = context.simulation_data.spendings[at],         // data before editing
                spendings_table = deficit_box.find('.spendings-table'),
                spendings_count = deficit_box.find('.spendings-count'),                
                spendings_edit = [];    // edited data
                spendings_final_edit = [];    // final edited data
                spendings_unsplit = []; // unsplitted items
                spendings_unsplit_years = {}; // unsplitted items years

            var spending_init = function(){
                spendings = context.simulation_data.spendings[at];         // data before editing
                spendings_edit = [];    // edited data
                spendings_final_edit = [];    // final edited data
                spendings_unsplit = []; // unsplitted items
                spendings_unsplit_years = {}; // unsplitted items years
                
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

           
            var spending_has_splits = function(item){
                                            if(!item || !item.org)return false;                                        
                                            return spendings_edit.findIndex((obj)=>{return obj.splitId == item.id}) >= 0;
                                        }
            
            var spendings_check = function(withAlert = false){
                        
                        spendings_final_edit = [];

                        // init all errors
                        $('.spendings-error').addClass('d-none');
                        spendings_table.find('.split-error').addClass('d-none');
                        spendings_table.find('.app-input-error').removeClass('app-input-error');

                        var has_error = false;

                        for(var i=0; i<spendings_edit.length; i++){
                    
                            var s = spendings_edit[i];
                            if(!s.org){
                                var similar = spendings_edit.findIndex((obj)=>{ return obj.splitId == s.splitId && obj.year == s.year});
                                var split_of_index = spendings_edit.findIndex((obj)=>{ return obj.id == s.splitId }); // item from which it's splitted
                                var split_of = split_of_index >= 0 ? spendings_edit[split_of_index] : null;
                                
                                // if(s.is_sirs == 1){
                                //     spendings_table.find('.app-input.year[data-index="'+i+'"]').addClass('app-input-error');
                                //     $('.spendings-error').removeClass('d-none');

                                //     has_error = true;
                                //     continue;
                                // }
                                    // has splits with same year  OR   has same year as original item
                                if((similar >= 0 && similar != i) || (split_of && split_of.year == s.year )){
                                    spendings_table.find('.split-error[data-index="'+split_of_index+'"]').removeClass('d-none');
                                    spendings_table.find('.app-input.year[data-index="'+i+'"]').addClass('app-input-error');
                                    spendings_table.find('.app-input.year[data-index="'+similar+'"]').addClass('app-input-error');

                                    // when same as originam
                                    if((split_of && split_of.year == s.year ))
                                        spendings_table.find('.app-input.year[data-index="'+split_of_index+'"]').addClass('app-input-error');

                                    $('.spendings-error').removeClass('d-none');

                                    has_error = true;
                                    continue;
                                }

                                /*
                                if(similar >= 0 && similar != i){
                                    if(withAlert)error('<br>Splits of this item must have different <b>Years</b> : <br><br><b>'+ucwords(s.name)+'</b><br> Error at Year <b>'+(fiscal_year + s.year)+'</b> ', 'Splitting');
                                    return false;
                                }else if(split_of && split_of.year == s.year ){
                                    if(withAlert)error('<br>Splits of this item must have different <b>Years</b> : <br><br><b>'+ucwords(s.name)+'</b><br> Year <b>'+(fiscal_year + s.year)+'</b> ', 'Splitting');
                                    return false;
                                }
                                */
        
                                // if the item from which is has been splitted is already a split
                                // set it's split_of id instead
                                //if(split_of && split_of.split_of)s.split_of = split_of.split_of;

                                spendings_final_edit.push(s);

                            }else if(s.edited){
                                spendings_final_edit.push(s);
                            }
        
                            
                        }

                        if(has_error)return false;

                        var ok = (spendings_final_edit.length > 0 || spendings_unsplit.length > 0);
                                        
                        //deficit_box.find('.simulation-tab-nav.togglable').toggleClass('invisible', ok);  
                        
                        // Adnan saleem.....
                        document.querySelectorAll('.app-input').forEach((input) => {
                            input.addEventListener('change', function () {
                                // Get the current value of data-index
                                
                                const dataIndex = this.getAttribute('data-index');
                                s = spendings_edit[dataIndex];
                                if(!s || s.is_sirs != 1){
                                const actions = deficit_box.find('.spendings-actions[data-index="'+dataIndex+'"]');
                                // Toggle the 'invisible' class based on the 'ok' value
                                // actions.toggleClass('invisible', ok);
                                actions.removeClass('invisible');
                                }
                                
                            });
                        });
                        document.querySelectorAll('.sirs-error').forEach((element) => {
                            const dataIndex = element.getAttribute('data-index');
                            let s = spendings_edit[dataIndex];
                            console.log("dataIndex");
                            console.log(dataIndex);
                            console.log("spendings_edit");
                            console.log(s);
                            // if (s.is_sirs == 1) {
                                if (s && s.is_sirs === 1) {
                                    console.log("==================");
                                let sirs = deficit_box.find('.sirs-error[data-index="' + dataIndex + '"]');
                                sirs.removeClass('d-none');
                            }
                        });
                        
                        // deficit_box.find('.spendings-actions').toggleClass('invisible', !ok);
                        // Adnan saleem.....
                        
                        // disable_nav(ok ? 'spendings' : '');

                        return ok;
                    }
                    // Adnan saleem.....
                    document.addEventListener('click', function (event) {
                        // Check if the clicked element has the class 'split'
                        if (event.target.classList.contains('split')) {
                            // console.log("Split label clicked!"); // Debugging
                    
                            // Select all .spendings-action elements
                            document.querySelectorAll('.spendings-action').forEach((actions) => {
                                // Get computed style to check visibility
                                const currentDisplay = window.getComputedStyle(actions).display;
                                // console.log("Current display:", currentDisplay); // Debugging
                    
                                // Toggle display based on current visibility
                                if (currentDisplay === "none" || currentDisplay === "") {
                                    actions.style.setProperty('display', 'flex', 'important'); // Force display flex
                                }
                            });
                        }
                    });
                    document.addEventListener('click', function (event) {
                        // Check if the clicked element has the class 'split'
                        if (event.target.classList.contains('split-cancel') || event.target.classList.contains('split-apply')) {
                            // console.log("Split label clicked!"); // Debugging
                            // Select all .spendings-action elements
                            document.querySelectorAll('.spendings-action').forEach((actions) => {
                                // Get computed style to check visibility
                                const currentDisplay = window.getComputedStyle(actions).display;
                    
                                // Toggle display based on current visibility
                                if (currentDisplay === "flex") {
                                    actions.style.setProperty('display', 'none', 'important'); // Force display flex
                                }
                            });
                        }
                    });
                    document.addEventListener('click', function (event) {
                        // Check if the clicked element has the class 'split'
                        if (event.target.classList.contains('unsplit')) {
                            // console.log("UnSplit label clicked!"); // Debugging
                    
                            // Select all .spendings-action elements
                            document.querySelectorAll('.spendings-action').forEach((actions) => {
                                // Get computed style to check visibility
                                const currentDisplay = window.getComputedStyle(actions).display;
                                // console.log("Current display:", currentDisplay); // Debugging
                    
                                // Toggle display based on current visibility
                                if (currentDisplay === "none" || currentDisplay === "") {
                                    actions.style.setProperty('display', 'flex', 'important'); // Force display flex
                                }
                            });
                        }
                    });
                    // Adnan saleem.....




            var spendings_fill = function(){
                
                

                spendings_table.html('');
                spendings_count.html(spendings.length);
    
                // data-index: the index witin the spendings array
    
                var spendings_tmpl = '  <div class="col-12 m-0 p-0">    \
                                            <div class="row p-0 m-0  pt-3">   \
                                                <div class="col font-weight-bold name"></div>   \
                                                <div class="col-auto">   \
                                                    <label class="d-none mt-2 btn btn-sm btn-danger unsplit" data-index="%INDEX%" >Unsplit</label>    \
                                                    <label class="d-none w3-indigo px-2 py-2 rounded pointer split font-weight-bold" style="font-size:0.8rem;" data-index="%INDEX%">Split</label>  \
                                                </div>   \
                                            </div> \
                                        </div> \
                                        <div class="col-3 p-2 pt-1"><input type="number" min="1" step="1" class="app-input year" data-field="Year<i data-tooltip=\'49\'></i>" value=""  data-index="%INDEX%" /></div>   \
                                        <div class="col-4 p-2 pt-1 text-center"><input type="number" min="1" step="1" data-field="Expected Life<i data-tooltip=\'50\'></i>"  class="app-input redundancy" data-index="%INDEX%" readonly /></div>   \
                                        <div class="col-4 p-2 pt-1">  \
                                            <input type="number" min="1" step="1" class="app-input cost org-cost" data-field="Cost<i data-tooltip=\'51\'></i>" value=""  data-index="%INDEX%" data-split="" readonly /> \
                                        </div>    \
                                        <div class="col-1 p-0 text-left pt-1">    \
                                            <i class="d-none pointer pt-4 fa fa-times w3-text-red del"  style="font-size:1.2rem;"  data-index="%INDEX%"></i>    \
                                        </div>  \
                                        <div class="col-12 w3-text-red"><span class="d-none sirs-error" data-index="%INDEX%">SIRS - Cannot be changed!</span></div>  \
                                        <div class="col-md-6 pb-0 text-right spendings-actions invisible" data-index="%INDEX%" style="display: flex; align-items: right; margin-bottom: 10px;">    \
                                            <label class="btn btn-sm btn-danger mb-0 spendings-cancel"><i class="fa fa-sync mr-2"></i> Undo</label>    \
                                            <label class="btn btn-sm btn-success mb-0 spendings-apply"><i class="fa fa-check mr-2"></i> Apply</label>    \
                                        </div>  \
                                        <div class="col-12 w3-text-red border-bottom border-dark"><span class="d-none split-error" data-index="%INDEX%">This <b>Item</b> has <b>Splits</b> on <b>Same Year</b></span></div>';



    
                //for(var i=0; i<10; i++)


                if(spendings.length == 0){
                    spendings_table.append('<div class="col-12 text-center pb-3">There are no <b>Expenses</b> in this year !</div>');
                }else{
                    
                        for(var i=0; i<spendings_edit.length; i++){
                            var s = spendings_edit[i];
            
                            var element = $(replaceAll(spendings_tmpl, '%INDEX%', i));
                            
                            // Split
                            if(!s.id && !s.org){
                                element.find('.split-error').remove();
                                element.find('.name').parent().replaceWith('<i class="ml-5 fa fa-arrow-down"></i>');

                                // add background colors
                                element.find('.app-input').attr('style', 'background: white !important')
                                                            //.prop('readonly', true)
                                                            .parent().addClass('w3-khaki');
                                element.find('.del').parent().addClass('w3-khaki');
                                

                            // Original
                            }else{
                                element.find('.name').html((ucwords(s.name, true)  + ('<b class="badge ml-1 w3-pink">'+(s.redundancy_at+1)+'</b>') + (s.split_of ? '<b class="badge ml-1 w3-deep-purple">Split</b>' : '') ) );

                                if(s.is_sirs == 1){
                                    //element.find('.app-input').prop('readonly', true)
                                    element.find('.split').remove();
                                    element.find('.unsplit').remove();
                                    element.find('.name').append('<span class="badge ml-1 w3-teal">SIRS</span>');
                                    element.find('input.year').prop('readonly', true);
                                }
                            }
                            
                            element.find('input.year').val(fiscal_year + parseInt(s.year))
                                    .on('change', function(){ 
                                        var index = $(this).data('index');
                                        var year = parseInt(this.value) - fiscal_year; //parseInt(this.value || 1) - 1;
                                        var tmp = spendings_edit[index];

                                        var oldValue = fiscal_year + parseInt(s.year);

                                        if(tmp.is_sirs == 1){
                                            // error("SIRS Can't be moved", '', default_error_alert_timeout);
                                            $(this).val(oldValue);;
                                            return;
                                        }


                                        if(year < 0){
                                            year = 0;
                                            this.value = fiscal_year;
                                        }
                                        
                                       
            
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
                            
                            element.find('.redundancy').val(s.redundancy);

                            // cost input
                            element.find('.cost').val(s.cost).prop('readonly', s.org)
                                    .on('input', function(){ 
                                        var index = $(this).data('index');
                                        var new_cost = parseInt(this.value || 1);
                                        var tmp = spendings_edit[index];
            
                                        var split_index = spendings_edit.findIndex((obj)=>{return obj.id == tmp.splitId});
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
                                        spendings_table.find('.org-cost[data-index="'+split_index+'"]').val(remaining + 1);
                                        
                                        spendings_check();
                                        
                                    });
            
                            // cost formatted
                            element.find('.formatted-cost').html(formatMoney(s.cost, 0)).toggleClass('d-none', !s.org);
                            
                            element.find('.del').toggleClass('d-none', s.org);
                            element.find('.unsplit').toggleClass('d-none', !(s.split_of && s.org));
                            element.find('.split').toggleClass('d-none', (s.split_of && s.org));
                            //element.find('.view-splits').toggleClass('d-none', !(s.splits > 0 && s.org));

                            
                            if(!s.org)element.find('.split').remove();
            
                            // splits item and adds a new row 
                            element.find('.split').click(function(){
                                
                                
                                var index = parseInt($(this).data('index'));
                                var tmp = cloneObj(spendings_edit[index]);

                                console.log(index);

                                
                                // clone the splitted item and change cost and year
                                tmp.id = '';
                                // tmp.year++;
                                tmp.cost = parseInt(tmp.cost/2);
                                if(!tmp.split_of)tmp.split_of = spendings_edit[index].id;

                                
                                // get higher year
                                var year = tmp.year,
                                    last_split_index = index; // used to push now split to last
                                for(var i=0; i < spendings_edit.length; i++){
                                    var s = spendings_edit[i];
                                    if(s.split_of == tmp.split_of && !s.id){
                                        last_split_index = i;
                                        
                                        if(s.year >= year){                                
                                            year = s.year;
                                        }
                                    }
                                }

                                // new split year
                                tmp.year = year + 1;

                                
                                tmp.org = false;
                                tmp.edited = true;
                                tmp.splitId = spendings_edit[index].id;
            
                                spendings_edit.splice(last_split_index+1, 0, tmp); //add right splite right after splitted item
            
                                // ecit orginal item                    
                                spendings_edit[index].cost -= tmp.cost;
                                spendings_edit[index].edited = true;
                                

                                var l = loading('', spendings_table.parent());
                                setTimeout(function(){ 
                                    spendings_check();
                                    spendings_fill();
                                    l.remove();}, 100);

                                
                                
                            });
            
            
                            // removes split done in modal and adds cost to split_of item
                            element.find('.del').click(function(){
                                var index = $(this).data('index');
                                var delItem = spendings_edit[index];
            
                                confirm('Are you sure you want to remove this split?', '', null, function(action){
                                    
                                    // get element splitted from
                                    var splittedFrom = spendings_edit.find((obj) => {return obj.id == delItem.splitId} );
            
                                    // remove it from list
                                    spendings_edit.splice(index, 1);   
                
                                    if(!delItem.org && !objEmpty(splittedFrom)){
                                        splittedFrom.cost += delItem.cost;
                                        
                                        // original spending
                                        var org = spendings.find((obj)=>{return obj.id == splittedFrom.id});

                                        // check to see if it's editted
                                        splittedFrom.edited = ( splittedFrom.year != org.year || 
                                                                splittedFrom.cost != org.cost ||
                                                                            spending_has_splits(splittedFrom));
                                    }
                                    //spendings[index].cost += s.cost;
                                    
                                    
                                    var l = loading('', spendings_table.parent());
                                    setTimeout(function(){ 
                                        spendings_check();
                                        spendings_fill();
                                        l.remove();}, 100);
                
            
                                });
            
                                
                            });
                            
            
                            // removes splitted element from edits and adds it to unsplit array
                            element.find('.unsplit').click(function(){
                                var index = $(this).data('index');
                                var tmp = spendings_edit[index];
                                
                                if(!tmp.org){
                                    return;
                                }
                                // Adnan Saleem.........
                                // confirm("Unsplitting this item will add its <b>Cost</b> to the item from which it has been splitted, would you like to continue ?",
                                confirm("Unsplitting this item will add its <b>Cost</b> to the item from which it has been splitted, would you like to continue ? <br> <span class='w3-text-red'>If you confirm, please click the <b>Apply</b> button to update the changes.</span>", 
                                                    "Warning", 
                                                    null, function(action){
                                                        if(action == 'ok'){
                                                            
                                                            spendings_unsplit.push(tmp.id);
                                                            spendings_unsplit_years[tmp.id] = tmp.year;
                                                            spendings_edit.splice(index, 1);  
            
                                                            var l = loading('', spendings_table.parent());
                                                            setTimeout(function(){ 
                                                                spendings_fill();
                                                                l.remove();}, 100);
            
                                                        }
                                                    });
                            });
            
                            spendings_table.append(element);
                        }
            
                        init_ui(spendings_table);

                        (new Tooltips(context.tooltips, spendings_table));

                        spendings_check();
                }


                
            }

            // deficit_box.find('.spendings-cancel').unbind().click(function(){ 
            // deficit_box.on('click', '.spendings-cancel', function () { 
            deficit_box.off('click', '.spendings-cancel').on('click', '.spendings-cancel', function () {
                confirm('Are you sure you want to revert back to the original data ?', 'Revert', null, 
                            function(action){ 
                                if(action != 'ok')return;
                                
                                var l = loading('', spendings_table.parent());
                                setTimeout(function(){ 
                                    spending_init();
                                    spendings_fill();
                                    l.remove();}, 100);

                            });
            });

            // deficit_box.find('.spendings-apply').unbind().click(function(){ 
            deficit_box.off('click', '.spendings-apply').on('click', '.spendings-apply', function () {
                if(if_any_src(spendings_final_edit)){
                    // error("SIRS Can't be moved", '', default_error_alert_timeout);
                    return;
                }
                // console.log(if_any_src(spendings_final_edit));
                // console.log(spendings_final_edit);
                    // get first year of splits
                    var first_year = 999;
                    for(const s of spendings_final_edit)
                        if(s.year < first_year)first_year = s.year;
                    for(const s of spendings_unsplit)
                        if(spendings_unsplit_years[s] < first_year)first_year = spendings_unsplit_years[s];
                        
                
                    if((spendings_final_edit.length > 0 || spendings_unsplit.length > 0)){

                        if(!spendings_check(true))return;
                        
                        if(has_deficits(first_year)){
                        
                            confirm('Changing Spendings will <b>Reset All Simulation Managed Data Starting Year <span class="w3-text-red"> '+(fiscal_year + first_year)+'</span></b>, Are you sure you want to apply ?', 'Warning', null, 
                                        function(action){ 
                                                                        if(action != 'ok')return;

                                                                        context.deficit(  context.model_id, 
                                                                                        at, null, 
                                                                                        function(resp){
                                                                                            //deficit_modal.close();
                                                                                            execfunc(cb);
                                                                                        }, spendings_final_edit, spendings_unsplit);
                                                                });
                        }else{
                            context.deficit(  context.model_id, 
                                            at, null, 
                                            function(resp){
                                                //deficit_modal.close();
                                                execfunc(cb);
                                            }, spendings_final_edit, spendings_unsplit);
                        }
  
                    }

            });

            var if_any_src = function(list) {
                for(const s of spendings_final_edit){
                    if(s.is_sirs) return true;
                }
                return false;
            }
            

            spending_init();
            spendings_fill();

            

            // Investment Strategy
            // Adnan Saleem.......
            // var inv_strategy_edit = function(parent, index){
            var inv_strategy_edit = function(parent, index, condition_ava_invest){
            // Adnan Saleem.......
                
                if(parent === undefined)parent = at;

                var working_data = cloneObj(context.simulation_data.calculated[parent]);

                // init 
                if(Array.isArray(working_data['deficit']) || objEmpty(working_data['deficit']))working_data['deficit'] = {};


                var working_inv_strategy = working_data['deficit']['inv_strategy'] || [];


                // prepare available investment types
                var types = Object.keys(context.simulation_data.inv_strategies).length > 1 ? '<option value="" disabled selected>Choose Investment Type</option>' : '';
                for(const t in context.simulation_data.inv_strategies){
                    if(!context.simulation_data.inv_strategies[t].show_model)continue;
                    types += '<option value="'+t+'">'+context.simulation_data.inv_strategies[t].name+'</option>';
                }

                // get available to invest
                var available = working_data.ip || 0,
                    max_amount = available,
                    max_perc = 100;
            
                // check if available 
                // Adnan Saleem........
                if(available <= 0 && condition_ava_invest != 'condition_ava_invest'){
                // if(available <= 0){
                // Adnan Saleem........
                    error('Insufficient Funds to Invests !', '', default_error_alert_timeout);
                    return;
                }else {

                    // deduct already invested money
                    if(!objEmpty(working_inv_strategy)){
                        

                        // calculate already invested using other strategies
                        var inv_amount = 0;
                        for(var i=0; i<working_inv_strategy.length; i++){
                            
                            // skip current editing
                            if(index !== undefined && i == index)continue;
                            if(working_inv_strategy[i].amount && working_inv_strategy[i].amount > 1) {
                                inv_amount += parseFloat(working_inv_strategy[i].amount);
                                
                            } else if(working_inv_strategy[i].perc && working_inv_strategy[i].perc > 0) {
                                inv_amount += parseFloat(working_inv_strategy[i].perc) * available / 100;
                                
                            }
                        }
                        

                        // get max available amount and percentage
                        max_amount = floatDecimals(available - inv_amount, 0);
                        max_perc = floatDecimals(max_amount * 100 / available);
                        
                        // Adnan Saleem...........
                        client_inv_p_label.html(formatMoney(max_amount, 0));
                        
                        // Adnan Saleem...................
                        // setTimeout(() => {
                            if(max_perc < 0.01){
                                info('You have Invested all your Funds !', '', default_error_alert_timeout);
                                return;
                            }
                        // }, default_success_alert_timeout);
                        // if(max_perc < 0.01){
                        //     error('Insufficient Funds to Invest !', '', default_error_alert_timeout);
                        //     return;
                        // }
                        // Adnan Saleem...................
                        

                    }
                }
                // Adnan Saleem...........
                if(condition_ava_invest == 'condition_ava_invest')return;
                // Adnan Saleem...........
                // editor
                var content = $('<div class="row m-0 p-0">  \
                                    <div class="col-12 h6 pt-2 font-weight-bold border-dark border-bottom pb-3 mb-2">    \
                                        Available to Invest <span class="w3-text-green float-right">'+formatMoney(max_amount, 0)+'</span> \
                                    </div>  \
                                    <div class="col-12 h6 pt-2 font-weight-bold border-dark border-bottom pb-3 mb-2">    \
                                        <select class="app-input editor-input" data-key="type" data-field="Investment Type" required> \
                                            '+types+'    \
                                        </select>   \
                                    </div>  \
                                    <div class="col-12 h6 pb-1 mb-1">    \
                                        <input class="app-input editor-input" data-key="name" data-field="Strategy Name (optional)" />   \
                                    </div>  \
                                    <div class="col-6 h6 pb-1 mb-1">    \
                                        <input class="app-input editor-input money" data-key="amount" step="1" min="1" data-field="Amount is USD" />   \
                                    </div>  \
                                    <div class="col-6 h6 pb-1 mb-1">    \
                                        <input class="app-input editor-input" data-key="perc" step="1" min="1" max="100" data-field="% of Reserve Fund Cash" />   \
                                    </div>  \
                                    <div class="col-6 h6 pb-1 mb-1">    \
                                        <input class="app-input editor-input" data-key="rate" min="0" max="100" data-field="Annualized Intrest Rate (%)"  required/>   \
                                    </div>  \
                                    <div class="col-6 h6 pb-1 mb-1">    \
                                        <input class="app-input editor-input" data-key="dur" min="1" data-field="Term (Years)"  required/>   \
                                    </div>  \
                                    <div class="col-6 h6 pb-1 mb-1 option-col d-none">    \
                                        <input class="app-input editor-input option" data-key="pd" step="1" min="1" data-field="Early Withdrawl Penalty <br>(Days)" data-helper="Number of Days\' Intrest" />   \
                                    </div>  \
                                    <div class="col-6 h6 pb-1 mb-1 option-col d-none">    \
                                        <input class="app-input editor-input money option" data-key="pm" step="1" min="1" data-field="Early Withdrawl Minimum Penalty <br>(USD)" />   \
                                    </div>  \
                                    <div class="col-12 h6 pb-1 mb-1">    \
                                        <textarea class="app-input editor-input" data-key="note" maxlength="100" data-field="Note"></textarea>  \
                                    </div>  \
                                </div>');

                // show options per selected type
                content.find('[data-key="type"]').change(function(){
                    var type = $(this).val();

                    content.find('.option[data-key]').each(function(){
                        var item = $(this),
                            key = item.data('key'),
                            strategy_has_option = type && context.simulation_data.inv_strategies[type].opt && has(context.simulation_data.inv_strategies[type].opt, key) !== false;

                        item.closest('.option-col').toggleClass('d-none', !strategy_has_option);
                        if(!strategy_has_option)item.val('');
                    });
                }).change();

                // clear amount in perc
                content.find('[data-key="perc"]').on('input', function(){
                    content.find('[data-key="amount"]').val('');
                    if(this.value > max_perc)this.value = floatDecimals(max_perc);
                });
                
                // clear perc in amount
                content.find('[data-key="amount"]').on('input', function(){
                    content.find('[data-key="perc"]').val('');
                    if(this.value > max_amount)this.value = max_amount;
                });

                // format money
                content.find('.money').on('input change', function(){
                    // this.value = formatMoney(this.value, (parseFloat(this.value) % 1 === 0 ? 0 : 2));
                });

                
                var modal = view_modal(content, 'Strategy', function(action, editor){

                    if(action.action != 'submit')return;

                    // check amount
                    if(action.value.amount < 1)action.value.amount = 0;
                    
                    // check perc
                    if(action.value.perc < 0)action.value.perc = 0;
                    else if(action.value.perc > 100)action.value.perc = 1;
                     
                    if(action.value.dur < 1){
                        error('Investment <b>Term</b> must be atleast <b>1 Year</b> !', '', default_error_alert_timeout);
                        return;
                    }

                    if(action.value.amount < 1 && action.value.perc <= 0){
                        error('Please specify <b>Amount</b> or <b>% of Reserve Fund Cash</b> !', '', default_error_alert_timeout);
                        return;
                    }


                    console.log(action.value);
                    // if strategy to edit is in inv_strategy
                    if(index !== undefined && index >= 0 && index < working_inv_strategy.length){
                        working_inv_strategy[index] = action.value;
                    }else{
                        working_inv_strategy.push(action.value);
                    }

                    

                    working_data['deficit']['inv_strategy'] = working_inv_strategy;

                    
                    // apply
                    apply_changes(parent, working_data['deficit']);
                    // Adnan Saleem...................
                        const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

                        // Function to handle alerts with delay
                        const handleAlerts = async () => {
                            // Show the success message
                            success('Successfully invested amount!', '', default_success_alert_timeout);
                            // Wait for 2 seconds (2000 ms) after the success alert
                            await delay(5000);
                        };

                        // Call the function where needed
                        handleAlerts();
                    // Adnan Saleem...................
                    modal.close();

                }, true);

                
                if(parent !== undefined && index !== undefined && [index]){
                    content.find('[data-key="type"]').val(working_data['deficit']['inv_strategy'][index].type).change();
                    fill_editor(modal, working_data['deficit']['inv_strategy'][index]);
                }
                

            }

            var init_inv_strategy = function(){
              
                deficit_box.find('.inv-strategy-box').html('');

                var active_inv_strategies = cloneObj(original_data.is);


                // inv strategy count
                deficit_box.find('.inv-strategy-count').html(inv_strategy.length)


                // add deficit managment inv_strategy
                for(var i=0; i<inv_strategy.length; i++){

                    var active_inv_strategy_index = active_inv_strategies.findIndex((inv)=>{ return inv.parent == at && inv.index == i; }),
                        active_inv_strategy = active_inv_strategies[active_inv_strategy_index] || {};


                    // console.log(active_inv_strategy);

                    // if found, remove from available
                    if(active_inv_strategy_index >= 0){
                        active_inv_strategies.splice(active_inv_strategy_index, 1);
                    }

                    var early_year = parseInt(active_inv_strategy.early || -1 );
                    if(early_year >= 0) early_year = parseInt(active_inv_strategy.parent) + early_year;

                    var random_id = generateId(12);

                    var existing = active_inv_strategy.existing == 1;

                    var item = $('  <div class="col-12 h6 pt-2 font-weight-bold">    \
                                        '+(existing ? 'Exisiting Strategy': ucwords(active_inv_strategy.name || 'Added Strategy '+(inv_strategy.length-i)))+' \
                                        <div class="float-right">   \
                                            <label class="link w3-text-red" data-action="delete" data-parent="'+at+'" data-index="'+i+'">Delete Strategy</label> \
                                            <label class="link w3-text-indigo ml-3" data-action="edit" data-parent="'+at+'" data-index="'+i+'">Edit Strategy</label>    \
                                        </div>  \
                                    </div>      \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Investment Type</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="type"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Investment Term</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-green" data-type="dur"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Invested Amount</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="amount"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Intrest Rate</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-green" data-type="rate"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 hold"> \
                                        <b class="">Total Projected P+I</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="total_pi"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 hold"> \
                                        <b class="">Total Projected Earnings</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-green" data-type="total_ne"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 d-none" data-togglable="'+random_id+'"> \
                                        <b class="">Projected P+I This Year</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="total"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 d-none" data-togglable="'+random_id+'"> \
                                        <b class="">Projected Earnings This Year</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-green" data-type="earned"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Early Withdrawl Penalty</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-red" data-type="penalty"></b>  \
                                    </div>  \
                                    <div class="col-12 mb-1 pb-1 w3-text-red font-weight-bold hold pointer" data-action="goto"> \
                                        This Investment have an early withdraw in <u data-type="early"></u>  \
                                    </div>  \
                                    <div class="col-12 pt-3"> \
                                        <b data-type="note"></b>  \
                                    </div>  \
                                    <div class="col-12 pt-2 border-bottom border-dark pb-3 mb-3 text-right" data-toggle="'+random_id+'"> \
                                        <b class="w3-text-brown link toggle-details">Toggle Details</b>  \
                                    </div>');

                                    
                    deficit_box.find('.inv-strategy-box').prepend(item);

                    item.find('[data-type="type"]').html(active_inv_strategy.type_name || 'Investment Strategy');
                    item.find('[data-type="amount"]').html((formatMoney(active_inv_strategy.amount, 0)) + ' ('+formatNumber(active_inv_strategy.perc*100)+'%)');
                    
                    item.find('[data-type="rate"]').html(formatNumber((active_inv_strategy.rate || 0) * 100, 2) + '%');
                    item.find('[data-type="dur"]').html(formatNumber(active_inv_strategy.terms || 1, 0) + ' Year(s)');
                    
                    
                    // remove items to show if HOLD of NOT HOLD
                    if(active_inv_strategy.hold != 1){ item.filter('.hold').remove(); item.find('.hold').remove(); }
                    else { item.filter('.not-hold').remove(); item.find('.not-hold').remove(); }
                    

                    item.find('[data-type="total_pi"]').html(formatMoney(active_inv_strategy.total_pi, 0));
                    item.find('[data-type="total_ne"]').html(formatMoney(active_inv_strategy.total_ne, 0));

                    item.find('[data-type="total"]').html(formatMoney(active_inv_strategy.total, 0));
                    item.find('[data-type="earned"]').html(formatMoney(active_inv_strategy.earned, 0));
                    
                    item.find('[data-type="note"]').html(active_inv_strategy.note ? 'Note: ' + active_inv_strategy.note : '');
                    
                    // item.filter('[data-type="amount"]').html(formatMoney(parseFloat(inv_strategy[i].perc || 0) * original_data.ip / 100));
                    
                    // remove actions when it's an existing strategy
                    if(existing)
                        item.find('[data-action]').remove();


                    // remove penalty element if < 0
                    if(!active_inv_strategy.penalty)item.find('[data-type="penalty"]').parent().remove();


                    if(early_year >= 0 && early_year != at){
                        item.find('[data-type="early"]').html((fiscal_year + early_year) + '<i class="fa fa-chevron-right ml-2"></i>')
                                .parent().data('parent', early_year);

                                
                        item.find('[data-type="penalty"]').html(formatMoney(active_inv_strategy.penalty));

                    }else{
                        item.find('[data-type="early"]').parent().remove();
                        item.find('[data-type="penalty"]').parent().remove();
                    }



                }



                // add active inv_strategy
                for(const active_inv_strategy of active_inv_strategies){

                    // console.log(active_inv_strategy);

                    var parent = active_inv_strategy.parent,
                        index = active_inv_strategy.index,
                        starting_year = active_inv_strategy.sy || (fiscal_year + parent),
                        terms = active_inv_strategy.terms,
                        end_of_year = (active_inv_strategy.end_of_year == undefined ? 1 : active_inv_strategy.end_of_year) == 1;  
                        
                    var active_title = parent == -1 ? '' : '<span class="badge w3-green">Active Strategy From <span data-type="start">'+starting_year+'</span></span>';

                    // existing strategy results
                    if(parent == -2){
                        active_title = '<span class="badge w3-indigo">Existing Strategy From <span data-type="start">'+starting_year+'</span></span>';
                        /*
                        if(active_inv_strategy.id){
                            active_title += '<label class="link w3-text-green float-right hold" data-action="withdraw" data-id="'+(active_inv_strategy.id || '')+'">Widthdraw</label>    \
                                             <label class="link w3-text-red float-right hold" data-action="unwithdraw" data-id="'+(active_inv_strategy.id || '')+'">Un-Widthdraw</label>   ';
                        }
                        */
                    
                    // simulation strategy results
                    }else if(parent >= 0){
                        active_title +=    '<label class="link w3-text-indigo float-right ml-3" data-action="edit" data-parent="'+parent+'" data-index="'+index+'">Edit Strategy</label>    \
                                            <label class="link w3-text-green float-right hold" data-action="withdraw" data-parent="'+parent+'" data-index="'+index+'">Widthdraw</label>    \
                                            <label class="link w3-text-red float-right hold" data-action="unwithdraw" data-parent="'+parent+'" data-index="'+index+'">Un-Widthdraw</label>   ';
                    }

                    

                    var early_year = parseInt(active_inv_strategy.early || -1 );
                    if(early_year >= 0) early_year = parseInt(active_inv_strategy.parent) + early_year;

                    var is_hold = active_inv_strategy.hold == 1,
                        is_wthd = active_inv_strategy.wthd == 1;

                    
                    // skip withdrawed that doesn't hold the amount
                    // if(!is_hold && !is_wthd)continue;


                    var random_id = generateId(12);

                    
                    var item = $('  <div class="col-12 h6 pt-2 font-weight-bold title">    \
                                        '+active_title+' <span class="badge w3-brown liquidated hold">Liquidated</span>    \
                                    </div>      \
                                    <div class="col-12"> \
                                        <div class="pb-3 font-weight-bold text-capitalize" data-type="name"></div>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Investment Type</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="type"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Current Investment Term</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-green" data-type="year"></b>/<b class="w3-text-green" data-type="terms"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 d-none" data-togglable="'+random_id+'"> \
                                        <b class="">Initial Principal</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="amount"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 d-none" data-togglable="'+random_id+'"> \
                                        <b class="">Projected Principal This Year</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="principal"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 liquidated"> \
                                        <b class="">Initial Principal</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="amount"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Intrest Rate</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-green" data-type="rate"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 liquidated"> \
                                        <b class="">Projected Earnings</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="earnings"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-2"> \
                                        <b class="">Projected P+I This Year</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="total"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-2 d-none" data-togglable="'+random_id+'"> \
                                        <b class="">Projected Earnings This Year</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-green" data-type="earned"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1 liquidated"> \
                                        <b class="">Liquidated P+I</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-blue" data-type="liquidated"></b>  \
                                    </div>  \
                                    <div class="col-6 mb-1 pb-1"> \
                                        <b class="">Early Withdrawl Penalty</b><i data-tooltip=\'46\'></i><br>  \
                                        <b class="w3-text-red" data-type="penalty"></b>  \
                                    </div>  \
                                    <div class="col-12 mb-1 pb-1 w3-text-red font-weight-bold hold pointer" data-action="goto"> \
                                        This Investment have an early withdrawl in <u data-type="early"></u>  \
                                    </div>  \
                                    <div class="col-12"> \
                                        <div class="pb-3 font-weight-bold" data-type="note"></div>  \
                                    </div>  \
                                    <div class="col-12 border-bottom border-dark pb-1 mb-3 font-weight-bold"> \
                                        '+(parent < 0 ? '' : '<label class="link w3-text-teal float-left" data-action="goto" data-parent="'+parent+'"data-index="'+index+'"><i class="fa fa-chevron-left mr-1"></i> Go To Strategy</label>')+'    \
                                        '+(parent < 0 ? '' : '<label class="link w3-text-red float-left ml-2" data-action="delete" data-parent="'+parent+'"data-index="'+index+'"> Delete Strategy</label>')+'    \
                                        <label class="w3-text-brown link float-right toggle-details" data-toggle="'+random_id+'">Toggle Details</label>  \
                                    </div>');

                                    
                    deficit_box.find('.inv-strategy-box').append(item);


                    item.find('[data-type="type"]').html(active_inv_strategy.type_name || 'Investment Strategy');
                    
                    
                    // remove items to show if HOLD of NOT HOLD
                    if(active_inv_strategy.hold != 1){ item.filter('.hold').remove(); item.find('.hold').remove(); }
                    else { item.filter('.not-hold').remove(); item.find('.not-hold').remove(); }
                    
                    item.find('[data-type="year"]').html(formatNumber((active_inv_strategy.year || 0) + 1, 0));
                    item.find('[data-type="terms"]').html(formatNumber(active_inv_strategy.terms || 1, 0) + ' Year(s)');
                    
                    item.find('[data-type="amount"]').html(formatMoney(active_inv_strategy.amount, 0));
                    item.find('[data-type="principal"]').html(formatMoney(active_inv_strategy.principal, 0));
                    item.find('[data-type="total"]').html(formatMoney(active_inv_strategy.total, 0));
                    item.find('[data-type="earned"]').html(formatMoney(active_inv_strategy.earned, 0));
                    item.find('[data-type="rate"]').html(formatNumber((active_inv_strategy.rate || 0) * 100, 2) + '%');
                    
                    
                    item.find('[data-type="name"]').html(active_inv_strategy.name ? active_inv_strategy.name : '').parent().toggleClass('d-none', !active_inv_strategy.name);
                    item.find('[data-type="note"]').html(active_inv_strategy.note ? 'Note: ' + active_inv_strategy.note : '').parent().toggleClass('d-none', !active_inv_strategy.note);
                    
                    // remove penalty element if penalty < 0
                    if(!active_inv_strategy.penalty)item.find('[data-type="penalty"]').parent().remove();


                    // show liquidated
                    if(is_wthd && end_of_year){
                        item.find('[data-type="amount"]').html(formatMoney(active_inv_strategy.amount, 0));
                        item.find('[data-type="earnings"]').html(formatMoney(active_inv_strategy.principal - active_inv_strategy.amount, 0));
                        item.find('[data-type="liquidated"]').html(formatMoney(active_inv_strategy.principal, 0));

                        item.find('[data-type="year"]').parent().remove();
                        item.find('[data-type="principal"]').parent().remove();
                        item.find('[data-type="total"]').parent().remove();
                        item.find('[data-type="earned"]').parent().remove();

                        item.find('.toggle-details').remove();


                    }else{
                        item.filter('.liquidated').remove();
                        item.find('.liquidated').remove();
                    }


                    // if has early withdraw but it's not year of early withdraw
                    if(early_year >= 0 && early_year != at){
                            item.find('[data-type="early"]').html((fiscal_year + early_year) + '<i class="fa fa-chevron-right ml-2"></i>')
                                    .parent().data('parent', early_year);

                            
                            item.find('[data-action="unwithdraw"]').remove();
                            item.find('[data-type="penalty"]').parent().remove();

                    // if has early withdraw and it's the year of early withdraw
                    }else if(early_year >= 0 && early_year == at){
                        item.find('[data-type="penalty"]').html(formatMoney(active_inv_strategy.penalty));
                        item.find('[data-type="early"]').parent().remove();
                        item.find('[data-action="withdraw"]').remove();

                    }else{

                        // if it doesn't have early withdrawl OR is the year of early withdrawl
                        item.find('[data-type="early"]').parent().remove();
                        item.find('[data-type="penalty"]').parent().remove();

                        // remove unwithdraw if no early year
                        if(early_year < 0)item.find('[data-action="unwithdraw"]').remove();

                        // remove withdraw if already withdrawn or current year is early withdraw year
                        if(early_year == at || active_inv_strategy.wthd == 1)item.find('[data-action="withdraw"]').remove();
                    }
                    
                    


                }


                // toggle details
                deficit_box.find('.inv-strategy-box').find('[data-toggle]').click(function(){
                    var id = $(this).data('toggle');
                    if(!id)return;

                    deficit_box.find('[data-togglable="'+id+'"]').toggleClass('d-none');
                });


                // go to year of parent strategy
                deficit_box.find('.inv-strategy-box').find('[data-action="goto"]').click(function(){
                    var parent = $(this).data('parent');
                    if(parent === undefined)return;

                    // console.log(at, inv_strategy[index].year);
                    manageDeficitYear(parent);
                });


                // edit parent strategy
                deficit_box.find('.inv-strategy-box').find('[data-action="edit"]').click(function(){
                    var parent = $(this).data('parent');
                    var index = $(this).data('index');
                    if(parent === undefined && index === undefined)return;

                    // console.log(at, inv_strategy[index].year);
                    inv_strategy_edit(parent, index);
                });


                // withdraw parent strategy
                deficit_box.find('.inv-strategy-box').find('[data-action="withdraw"]').click(function(){
                    var parent = $(this).data('parent');
                    var index = $(this).data('index');
                    var id = $(this).data('id');
                    
                    if(parent === undefined && index === undefined && id === undefined)return;

                    // console.log(parent, index);

                    // if withdrawing existing strategy
                    if(id){
                        
                        // get all the deficit data from this year, and add y_wthd to existing investment
                        var working_data = cloneObj(context.simulation_data.calculated[at]);
                            
                        if(!working_data['deficit']['inv_existing_wthd'])working_data['deficit']['inv_existing_wthd'] = [];
                        
                        if(has(working_data['deficit']['inv_existing_wthd'], id))return;

                        working_data['deficit']['inv_existing_wthd'].push(id);
                        
                        // apply
                        apply_changes(at, working_data['deficit']);

                        return;
                    }
                    
                    // get all the deficit data from the year of the parent
                    // add y_wth to the specific parent
                    var working_data = cloneObj(context.simulation_data.calculated[parent]),
                        working_inv_strategy = working_data['deficit']['inv_strategy'] || {};

                    if(!objEmpty(working_inv_strategy) && working_inv_strategy[index]){
                        working_data['deficit']['inv_strategy'][index]['y_wth'] =  at - parent; 
                        
                        // apply
                        apply_changes(parent, working_data['deficit']);
                    }
                });


                // unwithdraw parent strategy
                deficit_box.find('.inv-strategy-box').find('[data-action="unwithdraw"]').click(function(){
                    var parent = $(this).data('parent');
                    var index = $(this).data('index');
                    var id = $(this).data('id');
                    
                    if(parent === undefined && index === undefined && id === undefined)return;

                    // console.log(parent, index);

                    // if withdrawing existing strategy
                    if(id){
                        
                        // get all the deficit data from this year, and add y_wthd to existing investment
                        var working_data = cloneObj(context.simulation_data.calculated[at]);
                            
                        if(!working_data['deficit']['inv_existing_wthd'])return;
                        
                        var _index = has(working_data['deficit']['inv_existing_wthd'], id);

                        working_data['deficit']['inv_existing_wthd'].splice(_index, 1);
                        
                        // apply
                        apply_changes(at, working_data['deficit']);

                        return;
                    }

                    
                    var working_data = cloneObj(context.simulation_data.calculated[parent]),
                        working_inv_strategy = working_data['deficit']['inv_strategy'] || {};

                    if(!objEmpty(working_inv_strategy) && working_inv_strategy[index]){
                        delete working_data['deficit']['inv_strategy'][index]['y_wth']; 
                        
                        // apply
                        apply_changes(parent, working_data['deficit']);
                    }
                });

                // delete strategy
                deficit_box.find('.inv-strategy-box').find('[data-action="delete"]').click(function(){
                    var index = $(this).data('index'); 
                    var parent = $(this).data('parent');

                    if(context.simulation_data.calculated[parent] === undefined || context.simulation_data.calculated[parent].deficit.inv_strategy[index] == undefined)
                        return;

                    
                    confirm('Are you sure you want to delete this <b>Strategy</b>?', '', null, 
                            function(action){
                                if(action != 'ok')return;

                                var tmp_deficit = cloneObj(context.simulation_data.calculated[parent].deficit);

                                // remove strategy
                                tmp_deficit.inv_strategy.splice(index, 1);

                                // apply
                                apply_changes(parent, tmp_deficit);


                            });
                });

                
                
                setTimeout(function(){ init_ui(deficit_box.find('.inv-strategy-box')); }, 200);

                
            }
            

            init_inv_strategy();
            

            // remember scroll
            deficit_box.find('.inv-strategy-box').parent().unbind('scroll').scroll(function(){
                $(this).attr('data-scroll', $(this).scrollTop());
            }).scrollTop(deficit_box.find('.inv-strategy-box').parent().attr('data-scroll'));
            
            // add new startegy
            deficit_box.find('.inv-strategy-add').unbind().click(function(){
                
                inv_strategy_edit();
            }).toggleClass('d-none', original_data.ip <= 0); 

            // clear deficit with startegy
            deficit_box.find('.inv-strategy-clear').unbind().click(function(){
                
                // context.simulation_data.calculated[at];

                var deficit_to_cover = original_data.fa;
                var used_inflation_rate = (general_rules.use_infl == 1 && inflation_rate > 0) ? inflation_rate : 0;
                    
                // apply inflation
                deficit_to_cover *= 1 + used_inflation_rate;

                if(deficit_to_cover >= 0){
                    error('There is no deficit to clear !','', default_error_alert_timeout);
                    return;
                }

                var available_year_to_start = [];

                for(var i=0; i<at; i++){
                    
                    // get available funds in year Y, and Y+1
                    var fund = context.simulation_data.calculated[i].fa,
                        fund_next = context.simulation_data.calculated[i+1].fa;

                    // if no funds in year Y, or not enough to cover the next year expenses, skip
                    if(fund <= 0 || fund_next <= 0)continue;

                    // get diff between Y and Y+1 to see the max to invest
                    var fund_to_invest = Math.round(fund > fund_next ? fund_next : fund);

                    available_year_to_start.push({"year": i, "amount": fund_to_invest, "ratio":1});
                }

                // when no year
                if(objEmpty(available_year_to_start)){
                    error('There are no available funds in previous years to use as in an investment strategy !','', default_error_alert_timeout);
                }

                else{

                    // prepare available investment types
                    var types = Object.keys(context.simulation_data.inv_strategies).length > 1 ? '<option value="" disabled selected>Choose Investment Type</option>' : ''; 
                    for(const t in context.simulation_data.inv_strategies){
                        if(!context.simulation_data.inv_strategies[t].show_model)continue;
                        types += '<option value="'+t+'">'+context.simulation_data.inv_strategies[t].name+'</option>';
                    }

                    // editor
                    var content = $('<div class="row m-0 p-0">  \
                                        <div class="col-12 h6 pt-2 font-weight-bold border-dark border-bottom pb-3 mb-2">    \
                                            <select class="app-input editor-input" data-key="type" data-field="Investment Type" required> \
                                                '+types+'    \
                                            </select>   \
                                        </div>  \
                                        <div class="col-6 h6 pb-1 mb-1">    \
                                            <input class="app-input editor-focus" type="number" data-key="rate" min="0" max="100" step="any" data-field="Annualized Intrest Rate (%)" value="1"  required/>   \
                                        </div>  \
                                        <div class="col-6 h6 py-1 mb-1 font-weight-bold text-center">    \
                                            <span class="">Deficit to Clear</span> \
                                            <div class="h4 w3-text-red">'+formatMoney(deficit_to_cover, 0)+'</div>    \
                                            <div class="">'+(general_rules.use_infl == 1 && inflation_rate > 0 ? 'Including '+formatNumber(inflation_rate*100, 2)+'% Inflation' : '')+'</div>    \
                                        </div>  \
                                        <div class="col-12"></div>  \
                                        <div class="col-12 h6 pt-3 pb-1 mb-1 border-dark border-bottom font-weight-bold h5">    \
                                            Available Investments ('+available_year_to_start.length+')   \
                                        </div>  \
                                        <div class="col-12 pb-1 mb-1">   \
                                            <div class="w-100 overflow-auto available-list" style="min-height:150px; max-height:300px"></div>  \
                                        </div>  \
                                    </div>');

                    var list = content.find('.available-list');

                    var fill = function(){
                        var rate = parseFloat(content.find('[data-key="rate"]').val()),
                            compounding_frequency = 1;

                        if(isNaN(rate) || rate < 0)rate = 0;
                        rate /= 100;

                        list.html('');

                        var i = 0;
                        for(const str of available_year_to_start){

                            // get number of years
                            var years = at - str.year;
                            
                            // calculate P+I
                            var pi = Math.round(str.amount * Math.pow(1 + (rate/compounding_frequency), years * compounding_frequency));

                            // if P+I is more than deficit, find optimal
                            var over_by = Math.round((pi-Math.abs(deficit_to_cover))*100/Math.abs(deficit_to_cover));
                            if(over_by > 5){
                                
                                var ratio = 1;

                                // calculate each 10% of the amount
                                for(var j=90; j>0; j-=10){
                                    
                                    // calcuate 90%, 80% ...
                                    ratio = j/100;
                                    pi = Math.round(str.amount * ratio * Math.pow(1 + (rate/compounding_frequency), years * compounding_frequency));

                                    // when changed, fine tune 91%, 92% ...
                                    if(pi < Math.abs(deficit_to_cover)){
                                        for(var k=0; k<10; k+=0.5){
                                            ratio = (j+k)/100;

                                            pi = Math.round(str.amount * ratio * Math.pow(1 + (rate/compounding_frequency), years * compounding_frequency));
                                            if(pi >= Math.abs(deficit_to_cover)){
                                                str.ratio = ratio;
                                                break
                                            }
                                        }
                                        
                                        break;
                                    }
                                        
                                }
                                
                                str.ratio = ratio;

                            }else{
                                str.ratio = 1;
                            }

                            list.append('<div class="w-100 pt-3 pb-1 mb-2 border-dark border-bottom font-weight-bold h6"> '+
                                            'Investing <span class="w3-text-blue mx-1">'+formatMoney(str.amount * str.ratio, 0)+'</span> '+
                                            ' in <span class="w3-text-indigo mx-1">'+(str.year + fiscal_year)+'</span>'+ 
                                            ' <span class="w3-text-indigo mx-1">('+(years)+' Years)</span> <br>'+ 
                                            ' with an intrest rate of <span class="w3-text-blue mx-1">'+formatNumber(rate*100, 2)+'%</span>'+ 
                                            ' will clear <span class="w3-text-teal mx-1 h5">'+formatMoney(pi, 0)+'</span>'+ 
                                            ' <div class="w3-text-deep-purple mr-2 mt-3 h6 link text-right" data-index="'+i+'">Implement this Strategy <i class="fa fa-chevron-right ml-1"></i></div>'+ 
                                        '</div>');

                            i++;
                        }


                        list.find('[data-index]').click(function(){
                            var index = $(this).data('index');
                            var str = available_year_to_start[index];

                            var rate = parseFloat(content.find('[data-key="rate"]').val());
                            if(isNaN(rate) || rate < 0)rate = 0;

                            var working_data = cloneObj(context.simulation_data.calculated[str.year].deficit);

                            if(Array.isArray(working_data) || objEmpty(working_data))working_data = {};


                            // init deficit.inv_strategy
                            if(!working_data.inv_strategy){
                                working_data.inv_strategy = []
                            }

                            // add strategy
                            working_data.inv_strategy.push({
                                                            "type": content.find('[data-key="type"]').val(),
                                                            "amount": Math.ceil(str.amount * str.ratio),
                                                            "perc": "",
                                                            "rate": rate,
                                                            "dur": at - str.year,
                                                            "pd": "",
                                                            "pm": "",
                                                            "note": ""
                                                        });

                                    
                            // apply
                            apply_changes(str.year, working_data);

                            modal.close();
                            
                        })
    
                    };

                    fill();
                    content.find('[data-key="rate"]').on('input change', function(){ fill(); });
                    

                    var modal = view_modal(content, 'Clear With Strategy', function(action, editor){

                        if(action.action != 'submit')return;

                    }, true, 'md', false);
                        
                }


            }).toggleClass('d-none', original_data.fa >= 0);

            // goto LTIM from inv strategy
            deficit_box.find('.inv-strategy-goto-ltim').unbind().click(function(){
                
                goto_nav('ltim');
            });

            // inv strategy APPLY
            deficit_box.find('.inv-strategy-apply').unbind().click(function(){                
                apply_changes();
            }).addClass('invisible');

            // inv strategy RESET
            deficit_box.find('.inv-strategy-reset').unbind().click(function(){  
                update_deficit_data('inv_strategy', 0, true);              
                apply_changes();
            }).toggleClass('invisible', !deficit_data['inv_strategy']);

            
            
            //inv_strategy_update(true); // set original
            //inv_strategy_update();      // set edited
            


            
                
            init_ui(deficit_box, function(event, value){ 
                
                
                if(event.key == "loan_ratio"){        
                    
                    if(event.event != "stop")return;

                    loan_update_value(value.value);

                }else if(event.key == 'monthly_fees'){

                    if(event.event != "stop")return;

                    monthly_fee_increase_update_value(value.value);

                    

                }else if(event.key == 'ltim_amount'){

                    if(event.event != "stop")return;

                    ltim_amount_update_value(value.value);

                }else if(event.key == 'ltim_withdraw'){

                    if(event.event != "stop")return;

                    ltim_withdraw_update_value(value.value);

                }else if(event.event == "change"){

                    //deficit_data[event.key] = floatDecimals(parseFloat(event.type == 'slider' ? value.value : value ) / (event.element.data('percent') ? 100 : 1), 4);
                
                    //if(event.key == "bank_rate" || event.key == "loan_years")loan_update_value();
                }


            });
                

            //monthly_fees_slider.slider('value', deficit_data['monthly_fees']);  
            //assessment_input.val(deficit_data['assessment']);

            //var init_loan =  loan_amount * 100 / calculate_remaining_deficit('loan'); if(isNaN(init_loan))init_loan = 0;
            //loan_slider.slider('value', init_loan);  loan_update_value();
            

            $(window).on('resize', function(){
                if(deficit_chart != null && deficit_chart != undefined){
                    deficit_chart.resize();
                }

                // toggle_fa_position();

            });
            

            var apply_changes = function(year_to_update, d_data){


                    if(year_to_update === undefined)year_to_update = at;
                    
                    if(d_data === undefined)d_data = cloneObj(deficit_data);
                    
                    /*
                    if(d_data['inv_strategy'] && Array.isArray(d_data['inv_strategy'])){
                        var total_perc = 0, total_rate = 0;
                        for(const st of d_data['inv_strategy']){
                            total_perc += parseInt(st.perc || 0);
                            total_rate += parseInt(st.rate || 0);
                        }
                        if(total_rate > 0 && total_perc > 100){
                            error('The <b>Total % of Wallet</b> must be <b>100%</b> !<br> Total % of Wallet: <b>'+total_perc+'%</b>');                                                        
                            return;
                        }
                    }
                    */

                    //deficit_data = {...deficit_data, ...{"fa":amount, "ta": total_amount}};
                    
                    
                    context.deficit(    context.model_id, 
                                        year_to_update, d_data, 
                                        function(resp){
                                            //deficit_modal.close();
                                            execfunc(cb);
                                        });
                    
                    
            }

            deficit_box.find('.reset').unbind().click(function(){

                                confirm('Are you sure you want to <b>Reset</b> ? <br><b><u>This Action is irreversible</u></b> !', 'Reset', null, 
                                                            function(action){
                                                                if(action != 'ok')return;
                                                                
                                                                context.reset(context.model_id, 
                                                                            function(resp){
                                                                                //deficit_modal.close();
                                                                                execfunc(cb);
                                                                            }, at);

                                                            });
                        
            }).toggleClass('invisible', objEmpty(deficit_data));

            deficit_box.find('.apply').unbind().click(apply_changes);
            
            update_remaining_deficit();

            setTimeout(function(){ context.timelineDeficit(null, timelineCb) }, 200);


        }

        
        eraseDeficit_init();

        


        return true;
        
    };
    
    
    
    Simulation.prototype.timelineDeficit = function(simulation_data, cb, initOnly = false){
        

        if(!simulation_data)simulation_data = this.simulation_data;
        
        if(!simulation_data || objEmpty(simulation_data))return;

        // get the box        
        var box = this.root.find('#simulation_timeline');

        if(box.length == 0)return;

        if(initOnly){
            box.find('.simulation-timeline-mf-container').remove();
            box.find('.simulation-timeline-container').remove();

        }


        // make box horizontally scrollable
        // box.addClass("container p-2");

                
        // variables
        var calculated = simulation_data.calculated || [],
            model = simulation_data.model,
            period = calculated.length,
            fiscal_year = parseInt(model.fiscal_year || 0);
            

        // Maximum Remaining Amount;
        var positive_fa = 0,
            negative_fa = 0,
            max_fa = 0;

        for(const c of simulation_data.calculated){
            var accu_ltim = c.fa > 0 ? (c.ltim_acc || 0) : 0;
            var fa = Math.abs(c.fa) + accu_ltim;

            // use new value
            if(fa > 0 && fa > max_fa)max_fa = fa;
            
            // then original value
            if(Math.abs(c.fa_o) > 0 && Math.abs(c.fa_o) > max_fa)max_fa = Math.abs(c.fa_o);
        }
        /**************************/

        
        // Maximum Monthly Fee;
        var mf_max = 0;

        // check for both calculations for max mf
        for(const c of calculated){
            if(mf_max < c.mf)mf_max = c.mf;
        }
        /**************************/
        
        // remove timeline when period changed
        if(box.find('.simulation-timeline-container').length > 0 && box.find('.simulation-timeline-mf-col').length != calculated.length){
            console.log('resetting timeline');
            box.find('.simulation-timeline-container').remove();
            box.find('.simulation-timeline-mf-container').remove();
        }

        // create timeline
        var timeline = box.find('.simulation-timeline');
        var mf_timeline = box.find('.simulation-timeline-mf');

        
        
        // events handlers        
        var mf_timeline_handle_year_arrows = function(){
            
            // mf_timeline.find('.year-left').toggleClass('invisible', mf_timeline.scrollLeft() < 5);
            // mf_timeline.find('.year-right').toggleClass('invisible', mf_timeline.scrollLeft() == Math.abs(mf_timeline.prop('offsetWidth') - mf_timeline.prop('scrollWidth')) );
            
        }
        var timeline_handle_year_arrows = function(){
            var timeline = box.find('.simulation-timeline-container');

            // sync scroll with mf_timeline
            timeline_scroll_per = timeline.scrollLeft() / timeline[0].scrollWidth;
            mf_timeline_scroll_to = mf_timeline.parent()[0].scrollWidth * timeline_scroll_per;
            mf_timeline.parent().scrollLeft(mf_timeline_scroll_to);

            timeline.find('.year-left').toggleClass('invisible', timeline.scrollLeft() < 5);
            timeline.find('.year-right').toggleClass('invisible', timeline.scrollLeft() >= Math.abs(timeline.prop('offsetWidth') - timeline.prop('scrollWidth')) - 20 );
        }
        

        var set_value = function(item, value, value_o, isManaged = false, value_ltim = 0, has_inv = false){


            var year = item.data('year');
                item.attr('data-positive', value >= 0 ? 'true' : 'false');


            item.removeAttr('data-prev-positive');

            // set value
            item.find('.simulation-timeline-value').html(formatNegatif(value, null, ' ', '$', 'red', true, 'green', 'simulation-timeline-value-text'));

            
            var positive_bar = item.find('.positive-bar'),
                positive_bar_ltim = positive_bar.find('.simulation-timeline-positive-bar-ltim');
                negative_bar = item.find('.negative-bar');


            // console.log(year, value, max_fa, ((Math.abs(parseInt(Math.abs(value)*100/max_fa)))))

            var ghost_show_ratio = 1;

            
            item.find('.simulation-timeline-year').toggleClass('managed', !isManaged);

            
            var ratio_o = (Math.abs(parseInt(Math.abs(value_o)*100/max_fa))) - 10; if(ratio_o < 0)ratio_o = 0;
            if(value_o >= 0 ){
               
                // else if new value is different from original, show ghost
                // since ghost is child of positive_bar, we must find the correct height ratio from position_bar parent
                var change_ratio = Math.abs((value_o - value)*100 / value_o);
                positive_bar.siblings('.ghost').toggleClass('d-none', false).css({height: ratio_o+"%"});
                negative_bar.siblings('.ghost').toggleClass('d-none', true);

            }else{

                // else if new value is different from original, show ghost
                // since ghost is child of positive_bar, we must find the correct height ratio from position_bar parent
                var change_ratio = Math.abs((value_o - value)*100 / value_o);
                negative_bar.siblings('.ghost').toggleClass('d-none', false).css({height: ratio_o+"%"});
                positive_bar.siblings('.ghost').toggleClass('d-none', true);
            }

            
            positive_bar_ltim.css({height: "0%"});
            

            // if Remaining amount is positif
            if(value >= 0){
                var ratio = (Math.abs(parseInt((value+value_ltim)*100/max_fa))) - 10; if(ratio < 1)ratio = 1;
                var ratio_ltim = (Math.abs(parseInt((value_ltim)*100/(value+value_ltim)))); if(ratio_ltim < 0)ratio_ltim = 0;


                // show positive bar
                positive_bar.css({height: ratio+"%"});
                positive_bar_ltim.css({height: ratio_ltim+"%"});
                
                // if original data is negative & new is positive, show negative with outline
                if(value_o < 0){
                    item.attr('data-prev-positive', 'false');
                    var ratio2 = (Math.abs(parseInt(value_o*100/max_fa))) - 10; if(ratio2 < 1)ratio2 = 1;
                    negative_bar.css({height: ratio2+"%"});
                }

                
            // else if negatif
            }else{
                

                var ratio = (Math.abs(parseInt(Math.abs(value)*100/max_fa))) - 10; if(ratio < 1)ratio = 1;

                // animate negative bar
                negative_bar.css({height: ratio+"%"});
                
                // if original data is positive & new is negative, show positive with outline
                if(value_o >= 0){
                    item.attr('data-prev-positive', 'true');
                    var ratio2 = (Math.abs(parseInt(value_o*100/max_fa))) - 10; if(ratio2 < 1)ratio2 = 1;
                    // positive_bar.animate({height: ratio2+"%"}, 500, 'linear')
                    //             .siblings('.ghost').toggleClass('d-none', true);
                }

                
            }

            // has investment withdrawn
            item.find('.simulation-timeline-col-inv').toggleClass('d-none', !has_inv);
        }

        
        // var set_mf_value = function(item, value, prev_mf, is_auto = false){

        //     // const check = $('.monthly-fees-auto').is(':checked');
        //     // if (check) {
        //     //     is_auto = is_auto?false:true;
        //     // }

        //     var year = item.data('year');
                
        //     var ratio = (Math.abs(parseInt(Math.abs(value)*100/mf_max))) - 10; if(ratio < 0 || isNaN(ratio))ratio = 0;

        //     var mf_inc = (value - prev_mf)*100/prev_mf; if(isNaN(mf_inc) || !isFinite(mf_inc))mf_inc = 0;
        //     console.log("value",value);
        //     console.log("prev_mf",prev_mf);
        //     console.log("mf_inc",mf_inc);
        //     item.find('.simulation-timeline-mf-perc-text').html(formatNumber(mf_inc, 1)+'%').toggleClass('simulation-timeline-mf-auto', is_auto);;
        //     item.find('.simulation-timeline-mf-value-text').html(formatNegatif(value, null, ' ', '$', 'red', true, '', '')).toggleClass('simulation-timeline-mf-auto', is_auto);;

        //     item.find('.simulation-timeline-mf-bar').css({height: ratio+"%"}).toggleClass('simulation-timeline-mf-auto', is_auto);
            
        // }
        /***** New Code RUSHI Start *****/
        var set_mf_value = function(item, value, prev_mf, is_auto = false, mf_o = 0, cash_reserve_threshold = 0){
            const check = $('.monthly-fees-auto').is(':checked');
            var year = item.data('year');
                
            var ratio = (Math.abs(parseInt(Math.abs(value)*100/mf_max))) - 10; if(ratio < 0 || isNaN(ratio))ratio = 0;
            if (cash_reserve_threshold && cash_reserve_threshold != 0) {
                var mf_inc = (value - mf_o)*100/mf_o; if(isNaN(mf_inc) || !isFinite(mf_inc))mf_inc = 0;
            } else {
                var mf_inc = (value - prev_mf)*100/prev_mf; if(isNaN(mf_inc) || !isFinite(mf_inc))mf_inc = 0;
            }
            console.log("value",value);
            console.log("prev_mf",prev_mf);
            console.log("mf_inc",mf_inc);
            var selectedYear = parseInt($('.simulation-timeline-col.active').data('year'));
            console.log("selectedYear",selectedYear);
            // Determine color based on year comparison with selected year
            var useGreenColor = false;
            if (check) { // Only apply special coloring when optimize is enabled
                useGreenColor = year >= selectedYear; // Green for selected year and future years
            } else {
                useGreenColor = is_auto; // Use original logic when optimize is disabled
            }
            if (value !== mf_o) {
                useGreenColor = true;
            } else {
                useGreenColor = false;
            }
            item.find('.simulation-timeline-mf-perc-text')
                .html(formatNumber(mf_inc, 1)+'%')
                .toggleClass('simulation-timeline-mf-auto', useGreenColor);
            
            item.find('.simulation-timeline-mf-value-text')
                .html(formatNegatif(value, null, ' ', '$', 'red', true, '', ''))
                .toggleClass('simulation-timeline-mf-auto', useGreenColor);

            item.find('.simulation-timeline-mf-bar')
                .css({height: ratio+"%"})
                .toggleClass('simulation-timeline-mf-auto', useGreenColor);
                

            if (!mf_inc && value !== mf_o && selectedYear && cash_reserve_threshold == 0) {
               item.find('.simulation-timeline-mf-perc-text')
                   .toggleClass('simulation-timeline-mf-auto', true);
               
               item.find('.simulation-timeline-mf-value-text')
                   .toggleClass('simulation-timeline-mf-auto', true);

               item.find('.simulation-timeline-mf-bar')
                   .toggleClass('simulation-timeline-mf-auto', true);
               /* item.find('.simulation-timeline-mf-value-text')
                   .toggleClass('simulation-timeline-mf-auto', true); */
            }
            
        }
        /***** New Code RUSHI End *****/
        // if monthy fees timeline not been created, then create it
        if(mf_timeline.length == 0){
            
            // chart
            var bars = "", y = 1;


            mf_timeline_element = $('<div class="container text-center border-bottom border-top py-1 mt-2 mb-2 border-dark position-relative simulation-timeline-mf-container" data-tour-name="simulation_main" data-tour-step="14" style="overflow-x:hidden;max-width:100%;">    \
                                        <div class="position-sticky font-weight-bold h6 w-100 user-select-none" style="top:10px;left:0;right:0;margin:auto;">  \
                                            <!-- <span class="float-left  year-left invisible"><i class="fa fa-arrow-left mr-1"></i>Prev Years</span>   \
                                            <span class="float-right year-right invisible">Next Years <i class="fa fa-arrow-right ml-1"></i></span> -->   \
                                            <span class="text-center"><span data-tooltip="9">Monthly Fees Collection</span> <br><div class="link d-none" data-tour-name="simulation_main" data-tour-step="15" data-tooltip="10" onclick="$(\'.simulation-compare\').click()"><i class="fa fa-search-plus ml-1"></i> Click To Expand</div></span>   \
                                        </div> \
                                        <div class="d-inline-flex flex-row justify-content-center simulation-timeline-mf"></div>    \
                                     </div>');

            mf_timeline = mf_timeline_element.find('.simulation-timeline-mf').first();
            
            for(const c of calculated){
                var ratio = Math.random() * 99 + 1,
                    ratio_ltim = Math.random() * 99 + 1 ;
                

                bars += '<div class="container-inline simulation-timeline-mf-col" data-year="'+y+'" align="center">   \
                            <div class="simulation-timeline-mf-year">'+(fiscal_year + y - 1)+'</div> \
                            <div class="simulation-timeline-mf-bar" style="height:1%"></div> \
                            <div class="p-0 simulation-timeline-mf-perc '+(y%2==0 ? 'simulation-timeline-mf-perc-top' : '')+' text-center d-inline-flex align-items-start flex-row justify-content-center text-nowrap" style="width:0.1rem;height:1.5rem;font-size:1.1rem;">    \
                                <span class="simulation-timeline-mf-perc-text"></span>   \
                            </div>  \
                            <div class="p-0 simulation-timeline-mf-value text-center d-inline-flex align-items-start flex-row justify-content-center text-nowrap" style="width:0.1rem;height:1.5rem;font-size:1.1rem;">    \
                                <span class="simulation-timeline-mf-value-text"></span>   \
                            </div>  \
                         </div>';

                y++;
            }
            
            
            mf_timeline.append(bars);
            box.append(mf_timeline_element);
            
            mf_timeline_element.find('.year-left').unbind('click').click(function(){ mf_timeline_element.animate({scrollLeft: mf_timeline_element.scrollLeft() - 100}, 200); });
            mf_timeline_element.find('.year-right').unbind('click').click(function(){ mf_timeline_element.animate({scrollLeft: mf_timeline_element.scrollLeft() + 100}, 200); });
           

            
            mf_timeline_element.scroll(mf_timeline_handle_year_arrows);
            mf_timeline_handle_year_arrows();

            (new Tooltips(this.tooltips)).init(mf_timeline);
            

            // mf_timeline.find('.simulation-timeline-mf-bar').click(function(){ mf_timeline.find('.simulation-timeline-mf-ltim-bar').toggleClass('invisible'); });
            // mf_timeline.find('.simulation-timeline-mf-ltim-bar').click(function(){ mf_timeline.find('.simulation-timeline-mf-bar').toggleClass('invisible'); });
            
            // chart.append('<tr>'+negatives+'</tr>');

        }
        
        // if timeline not been created, then create it
        if(timeline.length == 0){

            var timeline_element = $('  <div class="container text-center simulation-timeline-container" data-tour-name="simulation_main" data-tour-step="16"  style="overflow-x:auto;max-width:100%;">   \
                                        <div class="position-sticky font-weight-bold h5 w-100 year-arrows" style="top:10px;left:0;right:0;margin:auto;">  \
                                            <span class="year-left invisible"><i class="fa fa-arrow-left mr-1"></i>Prev Years</span>   \
                                            <span></span>   \
                                            <span class="year-right invisible">Next Years <i class="fa fa-arrow-right ml-1"></i></span>   \
                                            <div class="h6 pt-4">Click On A Year to View Options</div>  \
                                        </div> \
                                        <div class="d-inline-flex flex-row py-b pt-3 mt-3 simulation-timeline"></div> \
                                    </div>');
            timeline = timeline_element.find('.simulation-timeline').first();

            // chart
            var bars = "", y = 1;

            // properties
            var bar_width = '4rem', bar_height = '12rem';

            for(const c of calculated){
                
                // amount value
                var value = formatNegatif(0, null, ' ', '$', 'red', true, 'green', 'simulation-timeline-value-text'),
                    ratio = 1;

                // if(c.fa >= 0)ratio = (Math.abs(parseInt(c.fa*100/positive_fa))+1); 
                // else ratio = (Math.abs(parseInt(c.fa*100/negative_fa))+1);
                
                if(ratio < 1)ratio = 1;


                // ratio to largest amount value

                // bars
                var bar_positif_ghost = '<div class="simulation-timeline-positive-bar-ghost ghost d-none" style="height:0%"></div>',
                    bar_negatif_ghost = '<div class="simulation-timeline-negative-bar-ghost ghost d-none" style="height:0%"></div>';

                var bar_positif = '<div class="simulation-timeline-positive-bar positive-bar" style="height:1%"><div class="simulation-timeline-positive-bar-ltim"></div></div>',
                    bar_negatif = '<div class="simulation-timeline-negative-bar negative-bar" style="height:1%"></div>';
                    
                // lines to bars
                var line_positive = '<label class="simulation-timeline-positive-line positive-line"></label>',
                    line_negative = '<label class="simulation-timeline-negative-line-t-'+(y%2==0?'even':'odd')+' negative-line"></label>  \
                                     <label class="simulation-timeline-negative-line-b negative-line"></label>';

                
                // chart bars
                bars += '  <div class="container-inline simulation-timeline-col" data-year="'+y+'" style="width:'+bar_width+';">  \
                                \
                                <div class="p-0 pb-3 pt-1 px-3 d-flex align-items-end flex-row justify-content-center position-relative" style="height:'+bar_height+';">    \
                                <div class="w-100 w3-text-blue position-absolute d-none simulation-timeline-col-inv" style="top: -2rem;"><i class="fa fa-star"></i></div> \
                                    '+bar_positif+' \
                                    '+bar_positif_ghost+'   \
                                </div>  \
                                \
                                <div class="p-0 text-center d-inline-flex align-items-start flex-row justify-content-center text-nowrap '+(y%2==0?'simulation-timeline-value':'')+'" style="width:'+bar_width+';height:1.5rem;font-size:1.1rem;">    \
                                    '+(y%2==0?value:'&nbsp;')+'   \
                                </div>  \
                                <div class="p-0 text-center d-inline-flex align-items-start flex-row justify-content-center text-nowrap '+(y%2!=0?'simulation-timeline-value':'')+'" style="width:'+bar_width+';height:3rem;font-size:1.1rem;">    \
                                    '+(y%2!=0?value:'&nbsp;')+'   \
                                </div>  \
                                <div class="px-3 text-center position-relative w3-gray '+(y==1?'simulation-timeline-year-start':'')+' '+(y==period?'simulation-timeline-year-end':'')+'" style="height:1rem;">    \
                                    '+line_positive+'    \
                                    '+line_negative+'    \
                                                         \
                                    <label class="btn btn-sm btn-circle simulation-timeline-year w3-border-gray position-absolute font-weight-bold" style="top:-40%;left:0;right:0;margin:auto;border-width:0.3rem;">   \
                                        '+(fiscal_year + y - 1)+'   \
                                    </label> \
                                                         \
                                </div>  \
                                \
                                <div class="p-0 pt-4 pb-4 mb-2 px-3 d-flex align-items-start flex-row justify-content-center position-relative" style="height:'+bar_height+';">    \
                                    '+bar_negatif+' \
                                    '+bar_negatif_ghost+'   \
                                </div>  \
                                \
                            </div>';

                y++;
            }
            
            // chart.append('<tr>'+positives+'</tr>');
            // chart.append('<tr>'+values_even+'</tr>');
            // chart.append('<tr>'+values_odd+'</tr>');

            timeline.append(bars);

            // scroll active col to center
            var center_col = function(){
                
                try{
                
                    var active_col = timeline.find('.simulation-timeline-col.active').first(),
                        col_to_parent = active_col.offset().left - timeline_element.offset().left,
                        parent_width = timeline_element.width(),
                        is_inside_parent = col_to_parent > 0 && col_to_parent < parent_width;
    
                        
                    if(col_to_parent >= parent_width){
                        timeline_element.animate({scrollLeft: timeline_element.scrollLeft() + (active_col.width() + col_to_parent - parent_width + 20)}, 500);
                    }else if(col_to_parent < 0){
                        timeline_element.animate({scrollLeft: timeline_element.scrollLeft() + col_to_parent - 20 }, 200);
                    }

                }catch(e){

                }
            }

            // scroll active col to left
            var left_col = function(){
                var active_col = timeline.find('.simulation-timeline-col.active').first(),
                    col_width = active_col.width(),
                    col_year = parseInt(active_col.data('year')) - 2 ;


                timeline_element.animate({scrollLeft: (col_year * col_width)}, 200);

            }


            timeline.find('.simulation-timeline-col').unbind('click').click(function(){
                
                var item = $(this),
                    year = item.data('year');

                if(year == undefined)return;
                year--; if(year < 0)return;

                item.addClass('active');
                item.siblings().removeClass('active');
                console.log('Selected year index:', year);
                // console.log(cb, year);
                execfunc(cb, year);

                center_col();

                
            });

            
            mf_timeline.find('.simulation-timeline-mf-col').unbind('click').click(function(){
                var item = $(this),
                    year = item.data('year');

                if(year == undefined)return;

                timeline.find('.simulation-timeline-col[data-year="'+year+'"]')
                        .addClass('active')
                        .siblings().removeClass('active');
                
                year--; if(year < 0)return;
                execfunc(cb, year, true);

                center_col();

            });
            
            // add 'select' function to select a specific col by year
            box.bind('selectTimeline', function(event, year){  
                                $(this).find('.simulation-timeline-col').removeClass('active')
                                        .filter('[data-year="'+year+'"]').addClass('active');   
                                        
                                        left_col();
                                  
                            });

            
            timeline_element.find('.year-left').unbind('click').click(function(){ timeline_element.animate({scrollLeft: timeline_element.scrollLeft() - timeline.width() * 0.2}, 200); });
            timeline_element.find('.year-right').unbind('click').click(function(){ timeline_element.animate({scrollLeft: timeline_element.scrollLeft() + timeline.width() * 0.2}, 200); });
            
            box.append(timeline_element);

            timeline_element.scroll(timeline_handle_year_arrows);
            timeline_handle_year_arrows();

            $(window).resize(function(){

                center_col();
                
            });
            
            // chart.append('<tr>'+negatives+'</tr>');

        }else{
            // console.log('Timeline already has children: ', timeline.children().length)
        }

        box.refresh =   function(){

                                // refresh data
                                if(!simulation_data)simulation_data = this.simulation_data;

                                // timeline values
                                setTimeout(function(){ 

                                    timeline.children().each(function(){
                                        
                                        var item = $(this),
                                            year = (item.data('year') || 1) - 1,
                                            c = simulation_data.calculated[year];

                                        var accu_ltim = c.ltim_acc || 0;

                                        var has_inv = false;
                                         for(const inv of simulation_data.calculated[year].is){
                                            if(inv.wthd == 1 && inv.type != "bbp"){
                                                has_inv = true; break; 
                                            }
                                        }
                                        
                                        setTimeout(function(){ set_value(item, (c.fa || 0), (c.fa_o || 0), objEmpty(c.deficit), accu_ltim, has_inv); }, 100,);
                                        // if (year === 0) {
                                        //     setTimeout(function(){ set_value(item, (c.sa || 0), (c.sa_o || 0), objEmpty(c.deficit), accu_ltim, has_inv); }, 100,);
                                        // } else {
                                        //     var previous_year = simulation_data.calculated[year - 1];
                                        //     var fa = previous_year.fa || 0;
                                        //     var fa_o = previous_year.fa_o || 0;
                                        //     setTimeout(function(){ set_value(item, (fa || 0), (fa_o || 0), objEmpty(c.deficit), accu_ltim, has_inv); }, 100,);
                                        // }
                                        
                                        
                                    });

                                }, 100);

                                //mf values
                                setTimeout(function(){ 
                                    var is_auto = simulation_data.simulation_rules.mf_auto == 1;
                                    var cash_reserve_threshold = simulation_data.simulation_rules.cash_reserve_threshold || 0;
                                    $(mf_timeline.children().get().reverse()).each(function(){
                                        
                                        var item = $(this),
                                            year = (item.data('year') || 1) - 1,
                                            mf = simulation_data.calculated[year].mf || 0,
                                            mf_o = simulation_data.calculated[year].mf_o || 0,
                                            prev_mf = year > 0 ? simulation_data.calculated[year-1].mf : simulation_data.model.monthly_fees;

                                            if(!is_auto && simulation_data.calculated[year].deficit.monthly_fees_auto == 1)
                                                is_auto = true;
       
                                            set_mf_value(item, mf, prev_mf, is_auto, mf_o, cash_reserve_threshold);
                                    });

                                }, 100);
                                // console.log(original_data.mf);
                                
                                
                        }
        
        // alert(initOnly);
        if(!initOnly){
            box.refresh();
        }

        
        $(window).resize(function(){
            mf_timeline_handle_year_arrows();
            timeline_handle_year_arrows();
            
        }).scroll();


        return box;

        
    };

    Simulation.prototype.init = function(model_id, callbacks){ 
        
        
            if(this.root.length == 0)return;
            //var table = this.root.find('.simulation-calculation-table tbody');
            

           

            if(!model_id)model_id = this.model_id;
            else { this.model_id = model_id; }


            // init tab navi
            $('.simulation-tab-nav[data-toggle="tab"]').on('shown.bs.tab', function (event) {
                    // event.target // newly activated tab
                    // event.relatedTarget // previous active tab
                    var tab = $(event.target).attr('href');
                    if(!tab)return;

                    // make tab full width
                    $(tab).closest('.simulation-options').toggleClass('simulation-options-full', $(tab).data('tab-size') == 'full');
                    $(tab).closest('.simulation-options').toggleClass('simulation-options-75', $(tab).data('tab-size') == '75');
                    $(tab).closest('.simulation-options').toggleClass('simulation-options-60', $(tab).data('tab-size') == '60');
                    $(tab).closest('.simulation-options').toggleClass('simulation-options-50', $(tab).data('tab-size') == '50');

            });

            var context = this;

            var first_run = true;

            this.simulation(model_id, function(data){
                
                console.log(data);

                
                context.simulation_data = data;
                

                var currency = '$';
                
                /* TABLE */

                
                

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
                                <div class="col-2 pb-2 border-bottom border-dark " data-id="%ID%"><b>Year</b><br><span class="year h6"></span><input type="number" class="app-input year d-none" data-id="%ID%" min="0" data-icon="check button w3-green apply d-none" /></div>   \
                                <div class="col-3 pb-2 border-bottom border-dark text-right" data-id="%ID%"><b>Expected Life</b><br><span class="redundancy h6"></span></div>   \
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

                            confirm('Are you sure you want to change the <b>Year</b> of this item, This will <b>Reset Managed Data <span class="w3-text-red"> '+(index)+'</span></b> ?', 
                                        "Warning", 
                                        null, function(action){
                                            if(action == 'ok'){

                                                edited = true;

                                                context.update(s, function(resp){
                                                    
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
                        line.find('span.year').html(parseInt(s.year) + 1);
                        line.find('input.year').val(parseInt(s.year) + 1)
                            .data('value', s.year)
                            .data('index', i)
                            .on('input', function(event){ 
                                                if(this.value < 1)this.value=1; 
                                                var val = parseInt(this.value) - 1, 
                                                    old = $(this).data('value'),
                                                    item_id = $(this).data('id'); 
                                                    
                                                if(val < 0)this.value=0; 
                                                

                                                $(this).siblings('i.apply').toggleClass('d-none', val == old);


                                            })
                            .on('keyup', function(event){ if(event.keyCode == 13)move_spending($(this).data('id')); });

                        line.find('.redundancy').html(s.redundancy);

                        line.find('.cost').html(formatMoney(s.cost, 0));
                        line.find('.del').toggleClass('d-none', !s.split_of).data('id', s.id)
                            .click(function(){
                                // console.log('SPLIT ');
                                var item = $(this), item_id = item.data('id');
                                if(!item_id)return;

                                confirm("Unsplitting this item will <b>Reset Simulation Managed Data</b> and add its <b>Cost</b> to the item from which it has been splitted, would you like to continue ?", 
                                        "Warning", 
                                        null, function(action){
                                            if(action == 'ok'){

                                                context.unsplit(item_id, function(resp){
                                                    
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
                        var percent = cell.hasClass('percent'); 


                        if(k == "year"){
                            cell.html(parseInt(context.simulation_data.model.fiscal_year) + i);
                            continue;
                        }


                        var value = decimals > 0 || k == "mf_inc_r" ? data[k] : Math.ceil(data[k]);
                        var formattedValue = formatMoney(value, decimals, undefined, undefined, '$');
                        

                        
                        if(k == "loan_pi" ){ if( value > 0) { cell.addClass('w3-purple'); } else{ formattedValue = ""; } }

                        // else if(k == "loan_i"){ if( value > 0) { formattedValue = formatMoney(value, decimals); cell.addClass('font-weight-bold w3-text-deep-orange'); }else formattedValue = '';  }
                        
                        else if(k == "loan_i") {
                            if (value > 0) {
                                formattedValue = formatMoney(value, decimals);
                                cell.addClass('font-weight-bold w3-text-deep-purple');
                            } else {
                                formattedValue = formatMoney(0, decimals); // Always show 0 if no value
                                cell.addClass('font-weight-bold w3-text-deep-purple');
                            }
                        }
                        
                        else if(k == "loan_y"){ if( value > 0) { formattedValue = formatMoney(-1 * value, decimals) ; cell.addClass('font-weight-bold w3-text-red'); }else formattedValue = '';  }

                        
                        else if(k == 'yc_inc'){ if(value > 0)cell.addClass('w3-amber'); }

                        else if(k == 'ir'){ formattedValue = formatNumber(value * 100, 2) + '%'; cell.addClass('font-weight-bold'); }
                        
                        else if(k == 'mf_new'){ cell.addClass('w3-text-'+(data.mf_inc_r  > 0 ? 'red' : 'green')); }
                        
                        
                        else if(k == 'mf_inc_r'){ formattedValue = '<i class="fa fa-'+(value > 0.01  ? 'chevron-up' : '')+' mr-1"></i>'+formatPercent(value, true, decimals); cell.addClass('w3-text-'+(value > 0.01  ? 'red' : 'green'));  }

                        else if(k == 'ta' && value < 0){ cell.addClass('w3-red');}
                        else if(k == 'ta_o' && value < 0){ cell.addClass('w3-red');}

                        else if(k == 'cp' && value < 0){ cell.addClass('w3-red');}
                        else if(k == 'cp_o' && value < 0){ cell.addClass('w3-red');}
                        
                        else if(k == 'fa' && value < 0){ cell.addClass('w3-red');}
                        else if(k == 'fa_o' && value < 0){ cell.addClass('w3-red');}
                                                
                        else if(k == 'inv_f' && value < 0){ cell.addClass('w3-red'); }
                                                
                        else if(k == 'inv_s'){ cell.addClass('w3-text-'+(value > 0 ? 'green' : 'red') ); }

                        
                        else if(k == 'ip'){ cell.addClass('w3-text-blue'); }
                                                
                        else if(k.indexOf('ltim') == 0 && !cell.hasClass('cell-purple')){ cell.addClass('w3-text-deep-purple'); }
                         
                        
                        else if(k == 'assess' && data.deficit.assessment){ 
                                        formattedValue = formatMoney(data.deficit.assessment, decimals);
                                        //cell.addClass('w3-green');
                        }

                        
                        else{ 
                            if(negative)cell.addClass('w3-text-red');  
                            else if(percent)formattedValue = formatPercent(value, false, decimals);
                        }

                        
                        
                        if(!cell.hasClass('empty'))cell.html(formattedValue);

                        if(k == 'sp'){
                            cell.append('<i class="fa fa-eye mr-2 p-1 rounded w3-blue float-left"></i>')
                                .addClass('link').data('year', data.year).click(function(){
                                
                                    var year = $(this).data('year');
                                    view_spendings(year, function(){ context.init(); });
                                //}
                                
                            });
                        }

                        

                    }

                }



                var print_table = function(){


                    var chart_series = {"remaining_amount":[], "cash":[], "total_expenses":[], "loan":[], "loan_payments":[], "collections":[], "ltim":[], "ltim_yoc":[], "ltim_i":[], "deficits":[]};
                    var chart_xaxis = [];
                    //chart_element.html('');
        
        
                    
                    var calculated = context.simulation_data.calculated;

                    
                    for(var i=0; i<calculated.length; i++){

                        c = calculated[i];
                        
    
                        // fill chart series first
                        chart_series.remaining_amount.push((c.fa).toFixed(2));
                        chart_series.total_expenses.push((-1 * c.tx).toFixed(2));
                        chart_series.loan.push((-1 * Math.abs(c.loan_t)) /* * loan_collection_ratio */ );
                        chart_series.loan_payments.push((-1 * Math.abs(c.loan_pay).toFixed(2)) /* * loan_collection_ratio */ );
                        chart_series.collections.push((c.yc).toFixed(2) /* * (1 - loan_collection_ratio) */);
                        // chart_series.cash.push((c.fa).toFixed(2) /* * (1 - loan_collection_ratio) */);
                        chart_series.cash.push((c.fa < 0 ? 0 : c.fa).toFixed(2) /* * (1 - loan_collection_ratio) */);
                        // chart_series.cash.push((c.ip).toFixed(2) /* * (1 - loan_collection_ratio) */);
                        chart_series.ltim.push((parseInt(c.ltim_acc) /* + parseInt(c.ltim_i)*/).toFixed(2));
                        chart_series.ltim_yoc.push((parseInt(c.ltim_yoc) /* + parseInt(c.ltim_i)*/).toFixed(2));
                        chart_series.deficits.push((parseInt(c.fa < 0 ? c.fa : 0) /* + parseInt(c.ltim_i)*/).toFixed(2));
                        //chart_series.ltim_i.push((/*parseInt(c.ltim) + */ parseInt(c.ltim_i)).toFixed(2));
                        //chart_series.inv_remaining.push((c.inv_f < 0 ? c.inv_f : 0).toFixed(2));
                        chart_xaxis.push(parseInt(context.simulation_data.model.fiscal_year) + parseInt(c.year));
    
                        
                    }

                    if(!context.table)
                        context.table = list_local(context.parent, {'rowFormatter': formatter}, null, calculated);
                    else
                        context.table.setData(calculated);
                    
        



                    /* CHART */
                    

                    context.chartInit();
                    //console.log("This wll load",chart_series);
                    var char_title = "Simulation";
                    var chart_initial_data = [  
                                                { name: 'Reserve Fund Cash', type: 'line', barMaxWidth:15, /* stack: 'amount', */
                                                            itemStyle: {color: '#2196F3'}, emphasis: { focus: 'series' }, data: chart_series.cash },
                                                
                                                { name: 'Remaining Amount', type: 'line', barMaxWidth:15, /* stack: 'amount', */
                                                            itemStyle: {color: '#00ff00'}, emphasis: { focus: 'series' }, data: chart_series.remaining_amount },

                                                
                                                { name: 'Total Expenses', type: 'line', barMaxWidth:15, /* stack: 'amount', */
                                                            itemStyle: {color: '#ff0000'}, emphasis: { focus: 'series' }, data: chart_series.total_expenses },

                                                
                                                { name: 'Deficits', type: 'bar', barMaxWidth:15, /* stack: 'amount', */
                                                            itemStyle: {color: '#ff0000'}, emphasis: { focus: 'series' }, data: chart_series.deficits },
                                                

                                                { name: 'Loan', type: 'bar', barMaxWidth:15,
                                                            itemStyle: {color: '#3f51b5'}, emphasis: { focus: 'series' }, data: chart_series.loan },
                                                
                                                { name: 'Loan Payments', type: 'bar', barMaxWidth:15,
                                                            itemStyle: {color: '#3f51b5'}, emphasis: { focus: 'series' }, data: chart_series.loan_payments },
                                                
                                                { name: 'Collections', type: 'bar', barMaxWidth:15, /* stack: 'Collections', */
                                                            itemStyle: {color: '#ffc107'}, emphasis: { focus: 'series' }, data: chart_series.collections }

                                            ];
                    

                    // LTIM
                    var ltim_yoc = context.simulation_data.simulation_rules.ltim_yoc || '';
                    chart_initial_data.push({ name: ltim_yoc+' Year(s) Of Projected Spendings', type: 'line', barMaxWidth:15, 
                                                itemStyle: {color: '#6a0f52'}, emphasis: { focus: 'series' }, data: chart_series.ltim_yoc });
                                                
                    chart_initial_data.push({ name: 'Projected LTIM Funds', type: 'line', barMaxWidth:15, stack: 'inv', 
                                                itemStyle: {color: '#9c27b0'}, emphasis: { focus: 'series' }, data: chart_series.ltim });

                                                            
                    var option = {
                        tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, 
                        legend: {top: 50, selected:{'Loan':true, 'Remaining Amount':false, 'Loan Payments':false,  'LTIM P+I':true}},
                        grid: { top: '25%', left: '3%', right: '4%', bottom: '3%', containLabel: true }, xAxis: [ { type: 'category', data: chart_xaxis} ],
                        yAxis: [ { type: 'value' } ], series: chart_initial_data
                        
                    };
            
                    

                    context.chart.setOption(option);
                    
                    /* ***** */

                    context.root.find('#simulation-table-export').unbind().click(function(){
                                  context.export();
                            });


                    // model edit btn
                    // model_edit_btns.toggleClass('d-none', !event.data);

                    
                    // show model name and association
                    $('.simulation-curr-model').html(ucwords(data.model.name, true));
                    $('.simulation-curr-association').html(ucwords(data.model.association_name, true));
                    $('.simulation-curr-model').parent().removeClass('invisible');
                    $('.simulation-version-options').removeClass('invisible');

                    // show main tour btn
                    $('.tour-start[data-tour-name="simulation_main"]').removeClass('d-none');


                    // auto start tour
                    if(first_run){
                        // guidedTour.start('simulation_main', true);
                        // first_run = false;
                    }


                    execfunc(callbacks, {'data': data});
                    
                }


                // save version
                context.root.find('.simulation-save-version').unbind().click(function(){
                    if(context.simulation_data.saved_versions > 0){
                        context.saveExistingVersion(data.model.id);
                    }else{
                        context.saveVersion(data.model.id);
                    }
                    

                });

                
                // available versions
                context.root.find('.simulation-get-version').unbind().click(function(){

                    var l = loading();

                    context.getAvailableVersions(data.model.id, function(data){
                        
                        
                        if(checkError(data, true)){
                            l.remove(); return;
                        }

                        // console.log(data);


                        var content = $('<div class="row m-0 p-0 mt-3 font-weight-bold h6"></div>');
                        var tmpl = '<div class="col text-capitalize name" data-id="%ID%"></div>  \
                                    <div class="col-auto font-weight-normal date" data-id="%ID%"></div>  \
                                    <div class="col-12 pt-2 text-capitalize user" data-id="%ID%" style="font-size: 0.85rem;"></div>  \
                                    <div class="w-100 pb-2" data-id="%ID%"></div>  \
                                    <div class="col-md " data-id="%ID%"><span class="w3-text-green link" data-id="%ID%" data-action="edit">Rename/Permissions</span></div>  \
                                    <div class="col-auto " data-id="%ID%"><span class="w3-text-red link" data-id="%ID%" data-action="delete">Delete</span></div>  \
                                    <div class="col-auto d-none" data-id="%ID%"><span class="w3-text-blue link" data-id="%ID%" data-action="view">View</span></div>  \
                                    <div class="col-auto " data-id="%ID%"><span class="w3-text-deep-purple link" data-id="%ID%" data-action="load">Load</span></div>  \
                                    <div class="w-100 border-bottom border-dark pb-1 mb-4" data-id="%ID%"></div>';


                        if(objEmpty(data)){

                            content.append('<div class="w-100 h6 py-3 text-center font-weight-bold">There Are No Available Versions ! </div>');

                        }else{

                            for(const d of data){
                                var item = $(replaceAll(tmpl, '%ID%', d.id));

                                content.append(item);

                                item.filter('.name').html(d.name + (d.note ? '<div class="pt-1 font-weight-normal">'+d.note : '')+'</div>');
                                item.filter('.date').html(formatDate(d.created_at, 'm-d-y'));
                                item.filter('.user').html('Created By <span class="w3-text-indigo">' + (d.owned == 1 ? 'You' : formatName(d.fn, d.ln)) + '</span>');

                                if(d.owned != 1){
                                    item.find('[data-action="edit"]').remove();
                                    item.find('[data-action="delete"]').parent().remove();
                                    if(d.can_view != 1)item.find('[data-action="view"]').parent().remove();
                                    if(d.can_load != 1)item.find('[data-action="load"]').parent().remove();
                                }

                            }

                        }

                        var modal = view_info(content, "Available Versions", 'md', true, true);


                        content.find('[data-action]').click(function(){

                            var action = $(this).data('action'),
                                version_id = $(this).data('id');


                            if(action == 'edit'){
                                var item = data.find((i)=>{return i.id == version_id;}),
                                    item_index = data.findIndex((i)=>{return i.id == version_id;});
                                if(item){
                                    context.editVersion(version_id, item, function(new_data){
                                        content.find('.name[data-id="'+version_id+'"]').html(new_data.name + (new_data.note ? '<div class="pt-2 font-weight-normal">'+new_data.note : '')+'</div>');
                                        data[item_index].name = new_data.name;
                                        data[item_index].note = new_data.note;
                                        data[item_index].can_view = new_data.can_view;
                                        data[item_index].can_load = new_data.can_load;
                                    });
                                }
                            }
                            
                            else if(action == 'delete'){
                                context.deleteVersion(version_id, function(resp){
                                    if(checkError(resp, true))return;

                                    content.find('[data-id="'+version_id+'"]').remove();
                                });
                            }

                            else if(action == 'load'){
                                context.loadVersion(version_id, function(resp){
                                    if(checkError(resp, true))return;

                                    modal.close();
                                    context.init();
                                });
                            }

                        });

                        l.remove();

                    }, function(){ l.remove(); });
                    

                });

                // show investment graph
                context.root.find('.simulation-inv-graph-btn').unbind().click(function(){

                //context.compare(context.model_id, function(resp){
                    
                        var inv_graph_modal = $('#simultaion_inv_modal');


                        var inv_graph = echarts.init(inv_graph_modal.find('.simulation-inv-chart')[0]);

                        
                        var data = context.simulation_data.calculated || {};
                        var available_inv = context.simulation_data.inv_strategies || {};

                        var chart_series = {"LTIM":[]};
                        var chart_xaxis = [];

                        var fiscal_year = parseInt(context.simulation_data.model.fiscal_year);

                        
                        for(var i=0; i<data.length; i++){

                            // add investments
                            invs = data[i].is || [];
                            if(!invs.length == 0){

                                for(const inv of invs){
                                    // if compounding, skip liquidated year
                                    if(inv.hold && inv.wthd)continue;

                                    
                                    if(!chart_series[inv.type_name]){
                                        chart_series[inv.type_name] = [];
                                        chart_series[inv.type_name][i] = floatDecimals(inv.total_pi);
                                        continue;
                                    }

                                    if(!chart_series[inv.type_name][i])chart_series[inv.type_name][i] = floatDecimals(inv.total_pi);
                                    else chart_series[inv.type_name][i] = floatDecimals(chart_series[inv.type_name][i] + inv.total_pi)
                                    
                                }
                            }

                            // add LTIM
                            chart_series["LTIM"].push(data[i].ltim_acc.toFixed(2));

                            // add xaxis
                            chart_xaxis.push(fiscal_year + i);
                            
                            
                        }
                        
                        var chart_initial_data = [];

                        for(const group in chart_series){

                            if(group == "LTIM")continue;

                            // fill missing data
                            for(var i=0; i<data.length; i++){
                                if(!chart_series[group][i])chart_series[group][i] = 0;
                            }

                            chart_initial_data.push({   name: group, type: 'line', barMaxWidth:15, /*stack: 'Collections',*/
                                                        emphasis: { focus: 'series' }, data: chart_series[group] });
                        }

                        
                        chart_initial_data.push({   name: "LTIM", type: 'line', barMaxWidth:15, /*stack: 'Collections',*/
                                                    itemStyle: {color: '#9c27b0'}, emphasis: { focus: 'series' }, data: chart_series["LTIM"] });

                        // chart_initial_data.push({ name: 'Collections', type: 'bar', barMaxWidth:15, /*stack: 'Collections',*/
                        //                                 itemStyle: {color: '#ffc107'}, emphasis: { focus: 'series' }, data: chart_series.collections_m });

                        // chart_initial_data.push({ name: 'Deficit', type: 'bar', barMaxWidth:15, /*stack: 'Collections',*/
                        //                                 itemStyle: {color: '#ff0000'}, emphasis: { focus: 'series' }, data: chart_series.deficit_m });

                                            
                        // chart_initial_data.push({ name: 'Cash Balance', type: 'line', barMaxWidth:15, /*stack: 'Collections',*/
                        //                                 itemStyle: {color: '#00ff00'}, emphasis: { focus: 'series' }, data: chart_series.cash_m });

                        
                        var option = {
                            tooltip: { trigger: 'item', axisPointer: { type:  'cross', label: { backgroundColor: '#267277' } } }, legend: {top: 50},
                            grid: { top: '20%', left: '5%', right: '5%', bottom: '0%', containLabel: true }, xAxis: [ { type: 'category', data: chart_xaxis} ],
                            yAxis: [ { type: 'value' } ], series: chart_initial_data,
                                                                    
                            dataZoom: [ { type: 'inside', start: 0 }, { start: 0 } ],
                            
                        };
                        

                        option && inv_graph.setOption(option);
                        
                        $(window).on('resize', function(){
                            if(inv_graph != null && inv_graph != undefined){
                                inv_graph.resize();
                            }
                        });



                        inv_graph_modal.modal('show');
                        setTimeout(inv_graph.resize, 200);


                //});

            });
                
                

                context.eraseDeficit(0, 
                    function(){ 
                        print_table(); 
                }, false); 
                
                /* ***** */


                print_table();

                context.print_summary();
                
                

                

                

                  

                

            }, function(){ execfunc(callbacks, {'data': null}); }, true);
    };

    Simulation.prototype.print_summary = function(){

        var context = this;
        var data = context.simulation_data || {};
        var starting_year = parseInt(data.model.fiscal_year || 0);


        context.summary = { "deficit":0, "years":0, "deficit_o":0, "years_o":0, "deficit_p":0, "last":'-', 
                            "min_mf":data.calculated[0].mf, "max_mf":data.calculated[0].mf, 
                            "loan":0, "assess":0,
                            "inv":0, "inv_ne":0, "inv_r":0,
                            "ltim":0, "ltim_ne":0, "ltim_r":0};

        var last_mf_inc = -1, prev_mf = data.calculated[0].mf;

        var prev_ltim = 0, prev_ltim_ne = 0;

        for(const c of data.calculated){
            
            context.summary.deficit += c.fa < 0 ? c.fa : 0;
            context.summary.years += c.fa < 0 ? 1 : 0;
            
            context.summary.deficit_o += c.fa_o < 0 ? c.fa_o : 0;
            context.summary.years_o += c.fa_o < 0 ? 1 : 0;

            if(context.summary.min_mf > c.mf)context.summary.min_mf = c.mf;
            if(context.summary.max_mf < c.mf)context.summary.max_mf = c.mf;
            context.summary.loan += c.deficit && c.deficit.loan_amount ? parseFloat(c.deficit.loan_amount) : 0;
            context.summary.assess += c.deficit && c.deficit.assessment ? parseFloat(c.deficit.assessment) : 0;

            if( (c.mf - prev_mf)/prev_mf  > 0)last_mf_inc = parseInt(c.year);
            prev_mf = c.mf;

            
            context.summary.inv += c.inv;
            context.summary.inv_ne += c.ne + c.pne;
            
            context.summary.ltim += c.ltim_p;
            context.summary.ltim_ne += c.ltim_ne + c.ltim_acc_ne;
        }

        
        context.summary.inv_r = context.summary.inv_ne * 100 / context.summary.inv;
        context.summary.ltim_r = context.summary.ltim_ne * 100 / context.summary.ltim;

        
        context.summary.deficit_p = Math.floor(context.summary.deficit_o - context.summary.deficit)/context.summary.deficit_o;
        context.summary.last = last_mf_inc >= 0 ?  last_mf_inc + starting_year : '-';


        $('.summary-value').each(function(){
            var element = $(this);
                key = element.data('key');

            if(!key)return;
            var value = objectGetDataFromPath(context.summary, key);

            if(value == null)return;

            var isPercent = element.hasClass('percent'),
                isNumber = element.hasClass('number'),
                isYear = element.hasClass('year'),
                decimals = element.hasClass('decimal') ? 2 : 0;

            if(isPercent)value *= 100;

            if(isNumber || isPercent) value = formatNumber(Math.floor(value), decimals);
            else if(!isYear) value = formatMoney(value, decimals);

            element.html(value);
        })

    }
       

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
    
    
    Simulation.prototype.simulation =   function(id, cb, errCb, loader = false){
                        
        var l = loading();
        ajax_get_no_loading("modules/simulation/app/simulation.php?cmd=get", {model_id:id, erase_deficit: this.erase_deficit},
                function(resp){ l.remove(); if(!checkError(resp, true)){ execfunc(cb, resp); } else { execfunc(errCb, []); } }, function(resp){ execfunc(errCb, []); error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };

    
    
    Simulation.prototype.saveVersion =   function(model_id, cb, errCb){
        
        if(!model_id)return false;

        var modal = view_modal('<div class="row m-0 p-0 mt-3">  \
                                    <div class="col-12 mb-2"><input data-key="name" class="app-input editor-input" type="text" data-field="Version Name" required /></div>    \
                                    <div class="col-12 mb-2 "><textarea data-key="note" class="app-input editor-input" data-field="Note"></textarea></div>    \
                                    <div class="col-12 mb-1 d-none"><input data-key="can_view" type="checkbox" class="app-check editor-input h6" data-field="Can be <b>Viewed</b> by other Users ?" /></div>    \
                                    <div class="col-12"><input data-key="can_load" type="checkbox" class="app-check editor-input h6" data-field="Can be <b>Loaded</b> by other Users ?" /></div>    \
                                 </div>', 'Save Version', 
                                 
                                 function(e){
                                    if(e.action != 'submit')return;

                                    var data = e.value;
                                    data['model_id'] = model_id;

                                    ajax_post("modules/simulation/app/simulation.php?cmd=save_version", data,
                                                function(resp){
                                                    
                                                    if(checkError(data, true)){
                                                        return;
                                                    }

                                                    success('Version Saved !', '', default_success_alert_timeout);
                                                    modal.close();

                                                    execfunc(cb);

                                                }, function(resp){ execfunc(errCb, []); error('Error while Saving Version !'); console.log(resp.responseText); });
        
                                    
                                    

                    }, true, 'md');


        return true;
    };

    

    
    
    Simulation.prototype.saveExistingVersion =   function(model_id, cb, errCb){
        
        if(!model_id)return false;

        var context = this;

        confirm('<b>Save as New Version</b> or <b>Save to Existing</b> ?', 'Saving', 
                                [{action:'cancel'}, {action:'new', text:'Save As New', classes:'btn-success'}, {action:'override', text:'Save to Existing', classes:'w3-deep-purple'}],
                                
                                function(action){

                                    if(action == 'new'){
                                        context.saveVersion(model_id);
                                    }

                                    else if(action == 'override'){
                                        
                                        var l = loading();
                                        context.getVersions(model_id, function(list){
                                            
                                            if(checkError(list, true)){
                                                l.remove(); return;
                                            }

                                            var content = $('<div class="w-100 p-4 "><select data-key="version_id" class="app-input editor-input" data-field="Existing Versions" required></select></div>');

                                            for(const d of list){
                                                content.find('select').append('<option value="'+d.id+'">'+ucwords(d.name, true)+'</option>');
                                            }

                                            var modal = view_modal(content, '', function(e){
                                                if(e.action != 'submit')return;
                                                console.log(e.value);

            
                                                ajax_post("modules/simulation/app/simulation.php?cmd=save_version", {"model_id":model_id, "version_id":e.value.version_id},
                                                            function(resp){
                                                                
                                                                if(checkError(resp, true)){
                                                                    return;
                                                                }
            
                                                                success('Version Saved !', '', default_success_alert_timeout);
                                                                modal.close();
            
                                                                execfunc(cb);
            
                                                            }, function(resp){ execfunc(errCb, []); error('Error while Saving Version !'); console.log(resp.responseText); });
                    



                                            }, true, 'md');

                                            l.remove();
                                        
                                        });
                                    }

                                });


        return true;
    };
    
    Simulation.prototype.editVersion =   function(version_id, data = {}, cb, errCb){
        
        if(!version_id)return false;

        var modal = view_modal('<div class="row m-0 p-0 mt-3">  \
                                    <div class="col-12 mb-2"><input data-key="name" class="app-input editor-input" type="text" data-field="Version Name" required /></div>    \
                                    <div class="col-12 mb-2 "><textarea data-key="note" class="app-input editor-input" data-field="Note"></textarea></div>    \
                                    <div class="col-12 mb-1 d-none"><input data-key="can_view" type="checkbox" class="app-check editor-input h6" data-field="Can be <b>Viewed</b> by other Users ?" /></div>    \
                                    <div class="col-12"><input data-key="can_load" type="checkbox" class="app-check editor-input h6" data-field="Can be <b>Loaded</b> by other Users ?" /></div>    \
                                 </div>', 'Save Version', 
                                 
                                 function(e){
                                    if(e.action != 'submit')return;

                                    var data = e.value;
                                        data['version_id'] = version_id;

                                    ajax_post("modules/simulation/app/simulation.php?cmd=edit_version", data,
                                                function(resp){
                                                    
                                                    if(checkError(data, true)){
                                                        return;
                                                    }

                                                    success('Version Saved !', '', default_success_alert_timeout);
                                                    modal.close();

                                                    execfunc(cb, data);

                                                }, function(resp){ execfunc(errCb, []); error('Error while Saving Version !'); console.log(resp.responseText); });
        
                                    
                                    

                    }, true, 'md');

        fill_editor(modal, data);

        return true;
    };

    
    
    Simulation.prototype.getVersions =   function(model_id, cb, errCb){
        
        if(!model_id)return false;

        ajax_post_no_loading("modules/simulation/app/simulation.php?cmd=get_version", {"model_id":model_id, "owned":1},
                                cb, function(resp){ execfunc(errCb, []); error('Error while Getting Versions !'); console.log(resp.responseText); });
        
        return true;
    };

    
    
    Simulation.prototype.getAvailableVersions =   function(model_id, cb, errCb){
        
        if(!model_id)return false;

        ajax_post_no_loading("modules/simulation/app/simulation.php?cmd=get_version", {"model_id":model_id},
                                cb, function(resp){ execfunc(errCb, []); error('Error while Getting Versions !'); console.log(resp.responseText); });
        
        return true;
    };

    
    
    Simulation.prototype.deleteVersion =   function(version_id, cb, errCb){
        
        if(!version_id)return false;

        confirm('Are you sure you want to <b>Delete This Version</b>!<br><u><b>Warning: </b></u> This Action is Irreversible !', '', null, 
                    function(action){
                            if(action != 'ok')return;
                            
                            ajax_post("modules/simulation/app/simulation.php?cmd=delete_version", {"version_id":version_id},
                                                    cb, function(resp){ execfunc(errCb, []); error('Error while Deleting Version !'); console.log(resp.responseText); });

                    });

        
        return true;
    };

    
    
    Simulation.prototype.loadVersion =   function(version_id, cb, errCb){
        
        if(!version_id)return false;

        confirm('Are you sure you want to <b>Load This Version</b>!<br><u><b>Warning: </b></u> If you didn\'t <b>Save the changes made to the current Simulation</b>, please do so, as loading a version will override it !', '', null, 
                    function(action){
                            if(action != 'ok')return;
                            
                            ajax_post("modules/simulation/app/simulation.php?cmd=load_version", {"version_id":version_id},
                                                    cb, function(resp){ execfunc(errCb, []); error('Error while Loading Version !'); console.log(resp.responseText); });

                    });

        
        return true;
    };
    
    
    
    Simulation.prototype.compare =   function(id, cb, errCb){
                        
        var context = this;

        ajax_get("modules/simulation/app/simulation.php?cmd=compare", { model_id:id },

                function(resp){ 
                    if(!checkError(resp, true)){ 
                        execfunc(cb, resp); 
                    } else { 
                        // setTimeout(function(){ 
                            execfunc(errCb, []); 
                        // }, context.delay); 
                    }

                },  
                function(resp){ execfunc(errCb, []); error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    

    Simulation.prototype.updateRules = function(model_id, rules, cb){
        
            if(!model_id){ return false; }

            if (!rules.inf_rate) {
                rules.inf_rate = 0;
            }
            
            if (!rules.cash_reserve_threshold) {
                rules.cash_reserve_threshold = 0;
            }
            
            if (!rules.cushion_fund) {
                rules.cushion_fund = 0;
            }
            
            ajax_post("modules/simulation/app/simulation.php?cmd=rule", {"model_id":model_id, "rules": rules},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while Updating General Rules !'); console.log(resp.responseText); });
    
    };

    
    Simulation.prototype.deficit = function(model_id, year, deficit_data, cb, splits, unsplit){
        
                                    if(!model_id)model_id = this._model;


                                    var params = {"model_id":model_id, "year":year};

                                    if(deficit_data != undefined && deficit_data != null)params['deficit'] = objEmpty(deficit_data) ? false : deficit_data;
                                    if(splits)params['splits']=splits;
                                    if(unsplit)params['unsplit']=unsplit;

                                    console.log(year, deficit_data);
                                    
                                    ajax_post("modules/simulation/app/simulation.php?cmd=deficit", params,
                                        function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while Saving Deficit !'); console.log(resp.responseText); });
                            
                            }
         

    Simulation.prototype.unsplit = function(split_id, cb){
                                    console.log('UNSPLIT TEST');
                                    console.log(split_id);
                                    ajax_post("modules/simulation/app/simulation.php?cmd=unsplit", {"id": split_id},
                                        function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while Unsplitting !'); console.log(resp.responseText); });
                            
                            }   

    Simulation.prototype.update = function(item, cb){
        
                                    ajax_post("modules/simulation/app/simulation.php?cmd=update", item,
                                        function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while Unsplitting !'); console.log(resp.responseText); });
                            
                            }   

    Simulation.prototype.reset = function(model_id, cb, year, fromYear, which = 'current'){
        
                                    if(!model_id)model_id = this._model;

                                    var params = {"model_id":model_id, "which":which};

                                    if(year != undefined && year != null && !fromYear)params['year'] = year;
                                    else if(fromYear != undefined && fromYear != null && fromYear)params['from_year'] = fromYear;

                                    ajax_post("modules/simulation/app/simulation.php?cmd=reset", params,
                                        function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while Unsplitting !'); console.log(resp.responseText); });
                            
                            } 

    
    Simulation.prototype.getSettings =   function(cb){
        

        ajax_post("modules/simulation/app/simulation.php?cmd=get_settings", {},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while getting settings !'); console.log(resp.responseText); });
        
        return true;
    };

    Simulation.prototype.setSettings =   function(settings, cb){
        

        ajax_post("modules/simulation/app/simulation.php?cmd=set_settings", settings,
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while saving settings !'); console.log(resp.responseText); });
        
        return true;
    };
    
    Simulation.prototype.export =   function(){
        

        if(!this.table.table)return;

        var model = this.simulation_data.model;
        var name = model.name + (model.fiscal_year ? " ("+model.fiscal_year+")" : "") + " - Simulation Data.xlsx";
        this.table.table.download("xlsx", name, {"sheetname":"Data"});
        
    };

    
    Simulation.prototype.delete =   function(){
                        
        this.table.delete();
        
    };

    


    Simulation.prototype.showTable =   function (){
        $('#simulation_table_modal').modal("show");
        this.table.table.redraw(true);
    }
    
    Simulation.prototype.showGraph =   function (){
        $('#simultaion_graph_modal').modal("show");
        if(this.chart)this.chart.resize();
    }

    Simulation.prototype.toggleColumns =   function (){
        if(this.table)this.table.toggleColumns();
    }


    Simulation.prototype.tour_guide =   {   "simulation_main": {
                                                        "init":{
                                                            started: function(){ },
                                                            finished: function(){ },
                                                            cancelled: function(){ },
                                                            config: {overlay: true}
                                                        }, 
                                                        "steps":[   
                                                                    // 0
                                                                    {textHTML:true},

                                                                    // 1
                                                                    'Click here to change the <b>Model</b> you\'re currently working on.',
                                                                    
                                                                    // 2
                                                                    {   text:"Choose the <b>Association</b> to get all it's available <b>Models</b>", attachTo: "<.app-input-group", skipOn: "d-none",
                                                                        before: function(element){
                                                                            $(element).closest('#simulation-change-model-box').fadeIn('fast');
                                                                        } 
                                                                    },

                                                                    // 3
                                                                    {   text:"Choose the <b>Model</b> to simulate", attachTo: "<.app-input-group", skipOn: "d-none",
                                                                        before: function(element, step, tour){
                                                                            // toggle model box if hidden
                                                                            $(element).closest('#simulation-change-model-box').fadeIn('fast');
                                                                            
                                                                        },
                                                                        after: function(element){
                                                                            // hide model
                                                                            $(element).closest('#simulation-change-model-box').fadeOut('fast');
                                                                            
                                                                        }
                                                                    },

                                                                    // 4
                                                                    "This is the name of the <b>Association</b> and <b>Model</b> you're currently working on",

                                                                    // 5
                                                                    "This indicates the <b>Mode</b> you're currently working on",
                                                                    
                                                                    // 6
                                                                    "This indicates the <b>Mode</b> you're currently working on",
                                                                    
                                                                    // 7
                                                                    "Click here to <b>Reset the whole simulation and start all over</b>",
                                                                    
                                                                    // 8
                                                                    "Click here to <b>Only Reset the Current Working Mode</b>",
                                                                    
                                                                    // 9
                                                                    "Click here to <b>Compare results of both Client Investment Strategy And LTIM Mode</b>",
                                                                    
                                                                    // 10
                                                                    "Click here to show the <b>Simulation Settings</b> of the <b>Current Working Mode</b>",
                                                                    
                                                                    // 11
                                                                    "Click here to view the <b>Data Table</b> of the <b>Current Working Mode</b>",
                                                                    
                                                                    // 12
                                                                    "Click here to show the <b>Graph</b> of the <b>Current Working Mode</b>",
                                                                    
                                                                    // 13
                                                                    "Click here to show the <b>Remaining Amounts</b> of the <b>Current Working Mode</b>",
                                                                    
                                                                    // 14
                                                                    "This is the <b>Monthly Fees Collection Timeline</b> where you can view the evolution of the <b>Monthly fees Collection</b> throughout the period of the simulation. The <b>Yellow</b> bars are the Monthly Fees Collection for the <b>Client Investment Strategy Mode</b> and the <b>Purple</b> bars are those of the <b>LTIM Mode</b>",
                                                                    
                                                                    // 15
                                                                    "Click here to show the <b>Comparing Graph</b> of the <b>Both Working Modes</b>",
                                                                    
                                                                    // 16
                                                                    "This is the <b>Simulation Timeline</b> where you can view the <b>Deficit (Red Lollipops) and Surplus (Green Lollipops)</b> throughout the period of the simulation. You can manage each year by clicking on the corresponding lollipop, and use different tools available. <b>Years</b> with <b>Orange</b> background are managed.",
                                                                    

                                                                ] 
                                                    }
                                        };


    

    Simulation.prototype.tooltips = {
                                        // Association Models
                                        "0": "Click here to choose the <b>Association's Model</b> you want to simulate.",
                                        
                                        // Top Buttons
                                        "1": "Click here to <b>Reset</b> the entire simulation to default. This will remove all your changes.",
                                        "2": "Click here to <b>Only Reset</b> changes made to the <b>Client's Strategy Mode</b> to default.",
                                        "3": "Click here to <b>Only Reset</b> changes made to the <b>LTIM Mode</b> to default.",
                                        "4": "Click here for <b>Comparative View</b> of data from both <b>Client's Strategy</b> and <b>LTIM</b> modes.",
                                        "5": "Click here to change <b>Simulation Settings</b> of the model you're currently working on.",
                                        "6": "Click here to view data of the mode you're currently working on as a <b>Table</b>.",
                                        "7": "Click here to view data of the mode you're currently working on as a <b>Graph</b>",
                                        "8": "Click here to have a quick view of all the <b>Years'</b> remaining amounts",
                                        "75": "Click here to view an <b>Explanatory Report</b> ",

                                        
                                        // MF timeline
                                        "9": "Hover over the bars to see the <b>Monthly Fees by Year</b> as an amount in USD, and percentage increase to previous year.<br>the <b>Yellow Bars</b> for <b>Client's Investment Mode</b>, and the <b>Purple Bars</b> for <b>LTIM Mode</b> for the same year.",
                                        "10": "Click here for <b>Comparative View</b> of data from both <b>Client's Strategy</b> and <b>LTIM</b> modes as a graph. ",
                                        
                                        // Toolbox
                                        "11": "Click <b>Toolbox</b> button to view available options to manage the <b>Selected Year</b>.",

                                        // Toolbox Nav
                                        "12": "Click here to view the <b>Summary Data</b> of the selected year",
                                        "13": "Click here to view and manage <b>All of the Planned Expenditures<b> for the selected year. Here you can split the costs between years, or move it to a different year",
                                        "14": "Click here to view and manage <b>Your Investment Strategy</b>.",
                                        "15": "Click here to view and manage <b>LTIM Mode</b>.",
                                        "16": "Click here to view and manage <b>Monthly Fees</b>",
                                        "17": "Click here to view and manage <b>Loans<b>",
                                        "18": "Click here to view and manage <b>Immediate Assessments</b>",
                                        "19": "Click here to view and manage <b>Simulation settings</b>",
                                        
                                        // Simulation Settings
                                        "20": "When activated, will keep the <b>Your Investment Stragery<b> for all future years",
                                        "21": "Maximum allowed % to increase Monthly fees, used in <b>Auto-calc Monthly fees<b>",
                                        "22": "When activated, will include the <b>Loss in Purchasing Power<b> in Calculations",
                                        "23": "",
                                        "24": "Which State's <b>LTIM Investment strategy</b> to use in calculculations",
                                        "25": "In LTIM the system calculates any surplus amouht to invest in the LTIM method. If you choose less than 100% the funds will be invested in your investment strategy",
                                        "26": "When activated, the system will automatically use <b>Available Accumulated Funds in LTIM</b> to cover any deficit.",
                                        "27": "When activated, will only cover a year's deficit when it's value is lower than 80% of the vailable LTIM Funds.",
                                        "28": "The number of years of expenditures you would like the system to concider in order to calculate the available surplus to invest in LTIM.",
                                        "75": "<b>Inflatin Rate</b> in % used in the <b>Simulation</b>",
                                        "76": "<b>Number of Years</b> for which the <b>Simulation Data Are Calculated</b>",
                                        
                                        // Toolbox Year Summary
                                        "29": "Total monthly fees collected in this year",
                                        "30": "Loan Principal taken this year",
                                        "31": "Immediate Assessment taken this year",
                                        "32": "Total amount available in bank to be invested with Your Investment Strategy. Equals to year's starting amount, plus annual monthly fees collected, minus any amount invested in LTIM",
                                        "33": "Projected earning of <b>Your Investment Strategy</b> from the available <b>Reserve Fund Cash</b>",
                                        "34": "Total Projected Accumulated LTIM Funds available in this year, to be either used this year, or re-invested in LTIM",
                                        "35": "The amount invested in LTIM from this year's available surplus",
                                        "36": "Projected LTIM earning from the <b>New Amount Allocated to LTIM</b>",
                                        "37": "The corrsponding % of this year's available surplus invested in LTIM",
                                        "38": "Which State's investment strategy is currently being used in LTIM",
                                        "39": "The planned expenditures for this year",
                                        "40": "The calculated loss in purchasing power in this year",
                                        "41": "The used loss in purchasing power rate",
                                        "42": "The loan's principal + intrest paid this year",
                                        "43": "the remaining loan balance to be paid-off",
                                        "44": "This year's Remaining Deficit",
                                        "45": "This year's Remaining Surplus",

                                        // Toolbox Investment Strategy
                                        "46": "Intrest Rate in % to be used to calculate the projected earning of this strategy. Input '0' if not used",
                                        "47": "% of the Wallet used to determine the amount in USD to be used to calculate the projected earnings of this strategy. Input '0' if not used",
                                        "48": "The calculated amount in USD from the given % of Wallet used to calculate the projected earnings of this strategy.",

                                        // Toolbox Change Priorities
                                        "49": "Year in which this expense will occur",
                                        "50": "The next occurence of this expense in Number Of Years. <br> E.g: Expected Life of 10 means the next time this expense will occur is in 10 years from the current year",
                                        "51": "The cost of the expense in USD",

                                        // Toolbox LTIM
                                        "52": "This year's total available savings to be used to cover it's expenditures. It's the sum of <b>Starting Amount</b>, <b>Total Annual Monthly Fees Collections</b> and <b>Immediate Assessment</b>",
                                        "53": "Total expenditure amount needed to determine if any surplus will be available to invest in LTIM",
                                        "54": "Calculated available surplus you can invest in LTIM",
                                        "55": "Which State's Investment strategy is currently used by LTIM",
                                        "56": "Click Here to see how LTIM invests your funds",
                                        "57": "% Of the available surplus to be invested in LTIM",
                                        "58": "Amount invested this year in LTIM",
                                        "59": "Projected LTIM earnings from this year's invested amount",
                                        "60": "Projected accumulated LTIM funds available this year from LTIM investments from previous years",
                                        "61": "Projected compound LTIM earning from LTIM investments from previous years",
                                        "62": "Total Projected LTIM Funds available this year that can be used",
                                        "63": "Input the amount to be withdrawn from total Projected available LTIM funds that can be used this year",
                                        "64": "Amount withdrawn this year and used from total Projected available LTIM funds",

                                        // Toolbox Adjust MF
                                        "65": "The actual Monthly Fee value used this year",
                                        "66": "Input the desired Monthly Fee value to be used. This new value will be used onto all future years",
                                        "67": "The default Monthly Fee value of the Model",
                                        "68": "Enable the <b>Optimal Monthly Fee</b> option to let the system automatically calculate the necessary Monhtly fees starting previous years. <br>This option will also optimize any previously available deficit, and will override all previous manually inputted Monthly Fees",

                                        // Toolbox Loan
                                        "69": "Input the amount to be taken as a Loan in USD",
                                        "70": "Intrest rate in % to be applied to the Loan taken",
                                        "71": "The number of years for which the loan will be repaid",
                                        "72": "Total calculated Principal + Intrests of the Loan taken",
                                        "73": "Total calculated Intrests of the Loan take",

                                        // Toolbox Assessment
                                        "74": "Input the amount of the Assessment"

                                    }

    return Simulation;

}();