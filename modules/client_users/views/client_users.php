<div class="modal fade <?=$arand;?>_client_users" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog" role="document" style="height:94vh; width:95%; max-width:1200px"> 
            <div class="modal-content shadow" style="height:100%"> 
                <div class="modal-header pb-2"> 
                <h5 class="modal-title" style="font-size:20px;" qompta-tr="client_user">Users (<span class="<?=$arand;?>_client_users_count">0</span>)</h5> 
                <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                    <span aria-hidden="true">×</span> 
                </button> 
                </div> 

                <div class="modal-body pt-0 d-flex flex-column">  
                    <!-- MODAL BODY -->


                    <!-- Actions -->
                    <div class="w-100 d-flex flex-row py-2">
                        
                        <div class="mr-auto">
                            <button class="btn btn-sm btn-danger shadow-sm" data-role="admin,manager" onclick="<?=$arand;?>_client_users.delete()"><i
                                    class="fas  fa-trash btn-icon text-white" ></i> <span class="btn-text" >Delete Selection</span> <i class="ml-1"></i>(<b><span class=" <?=$arand;?>_client_users_select_count" data-zero="empty">0</span></b>)</button>
                          
                        </div>
                              
                        
                        <label onclick="<?=$arand;?>_client_users.edit()" class="btn btn-sm btn-primary"><i class="fa fa-plus ml-1"></i> New</label>        
                        

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
                    <div class="table-responsive">
                        <table class="table-primary <?=$arand;?>_client_users_table invisible start-toggled" cellspacing="0" id="" style="">
                            <thead>
                                <tr>
                                    <th data-role="client_admin,company_admin"     
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

                                    <th data-role="client_admin,company_admin" tabulator-field="action" tabulator-headerSort="false" tabulator-hozAlign="center" tabulator-headerHozAlign="Center" tabulator-frozen="true" tabulator-minWidth="50" tabulator-maxWidth="50">...</th>
                                    <th tabulator-field="row_num" tabulator-frozen="true"  tabulator-headerHozAlign="center" tabulator-hozAlign="center" tabulator-minWidth="40" tabulator-width="60">#</th>
                                    
                                    <th tabulator-field="name" tabulator-headerFilter="input" tabulator-headerFilterLiveFilter="false" tabulator-minWidth="160" >Name</th>
                                    <th tabulator-field="username" tabulator-headerFilter="input" tabulator-headerFilterLiveFilter="false" tabulator-minWidth="160" >Login Email</th>
                                    <th tabulator-field="role" tabulator-minWidth="160" >Role</th>
                                    <th tabulator-field="phone" tabulator-headerFilter="input" tabulator-headerFilterLiveFilter="false">Phone</th>
                                    <th tabulator-field="reset_password" tabulator-minWidth="160" >Password</th>
                                    <th tabulator-field="active" tabulator-hozAlign="center" tabulator-headerHozAlign="Center" >Enabled</th>
                                    
                                </tr>
                            </thead>
                            <tbody class="table-sm">

                            </tbody>
                        </table>
                    </div>



                    <!-- MODAL BODY END -->    
                </div> 

                <script>
                    var <?=$arand;?>_client_users;
                    setTimeout(() => {
                        <?=$arand;?>_client_users = init_client_users('.<?=$arand;?>_client_users');    
                    }, 200);
                    
                </script>

                

            </div> 
        </div> 
    </div>