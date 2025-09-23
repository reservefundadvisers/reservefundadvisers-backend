
/*      */
$(function() {

    // login form
    $('form.login').each(function() {

        
        $(this).submit(function(e){
            // prevent <form> from recieving submit event
            event.preventDefault();
            event.stopPropagation()

            login();
        });
        
        
    });

    // login validation code form
    $('form.auth-validate').each(function() {

        
        $(this).submit(function(e){
            // prevent <form> from recieving submit event
            event.preventDefault();
            event.stopPropagation()

            auth_validate();
        });
        
        
    });
    
    // forgot password form
    $('form.forgot-password').each(function() {

        
        $(this).submit(function(e){
            // prevent <form> from recieving submit event
            event.preventDefault();
            event.stopPropagation()

            forgot_password();
        });
        
        
    });

    
    
    // reset password form
    $('form.reset-password').each(function() {

        
        $(this).submit(function(e){
            // prevent <form> from recieving submit event
            event.preventDefault();
            event.stopPropagation()

            reset_password();
        });
        
        
    });
    
});


/* LOGIN */

// login 
function login(){

    var e = $("#login_form");
    if(e.length < 1){

        error('Please enter login !');
        return;
    }

    var user = $('form.login').find('#username').val();
    var passwd = $('form.login').find('#password').val();


    if(user.length < 1){
        
        error('Please enter <b>Username</b> !');
        return;
    
    }else if(passwd.length < 1){
        
        error('Please enter <b>Password</b> !');
        return;
    }

    ajax_post(global_base+"/app/auth.php", {"cmd":"login", username: user, password: passwd}, function(resp){

        console.log(resp);

        if(resp.error != undefined){
            error(resp.error, '', default_alert_timeout);
        }else{
            window.location.href = 'index.php';
            //success(resp.success, '', default_alert_timeout);
            //console.log(document.cookie);
        }

    }, function(resp){console.log('error: ', resp.responseText)});
}


// resend validation code
function resend_code(){

    
    ajax_post(global_base+"/app/auth.php", {"cmd":"resend_auth"}, function(resp){

        console.log(resp);

        if(resp.error != undefined){
            error(resp.error, '', default_alert_timeout);
        }else{
            success(resp.success + ' <br>', '', default_alert_timeout);            
        }

    }, function(resp){console.log('error: ', resp.responseText)});
}

// validation code
function auth_validate(){

    var e = $("#validate_form");
    if(e.length < 1){
        return;
    }

    var code = e.find('#auth_code').val();
    
    
    if(code.length < 1){
        
        error('Please enter <b>Validation Code</b> !');
        return;
    
    }
    
    ajax_post(global_base+"/app/auth.php", {"cmd":"auth_login", "code": code }, function(resp){

        if(resp.error != undefined){
            error(resp.error, '', 5000);
        }else{
            success(resp.success + ' <br>You will be redirected soon.', '', 5000);
            setTimeout(function(){window.location.href = 'login.php';}, 2000);
            //console.log(document.cookie);
        }

    }, function(resp){console.log('error: ', resp.responseText)});
}

/* **** */


/* FORGOT PASSWORD */

function forgot_password(){

    var e = $("#forgot_password_form");
    if(e.length < 1){
        return;
    }

    var user = e.find('#username').val();

    
    if(user.length < 1){
        
        error('Please enter <b>Username</b> !');
        return;
    
    }
    

    ajax_post(global_base+"/app/auth.php", {"cmd":"forgot_password", "username": user }, function(resp){

        console.log(resp);

        if(resp.error != undefined){
            error(resp.error, '', default_alert_timeout);
        }else{
            success(resp.success , '', default_alert_timeout);
            setTimeout(function(){window.location.href = 'login.php';}, 2000);
            //console.log(document.cookie);
        }

    }, function(resp){console.log('error: ', resp.responseText)});
}


/* **** */



/* RESET PASSWORD */

function reset_password(){

    var e = $("#reset_password_form");
    if(e.length < 1){
        return;
    }

    var newpass = e.find('#newpassword').val();
    var confnewpass = e.find('#confirmnewpassword').val();
    var key = getURLparam('key');

    
    if(!key){
        error('The <b>Reset Key</b> is invalid, please check you email for the valid link !');
        return;
    }
    
    if(newpass.length < 1){
        error('Please enter <b>Password</b> !');
        return;
    }
    
    
    if(confnewpass.length < 1){
        error('Please <b>Confirm Password</b> !');
        return;
    }

    if(newpass != confnewpass){
        error('The entered <b>Password</b> and <b>Confirm Password</b> mismatch !');
        return;
    }
    

    ajax_post(global_base+"/app/auth.php", {"cmd":"reset_password", "key": key, "password":newpass }, function(resp){

        console.log(resp);

        if(resp.error != undefined){
            error(resp.error, '', default_alert_timeout);
        }else{
            success(resp.success , '', default_alert_timeout);
            setTimeout(function(){window.location.href = 'login.php';}, 2000);
            //console.log(document.cookie);
        }

    }, function(resp){console.log('error: ', resp.responseText)});
}


/* **** */


