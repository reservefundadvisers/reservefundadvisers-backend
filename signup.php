<?php


include_once('app/init.php');
include_once('app/auth.php');

auth_disconnect();


$order_error = false;
$order_cancel = false;
$order = [];


if(!isset($_GET['order_id'])){
    $order_error = true;
}else{

    $order_id = $_GET['order_id'];

    if(isset($_GET['status'])){

        if($_GET['status'] != 'success'){
            $order_error = true;
            $order_cancel = true;

            set_element($checkoutsTable, ['id'=>$order_id, 'status'=>'cancel']);

        }else{
            header("location: signup.php?order_id=$order_id"); 
        }
    
        
    }else{
        $order = get_element($checkoutsTable, ['id'=>$order_id, 'status'=>'pending']);
    
        if(empty($order)){
            $order_error = true;
        }
    }

    
}


?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Sign Up - <?php echo $app_name; ?></title>

    <!-- Custom fonts for this template-->
    <link href="<?php echo $base_web;?>/assets/js/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="<?php echo $base_web;?>/assets/fonts/fonts.css"
        rel="stylesheet">
    
    <!-- W3.CSS -->
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/bootstrap.min.css">
    <!-- W3.CSS -->
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/w3.css">
    <!-- Global CSS -->
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/global.css?v=1.2.0" type="text/css">
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/loaders.css">
    <link rel="stylesheet" href="<?php echo $base_web;?>/assets/css/ui/input.css">
    <!-- Custom styles for this template-->
    <link href="<?php echo $base_web;?>/assets/css/sb-admin-2.css?v=1.1" rel="stylesheet">
    
    <script>    
        var global_base = "<?php echo $base_web;?>";
    </script>

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center mt-2">

            <div class="signup-card d-none mb-5">

                <div class="card o-hidden border-0 shadow-lg my-1 mb-5 pb-3">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                           
                        <div class="">
                            <div class="px-2 py-2 px-3">
                                <div class="text-center mb-3 pb-3 border-bottom border-dark">
                                    <img class="mb-2 mt-0" style="height:64px;"  src="assets/images/logo.png?v=1.0"/>
                                </div>
                                <div class="text-center mb-3">
                                    <h1 class="h5 text-gray-900 mb-4">Sign Up</h1>
                                </div>

                                <?php 

                                    if($order_cancel){
                                        echo '  <div class="alert alert-success w3-red h6 text-center">
                                                    <div class="border-bottom w3-border-white pb-2 mb-3 h5 w-100">
                                                        <i class="fa fa-times mr-2"></i> Subscription Cancelled !
                                                    </div>

                                                    You\'ll soon be redirected !
                                                </div>
                                                <script> // setTimeout(function(){window.location.href = \''.$base_url.'\';}, 3000);</script>';
                                                
                                    }

                                    else if($order_error){
                                        echo '  <div class="alert alert-success w3-red h6 text-center">
                                                    <div class="border-bottom w3-border-white pb-2 mb-3 h5 w-100">
                                                        <i class="fa fa-times mr-2"></i> Invalid Sign up !
                                                    </div>

                                                    You\'ll soon be redirected !
                                                </div>
                                                <script> // setTimeout(function(){window.location.href = \''.$base_url.'\';}, 3000);</script>';
                                                
                                    }

                                    else{
                                        echo '  <form class="signup px-md-1 needs-validation" id="signup_form" novalidate>
                                    
                                                    <input class="app-input editor-input d-none" data-skip="true" data-key="cmd" value="signup" />
                                                    <input class="app-input editor-input d-none" data-skip="true" data-key="order_id" value="'.$order_id.'" />
                
                                                    <div  class="form-row mt-2"> 
                                                               
                                                        
                                                        <div class="col-12">
                                                            <input class="app-input editor-input d-none" data-skip="true" data-key="type" value="client" /> 
                                                        
                                                            <select class="app-input editor-input" data-key="type" data-field="Are you" required>
                                                                <option value="client">An Association</option>
                                                                <option value="client">A Board Member</option>
                                                                <option value="reserve">A Reserve Study Company</option>
                                                                <option value="property">A Property Management Company</option>
                                                            </select>
                                                        
                                                        </div>


                                                        <div class="col-12">
                                                            <input type="text" class="app-input editor-input editor-focus" data-key="association" data-field="Association Name" pattern="[A-Za-z0-9\s]+" data-helper="Use Alphanumrical Only (A-Z 0-9)" data-invalid="Association Name invalid: Use Alphanumrical Only (A-Z 0-9)"  required/>
                                                        </div>  
                
                
                                                        <div class="col-12">
                                                            <input type="text" class="app-input editor-input" data-key="company" data-field="Management Company Name" pattern="[A-Za-z0-9\s]+" data-helper="Use Alphanumrical Only (A-Z 0-9)" data-invalid="Management Company Name invalid: Use Alphanumrical Only (A-Z 0-9)" />
                                                        </div>
                
                                                        
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
                                                        
                                                        <div class="col-md-6">
                                                            <input type="password" class="app-input editor-input" data-key="admin_password" data-field="Password" data-invalid="Password invalid !"  required/>
                
                                                            <div class="custom-control custom-checkbox small">
                                                                <input type="checkbox" class="custom-control-input" id="passwordChk">
                                                                <label class="custom-control-label" for="passwordChk" style="font-size: .8rem;">
                                                                    Show Password
                                                                </label>
                                                            </div>
                                                        </div>
                
                                                        
                
                                                        <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                                                            Office
                                                        </div>
                                                        
                                                        
                                                        <div class="col-sm-6">
                                                            <input type="text" class="app-input editor-input" data-key="phone" data-field="Phone" data-invalid="Phone invalid !"  required/>
                                                        </div>
                                                        <div class="col-sm-6 invisible">
                                                            <input type="email" class="app-input editor-input" data-key="email" data-field="Alternative E-mail" data-invalid="E-mail invalid !" />
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
                                                            <select class="app-input editor-input" data-clear-option="<option value=\'\' disabled selected>Enter Zip Code</option>" data-key="city" data-field="City" data-invalid="City invalid !" required>
                                                                <option value="" disabled selected>Enter Zip Code</option>                            
                                                            </select>
                                                        </div>
                                                        <div class="col-3">
                                                            <input type="text" class="app-input editor-input" data-key="state" data-field="State" data-invalid="State invalid !" data-disabled="true"  required/>
                                                            
                                                        </div>
                
                
                                                        
                                                        
                
                                                    </div>
                                                    
                                                    <button class="btn btn-primary btn-user btn-block mt-4">
                                                        Sign Up
                                                    </button>
                                                </form> ';
                                    }
                                    
                                    
                                ?>

                                
                            </div>
                        </div>
                            
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="<?php echo $base_web;?>/assets/js/jquery/jquery.min.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="<?php echo $base_web;?>/assets/js/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?php echo $base_web;?>/assets/js/sb-admin-2.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/global.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/utils.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/auth.js?v=1.1"></script>
    <script src="<?php echo $base_web;?>/assets/js/ui.js"></script>

    <script type="text/javascript" src="assets/js/states.js"></script>

    <script>

        function init(){
            
            var root = $('body');

            var data = <?= check_val($order, 'order', '{}'); ?>;

            data['email'] = data.email || data.admin_email || '';

            fill_editor(root, data);

            init_ui(root);

            var states = new States();
            var zip_input = root.find('input[data-key="zip"]');
            var phone_input = root.find('input[data-key="phone"]');
            var cities_select = root.find('select[data-key="city"]');
            var states_input = root.find('input[data-key="state"]');
            
            var type_select = root.find('select[data-key="type"]');
            
            
            var update_state_select = function(editZip = false){ 
                                        var selected = selectOption(cities_select); 
                                        states_input.val((selected.length > 0 ? (selected.data('state') || '') : '')); 

                                        if(editZip && selected.data('zip') && selected.length > 0)zip_input.val(selected.data('zip')); 
                                    }

            var update_city_select = function(value, state){
                        if(!value) value = zip_input.val();
                        fillSelect(cities_select, [{v:'', t:'Enter Valid Zip Code'}]);

                        if(!value || value.length < 2){
                            fillSelect(cities_select, [{v:'', t:'Enter Valid Zip Code'}]);
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

            type_select.change(function(){
                var value = this.value;

                if(value == 'client'){
                    root.find('input[data-key="association"]').val('').prop('required',true).closest('.col-12').removeClass('d-none');
                    root.find('input[data-key="company"]').closest('.app-input-group').find('.app-input-label').html('Management Company Name');
                }else{
                    root.find('input[data-key="association"]').val('').removeAttr('required').closest('.col-12').addClass('d-none');
                    root.find('input[data-key="company"]').closest('.app-input-group').find('.app-input-label').html('Company Name');
                }
            });
            

            phone_input.on('input change', function(){
                this.value = formatPhone(this.value);
            });

            zip_input.on('input change', function(){
                update_city_select(this.value);
            }).change();
            
            var forms = root.find('form.needs-validation'); 
            Array.prototype.filter.call(forms, function(form) {
        
                // when form is submitted
                form.addEventListener('submit', function(event) {
                    

                    // prevent <form> from recieving submit event
                    event.preventDefault();
                    event.stopPropagation();

                    //console.log(editor)

                    // if form is not valid, mark fields and show global error
                    if (form.checkValidity() === false) {
                        $(form).find(':invalid').addClass("app-input-invalid");
                        $(form).find(':valid').removeClass("app-input-invalid");
                        $(form).find('.invalid-global-feedback').removeClass("d-none");

                    // else if form is valid, hide global error and proceed
                    }else{                
                        $(form).find(':invalid').addClass("app-input-invalid");
                        $(form).find(':valid').removeClass("app-input-invalid");
                        $(form).find('.invalid-global-feedback').addClass("d-none");

                        var formData = gather_editor(form);
                        
                        ajax_post(global_base+"/app/auth.php", formData, function(resp){

                                console.log(resp);

                                if(resp.success == undefined){
                                    error(resp.error || '', '', default_error_alert_timeout);
                                }else{
                                    loading();
                                    success('Sign up successful', '', default_success_alert_timeout);
                                    setTimeout(function(){window.location.href = 'index.php';}, 2000);
                                    //console.log(document.cookie);
                                }

                        }, function(resp){console.log('error: ', resp.responseText)});
                    }

                    // set as validated form
                    form.classList.add('was-validated');
                    
                }, false);
            });
            

            $('.signup-card').removeClass('d-none');

            $('#passwordChk').change(function(){
            var password_input = $('input[data-key="admin_password"]');

            if(password_input.is('input:text')){
                password_input.get(0).type = 'password';
            }else{
                password_input.get(0).type = 'text';
            }
        });


        }

        init();

    </script>

</body>

</html>