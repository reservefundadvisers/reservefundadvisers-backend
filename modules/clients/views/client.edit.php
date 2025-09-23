<div class="modal fade" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog modal-dialog-centered" role="document"> 
        <div class="modal-content shadow"> 
            <div class="modal-header pb-2"> 
            <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Association</h5> 
            <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                <span aria-hidden="true">×</span> 
            </button> 
            </div> 

            <form class="needs-validation" novalidate> 
            <div class="modal-body pt-0">  



                            

                <!-- MODAL BODY -->



                <div  class="form-row mt-2"> 
                
                    <div class="col-12">
                        <input type="text" class="app-input editor-input editor-focus" data-key="association" data-field="Association Name" pattern="[A-Za-z0-9\s]+" data-helper="Use Alphanumrical Only (A-Z 0-9)" data-invalid="Association Name invalid: Use Alphanumrical Only (A-Z 0-9)"  required/>
                    </div>  

                    <?php

                        // add management company
                        if(!$auth->checkRoleType('company')){
                                echo '  <div class="col-12">
                                            <input type="text" class="app-input editor-input" data-key="company" data-field="Management Company Name" pattern="[A-Za-z0-9\s]+" data-helper="Use Alphanumrical Only (A-Z 0-9)" data-invalid="Management Company Name invalid: Use Alphanumrical Only (A-Z 0-9)" />
                                        </div>';
                        }

                        if(!$auth->isClientRole()){
                                echo '  <div class="col-12">
                                            <select class="app-input editor-input" data-key="company_id" data-loadable="true" data-field="OR Select Management Company" data-invalid="Management Company invalid !" >
                                                <option value="">Loading...</option>
                                            </select>
                                        </div>';
                        }

                     

                        if(empty(check_val($vars, 'id'))){

                            echo '
                                    <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                                        Administrator
                                    </div>

                                    <div class="col-md-6">
                                        <input type="text" class="app-input editor-input" data-key="admin_fn" data-field="First Name" data-invalid="First Name invalid !"  required/>
                                    </div>
                                        
                                    <div class="col-md-6">
                                        <input type="text" class="app-input editor-input" data-key="admin_ln" data-field="Last Name" data-invalid="Last Name invalid !"  required/>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <input type="email" class="app-input editor-input" data-key="admin_email" data-field="Login Email" data-invalid="Login Email invalid !"  required/>
                                    </div>
                            
                            
                            ';

                        }

                    ?>

                    

                    <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                        Office
                    </div>
                    
                    
                    <div class="col-sm-6">
                        <input type="text" class="app-input editor-input" data-key="phone" data-field="Phone" data-invalid="Phone invalid !"  required/>
                    </div>
                    <div class="col-sm-6 <?= empty(check_val($vars, 'id')) ? 'invisible' : ''; ?>">
                        <input type="email" class="app-input editor-input" data-key="email" data-field="E-mail" data-invalid="E-mail invalid !" />
                    </div>
                    
                    <div class="col-12 mt-2">
                        <input type="text" class="app-input editor-input" data-key="address" data-field="Address" data-invalid="Address invalid !"  required/>
                    </div>
                    <div class="col-12">
                        <input type="text" class="app-input editor-input" data-key="address2" data-field="Address (Line 2)" data-invalid="Address (Line 2) invalid !"  />
                    </div>
                    <div class="col-3">
                        <input type="num" maxlength="5" class="app-input editor-input" data-key="zip" data-field="Zip Code" data-invalid="Zip Code invalid !"  required/>
                    </div>
                    <div class="col-6">
                        <select class="app-input editor-input" data-clear-option="<option value='' disabled selected>Enter Zip Code</option>" data-key="city" data-field="City" data-invalid="City invalid !" required>
                            <option value="" disabled selected>Enter Zip Code</option>                            
                        </select>
                    </div>
                    <div class="col-3">
                        <input type="text" class="app-input editor-input" data-key="state" data-field="State" data-invalid="State invalid !" data-disabled="true"  required/>
                        
                    </div>
                    

                </div> 
                
                <div class="invalid-global-feedback d-none text-sm text-danger mt-3 mb-0 font-weight-bold">* Please check errors before proceeding !</div>
        


                                



                <!-- MODAL BODY END -->    
            </div> 

            <div class="modal-footer"> 
                <button type="button" class="btn btn-sm btn-danger action-cancel" data-dismiss="modal"  qompta-tr="cancel">Cancel</button> 
                <button type="submit" class="btn btn-sm btn-success text-white action-submit"  qompta-tr="save">Save</button> 
                
                <button type="button" class="btn btn-sm btn-success text-white action-submit keep-editor"  qompta-tr="save">Save & Add Another</button> 
                <input class="editor-multiple-edit d-none" type="checkbox"/>

            </div> 
            </form> 

            <?php echo print_js(['clients']); ?>
            <script type="text/javascript" src="assets/js/states.js"></script>
            <script>
                function <?=$arand;?>_init(){
                    var root = $('#<?=$arand;?>_modal');
                    var data = <?php echo json_encode($vars); ?>;
                    
                    if(data.id){                        
                        root.find('.keep-editor').remove();
                    }

                    

                    var states = new States();
                    var zip_input = root.find('input[data-key="zip"]');
                    var cities_select = root.find('select[data-key="city"]');
                    var states_input = root.find('input[data-key="state"]');
                    var company_input = root.find('[data-key="company"]');
                    var company_select = root.find('[data-key="company_id"]');
                    
                    var update_state_select = function(editZip = false){ 
                                                var selected = selectOption(cities_select); 
                                                states_input.val((selected.length > 0 ? (selected.data('state') || '') : '')); 

                                                if(editZip && selected.data('zip') && selected.length > 0)zip_input.val(selected.data('zip')); 
                                            }

                    var update_city_select = function(value, state){
                                if(!value) value = zip_input.val();
                                fillSelect(cities_select, [{v:'', t:'Enter Zip Code'}]);

                                if(!value || value.length < 2){
                                    fillSelect(cities_select, [{v:'', t:'Enter Zip Code'}]);
                                    update_state_select();
                                    return;
                                }
                                
                                
                                
                                
                                var zips = states.listByZip(value, false), zip_count = Object.keys(zips).length;
                                var i = 0;
                                for(const zip in zips){
                                    var cities = zips[zip], cities_count = cities.length;
                                    for(const city of cities){
                                        var city_name = city.city + (zip_count > 1 ? ' ('+zip+')' : '') + ((cities_count > 1) ? ' - ' + city.state : '');
                                        
                                        fillSelect(cities_select, [{ v:city.city, t: city_name,
                                                                     data:{'state': city.state, 'zip':zip},
                                                                     s: (state && state == city.state)}], (i++ != 0));
                                        
                                    }
                                }

                                update_state_select();
                    }

                    cities_select.change(function(){update_state_select(true); });

                    fill_editor(root, data);
                    
                    root.find('input[data-key="phone"]').val(formatPhone(data.phone || ''));

                    init_ui(root, function(event, value){ 
                        
                        if(event.event == 'change'){

                            if(event.key == 'phone'){
                                event.element.val(formatPhone(value));
                            }else if(event.key == 'zip'){
                                
                                update_city_select(value);
                                
                            }
                        }
                        
                    });

                    update_city_select(null, data.state);

                    company_input.on('input', function(){ company_select.val(''); });
                    company_select.change(function(){ company_input.val(''); });

                    if(company_select.length > 0){
                        var m_companies = new Clients('', 'company');
                        m_companies.get(function(companies){
                            company_select.html('<option value="">--- Choose ---</option>');
                            for(const c of companies){
                                fillSelect(company_select, {v: c.id, t: ucwords(c.company), s: (c.id == data.company_id)}, true);
                            }
                            
                        });
                    }
                }

                <?=$arand;?>_init();
                
                
                //init_ui(".modal", function(ev, val){console.log(ev, val)} );
            </script>

        </div> 
        </div> 
    </div>