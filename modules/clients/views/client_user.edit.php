<div class="modal fade" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog modal-dialog-centered" role="document"> 
        <div class="modal-content shadow"> 
            <div class="modal-header pb-2"> 
            <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Association's User</h5> 
            <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                <span aria-hidden="true">×</span> 
            </button> 
            </div> 

            <form class="needs-validation" novalidate> 
            <div class="modal-body pt-0">  


                <?php print_r($vars['client_type']); ?>

                <!-- MODAL BODY -->



                <div  class="form-row mt-2"> 
                
                    <div class="col-md-6">
                        <input type="text" class="app-input editor-input editor-focus" data-key="fn" data-field="First Name" data-invalid="First Name invalid !"  required/>
                    </div>
                          
                    <div class="col-md-6">
                        <input type="text" class="app-input editor-input" data-key="ln" data-field="Last Name" data-invalid="Last Name invalid !"  required/>
                    </div>

                    <div class="col-10">
                        <select class="app-input editor-input" data-key="position_id" data-field="Position" data-invalid="Please choose a Position !"  required>
                            <option value="" selected disabled>Loading...</option>
                        </select>
                    </div>
                    <div class="col-2">
                        <label class="btn btn-sm btn-primary position-btn w-100" style="margin-top: 1.6rem;">Manage</label>
                    </div>
                    

                    <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                        Login
                    </div>
                    
                          
                    <div class="col-md-6">
                        <!-- <input type="text" class="app-input editor-input" data-key="username" data-field="Username" data-invalid="Username invalid !"  required/> -->
                        <input type="email" class="app-input editor-input" data-key="email" data-field="Login Email" data-invalid="Email invalid !"  required/>
                    </div>
                          
                    <div class="col-md-6">
                        <select class="app-input editor-input" data-key="role" data-field="User Role" data-invalid="User Role invalid !" required>
                            <option value="<?= $vars['client_type']; ?>_user">User</option>
                            <option value="<?= $vars['client_type']; ?>_admin">Administrator</option> 
                        </select>
                    </div>
                          
                    <!-- <div class="col-md-6">
                        <input type="text" class="app-input editor-input" data-key="password" data-icon="random button" data-icon-action="password_gen" data-field="Password" data-invalid="Password invalid !" data-helper="Click <i class='fa fa-random'></i> to Generate Random Password"  required/>
                    </div> -->

                    <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                        Contact
                    </div>
                    
                    
                    <div class="col-sm-6">
                        <input type="text" class="app-input editor-input" data-key="phone" data-field="Phone" data-invalid="Phone invalid !"  />
                    </div>
                    <!-- <div class="col-sm-6">
                        <input type="email" class="app-input editor-input" data-key="email" data-field="E-mail" data-invalid="E-mail invalid !" required />
                    </div> -->
                                        

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
                    
                    if(data.id){
                        root.find('.keep-editor').remove();
                    }


                    var cUser = new ClientUsers();
                    var cPositions = new ClientPositions(data.client_id || '');

                    var position_select = root.find('select[data-key="position_id"]');

                    var fill_positions = function(){
                        
                        fillSelect(position_select, [{v:"", t:"Choose Position",  s:true, d:true}]);
                        cPositions.get(null, null, function(list){
                                var tmp = []
                                for(const p of list){
                                    tmp.push({v:p.id, t:p.value, s: (data.position_id == p.id)});                                    
                                }
                                
                                fillSelect(position_select, tmp, true);
                        });
                    }

                    fill_positions();

                    root.find('.position-btn').click(function(){
                        
                        cPositions.list(function(resp){
                                fill_positions();
                        });
                    })
                    
                    fill_editor(root, data);
                    
                    root.find('input[data-key="phone"]').val(formatPhone(data.phone || ''));

                    init_ui(root, function(event, value){ 
                                        if(event.event == "password_gen")event.element.val(cUser.generatePassword()); 

                                        if(event.event == 'change' && event.key == 'phone'){
                                            event.element.val(formatPhone(value));
                                        }
                                    })

                }

                <?=$arand;?>_init();
                
                
                //init_ui(".modal", function(ev, val){console.log(ev, val)} );
            </script>

        </div> 
        </div> 
    </div>