function init_simulation(e){

    if(!e)return;
	
    var simulation = new Simulation(e);
    e = $(e);
    
    init_ui(e);

    var chart = echarts.init(e.find('.simulation-chart')[0]);

    var association_select = e.find('select.associations'),
        model_select = e.find('select.models');

    var model_edit_btns = e.find('.model-edit');
    var model = new Models(), model_items = new ModelItems();


    var model_info = e.find('.model-info');
        model_info.reset = function(){  };


    // get model info Y on init then toggle d-none to make it FIXED TOP
    var model_info_y = Math.floor(model_info[0].getBoundingClientRect().y),
        model_info_l = Math.floor(model_info[0].getBoundingClientRect().x),
        model_info_r = $(window).width() - model_info_l - Math.floor(model_info[0].getBoundingClientRect().width),
        model_info_height = model_info.height();
        model_info.addClass('d-none').removeClass('invisible');

        
    $("html, body").scrollTop(0);

    

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

    /*
    
    $(window).scroll(function() {

        //return;

        if(model_info.hasClass('d-none'))return;
        
        var scrollTop = Math.ceil($(window).scrollTop() - model_info_y);
        
        if(!model_info.hasClass('position-fixed') && scrollTop > 0){
            model_info.addClass('position-fixed pt-4  px-1').css({'top': '0', 'left':model_info_l, 'right':model_info_r, 'z-index':'200'});
            model_info.parent().css('padding-top', model_info_height);
            //start_at_scrollTop = Math.ceil($(window).scrollTop()) - scrollTop;
        }else if(model_info.hasClass('position-fixed') && scrollTop < 0){
            //start_at_scrollTop = 0;
            model_info.removeClass('position-fixed pt-4 px-1').css({'z-index':'0'});
            model_info.parent().css('padding-top', '');
        }else if(model_info.hasClass('position-fixed')){
            //model_info.css('top', scrollTop + 42);  
        }      

    });
    */

    return simulation;

}


