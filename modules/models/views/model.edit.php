<div class="modal fade" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog modal-dialog-centered" role="document"> 
        <div class="modal-content shadow"> 
            <div class="modal-header pb-2"> 
            <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Model</h5> 
            <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                <span aria-hidden="true">×</span> 
            </button> 
            </div> 

            <form class="needs-validation" novalidate> 
            <div class="modal-body pt-0">  




                <!-- MODAL BODY -->
                <?php
                    if(!is_valid($vars, 'id')){
                        echo '
                                <div class="w-100 mt-2 overflow-auto text-right">
                                    <label class="btn btn-sm w3-green mr-2" id="model_template"><i class="fa fa-file" style="font-size:0.7rem;"></i> Download Template</label>
                                    <label class="btn btn-sm w3-indigo" id="model_import"><i class="fa fa-upload" style="font-size:0.7rem;"></i> Import Model</label>
                                </div>
                        ';
                    }
                ?>


                <div  class="form-row"> 

                    <div class="col-12 mb-3 border-bottom border-dark" data-role="admin,manager,company_admin,company_user">
                        <select class="app-input editor-input" data-key="client_id" data-field="Association" data-invalid="Please choose an $? !" data-helper="Note: Must be selected before importing model!"  required>
                            <option value="" disabled selected>Loading...</option>
                        </select>
                    </div>
                            
                                         
                    <div class="col-12">
                        <input type="text" maxlength="126" class="app-input editor-input editor-focus" data-key="name" data-field="Model Name" required/>
                    </div>

                    <div class="col-sm-6">
                        <input type="number" min="0" class="app-input editor-input" data-key="housing" data-field="Housing Units" data-icon="home" required/>
                    </div>

                    
                    <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                        Financials
                    </div>

                    <div class="col-sm-6">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="starting_amount" data-field="Starting Amount in Bank" data-icon="dollar-sign" required/>
                    </div>   

                    <div class="col-sm-6">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="monthly_fees" data-field="Monthly Reserve Fees Per Unit" data-icon="dollar-sign" required/>
                    </div>    
                    <div class="col-sm-6">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="monthly_fees_rate" data-field="Maximum Allowable Fee Increase" data-icon="percent" data-invalid="$? invalid !" value="0"/>
                    </div>   
                    <!-- <div class="col-sm-6">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="cushion_fund" data-field="Contingency Fund" data-icon="percent" data-invalid="$? invalid !" value="0"/>
                    </div>     -->
                    
                    <div class="col-sm-6">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="inflation_rate" data-field="Inflation Rate Estimate" data-icon="percent" data-invalid="$? invalid !" value="0"/>
                    </div>    
                    
                    <div class="col-sm-6">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="bank_int_rate" data-field="Bank Interest Income Rate" data-icon="percent" data-invalid="$? invalid !" value="0"/>
                    </div>    
                    
                    <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark d-none">
                        Loan
                    </div>
                    
                    
                    <div class="col-sm-6 d-none">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="bank_rate" data-field="Intrests Rate" data-icon="percent" value="0"/>
                    </div>
                    <div class="col-sm-6 d-none">
                        <input type="number" min="0" step="any" class="app-input editor-input" data-key="loan_years" data-field="Terms" data-icon="hastag" value=""/>
                    </div>

                    <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                        Simulation
                    </div>

                    <div class="col-sm-6">
                        <input type="number" value="30" min="0" step="any" class="app-input editor-input" data-key="period" data-field="Period (Years)" required/>
                    </div>  

                    <div class="col-sm-6">
                        <input type="text" value="" min="0" maxlength="4" step="any" class="app-input editor-input" data-key="fiscal_year" data-field="Fiscal Year" required/>
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
            <script>
                function <?=$arand;?>_init(){
                    var root = $('#<?=$arand;?>_modal');
                    var data = <?php echo json_encode($vars); ?>;

                    
                    check_role(root);

                    if(data.id){
                        root.find('.keep-editor').remove();
                    }else{
                        root.find('[data-key="fiscal_year"]').val((new Date()).getFullYear());
                    }
                    
                    
                    //data.inflation_rate = floatDecimals(data.inflation_rate * 100.000, 3); 
                    //data.monthly_fees_rate = floatDecimals(data.monthly_fees_rate * 100.000, 3); 
                    //data.bank_rate = floatDecimals(data.bank_rate * 100.000, 3); 

                    fill_editor(root, data);
                    
                    var association_select = root.find('[data-key="client_id"]');
                    var clients = new Clients();

                    if(association_select.length > 0){
                        clients.get(function(list){
                            var i=0;
                            for(const d of list){
                                fillSelect(association_select, {v:d.id, t:d.association, s: (data.client_id == d.id)}, (i++) != 0);
                            }
                        });
                    }

                    
                    // download model template
                    root.find('#model_template').click(function(){
                        window.open(global_base+'/files/model_template.xls', '_blank');
                    });

                    var model_keys = []; 
                    root.find('[data-key]').each(function(){ if($(this).data('key') == 'client_id')return; model_keys.push($(this).data('key')); });
                    
                    
                    // import model
                    root.find('#model_import').click(function(){

                            var client_id = root.find('[data-key="client_id"]').val() || '';

                            
                            var file_dialog = $('<input type="file"  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"/>');

                            
                            var context = this;

                            file_dialog.change(function(e) {

                                    
                                    // check if no file is selected
                                    if(e.target.files.length < 1)return;
                                    
                                    // get selected file
                                    f = e.target.files[0];
                                    var reader = new FileReader();
                                    var name = f.name;

                                    // on file read
                                    reader.onload = function(e) {

                                        var l = loading();

                                        // get all data read
                                        var data = e.target.result;

                                        // create a EXCEL WORKBOOK
                                        var workbook;
                                        if(rABS) {
                                            /* if binary string, read with type 'binary' */
                                            workbook = XLSX.read(data, {type: 'binary'});
                                        } else {
                                            /* if array buffer, convert to base64 */
                                            var arr = fixdata(data);
                                            workbook = XLSX.read(btoa(arr), {type: 'base64'});
                                        }

                                        // if empty, return
                                        if(workbook.SheetNames.length < 1){
                                            error('File Empty or Not an Excel/CSV File !');
                                            return;
                                        }

                                        // get first SHEET of EXCEL
                                        var sheet = workbook.Sheets[workbook.SheetNames[0]];
                                        //var data = XLSX.utils.sheet_to_json (sheet, {header: ['num', 'company_name', 'individual_fn', 'individual_ln', 'type']});


                                        // read sheet as CSV file
                                        var rows = XLSX.utils.sheet_to_csv(sheet, {FS:";", strip:true, blankrows: true}).split('\n');

                                        if(rows.length < 1){
                                            error('This file is empty !');
                                            return;
                                        }

                                        var model_data = {"client_id": client_id};
                                        var model_items = [];


                                        var model_data_start = 3;
                                        var model_items_start = 19;

                                        var process_model = function(){

                                                for(var i=0; i<model_keys.length; i++){
                                                    
                                                                                                
                                                    
                                                    // split csv line and skip if empty
                                                    var cells = rows[i + model_data_start].split(';');
                                                    if(cells.length < 2)continue;

                                                    model_data[model_keys[i]] = model_keys[i] == "name" ? cells[1] : parseFloat(cells[1].replace(/[^\d,.]+/g, ''));

                                                }

                                        }

                                        var process_items = function(model_id){


                                                for(var i=model_items_start; i<rows.length; i++){
                                                    
                                                    // skip if line is empty
                                                    if(rows[i].length < 1)continue;

                                                    
                                                    // split csv line and skip if empty
                                                    var cells = rows[i].split(';');
                                                    if(cells.length < 4)continue;

                                                    //console.log(cells);

                                                    if(isNaN(cells[1]) && isNaN(cells[2]) && isNaN(cells[3]))continue;

                                                    model_items.push({      "name":cells[0].trim(), 
                                                                            "redundancy":parseInt(cells[1].replace(/\D/g, '')), 
                                                                            "remaining_life":parseInt(cells[2].replace(/\D/g, '')), 
                                                                            "cost":parseFloat(cells[3].replace(/\D/g, '')),
                                                                            "estimated_cost": parseFloat(cells[5].replace(/[^\d.]/g, '')),
                                                                            "actual_cost": parseFloat(cells[6].replace(/[^\d.]/g, ''))
                                                                });

                                                }

                                                ajax_post("modules/models/app/modelItems.php?cmd=save", {"model_id": model_id, "items": model_items},
                                                    function(resp){ l.remove(); if(!checkError(resp, true)){ 
                                                                                        root.addClass('update'); 
                                                                                        success('Model created successfully !', '', default_success_alert_timeout); 
                                                                                        // root.trigger('customEvent', {action:"update"});
                                                                                        root.modal('hide');
                                                                                    } }, 
                                                    function(resp){ l.remove(); error('Error while saving model data !'); console.log(resp.responseText); });

                                        }

                                        process_model();
                                        // process_items();

                                        // console.log(model_data, model_items);

                                        

                                        // l.remove();
                                        // return;

                                        ajax_post("modules/models/app/models.php?cmd=save", model_data,
                                                    function(resp){ if(checkError(resp, true)){ l.remove(); return; } process_items(resp.success); }, 
                                                    function(resp){ l.remove(); error('Error while saving model data !'); console.log(resp.responseText); });
        
                                        

                                        
                                        
                                        
                                    }
                                    
                                    reader.readAsBinaryString(f);
                            });

                            
                            
                            file_dialog.trigger('click');

                    });
                    
                }

                <?=$arand;?>_init();
                
                
                //init_ui(".modal", function(ev, val){console.log(ev, val)} );
            </script>

        </div> 
        </div> 
    </div>