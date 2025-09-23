function init_models(e){

    if(!e)return;
	
    var models = new Models(e);
    var search_input = undefined;
    models.list();
	

    var cols = $('#columnToggle');
    cols.html('<h6 class="dropdown-header text-primary">More Columns</h6>');

    if(models.table.table){
        for(const col of models.table.table.getColumns()){
            if($(col.getElement()).hasClass('togglable')){
                var colDef = col.getDefinition();
                cols.append('<a class="dropdown-item font-weight-bold" href="#" data-col="'+btoa(colDef.field)+'"><i class="fa fa-check mr-2 invisible"></i>'+colDef.title+'</a>')
            }
        }
    }

    $('#dropdownMenuButton').dropdown();

    cols.find('a').click(function(){ 
        var field = $(this).data('col');
        if(!field || !models.table.table)return;

        field = atob(field);

        var col = models.table.table.getColumn(field);
        models.table.table.toggleColumn(field); 

        $(this).find('i').toggleClass('invisible', !col.isVisible());

    });


    return models;

}


var Models = function(){

    function Models(parent){

        this.parent = parent;
        this.root = $(parent);
        this.table = null;

        this.modelItems = new ModelItems();
    }

    
    
    Models.prototype.data_formatter = function(items){
            
        //console.log(items);

        for(const item of items){
            
            // console.log(item);           

        }

        return items;
    };
    
    Models.prototype.init_table = function(callbacks){ 
        var row_formatter = function(row, updateCb){
            
            
            var context = this;

            var data = row.getData();
            var cells = row.getCells();

            // console.log(data);
            
            var edit_btn = $('<button class="btn btn-dark btn-sm" title="" type="button" >Edit</button>');
            edit_btn.click(function(){ 
                context.edit(data.id); 
            });


            var reserve_items_btn = $('<a class="link w3-text-indigo font-weight-bold" href="javascript:void(0);"> <i class="fa fa-table mr-2"></i>Items</a>');
            reserve_items_btn.click(function(){  
                context.modelItems.edit(data.id);
            });
            
            var inv_strategies = parseJSON(data.inv_strategy, []);
                if(!isArray(inv_strategies))inv_strategies = [];
            var inv_strategy_btn = $('<a class="link w3-text-indigo font-weight-bold" href="javascript:void(0);"> <i class="fa fa-chart-bar mr-2"></i>Investments ('+inv_strategies.length+')</a>');
            inv_strategy_btn.click(function(){  
                context.editInv(data.id, function(resp){context.table.refresh(true);});
            });

            var updated_at = formatDate(data.updated_at || data.created_at, 'm/d/y - h:i:s');
            
            for(var cell of cells){
    
                var field = cell.getField();
                element = $(cell.getElement());
                
        
                
    
                switch(field){
                    
                    case 'name': element.html(ucwords(data.name+' '+(data.fiscal_year ? '('+data.fiscal_year+')' : ''), true)).addClass('font-weight-bold link').click(function(){ context.edit(data.id); }); break;

                    case 'association': element.html(ucwords(data.association, true)).addClass('font-weight-bold w3-text-indigo'); break;
                    
                    case 'housing': element.html(formatNumber(data.housing, 0)).addClass('font-weight-bold'); break;
                    case 'starting_amount': element.html(formatMoney(data.starting_amount)).addClass('font-weight-bold w3-text-green'); break;
                    case 'monthly_fees': element.html(formatMoney(data.monthly_fees)).addClass('font-weight-bold w3-text-indigo'); break;
                    
                    case 'period': element.html(formatNumber(data.period, 0)).addClass('font-weight-bold w3-text-green'); break;
                    
                    case 'inflation_rate': element.html(formatPercent(data.inflation_rate, false)).addClass(' w3-text-red'); break;
                    case 'monthly_fees_rate': element.html(formatPercent(data.monthly_fees_rate, false)); break;

                    case 'bank_rate': element.html(formatPercent(data.bank_rate, false)).addClass('font-weight-bold w3-text-purple'); break;
                    case 'loan_years': element.html(formatNumber(data.loan_years, 0)).addClass('font-weight-bold w3-text-purple'); break;

                    case 'reserve_items': element.html(reserve_items_btn); break;

                    case 'inv_strategy': element.html(inv_strategy_btn); break;

                    case 'fiscal_year': element.addClass('font-weight-bold'); break
                    case 'updated_at': element.html(updated_at); break
                    
                    case 'action': element.html(edit_btn); break;
    
                }
            }
            
        }.bind(this);

            this.table = list_pagination(this.parent, {rowFormatter: row_formatter, dataFormatter: this.data_formatter}, {persistenceID: 'models', ajaxFiltering: true}, "modules/models/app/models.php", {cmd: "get"}, "modules/models/app/models.php?cmd=set", "modules/models/app/models.php?cmd=delete", callbacks);
    };

    Models.prototype.list = function(callbacks){ if(!this.table){ this.init_table(callbacks); }else{ this.table.refresh(true); this.table.clearFilter(true); } }
    
                        
    Models.prototype.search =   function (search){ 
        if(this.table)this.table.remoteSearch(search);
    }

    Models.prototype.filter =   function (filters){
                                    if(this.table)this.table.remoteFilter(filters);
                                }

    Models.prototype.toggleColumns =   function (){
                                    if(this.table)this.table.toggleColumns();
                                }

    Models.prototype.edit = function(id, cb, beforeCb){

                                var context = this;
                                edit_element("modules/models/app/models.php?cmd=edit", { "id":id}, "modules/models/app/models.php?cmd=save",
                                                function(e, context){

                                                    var m_id = e.success;
                                                                   
                                                    execfunc(cb, {success: m_id, type: "model"}); 

                                                    if(!id && !checkError(e, true)){
                                                        let util = new ModelItems();
                                                        util.edit(m_id, function(resp){
                                                            if(checkError(resp, true))return;
                                                            
                                                            execfunc(cb, {success: m_id, type: "items"});

                                                            context.editInv(m_id, function(resp2){
                                                                execfunc(cb, {success: m_id, type: "inv"});
                                                            });
                                                        });
                                                    }
                                                    context.list();

                                                },
                                                function(resp){
                                                    if(resp.modal && resp.modal == 'has_simulation'){
                                                        confirm('This <b>Model</b> has simulation ran by users ! Would you like to <b>Reset All Simulation</b> to be able to edit it ?', 
                                                                null, null, function(action){ if(action != 'ok')return; context.reset_simulations(id, function(){context.edit(id);}); });

                                                        return false;
                                                    }

                                                    return resp;
                                                      
                                                },
                                                function(data){
                                                    
                                                    //data.inflation_rate = 100.000;
                                                    //data.monthly_fees_rate /= 100.000;
                                                    //data.bank_rate /= 100.000; 
                                                    
                                                    // console.log(data);
                                                    
                                                    // return false;
                                                },
                                                function(action){ console.log(action); if(action.action == 'update'){ context.list(); execfunc(cb, action);}}, this);
    }

    Models.prototype.editInv = function(model_id, cb, beforeCb){
                                edit_element("modules/models/app/models.php?cmd=edit_inv", { "model_id":model_id}, "modules/models/app/models.php?cmd=save_inv",
                                                function(e, context){                                                    
                                                    execfunc(cb, e);
                                                },
                                                null,
                                                null,
                                                function(action){}, this);
    }
    
    Models.prototype.set =   function(model_id, params, cb){
        
        if(!params || !model_id){ return false; }

        params['id'] = model_id;
        ajax_post("modules/models/app/models.php?cmd=set", params,
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while setting model !'); console.log(resp.responseText); });
        
        return true;
    };

    
    
    Models.prototype.reset_simulations =   function(model_id, cb){
        
        if(!model_id){ return false; }

        ajax_post("modules/models/app/models.php?cmd=reset_simulations", {"id":model_id},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while resetting model simulations !'); console.log(resp.responseText); });
        
        return true;
    };

    
    Models.prototype.delete =   function(){
                        
        this.table.delete();
        
    };

    return Models;

}();





var ModelItems = function(){

    function ModelItems(model_id){

        this._model = model_id;
        this.root = null;
        this.table = null;
    }

    ModelItems.prototype.model = function(model){
            
        if(!model)return this._model;
        else this._model = model;

    };
    
    
    ModelItems.prototype.view = function(model_id, callbacks){ 
                        
                        var context = this;
                        if(!model_id)model_id = this._model;

                        view_element("modules/models/app/modelItems.php?cmd=edit", {"model_id":model_id}, callbacks);


    }
    

    ModelItems.prototype.edit = function(model_id, cb, forceEdit = false){
        
                                    if(!model_id)model_id = this._model;
                                    var params = {"model_id": model_id};

                                    if(forceEdit)params['force']=1;

                                    var context = this;
                                    edit_element("modules/models/app/modelItems.php?cmd=edit", params, "modules/models/app/modelItems.php?cmd=save",
                                                    function(e, context){
                                                        
                                                        
                                                        //context.list();
                                                        execfunc(cb, e);

                                                    },
                                                    function(resp){
                                                        if(resp.modal){
                                                            if(resp.modal == 'used'){
                                                                confirm('This <b>Model</b> has simulation ran by users ! <br>Would you like to <b>Reset All Simulation</b> to be able to edit it, or just <b>Update Items Costs</b> ?', 
                                                                        null, [ {text: 'Cancel', action:'cancel', classes: 'btn-danger'}, 
                                                                                {text: 'Reset & Edit', action:'reset', classes: 'w3-deep-orange'}, 
                                                                                {text: 'Update Costs', action:'update', classes: 'w3-deep-purple'}],
                                                                        function(action){ 
                                                                                if(action == 'cancel')return;
                                                                                else if(action == 'reset'){
                                                                                    context.reset_simulations(model_id, function(){context.edit(model_id, cb, true);});
                                                                                    return;
                                                                                }
                                                                                else { context.editActual(model_id, cb); }  });
        
                                                                return false;

                                                            }else if(resp.modal == 'has_actual' || resp.modal == 'update'){
                                                                confirm('Would you like to <b>Edit Items</b>, or just <b>Update Items Costs over the Years</b> ?', 
                                                                        null, [ {text: 'Cancel', action:'cancel', classes: 'btn-danger'}, 
                                                                                {text: 'Edit', action:'edit', classes: 'w3-deep-orange'}, 
                                                                                {text: 'Update Costs', action:'update', classes: 'w3-deep-purple'}],
                                                                        function(action){ 
                                                                                if(action == 'cancel')return;
                                                                                else if(action == 'edit'){
                                                                                    context.edit(model_id, cb, true);
                                                                                    return;
                                                                                }
                                                                                else { context.editActual(model_id, cb); }  });
                                                                
                                                                
                                                                return false;
                                                            }
                                                        }

                                                        return resp;

                                                    },
                                                    function(data){

                                                        
                                                        if(data['force'])delete data['force'];
                                                        if(data['model_id'])delete data['model_id'];
                                                        
                                                        var err = "";
                                                        for(const i in data){
                                                            if(data[i].cost == 0){
                                                                err += "- " + ucwords(data[i].name, true) + "<br>";
                                                                //console.log(data[i].name+' = $'+data[i].cost);
                                                            }
                                                        }

                                                        if(err){
                                                            error('Please correct the <b>Cost</b> of the following items: <br><br>'+err, 'Cost Error');
                                                            return false;
                                                        }
                                                        
                                                        return {"model_id":model_id, "items": data};
                                                    },
                                                    function(action){}, this);
                            }
    

    ModelItems.prototype.editActual = function(model_id, cb, beforeCb){
        
                                    if(!model_id)model_id = this._model;

                                    edit_element("modules/models/app/modelItems.php?cmd=edit_actual&model_id="+encodeURIComponent(model_id), {}, "modules/models/app/modelItems.php?cmd=save_actual&model_id="+model_id,
                                                    function(e, context){
                                                        
                                                        //context.list();
                                                        execfunc(cb, e);

                                                    },
                                                    null,
                                                    function(data){
                                                        
                                                        var err = "";
                                                        for(const i in data){
                                                            if(data[i].cost == 0){
                                                                err += "- " + ucwords(data[i].name, true) + "<br>";
                                                                //console.log(data[i].name+' = $'+data[i].cost);
                                                            }
                                                        }

                                                        if(err){
                                                            error('Please correct the <b>Cost</b> of the following items: <br><br>'+err, 'Cost Error');
                                                            return false;
                                                        }
                                                        
                                                        return {"model_id":model_id, "items": data};
                                                    },
                                                    function(action){}, this);
                            }
    
             
    
        
    ModelItems.prototype.reset_simulations =   function(model_id, cb){
        
        if(!model_id){ return false; }

        ajax_post("modules/models/app/models.php?cmd=reset_simulations", {"id":model_id},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while resetting model simulations !'); console.log(resp.responseText); });
        
        return true;
    };     
    
    
    ModelItems.prototype.reset_actual =   function(model_id, cb){
                        
        if(!model_id)model_id = this._model;
        
        ajax_get_no_loading("modules/models/app/modelItems.php?cmd=reset_actual", {"model_id":model_id},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    
    ModelItems.prototype.get =   function(cb){
                        
        ajax_get_no_loading("modules/models/app/modelItems.php?cmd=get", {},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    
    return ModelItems;

}();