<div class="modal fade <?=$arand;?>_settings" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog" role="document" style="height:94vh; width:95%; max-width:1200px"> 
            <div class="modal-content shadow" style="height:100%"> 
                <div class="modal-header pb-2"> 
                <h5 class="modal-title" style="font-size:20px;" qompta-tr="setting">Settings</h5> 
                <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                    <span aria-hidden="true">×</span> 
                </button> 
                </div> 

                <div class="modal-body pt-0 overflow-auto">  
                    <!-- MODAL BODY -->




                    

                    <div id="ltim_rates_list" class="mt-3" align="center">

                            <div class="w-100 py-2 border-bottom border-dark mb-3 h5 font-weight-bold">LTIM Investment Rates</div>

                            <div class="d-flex flex-row align-items-center w-lg-50 w-md-75 w-100" >


                                    <select class="app-editor" data-style="width:200px;" id="ltim-states" data-field="Add State">
                                        <option value="">Select State To Add</option>
                                    </select>

                                    <label class="btn btn-sm btn-primary ml-3 mt-3 mb-0 ltim-group-add"><i class="fa fa-plus mr-2"></i>Add</label>

                                    <label class="btn btn-sm btn-success ml-auto mt-3  mb-0  ltim-apply"><i class="fa fa-save mr-2"></i>Save</label>

                            </div>

                            <div class="d-inline-block w-lg-50 w-md-75 w-100 mt-2 pt-4 border-top border-dark">

                                <select class="app-editor" data-style="" id="ltim-added-states" data-field="Added States (<span id='ltim-added-states-total'>0</span>)">
                                    <option value="" selected>Select State to View</option>
                                </select>

                            </div>

                    </div>



                    <template id="rate_template">

                            <div class="card-body text-center mb-3 ltim-group d-none">

                                <div class="container d-inline-block w-lg-50 w-md-75 w-100 " >

                                    <div class="container text-left px-0">
                                        <i class="fa fa-times w3-text-red pointer mt-1 ltim-group-del mr-2" style="font-size: 1.3rem;"></i>
                                        <span class="h6 m-0 mt-2 font-weight-bold text-primary mb-3 ltim-title" style="font-size: 1.4rem;"></span>  

                                        <label class="btn btn-sm btn-primary font-weight-bold pointer float-right mt-2 collapsed d-none" data-toggle="collapse">
                                            Expand </label>
                                    </div>

                                    <div class="my-3 border-bottom border-dark"></div>

                                    <div class="container w-100 collapseeeeee ">
                                        <div class="row m-0 p-0 w-100 ltim-rates" >


                                        </div>

                                        <div class="w-100 text-center mt-3">
                                            <label class="btn btn-sm btn-info ltim-add"><i class="fa fa-plus mr-2"></i>Add Bucket</label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                    </template>


                    <!-- MODAL BODY END -->    
                </div> 


                <script>
                    var settings = new Simulation();

                    // States Utils
                    var states_utils = new States();
                    var states = states_utils.states();

                    // State Select
                    var state_select = $('#ltim-states');
                    ui_input(state_select);

                    // Added States Select
                    var added_state_select = $('#ltim-added-states');
                    ui_input(added_state_select);

                    // Addes States Elements
                    var rates_list = $('#ltim_rates_list');



                    function print_rates(rates){

                        var rate_tmpl = $('#rate_template').html();

                        var tmpl = '    <div class="col-5" data-index="%ID%"><input class="app-input ltim-rate" data-index="%ID%" data-field="Rate" data-icon="percent" type="number" step="0.1" min="0"/></div>  \
                                        <div class="col-5" data-index="%ID%"><input class="app-input ltim-dur" data-index="%ID%" data-field="Duration (Years)" data-icon="" type="number" step="0.1" min="1"/></div>   \
                                        <div class="col-2 action text-left pl-2 pt-4"  data-index="%ID%"></div>';

                                        


                        var addBucket =  function(list, state, r){
                            var item = $(replaceAll(tmpl, '%ID%', state + '_' + parseInt(Math.random()*999999)) );

                            if(!r)r = {rate:0, dur:""};

                            var total = list.find('.ltim-rate').length + 1;

                            item.find('.ltim-rate').data('field', 'Rate Bucket '+total).val(floatDecimals(r.rate*100, 2)).attr('data-val', floatDecimals(r.rate*100, 2));
                            item.find('.ltim-dur').val(r.dur).attr('data-dur', r.dur);
                            item.filter('.action').html('<i class="fa fa-times w3-text-red pointer mt-1" style="font-size: 1.3rem;"></i>')
                                .addClass('del').click(function(){
                                                        var index = $(this).data('index');
                                                        if(index == undefined)return;

                                                        confirm('Are you sure you want to delete this rate ?', '', null, function(action){
                                                            if(action != 'ok')return;

                                                            list.find('[data-index="'+index+'"]').remove();


                                                        });

                                                });
                            
                            init_ui(item);
                            list.append(item);
                        }


                        var sortGroups = function() {
                            
                                        var items = rates_list.children('.ltim-group').sort(function(a, b) {
                                            
                                            return $(a).data('state-name').localeCompare($(b).data('state-name'));
                                        });
                                        rates_list.append(items);

                                        // update select
                                        added_state_select.html('<option value="" disabled selected>Select State to View</option>');
                                        items.each(function(){
                                            added_state_select.append('<option value="'+$(this).data('state')+'">'+ucwords($(this).data('state-name'))+'</option>');
                                        });
                                        $('#ltim-added-states-total').html(items.length);


                        }


                        var addGroup = function(state, withNew = false){
                            
                            if(!state)return;


                            // get rates group
                            var group = $(rate_tmpl),
                                state_name = states[state];


                            // set rates state
                            group.find('.ltim-title').html(ucwords(state_name) + ' Buckets');
                            group.attr('data-state', state);
                            group.attr('data-state-name', state_name);
                            

                            // get list
                            var list = group.find('.ltim-rates');
                            list.html('');

                            
                            // get bucket
                            var buckets = rates[state] && rates[state].buckets || [];
                            for(const r of buckets){                
                                addBucket(list, state, r);
                            }

                            
                            if(withNew){
                                addBucket(list, state);
                            }

                            group.find('.ltim-group-del').click(function(){
                                
                                        var group = $(this).closest('.ltim-group'),
                                            state = group.data('state');
                                
                                        confirm('Are you sure you want to delete this State Buckets ?', '', null, function(action){
                                                            if(action != 'ok')return;

                                                            // delete group
                                                            group.remove();

                                                            state_select.find('option[value="'+state+'"]').removeClass('d-none');


                                        });

                            });

                            group.find('.ltim-add').click(function(){ addBucket($(this).closest('.ltim-group').find('.ltim-rates'), state); });
                            

                            // toggle
                            group.find('[data-toggle="collapse"]').attr('data-target', '#collpase_'+state)
                                .click(function(){ var item = $(this); item.html( !item.hasClass('collapsed') ? 'Expand' : 'Collapse' ); });
                            group.find('.collapse').attr('id', 'collpase_'+state);

                            console.log(group);

                            // add new group
                            rates_list.append(group);
                            
                            // sort groups
                            sortGroups();


                            added_state_select.val(state).trigger('change');


                            return group;
                            
                        }
                        

                        // add added states rates
                        for(const state in rates){

                            var tmp = addGroup(state);               
                        }

                        added_state_select.change(function(){
                            var state = $(this).val();

                            rates_list.find('.ltim-group').each(function(){
                                var item = $(this);
                                item.toggleClass('d-none', item.data('state') != state);
                            });

                        }).change();


                        // add selected new state
                        $('.ltim-group-add').click( function(){ 

                            var state = state_select.val();

                            if(!state)return;
                            
                            if($('.ltim-group[data-state="'+state+'"]').length > 0){
                                error('This State Bucket Already Added !', '', default_error_alert_timeout);
                                return;
                            }

                            var tmp = addGroup(state, true);
                            tmp.find('[data-toggle="collapse"]').click();

                            selectOption(state_select).addClass('d-none');

                            state_select.val('');

                            $("html, body").animate({ scrollTop: $(document).height() }, 1000);


                        });

                        // save changes
                        $('.ltim-apply').click( function(){ 

                                                        
                                        var save_buckets = {};
                                        
                                        $('.ltim-group').each(function(){

                                                        var state = $(this).data('state');
                                                        var state_name = $(this).data('state-name') || '';
                                                        if(!state)return;

                                                        var tmp = [];

                                                        $(this).find('.ltim-rate').each(function(){
                                                            
                                                            var item = $(this),
                                                                index = item.data('index'),
                                                                rate = parseFloat(item.val()) / 100,
                                                                dur = floatDecimals($('.ltim-dur[data-index="'+index+'"]').val());

                                                            if(rate < 0 || dur <= 0 )return;

                                                            tmp.push({"rate":floatDecimals(rate, 5), "dur":floatDecimals(dur, 5)});

                                                        });

                                                        if(objEmpty(tmp))return;


                                                        save_buckets[state] = {"name":state_name, "buckets":tmp};


                                                        

                                                    
                                        });


                                        settings.setSettings({"param":"ltim", "value": save_buckets }, function(resp){

                                                            if(checkError(resp, true))return;

                                                            success('Setting Saved !', '', default_success_alert_timeout);

                                        });
                            });

                        

                    }


                    

                    settings.getSettings(function(data){
                        
                        console.log(data);

                        for(const state in states){
                            state_select.append('<option value="'+state+'" class="'+(data.ltim && data.ltim[state] ? 'd-none' : '')+'">'+ucwords(states[state])+'</option>');
                        }
                        
                        print_rates(data.ltim || []);
                    });

                </script>

            </div> 
        </div> 
    </div>