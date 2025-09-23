<?php


include_once('app/init.php');
include_once('app/auth.php');

auth_disconnect();



?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Offers - <?php echo $app_name; ?></title>

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

    <div class="container px-xs-4">

        <!-- Outer Row -->
        <div class="row justify-content-center mt-2 main-content d-none">

            <div class="w-100 mb-5 mt-md-5">

                <div class="card o-hidden border-0 shadow-lg my-1 mb-5 pb-3">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                           
                        <div class="">
                            <div class="px-2 py-2 px-3">
                                <div class="text-center mb-3 pb-3 border-bottom border-dark">
                                    <img class="mb-2 mt-0" style="height:64px;"  src="assets/images/logo.png?v=1.0"/>
                                </div>
                                <div class="text-center mb-3">
                                    <h1 class="h5 text-gray-900 mb-4 text-capitalize">Choose your pricing plan</h1>
                                </div>

                                <div class="row">
                                    
                                    <div class="col-md-4 pb-3">
                                        <div class="border border-dark w3-round-xlarge px-0 py-4 text-center">
                                            <h5 class="font-weight-bold mb-3">Free 30 Days Trial</h5>
                                            <span class="font-weight-bold w3-text-green align-top" style="font-size:2rem;"></span>
                                            <span class="font-weight-bold w3-text-green align-top" style="font-size:4.5rem; line-height:4rem;">Free</span>

                                            <div class="w-100 mb-3"></div>

                                            <span class="font-weight-bold">Then $180 /Month</span><br>
                                            <span class="font-weight-bold">Or $1,800 /Year</span><br>
                                            <div class="h6 pt-2 font-weight-bold">&nbsp;</div>

                                            <label data-redirect="free" name="free" class="btn w3-green mt-3 mb-0">Start Free Trial</label>
                                            <div class="h6 pt-2 font-weight-bold">&nbsp;</div>

                                            
                                            <div class="w-100 border-top border-dark pt-3 mt-">
                                                <div><i class="fa fa-check mr-1 w3-text-green"></i> Acces to Reserve Data Visualization Platform</div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="col-md-4 pb-3">
                                        <div class="border border-dark w3-round-xlarge px-0 py-4 text-center">
                                            <h5 class="font-weight-bold mb-3">Monthly Subscription</h5>
                                            <span class="font-weight-bold w3-text-green align-top" style="font-size:2rem;">$</span>
                                            <span class="font-weight-bold w3-text-green align-top" style="font-size:4.5rem; line-height:4rem;">180</span>
                                            
                                            <div class="w-100 mb-3"></div>

                                            <span class="font-weight-bold">Monthly</span><br>
                                            <span class="">30 Free Trial</span><br>
                                            <div class="h6 pt-2 font-weight-bold">&nbsp;</div>

                                            <div class="d-flex flex-row justify-content-center w-100 font-weight-bold mt-4">
                                                <div class="mx-1 d-none">
                                                    <button type="submit" data-redirect="monthly_paypal" data-subscription="monthly_paypal" class="btn w3-green">Start Free Trial</button><br>
                                                    <span style="font-size:0.8rem;"><i class="fab fa-paypal w3-text-indigo mr-1"></i> Paypal</span>
                                                </div>
                                                <div class="mx-1">
                                                    <!-- <button type="submit" data-redirect="monthly_card" data-subscription="monthly_card" class="btn w3-green">Start Free Trial</button><br> -->
                                                    <label data-redirect="monthly_card" name="monthly_card" class="btn w3-green mt-3 mb-0">Start Free Trial</label><br>
                                                    <span style="font-size:0.8rem;"><i class="fa fa-credit-card w3-text-indigo mr-1"></i> Debit/Credit Card</span>
                                                </div>
                                            </div>

                                            
                                            <div class="w-100 border-top border-dark pt-3 mt-3">
                                                <div><i class="fa fa-check mr-1 w3-text-green"></i> Acces to Reserve Data Visualization Platform</div>
                                            </div>

                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 pb-3">
                                        <div class="border border-dark w3-round-xlarge px-0 py-4 text-center">
                                            <h5 class="font-weight-bold mb-3">Yearly Subscription</h5>
                                            <span class="font-weight-bold w3-text-green align-top" style="font-size:2rem;">$</span>
                                            <span class="font-weight-bold w3-text-green align-top" style="font-size:4.5rem; line-height:4rem;">1,800</span>
                                            
                                            <div class="w-100 mb-3"></div>

                                            <span class="font-weight-bold">Annually</span><br>
                                            <span class="">30 Free Trial</span><br>
                                            <div class="h6 pt-2 font-weight-bold">Save $360</div>
                                            


                                            <div class="d-flex flex-row justify-content-center w-100 font-weight-bold mt-4">
                                                <div class="mx-1 d-none">
                                                    <button type="submit" data-redirect="yearly_paypal" data-subscription="yearly_paypal" class="btn w3-green">Start Free Trial</button><br>
                                                    <span style="font-size:0.8rem;"><i class="fab fa-paypal w3-text-indigo mr-1"></i> Paypal</span>
                                                </div>
                                                <div class="mx-1">
                                                    <!-- <button type="submit" data-redirect="yearly_card" data-subscription="yearly_card" class="btn w3-green">Start Free Trial</button><br> -->
                                                    <label data-redirect="yearly_card" name="yearly_card" class="btn w3-green mt-3 mb-0">Start Free Trial</label><br>
                                                    <span style="font-size:0.8rem;"><i class="fa fa-credit-card w3-text-indigo mr-1"></i> Debit/Credit Card</span>
                                                </div>
                                            </div>

                                            
                                            <div class="w-100 border-top border-dark pt-3 mt-3">
                                                <div><i class="fa fa-check mr-1 w3-text-green"></i> Acces to Reserve Data Visualization Platform</div>
                                            </div>

                                        </div>
                                    </div>


                                </div>
                                
                                
                                
                            </div>
                        </div>
                            
                    </div>
                </div>

            </div>

        </div>


        <!-- Free trial form -->
        <div class="position-fixed d-none" style="top:0;left:0;right:0;bottom:0; background:rgba(0,0,0,0.5);" id="free_template">
            <div class="d-flex flex-column align-items-center py-3  pt-md-5">
                <div class="w3-white border-dark border w3-round-xlarge "  style="width:500px;">
                    
                    <div class="w-100 p-2 px-3 text-right h6 font-weight-bold " ><span class="close-btn pointer" onclick="$(this).closest('.position-fixed').addClass('d-none'); $('.main-content').removeClass('d-none');"><i class="fa fa-times"></i> Close</span></div>
                    
                    <div  class="form-row mt-2 m-0 px-2 pb-3"> 
                                        
                        
                        <div class="col-12">
                            <input type="text" class="app-input editor-input editor-focus" data-key="association" data-field="Association Name" pattern="[A-Za-z0-9\s]+" data-helper="Use Alphanumrical Only (A-Z 0-9)" data-invalid="Association Name invalid: Use Alphanumrical Only (A-Z 0-9)"  required/>
                        </div>  

                        
                        <div class="col-12 h6 mt-3 pb-1 border-bottom border-dark">
                            Contact
                        </div>

                        <div class="col-md-6">
                            <input type="text" class="app-input editor-input" data-key="admin_fn" data-field="First Name" data-invalid="First Name invalid !"  required/>
                        </div>
                            
                        <div class="col-md-6">
                            <input type="text" class="app-input editor-input" data-key="admin_ln" data-field="Last Name" data-invalid="Last Name invalid !"  required/>
                        </div>
                        
                                                                    
                        
                        <div class="col-sm-6">
                            <input type="text" class="app-input editor-input" data-key="phone" data-field="Phone" data-invalid="Phone invalid !"  required/>
                        </div>
                        <div class="col-sm-6">
                            <input type="email" class="app-input editor-input" data-key="admin_email" data-field="E-mail" data-invalid="E-mail invalid !" required/>
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

                        <div class="col-12 pt-4 text-center">
                            <label class="btn w3-green subscribe">Start 30  Day Free Trial</label>

                        </div>

                        
                        

                    </div>
                </div>
            </div>
        </div>


        <!-- Payment form -->
        <div class="position-fixed d-none" style="top:0;left:0;right:0;bottom:0; background:rgba(0,0,0,0.5);" id="pay_template">
            <div class="w-100 h-100 d-flex flex-row justify-content-center align-items-center">
                <div class="w3-white border-dark border w3-round-xlarge"  style="">
                    <div class="w-100 p-2 px-3 text-right h6 font-weight-bold " ><span class="close-btn pointer" onclick="$(this).closest('.position-fixed').addClass('d-none')"><i class="fa fa-times"></i> Close</span></div>
                    
                    <div class="w-100 text-center text-dark-gray h5 font-weight-bold " >
                        <h5 class="font-weight-bold mb-3">Choose your Subscription</h5>
                        <h5 class="font-weight-bold mb-3">Free 30 Day Trial Then</h5>
                    </div>

                    <div class="row m-0 p-3">
                        <div class="col-md-6 px-4 border-right-md border-dark">
                            <div class="text-nowrap text-center">
                                <span class="font-weight-bold w3-text-green align-bottom" style="font-size:2rem; line-height:100%">$180</span>
                                <span class="font-weight-bold align-bottom" style="font-size:1rem;">/Month</span>
                            </div>
                            <div class="d-flex flex-column text-center w-100 font-weight-bold mt-4">
                                <div class="mx-1 d-none">
                                    <button type="submit" data-subscription="free_monthly_paypal" class="btn w3-green">Start Free Trial</button><br>
                                    <span style="font-size:0.8rem;"><i class="fab fa-paypal w3-text-indigo mr-1"></i> Paypal</span>
                                </div>
                                <div class="mx-1 mt-3">
                                    <button type="submit" data-subscription="free_monthly_card" class="btn w3-green">Start Free Trial</button><br>
                                    <span style="font-size:0.8rem;"><i class="fa fa-credit-card w3-text-indigo mr-1"></i> Debit/Credit Card</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="w-100 my-3 d-md-none border-top border-dark"></div>

                        <div class="col-md-6 px-4 border-dark">
                            <div class="text-nowrap text-center">
                                <span class="font-weight-bold w3-text-green align-bottom" style="font-size:2rem; line-height:100%">$1,800</span>
                                <span class="font-weight-bold align-bottom" style="font-size:1rem;">/Year</span>
                            </div>
                            <div class="d-flex flex-column text-center w-100 font-weight-bold mt-4">
                                <div class="mx-1 d-none">
                                    <button type="submit" data-subscription="free_yearly_paypal" class="btn w3-green">Start Free Trial</button><br>
                                    <span style="font-size:0.8rem;"><i class="fab fa-paypal w3-text-indigo mr-1"></i> Paypal</span>
                                </div>
                                <div class="mx-1 mt-3">
                                    <button type="submit" data-subscription="free_yearly_card" class="btn w3-green">Start Free Trial</button><br>
                                    <span style="font-size:0.8rem;"><i class="fa fa-credit-card w3-text-indigo mr-1"></i> Debit/Credit Card</span>
                                </div>
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

    <!-- Role -->
    <script src="<?php echo $base_web;?>/assets/js/role.js?v=<?=time();?>"></script>

    <!-- Custom scripts for all pages-->
    <script src="<?php echo $base_web;?>/assets/js/sb-admin-2.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/global.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/errors.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/utils.js?v=1.1"></script>
    <script src="<?php echo $base_web;?>/assets/js/auth.js?v=1.1"></script>
    <script src="<?php echo $base_web;?>/assets/js/ui.js"></script>

    <script type="text/javascript" src="assets/js/states.js"></script>

    <script>

        function init(){

            var url= document.location.href;
            window.history.pushState({}, "", url.split("?")[0]);
            
            var root = $('body');

            root.find('button').click(function(){
                // loading();
            });


            var free_form = $('#free_template');
            var pay_form = $('#pay_template');
            
            root.find('label[name]').click(function(){

                var name = $(this).attr('name');
                free_form.attr('data-type', name).removeClass('d-none');

            });

            
            init_ui();

            var states = new States();
            var zip_input = free_form.find('input[data-key="zip"]');
            var phone_input = free_form.find('input[data-key="phone"]');
            var cities_select = free_form.find('select[data-key="city"]');
            var states_input = free_form.find('input[data-key="state"]');
            var subscriber = free_form.find('label.subscribe');

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

            phone_input.on('input change', function(){ this.value = formatPhone(this.value); });

            zip_input.on('input change', function(){ update_city_select(this.value); }).change();

            subscriber.click(function(){

                // validation
                var has_error = false;
                free_form.find('[required]').each(function(){
                    var item = $(this);

                    if(item.val().length == 0 || (item.data('key').includes('email') && !check_email(item.val()))){
                        has_error = true;
                        $(this).siblings('.invalid-feedback').show();
                    }else{
                        $(this).siblings('.invalid-feedback').hide();
                    }
                });

                if(has_error)return;

                
                ajax_get_no_loading(global_base+"/subscription.php", {"save":gather_editor(free_form)}, function(resp){

                    if(resp.error != undefined){
                        error(resp.error, '', default_alert_timeout);
                    }else{

                        var pay_type = free_form.data('type');

                        console.log(pay_type);

                        if(pay_type == 'free'){
                            pay_form.removeClass('d-none');

                        }else if (pay_type == 'monthly_card'){
                            subscription('monthly_card');

                        }else if (pay_type == 'yearly_card'){
                            subscription('yearly_card');

                        }

                    }

                }, function(resp){ console.log('error: ', resp.responseText)});


                
                

            });


            var subscription = function(subscription){
                    
                    var data = {"subscription":subscription};

                    // if(subscription.indexOf('free') == 0){
                        data['infos'] = gather_editor(free_form);
                    // }

                    var l = loading();

                    ajax_get(global_base+"/subscription.php", data, function(resp){

                        console.log(resp);

                        if(resp.error != undefined){
                            l.remove();
                            error(resp.error, '', default_alert_timeout);
                        }else{

                            window.location.href = resp.success;
                            
                            reset_editor(free_form);
                            pay_form.addClass('d-none');
                            free_form.addClass('d-none'); 

                            reset_editor(free_form);
                            
                        }

                    }, function(resp){ l.remove();console.log('error: ', resp)});

            }

            // get checkout
            root.find('button[data-subscription]').click(function(){
                var type = $(this).data('subscription');

                subscription(type);
                
            });



            // redirect action
            var redirect = '<?= isset($_GET['redirect']) ? $_GET['redirect']  : '';?>';
            if(redirect && root.find('[data-redirect="'+redirect+'"]').length > 0){
                root.find('[data-redirect="'+redirect+'"]').click();
            }else{
                root.find('.main-content').removeClass('d-none');
            }

        }

        init();

    </script>

</body>

</html>