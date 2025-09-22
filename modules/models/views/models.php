<div class="modal fade <?=$arand;?>_models" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog" role="document" style="height:94vh; width:95%; max-width:1200px"> 
            <div class="modal-content shadow" style="height:100%"> 
                <div class="modal-header pb-2"> 
                <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Models (<span class="<?=$arand;?>_models_count">0</span>)</h5> 
                <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                    <span aria-hidden="true">×</span> 
                </button> 
                </div> 

                <div class="modal-body pt-0 d-flex flex-column">  
                    <!-- MODAL BODY -->


                    <!-- Actions -->
                    <div class="w-100 d-flex flex-row py-2">
                        
                        <div class="mr-auto">
                            <button class="btn btn-sm btn-danger shadow-sm" data-role="admin,manager,company_admin,company_user,client_admin" onclick="<?=$arand;?>_models.delete()"><i
                                    class="fas  fa-trash btn-icon text-white" ></i> <span class="btn-text" >Delete Selection</span> <i class="ml-1"></i>(<b><span class=" <?=$arand;?>_models_select_count" data-zero="empty">0</span></b>)</button>
                          
                        </div>
                              
                        
                        <label onclick="<?=$arand;?>_models.edit()" class="btn btn-sm btn-primary"><i class="fa fa-plus ml-1"></i> New</label>        
                        

                        <div class="d-inline-block text-right ml-3">
                            <div class="dropdown">

                            
                                <button class="btn btn-sm bg-primary shadow-sm ml-1 " id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="false" aria-expanded="false"><i
                                        class="fas  fa-cog btn-icon text-white" ></i> <span class="btn-text"></span></button>
                            
                                <div class="dropdown-menu dropdown-menu-right " id="columnToggle" aria-labelledby="dropdownMenuButton">
                                    
                                </div>

                            </div>
                                    
                        </div>

                        
                                
                    </div>




                    <!-- Table -->
                    <div class="table-responsive" style="min-height:300px;">
                        <table class="table-primary <?=$arand;?>_models_table invisible start-toggled" cellspacing="0" id="" style="">
                        <thead>
                                        <tr>
                                         
                                            <th 
                                                tabulator-cssClass="row_select pl-1 pt-2"
                                                tabulator-formatter="rowSelection"
                                                tabulator-titleFormatter="rowSelection"
                                                tabulator-hozAlign="center"
                                                tabulator-headerSort="false"
                                                tabulator-headerHozAlign="Center"
                                                tabulator-minWidth="50"
                                                tabulator-maxWidth="50"
                                                tabulator-resizable="false"
                                                tabulator-frozen="true"></th>

                                            <th tabulator-field="action" tabulator-headerSort="false" tabulator-hozAlign="center" tabulator-headerHozAlign="Center" tabulator-frozen="true" tabulator-minWidth="50" tabulator-maxWidth="50">...</th>
                                            <th tabulator-field="row_num"  tabulator-frozen="true" tabulator-headerHozAlign="center" tabulator-hozAlign="center" tabulator-minWidth="40" tabulator-width="60" qompta-tr="">#</th>
                                            
                                            <th tabulator-field="name" tabulator-headerFilter="input" tabulator-headerFilterLiveFilter="false" tabulator-minWidth="160" >Name</th>
                                            <th tabulator-field="association" tabulator-headerFilter="input" tabulator-headerFilterLiveFilter="false" tabulator-minWidth="160" >Association</th>
                                            <th tabulator-field="reserve_items" >Items</th>
                                            <th tabulator-field="inv_strategy" >Existing Investments</th>
                                            <th tabulator-field="housing" >Housing Units</th>
                                            <th tabulator-field="starting_amount" >Starting Amount</th>
                                            <th tabulator-field="monthly_fees"  tabulator-headerSort="false">Monthly Fees</th>
                                            <th tabulator-field="monthly_fees_rate"  tabulator-headerSort="false" tabulator-cssClass="togglable">MF Yearly Increase</th>
                                            <th tabulator-field="inflation_rate"  tabulator-headerSort="false" tabulator-cssClass="togglable">Inflation Rate</th>
                                            <th tabulator-field="period"  tabulator-headerSort="false">Period (Years)</th>
                                            <th tabulator-field="bank_rate"  tabulator-cssClass="togglable" >Bank Rate</th>
                                            <th tabulator-field="loan_years"  tabulator-headerSort="false" tabulator-cssClass="togglable">Years of Loan</th>
                                            <th tabulator-field="fiscal_year" tabulator-headerFilter="input" tabulator-headerFilterLiveFilter="false"  >Fiscal Year</th>
                                            <th tabulator-field="updated_at" tabulator-cssClass="togglable">Last Update</th>

                                            <!-- <th tabulator-field="active" tabulator-hozAlign="center" tabulator-headerHozAlign="Center" >Enabled</th> -->
                                            
                                        </tr>
                                    </thead>
                            <tbody class="table-sm">

                            </tbody>
                        </table>
                    </div>



                    <!-- MODAL BODY END -->    
                </div> 

                <script>
                    var <?=$arand;?>_models;
                    setTimeout(() => {
                        <?=$arand;?>_models = init_models('.<?=$arand;?>_models');    
                    }, 200);
                    
                </script>

                

            </div> 
        </div> 
    </div>