<div class="modal fade" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog  modal-dialog-centered" role="document"> 
        <div class="modal-content shadow"> 
            <div class="modal-header pb-2"> 
            <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Existing Investments</h5> 
            <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                <span aria-hidden="true">×</span> 
            </button> 
            </div> 


            <form class="needs-validation" novalidate> 
            <div class="modal-body pt-0" style="flex: 1 1 0px">  




                    <!-- MODAL BODY -->




                    <div class="w-100 text-right pt-2"><label class="btn btn-sm btn-primary text-white inv-add ">Add Investment</label></div>
                    
                    <div  class="form-row mt-2 pr-3 overflow-auto inv-strategies" style="max-height:60vh"> 
                                
                        
                                    
                    </div> 

                    <div class="invalid-global-feedback d-none text-sm text-danger mt-3 mb-0 font-weight-bold">* Please check errors before proceeding !</div>
            


                



                <!-- MODAL BODY END -->    
            </div> 

            <div class="modal-footer"> 
                <button type="button" class="btn btn-sm btn-danger action-cancel" data-dismiss="modal"  qompta-tr="cancel">Cancel</button> 
                <button type="submit" class="btn btn-sm btn-success text-white action-submit"  qompta-tr="save">Save</button> 
            </div> 

            </form> 
            

            <script>
                function <?=$arand;?>_init(){
                    var root = $('#<?=$arand;?>_modal');
                    var data = <?php echo json_encode($vars); ?>;
                    var list = root.find('.inv-strategies');

                    var editor_variable_name = root.data('editor-var');

                    var fiscal_year = parseInt(data.fiscal_year || (new Date()).getUTCFullYear()); 

                    var strategies = data.inv_strategy;
                    var available_strategies = data.available_inv_strategies;

                    // console.log(data);
                    
                    // Investment Strategy
                    var inv_strategy_edit = function(index){


                        var working_inv_strategy = isObject(index) ? cloneObj(index) : strategies[index] || {type:'', rate:0, dur:1, perc:0, note: ''};


                        // prepare available investment types
                        var types = Object.keys(available_strategies).length > 1 ? '<option value="" disabled selected>Choose Investment Type</option>' : '';
                        for(const t in available_strategies){
                            if(!available_strategies[t].show_model)continue;
                            types += '<option value="'+t+'">'+available_strategies[t].name+'</option>';
                        }


                        // editor
                        var content = $('<div class="row m-0 p-0">  \
                                            <div class="col-12 h6 pt-2 font-weight-bold border-dark border-bottom pb-3 mb-2">    \
                                                <select class="app-input editor-input" data-key="type" data-field="Investment Type" required> \
                                                    '+types+'    \
                                                </select>   \
                                            </div>  \
                                            <div class="col-12 h6 pb-1 mb-1">    \
                                                <input class="app-input editor-input" data-key="name" data-field="Strategy Name (optional)" />   \
                                            </div>  \
                                            <div class="col-6 h6 pb-1 mb-1">    \
                                                <input class="app-input editor-input" data-key="amount" step="1" min="1" data-field="Amount Invested (USD)" required/>   \
                                            </div>  \
                                            <div class="col-6 h6 pb-1 mb-1">    \
                                                <input class="app-input editor-input" data-key="sy" step="1" min="2000" max="'+fiscal_year+'" data-field="Started in (Year)"  required/>   \
                                            </div>  \
                                            <div class="col-6 h6 pb-1 mb-1">    \
                                                <input class="app-input editor-input" data-key="rate" min="0" max="100" data-field="Annualized Intrest Rate (%)"  required/>   \
                                            </div>  \
                                            <div class="col-6 h6 pb-1 mb-1">    \
                                                <input class="app-input editor-input" data-key="dur" min="1" data-field="Terms (Years)"  required/>   \
                                            </div>  \
                                            <div class="col-6 h6 pb-1 mb-1 option-col d-none">    \
                                                <input class="app-input editor-input option" data-key="pd" step="1" min="1" data-field="Number Of Days Of Intrest" />   \
                                            </div>  \
                                            <div class="col-6 h6 pb-1 mb-1 option-col d-none">    \
                                                <input class="app-input editor-input option" data-key="pm" step="1" min="1" data-field="Minimum Penalty (USD)" />   \
                                            </div>  \
                                            <div class="col-12 h6 mt-2 pb-1 mb-1">    \
                                                <textarea class="app-input editor-input" data-key="note" maxlength="100" data-field="Note"></textarea>  \
                                            </div>  \
                                        </div>');


                        // show options per selected type
                        content.find('[data-key="type"]').change(function(){
                            var type = $(this).val();

                            content.find('.option[data-key]').each(function(){
                                var item = $(this),
                                    key = item.data('key'),
                                    strategy_has_option = type && available_strategies[type].opt && has(available_strategies[type].opt, key) !== false;

                                item.closest('.option-col').toggleClass('d-none', !strategy_has_option);
                                if(!strategy_has_option)item.val('');
                            });
                        }).change();
                        
                        var modal = view_modal(content, 'Strategy', function(action, editor){

                            if(action.action != 'submit')return;

                            // check amount
                            if(action.value.amount < 1){
                                error('Amount should be at least <b>1 USD<\/b> !', '', default_error_alert_timeout);
                                return;
                            }

                            // check year before fiscal year
                            if(action.value.sy > fiscal_year){
                                error('The <b>Starting Year</b> should be before the <b>Model\'s Fiscal Year of '+fiscal_year+'<\/b> !', '', default_error_alert_timeout);
                                modal.find('[data-key="sy"]').addClass('app-input-invalid');
                                return;
                            }

                            

                            // check year unreachable
                            if(parseInt(action.value.sy) + parseInt(action.value.dur) < fiscal_year){
                                error('The <b>Starting Year + Terms</b> doesn\'t reach the <b>Model\'s Fiscal Year of '+fiscal_year+'<\/b> !', '', default_error_alert_timeout);
                                modal.find('[data-key="sy"]').addClass('app-input-invalid');
                                modal.find('[data-key="dur"]').addClass('app-input-invalid');
                                return;
                            }
                            

                            modal.find('[data-key="sy"]').removeClass('app-input-invalid');
                            modal.find('[data-key="dur"]').removeClass('app-input-invalid');


                            // if strategy to edit is in inv_strategy
                            if(index !== undefined && index >= 0 && index < strategies.length){
                                // set id after editing to avoid passing it as data-key
                                action.value['id'] = strategies[index].id || generateId(32);
                                strategies[index] = action.value;
                            }else{
                                // set id after editing to avoid passing it as data-key
                                action.value['id'] = generateId(32);
                                strategies.push(action.value);
                            }

                            modal.close();

                            fill_inv();
                            

                        }, true);

                        
                        if(index !== undefined){
                            content.find('[data-key="type"]').val(working_inv_strategy.type).change();
                            fill_editor(modal, working_inv_strategy);
                        }



                    }


                    function fill_inv(){
                        
                        list.html('');

                        
                        for(var i=0; i<strategies.length; i++){
                            
                            var inv = strategies[i];

                            // console.log(inv, available_strategies[inv.type]);
                            
                            // check id, if doesn't have, create one and check it's uniqueness
                            if(!inv.id){
                                inv['id'] = generateId(32);
                            }

                            var item = $('  <div class="col-12 h6 pt-2 font-weight-bold">    \
                                                '+ucwords(inv.name || 'Investment '+(i+1))+' \
                                                <div class="float-right">   \
                                                    <label class="link w3-text-indigo mr-3" data-action="edit" data-index="'+i+'">Edit</label>    \
                                                    <label class="link w3-text-deep-purple mr-3" data-action="duplicate" data-index="'+i+'">Duplicate</label>    \
                                                    <label class="link w3-text-red" data-action="delete" data-index="'+i+'">Delete</label> \
                                                </div>  \
                                            </div>      \
                                            <div class="col-6 mb-1 pb-1"> \
                                                <b class="">Investment Type</b><br>  \
                                                <b class="w3-text-blue" >'+available_strategies[inv.type].name+'</b>  \
                                            </div>  \
                                            <div class="col-6 mb-1 pb-1"> \
                                                <b class="">Invested Amount</b><br>  \
                                                <b class="w3-text-blue" >'+formatMoney(inv.amount)+'</b>  \
                                            </div>  \
                                            <div class="col-6 mb-1 pb-1"> \
                                                <b class="">Investment Terms</b><br>  \
                                                <b class="w3-text-green" >'+inv.dur+' Year(s)</b>  \
                                            </div>  \
                                            <div class="col-6 mb-1 pb-1"> \
                                                <b class="">Intrest Rate</b><br>  \
                                                <b class="w3-text-green" >'+inv.rate+'%</b>  \
                                            </div>  \
                                            <div class="col-6 mb-1 pb-1"> \
                                                <b class="">Started In</b><br>  \
                                                <b class="w3-text-blue" >'+inv.sy+'</b>  \
                                            </div>  \
                                            <div class="col-12 my-2 text-capitalize"> \
                                                <b>Note: '+inv.note+'</b>  \
                                            </div>  \
                                            <div class="w-100 border-bottom border-dark pb-2 mb-2"></div>');

                            
                            list.append(item);
                                
                        }


                        list.find('[data-action]').click(function(){
                            var action = $(this).data('action'), 
                                index = $(this).data('index');

                            if(action == 'edit'){
                                inv_strategy_edit(index);
                            }else if(action == 'delete'){
                                confirm('Do you want to <b>Delete</b> this <b>Investment Strategy</b> ?', '', null, function(action){
                                    if(action != 'ok')return;
                                    strategies.splice(index, 1);
                                    fill_inv(); 
                                });
                                
                            }else if(action == 'duplicate'){
                                inv_strategy_edit(strategies[index]);
                            }
                        });

                        
                        // update editor data to be saved
                        update_global(editor_variable_name, {inv_strategy: strategies});
                        // console.log(strategies);
                    }
                    

                    // remove all delete btn and keep last one only
                    root.find('.inv-add').click(function(){ inv_strategy_edit();});

                    fill_inv();


                }

                <?=$arand;?>_init();
                
                
                //init_ui(".modal", function(ev, val){console.log(ev, val)} );
            </script>

        </div> 
        </div> 
    </div>