var Simulation = function(){

    function Simulation(parent){

        this.parent = parent;
        this.root = $(parent);


        this.interface = null;
        this.simulation_data = null;

        this.take_loans = {};

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

        
        console.log(this.table);

        if(this.table)this.table.setData([]);


        this.chartInit();

        this.take_loans = {};
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
    
    
    Simulation.prototype.takeLoan = function(at, amount = 0, cb){

        
        if(at == undefined)return false; 
        
        var index = "y_"+at;
        var loan = this.take_loans[index];

        var bank_rate = !objEmpty(loan) ? this.take_loans[index].br : parseFloat(this.simulation_data.model.bank_rate)/100.0;
        var loan_years = !objEmpty(loan) ? this.take_loans[index].ly : parseFloat(this.simulation_data.model.loan_years);
        var ratio = !objEmpty(loan) ? this.take_loans[index].r : 1;

        var content = $('<div class="row m-0 mt-2 pt-3"> \
                            <div class="col-12 mt-2 border-bottom border-dark"><div class="app-slider editor-slider ui-orange" data-key="r" data-range="min" data-min="1" data-max="100" data-format="?%" data-value="'+(ratio*100)+'"  ></div><span class="h6">Take Loan for : <span class="amount ml-2 h5 font-weight-bold w3-text-deep-purple"></span> &nbsp;&nbsp;of&nbsp;&nbsp; <span class=" h5 font-weight-bold text-primary">'+formatMoney(Math.abs(amount), 0, null, null, '$')+'</span></span></div>  \
                            <div class="col-sm-6 mt-4"><input type="number" step="0.01" min="0" class="app-input editor-input" data-field="Bank Rate" data-key="br" ></div>  \
                            <div class="col-sm-6 mt-4"><input type="number" min="0" class="app-input editor-input" data-field="Loan Years" data-key="ly" ></div>  \
                        </div>'); 

        content.find('[data-key="br"]').val(bank_rate * 100);
        content.find('[data-key="ly"]').val(loan_years);

        var amount_label = content.find('span.amount'); 
            amount_label.html(formatMoney(ratio * Math.abs(amount), 0, null, null, '$'));


        var context = this;

        var modal = view_modal(content, 'Take Loan', function(action){

                                                if(action.action != "submit")return;


                                                if(!action.value.br || action.value.br < 0){
                                                    error('Bank Rate Invalid !'); return;
                                                }
                                                if(!action.value.ly || action.value.ly == 0){
                                                    error('Loan Years Invalid !'); return;
                                                }
                                                if(!action.value.r || action.value.r == 0){
                                                    error('Ratio Invalid !'); return;
                                                }

                                                context.take_loans[index] = {br: parseFloat(action.value.br)/100.0, ly: parseFloat(action.value.ly), r: action.value.r/100.0 };

                                                execfunc(cb);

                                                modal.close();

                                        }, true, null, true);

        //
        ui_slider(content.find('.app-slider'), function(event, value){ 

            if(event.type == "slider"){  
                
                amount_label.html(formatMoney(value.value * Math.abs(amount) / 100.0, 0, null, null, '$'));
                

            } });


        return true;
        
    };
    
    
    
    Simulation.prototype.removeLoan = function(at){

        if(at == undefined || !this.take_loans["y_"+at] == -1)return false;
        delete this.take_loans["y_"+at];
        
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

                
                /* MODEL INFOS */

                // fill model infos
                model_infos.each(function(){
                    
                    
                    var info = $(this), // DOM element
                        type = info.hasClass('app-slider') ? 'slider' : 'input',
                        key = info.data('key'); // field in data
                    
                    var value = data['model'][key] || 0;   // value in data

                    console.log(key, type, value)
                    if(type == 'slider')info.slider('value', value);
                    else info.val(value);

                    /*
                    // get SPAN containing value
                    var info = $(this), // DOM element
                        field = info.data('field'), // field in data
                        editType = info.data('edit') || 'inline',
                        decimals = info.data('decimals') ,
                        symbol = info.data('symbol') || '' ; // value symbol



                    if(!field)return;
                    
                    if(decimals == undefined)decimals = 2;

                    var value = data['model'][field] || 0;   // value in data

                    // remove all previously defined listeners
                    info.unbind();

                    
                    if(field == "remaining_loans"){
                        info.html(formatMoney(data.other.remaining_loans, 0));
                        return;
                    }
                    

                    // Show data
                    if(editType == 'show'){
                        info.html(formatMoney(value, decimals, null, null, symbol));
                        return;
                    }

                    // Popup edit data
                    if(editType == 'popup'){

                        info.html(formatMoney(value, decimals, null, null, symbol))
                            .data('value', value).data('field', field)
                            .addClass('link w3-hover-text-indigo');

                        info.click(function(){
                            var value = $(this).data('value'),
                                field = $(this).data('field'),
                                title = $(this).closest('div').find('.field-name').html();

                            if(!field)return;

                            number_editor(value, function(new_val){
                                
                                    if(new_val != value){
                                        var model = new Models();
                                        var params = {}; params[field] = new_val;
                                        model.set(model_id, params, function(resp){ context.run(); });
                                    }
                                }, ucwords(title, true), 'sm', 0, null, true);
                        });

                        info.addClass('done');
                        return;
                    }

                    

                    // Inline edit data
                    if(editType == 'inline'){

                        info.html(formatMoney(value, decimals, null, null, symbol))
                            .data('value', value).data('field', field)
                            .addClass('link w3-hover-text-indigo');

                        info.click(function(){
                            var value = $(this).data('value'),
                                field = $(this).data('field'),
                                title = $(this).closest('div').find('.field-name').html();

                            if(!field)return;

                            var editor = inline_editor(info, value, function(new_val){
                                
                                                            if(new_val != value){
                                                                var model = new Models();
                                                                var params = {}; params[field] = new_val;
                                                                model.set(model_id, params, function(resp){                                                                    
                                                                    // refresh simulation
                                                                    context.run();
                                                                    // update SPAN
                                                                    info.html(formatMoney(new_val, decimals, null, null, symbol));
                                                                    //close inline editor
                                                                    editor.close();
                                                                });
                                                            }
                                                        }, 'number', false);
                        });

                        info.addClass('done');
                        return;
                    }
                    

                    // Input edit data
                    if(editType == 'input'){
                        
                        //hide info span
                        info.addClass('d-none');
                        
                        info.html(formatMoney(value, decimals, null, null, symbol));


                        var input = $('<input type="'+(info.data('type') || 'number')+'" step="'+(info.data('step') || 'any')+'" min="'+(info.data('min') || '')+'" max="'+(info.data('max') || '')+'" maxlength="'+(info.data('type') || '')+'" class="app-input '+(info.data('classes') || '')+'" />');
                            input.val(info.html());
                        
                        info.parent().append(input);

                        ui_input(input, null, function(e, v){ if(e.event != 'change')return; var new_val = v.value; 
                                                                console.log(field, new_val);
                                                                return;
                                                                if(new_val != value){
                                                                    var model = new Models();
                                                                    var params = {}; params[field] = new_val;
                                                                    model.set(model_id, params, function(resp){                                                                    
                                                                        // refresh simulation
                                                                        context.run();
                                                                    });
                                                                }  });


                        info.addClass('done');
                        return;
                    }

                    

                    // Slider edit data
                    if(editType == 'slider'){
                        
                        //hide info span
                        info.addClass('d-none');
                        
                        info.html(formatMoney(value, decimals, null, null, symbol));


                        info.parent().find('.app-slider').remove();

                        var slider = $('<div class="app-slider ui-orange mt-2 '+(info.data('classes') || '')+'" data-range="min" data-step="'+(info.data('step') || '1')+'" data-min="'+(info.data('min') || 0)+'" data-max="'+(info.data('max') || 100)+'" data-value="'+value+'" data-format="'+(info.data('format') || 'N?%')+'" data-limits="true" data-handle-top="0rem" ></div>');
                        
                        info.parent().append(slider);

                        ui_slider(slider, function(e, v){ if(e.event != 'stop')return; var new_val = v.value; 
                                                                console.log(field, new_val);
                                                                return;
                                                                if(new_val != value){
                                                                    var model = new Models();
                                                                    var params = {}; params[field] = new_val;
                                                                    model.set(model_id, params, function(resp){                                                                    
                                                                        // refresh simulation
                                                                        context.run();
                                                                    });
                                                                }  });


                        info.addClass('done');
                        return;
                    }
                    */


                })

                /* **** */





                // clear table
                //table.html('');


                
                /* TABLE */

                
                var calculated = data.calculated, spendings = data.spendings;
                var monthly_fees = parseFloat(data.model.monthly_fees);

                
                var view_spendings = function(index){
                    var sp = spendings[index] || {};

                    if(objEmpty(sp))return;

                    var content = $('<div class="row m-0 py-2"></div>');
                    var tmpl = '<div class="col-sm-12 pt-3 name h5"></div>   \
                                <div class="col-6 pb-2 border-bottom border-dark "><b>Redundancy</b><br><span class="redundancy h6"></span></div>   \
                                <div class="col-6 pb-2 border-bottom border-dark "><b>Cost</b><br><span class="cost h6"></span></div>';

                    for(const s of sp){
                        var line = $(tmpl);
                        line.filter('.name').html(ucwords(s.name));
                        line.find('.redundancy').html(formatNumber(s.redundancy, 0));
                        line.find('.cost').html(formatMoney(s.cost, 2, null, null, '$'));
                        content.append(line);
                    }

                    view_modal(content, 'Spendings ('+sp.length+')', null, true, 'md', false);
                    
                }
                
                
                for(var i=0; i<calculated.length; i++){

                    c = calculated[i];
                    

                    
                    // fill chart series first
                    chart_series.compound.push((c.fa < 0 ? c.cp : c.fa).toFixed(2));
                    chart_series.remaining_amount.push((c.fa < 0 ? c.fa : 0).toFixed(2));
                    //chart_series.loan.push((-1 * Math.abs(c.loan_t)) /* * loan_collection_ratio */ );
                    chart_series.loan_payments.push((-1 * Math.abs(c.loan_pay).toFixed(2)) /* * loan_collection_ratio */ );
                    chart_series.collections.push((c.yc).toFixed(2) /* * (1 - loan_collection_ratio) */);
                    chart_series.collectionsInc.push((c.yc_inc).toFixed(2) /* * (1 - loan_collection_ratio) */);
                    chart_series.investment.push((c.inv).toFixed(2));
                    chart_series.inv_remaining.push((c.inv_f < 0 ? c.inv_f : 0).toFixed(2));
                    chart_xaxis.push(c.year);

                    
                }

                var formatter = function(row){

                    var data = row.getData();
                    var cells = row.getCells();

                    var i = parseInt(data.year); 


                    for(var c of cells){

                        var k = c.getField();
                        var cell = $(c.getElement());
                        
                        
                        var decimals = cell.hasClass('decimals') ? 2 : 0; 
                        var negative = cell.hasClass('negative'); 

                        if(k == "year"){
                            if(data.year == 0)cell.html('Current');
                            continue;
                        }


                        var value = decimals > 0 || k == "mf_inc_r" ? data[k] : Math.ceil(data[k]);
                        var formattedValue = formatMoney(value, decimals, undefined, undefined, '$');
                        

                        
                        if(k == "loan_t" ){ 
                                if( value > 0) { 
                                    
                                    cell.addClass('pointer w3-hover-pink edit-loan w3-purple take-loan').data('at', i).data('amount', data.fa)
                                        .click(function(){ context.takeLoan($(this).data('at'), $(this).data('amount'), function(){ context.init(); }); });

                                    formattedValue = $('<div> <label class="pr-3 pointer w3-text-white remove-loan float-left" style="font-size:1.2rem;" data-at="'+i+'" ><i class="fa fa-times"></i></label> ' + formattedValue +'</div>');
                                    formattedValue.find('.remove-loan').click(function(){ event.preventDefault(); event.stopPropagation(); if(context.removeLoan($(this).data('at'))) context.init(); });
                                } 
                                else if(data.fa < 0){ 
                                    formattedValue = "Take Loan ?"; 
                                    cell.addClass('w3-text-pink pointer w3-hover-deep-orange take-loan').data('at', i).data('amount', data.fa) 
                                        .click(function(){ context.takeLoan($(this).data('at'), $(this).data('amount'), function(){ context.init(); }); });
                                }else{
                                    formattedValue = "";
                                }
                            
                        }else if(k == "loan_pay"){ if( value > 0) { formattedValue = formatMoney(value, decimals, undefined, undefined, currency) ; cell.addClass('font-weight-bold w3-deep-orange'); }else formattedValue = '';  }

                        
                        else if(k == 'yc_inc'){ if(value > 0)cell.addClass('w3-amber'); }

                        
                        else if(k == 'mf_new'){ cell.addClass('w3-text-'+(data.mf_inc_r  > 0 ? 'red' : 'green')); }
                        
                        
                        else if(k == 'mf_inc_r'){ formattedValue = '<i class="fa fa-'+(value > 0.01  ? 'chevron-up' : '')+' mr-1"></i>'+formatPercent(value, true, decimals); cell.addClass('w3-text-'+(value > 0.01  ? 'red' : 'green'));  }

                        
                        else if(k == 'fa' && value < 0){ cell.addClass('w3-red');}
                                                
                        else if(k == 'inv_f' && value < 0){ cell.addClass('w3-red'); }
                                                
                        else if(k == 'inv_s'){ cell.addClass('w3-text-'+(value > 0 ? 'green' : 'red') ); }
                         
                        
                        else{ if(negative)cell.addClass('w3-text-red');  }


                        cell.html(formattedValue);


                        if(k == 'sp'){
                            cell.addClass('link').data('year', data.year).click(function(){
                                var year = $(this).data('year');
                                view_spendings(year);
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
    
    
    Simulation.prototype.simulation =   function(id, cb, errCb){
                        
        ajax_get_no_loading("modules/simulation/app/simulation.php?cmd=get", {model_id:id, take_loan: this.take_loans},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } else { execfunc(errCb, []); } }, function(resp){ execfunc(errCb, []); error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };

    
    Simulation.prototype.delete =   function(){
                        
        this.table.delete();
        
    };

    return Simulation;

}();