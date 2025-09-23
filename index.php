<?php
    include_once('app/init.php');
    include_once('app/auth.php');

    // auth_handle_user();
    
    auth_handle_login();


    function print_nav(){
        global $auth;

        $navs = [   'Managements'=>'modules/clients/app/clients.php?cmd=load_company', 
                    'Associations'=>'modules/clients/app/clients.php?cmd=load_client', 
                    'Models'=>'modules/models/app/models.php?cmd=load', 
                    'Assistants'=>'modules/users/app/users.php?cmd=load', 
                    'Users'=>'modules/client_users/app/clientUsers.php?cmd=load', 
                    'Settings'=>'modules/simulation/app/simulation.php?cmd=load_settings'];
        $nav = [];
        switch($auth->role()){
            case 'admin': $nav = ['Managements', 'Associations', 'Models', 'Assistants', 'Settings']; break;
            case 'manager': $nav = ['Managements', 'Associations', 'Models']; break;
            case 'company_admin': $nav = ['Associations', 'Models', 'Users']; break;
            case 'company_user': $nav = ['Models']; break;
            case 'client_admin': $nav = ['Models', 'Users']; break;
        }

        $content = "";
        foreach($nav as $n){
            $content .= '<label class="px-2 nav-row-item" data-link="'.$navs[$n].'">'.$n.'</label>' . PHP_EOL;
        }

        echo $content;
    }

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?php echo $app_name; ?></title>

    <!-- Header -->        
    <?php echo print_header(['simulation']); ?>
    <!-- End of Header -->

        

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper" class="" style="min-height:100vh;">

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            
            

            <!-- Main Content -->
            <div id="content" class="" style="flex: 1 1 0px; padding-top: 70px;">

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <?= view('simulation', 'simulation.php'); ?>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <div class="w-100 pt-3 pb-2 px-4 d-flex flex-row w3-white position-fixed" style="top:0; left:0; right:0; z-index:99">
                
                <!-- Left -->
                <div class="text-nowrap pr-2">
                    <!-- Logo -->
                    <img class="mb-2 sidebar-brand-img" style="" src="<?= $base_web; ?>/assets/images/logo.png?v=1.0"/>
                    
                    
                    <!-- Title -->
                    <span class="h3 mb-0 ml-4 text-gray-800 text-capitalize" style="line-height: 100%;">
                        <?= !empty($auth->clientType()) ? $auth->info('company') :  'Reserve Funds Advisers'; ?>
                    </span>
                </div>


                <!-- Right -->
                <div class="pl-2 d-flex flex-row align-items-center"  style="flex: 1 1 0px;">
                    
                    <!-- Nav Item - User Information -->
                    <div class="text-right font-weight-bold text-nowrap" style="flex: 1 1 0px;">
                        
                        <?php print_nav(); ?>
                              
                    </div>


                    <!-- Nav Item - User Information -->
                    <div class="align-self-stretch pl-5 pr-3 border-right border-red ">

                        <div class="text-nowrap text-truncate font-weight-bold"  style="font-size:13px; max-width:140px; line-height: 100%;">
                            <?php $infos = $auth->sessioninfo(); echo ( ucfirst($infos['fn'])." ".strtoupper($infos['ln']) ); ?>
                        </div>
                        <span class="badge <?= $roles_badge[$auth->role()]; ?>"><?= ucfirst($roles_name[$auth->role()]); ?></span>

                    </div> 
                        
                    <!-- logout -->
                    <label class="pl-2 btn btn-sm w3-hover-text-red font-weight-bold text-nowrap" onclick="window.location.href = '<?php echo $pages['login']."?disconnect"; ?>' ">
                        <i class="fas fa-power-off fa-fw " style=""></i>
                        Logout
                        <!-- Counter - Messages -->
                    </label>
                </div>

                

            </div>

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span><b class="mr-2">Copyright &copy;</b> <?php echo $app_name; ?> 2021</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->
    

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>


    <template id="welcome_modal">
        <div class="row m-0 p-0 py-2 h5 text-center">
            <div class="col-12 font-weight-bold h4">Welcome to the Reserve Fund Advisors Visualizer!</div>

            <div class="col-12 pt-2">How would you like to start ?</div>

            <div class="col-12 pt-4">
                <div class="w-100 d-flex flex-row justify-content-center">
                    <?php

                        if(array_has(['admin', 'manager'], $auth->role())){
                            echo '  <label class="btn btn-primary text-capitalize mr-4" data-action="new_user">add another Assistant</label>
                                    <label class="btn btn-primary text-capitalize mr-4" data-action="new_company">add a management company</label>
                                    <label class="btn btn-primary text-capitalize mr-4" data-action="new_assoc">add an association</label>
                                    <label class="btn btn-primary text-capitalize" data-action="new_model">create a model for an association</label>';
                            
                        }else if($auth->role() == 'company_admin'){
                            echo '  <label class="btn btn-primary text-capitalize mr-4" data-action="new_user2">add a management user</label>
                                    <label class="btn btn-primary text-capitalize mr-4" data-action="new_assoc">add an association</label>
                                    <label class="btn btn-primary text-capitalize" data-action="new_model">create a model for an association</label>';
                            
                        }else if($auth->role() == 'company_user'){
                            echo '  <label class="btn btn-primary text-capitalize" data-action="new_model">create a model for an association</label>';
                            
                        }else if($auth->role() == 'client_admin'){
                            echo '  <label class="btn btn-primary text-capitalize mr-4" data-action="new_user2">add another user</label>
                                    <label class="btn btn-primary text-capitalize" data-action="new_model">create a model for my association</label>';
                            
                        }else if($auth->role() == 'client_user'){
                            
                        }
    
                    ?>
                    
                </div>

                <div class="col-12 text-center pt-4">
                    <div class="d-flex flex-row justify-content-center align-items-start">
                        <div class="d-inline-block font-weight-bold">Don't show again ? </div>
                        <label class="checkbox ml-2 mb-0">
                            <label class="switch">
                                <input type="checkbox" class="simulation-rule" data-key="inv_keep">
                                <span class="switch-slider  rounded-circle" ></span>
                            </label>
                        </label>
                    </div>
                </div>
            </div>

        </div>
    </template>

    
    <template id="simulate_modal">
        <div class="row m-0 p-0 py-2 h5 text-center">
            <div class="col-12 font-weight-bold h4">Congratulations !</div>

            <div class="col-12 pt-2">You have created a <b>Model</b>, would you like to run it throught our <b>Visualizer</b> now ?</div>
            
            <div class="col-12 pt-4">
                <div class="w-100 d-flex flex-row justify-content-center">
                    <label class="btn btn-success text-capitalize mr-4" data-action="yes">Yes</label>
                    <label class="btn btn-danger text-capitalize" data-action="no">No, I will do it later</label>
                </div>
            </div>
        </div>
    </template>

    
    <!-- Footer -->        
    <?php echo print_footer(); ?>
    <!-- End of Footer -->
    
    <!-- JS -->        
    <?php 
        $modules = [];
        switch($auth->role()){
            case 'admin': $modules = ['clients', 'models', 'users', 'simulation']; break;
            case 'manager': $modules = ['clients', 'models', 'simulation']; break;
            case 'company_admin': $modules = ['clients', 'models', 'client_users', 'simulation']; break;
            case 'company_user': $modules = ['models', 'simulation']; break;
            case 'client_admin': $modules = ['models', 'client_users', 'simulation']; break;
            case 'client_user': $modules = ['models', 'simulation']; break;
        }
        echo print_js($modules); 
        
    ?>
    <!-- End of JS -->


    <script>


        var simulation = init_simulation('#simulation');

        window.addEventListener('click', function(e){   
            
            if($(e.target).hasClass('simulation-options-close'))return;

            if($(e.target).closest('#simulation-change-model-box-parent').length == 0){
                $('#simulation-change-model-box').fadeOut('fast');
            }

        });
        

        $('body').find('[data-link]').click(function(){
                var link = $(this).data('link');
                if(!link)return;

                load_page(link);
        });


        // welcome modal
        if(getStorage('welcome_<?= $auth->uid();?>', 0) == 0){

            var modal = view_info($('#welcome_modal').html(), 'Welcome', 'lg', false, true);

            modal.find('[data-action]').click(function(){
                var btn = $(this),
                    action = btn.data('action');

                if(action == 'new_user'){
                    let util = new Users();
                    util.edit('', function(resp){
                        if(checkError(resp, true))return;
                        
                        btn.removeClass('btn-primary').addClass('btn-success').prepend('<i class="fa fa-check mr-2"></i>').unbind();
                    });
                
                }else if(action == 'new_company'){
                    let util = new Clients('', 'company');
                    util.edit('', function(resp){
                        if(checkError(resp, true))return;
                        
                        btn.removeClass('btn-primary').addClass('btn-success').prepend('<i class="fa fa-check mr-2"></i>').unbind();
                    });
                
                }else if(action == 'new_user2'){
                    let util = new ClientUsers2();
                    util.edit('', function(resp){
                        if(checkError(resp, true))return;
                        
                        btn.removeClass('btn-primary').addClass('btn-success').prepend('<i class="fa fa-check mr-2"></i>').unbind();
                    });
                
                }else if(action == 'new_assoc'){
                    let util = new Clients();
                    util.edit('', function(resp){
                        if(checkError(resp, true))return;
                        
                        btn.removeClass('btn-primary').addClass('btn-success').prepend('<i class="fa fa-check mr-2"></i>').unbind();
                    });
                }else if(action == 'new_model'){
                    let util = new Models();
                    util.edit('', function(resp){
                        if(checkError(resp, true))return;

                        var m_id = resp.success;
                        
                        if(resp.type == 'inv'){
                            btn.removeClass('btn-primary').addClass('btn-success').prepend('<i class="fa fa-check mr-2"></i>').unbind();

                            var modal2 = view_info($('#simulate_modal').html(), 'Welcome', 'lg', false, true);
                            modal2.find('[data-action]').click(function(){
                                var btn = $(this),
                                    action = btn.data('action');

                                if(action == 'yes'){
                                    modal.close();
                                    modal2.close();
                                    simulation.run(m_id);
                                }else{
                                    modal2.close();
                                }

                            });
                        }

                        


                    });
                }
            });

            modal.find('[type="checkbox"]').change(function(){
                var checked = this.checked;
                if(checked){
                    setStorage('welcome_<?= $auth->uid();?>', 1);
                }else{
                    deleteStorage('welcome_<?= $auth->uid();?>');
                }

            });
        }

    </script>

</body>

</html>