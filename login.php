<?php


include_once('app/init.php');
include_once('app/auth.php');

auth_handle_disconnect();

auth_handle_login();

?>
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Login - <?php echo $app_name; ?></title>

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
    <!-- Custom styles for this template-->
    <link href="<?php echo $base_web;?>/assets/css/sb-admin-2.css?v=1.1" rel="stylesheet">
    
    <script>    
        var global_base = "<?php echo $base_web;?>";
    </script>

</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center mt-5">

            <div class="login-card ">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                           
                        <div class="">
                            <div class="px-2 py-2 px-3">
                                <div class="text-center mb-3 pb-3 border-bottom border-dark">
                                    <img class="mb-2 mt-0" style="height:64px;"  src="assets/images/logo.png?v=1.0"/>
                                </div>
                                <div class="text-center mb-3">
                                    <h1 class="h5 text-gray-900 mb-4">Login!</h1>
                                </div>
                                <form class="user login px-md-5" id="login_form">
                                    <div class="form-group">
                                        <input type="text" class="form-control form-control-user username"
                                            id="username" placeholder="Email Or Username">
                                    </div>
                                    <div class="form-group">
                                        <input type="password" class="form-control form-control-user password"
                                            id="password" placeholder="Password" >
                                    </div>
                                    <div class="form-group">
                                        <div class="custom-control custom-checkbox small">
                                            <input type="checkbox" class="custom-control-input" id="customCheck">
                                            <label class="custom-control-label" for="customCheck" style="font-size: .8rem;">
                                                Show Password
                                            </label>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-user btn-block">
                                        Login
                                    </button>
                                    <hr>
                                    <div class="text-center w-100">
                                        <a href="forgot.php" class="">
                                            <i class="fa fa-lock fa-fw"></i> Forgot Password ?
                                        </a>
                                    </div>
                                </form>
                                <hr>
                                <div class=" px-md-5 mb-3">
                                    <div class="text-center mb-2">You don't have an account ?</div>
                                    <a class="btn btn-success btn-user btn-block" href="offers.php">
                                        Sign Up
                                    </a>
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

    <!-- Custom scripts for all pages-->
    <script src="<?php echo $base_web;?>/assets/js/sb-admin-2.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/global.js"></script>
    <script src="<?php echo $base_web;?>/assets/js/auth.js?v=1.1"></script>

    <script>
        $('#customCheck').change(function(){
            var password_input = $('#password');

            if(password_input.is('input:text')){
                password_input.get(0).type = 'password';
            }else{
                password_input.get(0).type = 'text';
            }
        });
    </script>

</body>

</html>