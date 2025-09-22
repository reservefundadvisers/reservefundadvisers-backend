/* INIT */

init();


$(document).ready(function() {
    
    $('[data-toggle="tooltip"]').tooltip();


    
});


function init(){

    check_role();

    $('#main').removeClass('d-none');

}
  

/* ---- */



/* VARS */

var default_alert_timeout = 2000;
var default_error_alert_timeout = 3000;
var default_success_alert_timeout = 2000;



// execute ajax post with loader
function ajax_post(_url, _data, successCb, errorCb, notJson, loadingContent = '', loadingElement, loadingHasCancel, loadingCancelCallback, showProgress){
    
    var ajax;
    var cb = function(){
        if(ajax)ajax.abort();
        if(loadingCancelCallback)loadingCancelCallback();
    };

    var l = loading(loadingContent, loadingElement, loadingHasCancel, cb);
    ajax = ajax_post_no_loading(_url, _data, 
            function(data){l.remove(); if(successCb)successCb(data)}, 
            function(data){l.remove(); if(errorCb)errorCb(data)}, 
            notJson, 
            function(loaded, total){
                if(!showProgress)return;
                if(total > 0){
                    l.content(loadingContent+'<br>'+parseInt(loaded*100/total)+"%");
                }else{
                    l.content(loadingContent+'<br>'+loaded+' B');
                }
            }
    );
}


// execute ajax get with loader
function ajax_get(_url, _data, successCb, errorCb, notJson, loadingContent, loadingElement, loadingHasCancel, loadingCancelCallback, showProgress){
    
    var ajax;
    var cb = function(){
        if(ajax)ajax.abort();
        if(loadingCancelCallback)loadingCancelCallback();
    };

    var l = loading(loadingContent, loadingElement, loadingHasCancel, cb);
    ajax = ajax_get_no_loading(_url, _data, 
                        function(data){l.remove(); if(successCb)successCb(data)}, 
                        function(data){l.remove(); if(errorCb)errorCb(data)}, 
                        notJson, 
                        function(loaded, total){
                            if(!showProgress)return;
                            if(total > 0){
                                l.content(loadingContent+'<br>'+parseInt(loaded*100/total)+"%");
                            }else{
                                l.content(loadingContent+'<br>'+loaded+' B');
                            }
                        }
                );
}

// execute ajax post without loader
function ajax_post_no_loading(_url, _data, successCb, errorCb, notJson, progressCb){
    
    var opt = {  
                    url: _url,
                    type: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    dataType: notJson == true ? 'text' : 'json',
                    data: _data,
                    error:function(data){ if(errorCb)errorCb(data) },
                    success:function(data){
                        if(typeof data == 'string')data = data.trim();
                        if((typeof data == 'string' && data.indexOf('ERR_LOGIN') >= 0) || (data.error && data.error == "ERR_LOGIN")){window.location.href = 'login.php'; return;}
                        if(successCb)successCb(data)
                    },
                    xhr: function() {
                        var xhr = $.ajaxSettings.xhr();
                        xhr.onprogress = function (e) {
                            // For downloads
                            //console.log(e.loaded, e.total);
                            if(progressCb)progressCb(e.loaded, e.total);
                            
                        };
                        xhr.upload.onprogress = function (e) {
                            // For uploads
                            //console.log(e.loaded, e.total);
                            if(progressCb)progressCb(e.loaded, e.total);

                        };
                
                        return xhr;
                    }  
                };

    if(_data instanceof FormData){
        opt['contentType'] = false;
        opt['processData'] = false;
    }
    
    var ajax = $.ajax(opt);
    return ajax;
}

// execute ajax get without loader
function ajax_get_no_loading(_url, _data, successCb, errorCb, notJson, progressCb){
    
    var opt =   {  
                    url: _url,
                    type: 'GET',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    dataType: notJson == true ? 'text' : 'json',
                    data: _data,
                    error:function(data){if(errorCb)errorCb(data) },
                    success:function(data){            
                        if(typeof data == 'string')data = data.trim();
                        if((typeof data == 'string' && data.indexOf('ERR_LOGIN') >= 0) || (data.error && data.error == "ERR_LOGIN")){window.location.href = 'login.php'; return;}
                        if(successCb)successCb(data)
                    },
                    xhr: function() {
                        var xhr = $.ajaxSettings.xhr();
                        xhr.onprogress = function (e) {
                            // For downloads
                            //console.log(e.loaded, e.total);
                            if(progressCb)progressCb(e.loaded, e.total);
                            
                        };
                        xhr.upload.onprogress = function (e) {
                            // For uploads
                            //console.log(e.loaded, e.total);
                            if(progressCb)progressCb(e.loaded, e.total);

                        };
                
                        return xhr;
                    }
                }
    if(_data instanceof FormData){
        opt['contentType'] = false;
        opt['processData'] = false;
    }

    var ajax = $.ajax(opt);

    return ajax;
}


// error alert
function error(content, title, timeout, actions, actionCallback){
    return alertBox('bg-danger', 'text-white', true, ( content ? content : 'Unknown Error !'), ( title ? title : 'Error'), timeout, actions, function(e){if(actionCallback)actionCallback(e);} );
}

// success alert
function success(content, title, timeout, actions, actionCallback){
    return alertBox('bg-success', 'text-white', false, ( content ? content : 'Done successfully !'), ( title ? title : 'Success'), timeout, actions, function(e){if(actionCallback)actionCallback(e);} );
}

// info alert
function info(content, title, timeout, actions, actionCallback){
    return alertBox('bg-info', 'text-white', true, ( content ? content : 'No information to display !'), ( title ? title : 'Info'), timeout, actions, function(e){if(actionCallback)actionCallback(e);} );
}

// warning alert
function warning(content, title, timeout, actions, actionCallback){
    return alertBox('bg-warning', 'text-dark', true, ( content ? content : 'No warning to display !'), ( title ? title : 'Warning'), timeout, actions, function(e){if(actionCallback)actionCallback(e);} );
}

// alert
function alert(content, title, timeout, actions, actionCallback){
    return alertBox('bg-white', 'text-dark', true, ( content ? content : ''), ( title ? title : 'Alert'), timeout, actions, function(e){if(actionCallback)actionCallback(e);} );
}


// confirmation alert
function confirm(content, title, actions, actionCallback){
    return alertBox('bg-white', 'text-dark', true, ( content ? content : 'Are you sure ?'), ( title ? title : 'Confirmation'), 0, actions ||[{text: 'Cancel', action:'cancel', classes: 'btn-danger'}, {text: 'Confirm', action:'ok', classes: 'btn-success'}], function(e){if(actionCallback)actionCallback(e);} );
}

// generic alert
function alertBox(bg, color, modal, content, title, timeout, actions, actionCallback){
 
    var actionBtns = "";
    
    if(actions){
        for(var i=0; i<actions.length; i++){
            const btn = actions[i];

            // if has 'cancel' btn but no text or classes, add by default
            if(btn.action == 'cancel'){
                btn.text = btn.text || 'Cancel';
                btn.classes = btn.classes || 'btn-danger';
            }
            
            // if has 'ok' btn but no text or classes, add by default
            else if(btn.action == 'ok'){
                btn.text = btn.text || 'Confirm';
                btn.classes = btn.classes || 'btn-success';
            }
            
            if(!btn.text)continue;

            actionBtns += '<button type="button" class="btn action-btn btn-sm '+( btn.classes ? btn.classes+' ' : '' )+'" data-value="'+( btn.action ? btoa(btn.action) : btoa(btn.text) )+'" style="'+( btn.bg ? 'background:'+btn.bg+';' : '' )+( btn.col ? 'color:'+btn.col : '' )+'">'+btn.text+'</button>';
        }
    }

    var alert = $(' <div class="modal d-block"  style="'+(modal ? 'background:rgba(0,0,0,0.5);' : '' )+'" onclick="$(this).remove()" ><div class="modal-dialog " ><div class="modal-content '+bg+' '+color+' r-0 "  onclick="event.stopPropagation();event.preventDefault();">   \
                        <div class="modal-header  border-0 pt-3 pl-3 pb-0  '+color+' " ><h4 class="modal-title '+color+' font-weight-bold" style="font-size: 1.075rem;">'+title+'</h4><button type="button" class="close  '+color+' p-3"  onclick="$(this).closest(\'.modal\').remove()">&times;</button></div>  \
                        <div class="modal-body '+color+' pl-3 pt-0 "  style="font-size: .975rem;"> '+content+'</div>    \
                        <div class="modal-footer '+(actionBtns.length > 0 ? '' : 'd-none')+'">'+actionBtns+'</div>    \
                    </div></div></div>');

    alert.find('.action-btn').on('click', function(e){               

                            if(actionCallback)actionCallback(atob($(e.target).data('value')));
                            alert.modal('hide'); alert.remove();
                        });


    $('body').append(alert);


    if(timeout > 0)setTimeout(function(){alert.modal('hide'); alert.remove(); }, timeout)

    return alert;
}

// generic loading
function loading( content, e, hasCancel, cancelCallback){
    
    var actionBtns = "";

    var parent = e ? ( typeof e === 'object' ? e : $(e) ) : $('body');
        
    //if(parent.find('.loading').length > 0)return $('<div></div>');        

    var alert = $(' <div class="modal d-block '+(e ? 'position-absolute' : '')+' loading"  style="background:rgba(40,40,40,0.3);" onclick=""><div class="w-100 modal-c" align="center"> \
                        <div class="">  \
                        <div class="text-black font-weight-bold mb-2 alert-content '+( !content ? 'd-none' : '')+' " style="text-shadow: 1px 1px #ffffff;" align="center">'+content+'</div>      \
                        <div class="lds-ellipsis d-block ml-2"><div></div><div></div><div></div><div></div></div>     \
                        <button type="button" class="btn btn-sm mt-0 btn-danger '+( !hasCancel ? 'd-none' : '')+'">Annuler</button>      \
                        </div>  \
                    </div></div> ');

    alert.on('click', function(e){
                            e.preventDefault();
                            e.stopPropagation();
                            if(!$(e.target).hasClass('btn'))return; 
                            if(cancelCallback)cancelCallback();
                            alert.modal('hide'); alert.remove();
                        });
    alert.content = function(c){
                        var content = alert.find('.alert-content')
                        content.html(c);
                        content.toggleClass('d-none', c.length < 1);
                    }

    parent.append(alert);


    // adjust vertical height
    var ratio = (alert.outerHeight() - alert.find('.modal-c').first().outerHeight()) / 2
    alert.find('.modal-c').css('margin-top', Math.trunc(ratio)+'px');

    return alert;
}




// fetch editor modal
function edit_element(get_url, get_params, set_url, callback, fetchedCb, beforeSubmitCb, actionCb, context){
    
    var editor;

    // process callback for editing before filling form
    var processFetchedCb = function(form){
                            if(fetchedCb)form = fetchedCb(form);
                            return form;                            
                        };

    // success callback when element is saved
    var successCb = function(resp){ if(callback)callback(resp, context);  };

    // button action callback for form clicked buttons or events of editor
    var _actionCb = function(action, _editor){ if(action.action == 'loaded')editor = _editor; if(actionCb)actionCb(action, editor, context); };

    // create editor form
    editor = create_editor(get_url, get_params, set_url, _actionCb, beforeSubmitCb, successCb, processFetchedCb);

    return editor;
    
}


// fetch viewer modal
function view_element(get_url, get_params, callback, fetchedCb, actionCb, context){
    
    var viewer;

    // process callback for editing before filling form
    var processFetchedCb = function(form){
                            if(fetchedCb)form = fetchedCb(form);
                            return form;                            
                        };

    // success callback when student is saved
    var successCb = function(resp){ if(callback)callback(resp, context);  };

    // button action callback for form clicked buttons
    var _actionCb = function(action, _viewer){ if(action.action == 'loaded')viewer = _viewer; if(actionCb)actionCb(action, viewer, context); };

    // create student viewer form
    viewer = create_viewer(get_url, get_params, _actionCb);

    return viewer;
    
}


/**
 *  Data editor:
 * 
 *      create_editor -> open_editor -> close_editor
 * 
 *      reset_editor() or update_editor() on given editor object
 * 
 * 
 *  see 'used_classes_and_data.txt' for used classes and data values      
 * 
 * 
 */

// local editor creator
 function open_editor(template, actionCb, closeOnSubmit, allowMultiple, e){

    if(!template)return false;

    // init the editor with the given template
    var editor = $(template);

    if(checkError(template, true) || editor.length == 0 )return;

    

    check_role(editor);

    //console.log(template);

    // Format: each input to be used add class "editor-input" and type in "data-key"
    // Returned data: {"data-key":"value", ...}
    // for sub data include "path/to/key" in "data-key", exemple: data-key="a/b/c" -> {a: b: c:{"data-key":"value"}}
    // initData is the data to be inputed on creation: see update_editor() for format

    var parser = function(data){

    };

    // editor variable used instead of editor-input data-key values, in case
    // a more complexe computation is needed...the variable name is added as editor data-editor-var attribute
    // and can be retrieved using the window['<data-editor-var>']
    var editor_var = 'editor_'+parseInt(Math.random()*99999)+'_data';  
    var editor_func = 'editor_'+parseInt(Math.random()*99999)+'_func';

    // Implement Form validation
    var forms = editor.find('form.needs-validation'); 
    var validation = Array.prototype.filter.call(forms, function(form) {
        
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
                $(form).find('.invalid-global-feedback').addClass("d-none");


                // init form data object to be sent from all inputs marked as editor-input
                var formData = {}
                
                // check if the given editor variabale has been used
                if(window[editor_var]){
                    
                    formData = window[editor_var];

                }else{

                    formData = gather_editor(form);
    
                    //console.log(formData);
    
                    //return;
                }
                

                // call submit callback
                if(actionCb)actionCb({action:"submit", value:formData}); 

                // if close on submit, close editor
                if(closeOnSubmit)
                    close_editor(editor);
            }

            // set as validated form
            form.classList.add('was-validated');
            
        }, false);
    });


    // add to be able to programmatically submit form for validation
    if(forms.find('button:submit').length == 0)
        forms.append('<button class="d-none" type="submit"></button>'); 

    
    // set all loadable values
    editor.find('.editor-input[data-loadable="true"]').each(function(){
            var item = $(this);
            $(this).val(item.data('load-value'))
    });
    
    // Implement Button clicks
    editor.on('click', function(e){
        var btn = $(e.target).closest('button');
        
        // if event is not from a button return
        if(btn.closest('button').length == 0 || btn.is(':submit'))return;

        // if submit button clicked
        if(btn.is('.action-submit:not(.keep-editor)') && !btn.parent().hasClass('modal-footer')){ forms.find('button:submit').trigger('click'); return; }
        
        // if keep editor button clicked, select editor-multiple-edit checkbox then delay form submit
        if(btn.hasClass('action-submit keep-editor')){editor.find('.editor-multiple-edit').prop('checked', 'true'); setTimeout(function(){forms.find('button:submit').trigger('click');}, 10); return;}
        
        // send as cancel, or custom action
        if(btn.hasClass('action-cancel')){if(actionCb)actionCb({action:"cancel"}); return;}
        if(btn.hasClass('action-custom')){if(actionCb)actionCb({action:"custom", value: btn.data('action'), data: btn.data()}); return;}
        
    });
    
    
    // Implement Button clicks
    editor.on('change', function(e){
        var btn = $(e.target);
        
        // if event is not from a button return
        if(btn.length == 0 || !btn.is(':checkbox'))return;

        // send as cancel, or custom action
        if(btn.hasClass('action-custom')){if(actionCb)actionCb({action:"custom", value: btn.data('action'), data: {...btn.data(), state: (btn.is(':checked') ? 1 : 0)}, element: btn}); return;}
        
    });

    // if modal is hidden, remove it
    editor.on("hidden.bs.modal", function () {        
        //editor.modal("dispose");
        if(editor.hasClass('update'))if(actionCb)actionCb({action:"update"});
        editor.remove();
        if($('.modal').length > 0)
        {
            $('body').addClass('modal-open');
        }
        
    });


    // if multiple edits is not allowed, remove multiple editor checkbox
    if(allowMultiple != undefined && !allowMultiple)
        editor.find('.global-multiple-edit').parent().remove();


    editor.prepend('<script>var  '+editor_var+' = null; var '+editor_func+' = function(){}; </script>');
    editor.data('editor-var', editor_var);
    editor.data('editor-func', editor_func);

    editor.bind('customEvent', function(event, action){ if(actionCb)actionCb(action); });
    

    // add editor component
    var parent = e ? $(e) : $('body');
    parent.append(editor);



    // restart input functions
    init_ui(editor);

    // init help
    //init_help(editor);

    // init too
    editor.find('[data-toggle="tooltip"]').tooltip();
    

    // show editor
    editor.modal('show');
    
    $('.modal-backdrop').remove();
    

    return editor;
}

// fetch modal with given url and call local editor creator
function create_editor(get_url, get_params, set_url, actionCb, processBeforeSubmit, successCb, processFetched, allowMultiple ){


    var editor;
    var cb = function(ret){
        
        //console.log(ret);
        
        // if editor form is submitter
        if(ret.action == "submit"){
            
            //console.log(ret.value)
            
            // get editor form data
            var data = ret.value;

            // if editing append get_params
            if(get_params)data = {...data, ...get_params};

            // process form data before submitting, and if received false, don't submit
            var no_submit = false;
            if(processBeforeSubmit){
                                    var tmp = processBeforeSubmit(data); 
                                    if(tmp == false){ no_submit = true; }
                                    if(tmp != undefined && tmp != null)data = tmp;
                                    //console.log('data: ', tmp, data)
            }



            // request save
            if(!no_submit && set_url && set_url.length > 0){
                ajax_post(  set_url, data, function(resp){

                                console.log(resp);
                                
                                // processing saving errors
                                if(resp && resp.error !== undefined ){
                                    var errTxt;
                                    if(!resp.error){

                                    }else if(typeof resp.error === 'string'){
                                        // single error code
                                        errTxt = handleError(resp.error);
                                    }else{
                                        // multiple errors
                                        errTxt = "Following errors had occured:</br></br>"
                                        for(const err of Object.keys(resp.error)){
                                            errTxt += getErrorField(err, editor, resp.error[err], true) + "</br></br>";
                                        }
                                    }
                                        
                                    error(errTxt);
                                    return;
                                }
                                
                                // check if multiple edit is checked
                                var multiedit = editor.find('.editor-multiple-edit').first().is(':checked')
                                if(multiedit != undefined && multiedit == false){
                                    close_editor(editor);
                                    if(actionCb)actionCb({action:'closed'}, editor)
                                }else if(multiedit){
                                    reset_editor(editor)
                                    if(actionCb)actionCb({action:"reset"}, editor);

                                    editor_func = editor.data('editor-func');
                                    
                                    if(window[editor_func] && typeof window[editor_func] == 'function'){
                                        window[editor_func]({event:"reset"});
                                    }
                                    
                                }

                                // success alert
                                success((get_params ? "Edited":"Added")+" successfully !", null, multiedit ? 500 : default_success_alert_timeout)

                                // call success callback
                                if(successCb)successCb(resp);

                            }, 
                            function(data){ error("Error while "+(get_params ? "editing":"adding")+" !", "", default_error_alert_timeout);  console.log(data.responseText); }
                        );
            }else{
                    /*            
                    // check if multiple edit is checked
                    var multiedit = editor.find('.global-multiple-edit').first().is(':checked')
                    if(multiedit != undefined && multiedit == false){
                        close_editor(editor);
                    }else if(multiedit){
                        reset_editor(editor)
                        if(actionCb)actionCb({action:"reset"}, editor);
                    }
                    */

                    // call success callback
                    if(actionCb)actionCb({action:"submit", value: data}, editor);

            }
        }else{
            if(actionCb)actionCb(ret, editor);
        }

    };

    //console.log(get_params)

    if(get_url){
    

        // if id is defined start in edit mode
        ajax_get(  get_url, (get_params || {}), function(resp){

                // console.log(resp);

                if(isJSON(resp)){
                    resp = parseJSON(resp);
                    if(resp.error){
                        error(handleError(resp.error),'', default_error_alert_timeout);
                        return;
                    }
                }

                
                if(resp.length < 1){
                    error("Couldn't retrieve editor.", "", default_error_alert_timeout);
                    return;
                }
                
                // add any custom process 
                if(processFetched){resp = processFetched(resp); if(!resp)return; }
                
                // create editor window
                editor = open_editor(resp, cb, false, allowMultiple);

                
                if(actionCb)actionCb({action:'loaded'}, editor)
                

                // update editor with id data
                //update_editor(editor, data);

            }, 
            function(data){ error("Couldn't retrieve editor.", "", default_error_alert_timeout);  console.log(data.responseText); },
            true
        );
    
    }
    

}

// reset all inputs inside editor
function reset_editor(editor){
    
    if(!editor)return;
    
    
    // reset all input fields
    editor.find('.editor-input').each(function(){

        var item = $(this);
        if(item.hasClass('editor-no-init'))return;
        var init_value = item.data('init-value');
        //console.log('init_value: '+init_value);
        if(item.is('select')){
            if(item.data('clear-text'))
                fillSelect(item, [{v:'', t:item.data('clear-text')}]);
            if(item.data('clear-option'))
                item.html(item.data('clear-option'));
            else
                selectByValue(item, init_value || item.find("option:first").val());
        }else{
            item.val(init_value || '');
        }
        

    });
    
    editor.find('.editor-value').each(function(){

        var item = $(this);
        if(item.hasClass('editor-no-init'))return;
        var init_value = item.data('init-value');
        //console.log(item.is(':required'));
        if(item.is('select')){
            selectByValue(item, init_value || item.find("option:first").val());
        }else{
            item.html(item, init_value || "");
        }
        

    });

    
    // reset all input fields
    editor.find('.editor-collapse').removeClass('show').parent().removeClass('collapsed');
    
    // reset form validation
    editor.find('form')[0].classList.remove('was-validated');

    // focus
    setFocus(editor.find('.editor-focus'));
    
}

// close the editor modal
function close_editor(editor){
    if(!editor)return;

    editor.modal("hide");
    //editor.remove(); // remove is emplement on creation from template
}

// editor gather data from inputs
function gather_editor(e){
    
    var formData = {};
    if(!e)return formData;


    $(e).find('.editor-slider').each(function(){

        // get the field
        var obj = $(this);

        var value = obj.slider("option", "range") == true ? obj.slider("values") : obj.slider("value")
        
        if(obj.data('key') && obj.data('key').length > 0){
            objectSetDataToPath(formData, obj.data('key'), value || null)
        }

    });

    $(e).find('.editor-input').each(function(){
                    
        // get the field
        var obj = $(this);

        if(obj.hasClass('editor-ignore'))return;

        // if radio and unchecked, continue
        if(obj.is(':radio') && !obj.is(":checked"))return;
        

        // get the value of the field
        var value;
        if(obj.is(':checkbox')) {
            value = obj.is(":checked") ? '1' : '0' ;                            
        }else if(obj.attr('type') == 'date') {
            value = getTimestamp(obj.val());                            
        }else {
            if(obj.data('hidden-value') !== undefined )value = obj.data('hidden-value');
            else if(obj.data('value') !== undefined )value = obj.data('value');
            else value = obj.val();
            
        }

        // set value to text transform
        switch(obj.css('text-transform')){
            case 'uppercase': value = value.toUpperCase(); break;
            case 'lowercase': value = value.toLowerCase(); break;
            case 'capitalize': value = ucfirst(value); break;
        }

        if(obj.data('accept-on') && obj.data('accept-on') != value) return;

        // if the value need to be added to nested object, create object then add in key/value
        //if(obj.data('sub-key')){
        //    objectSetDataToPath(formData, obj.data('sub-key'), {"key":obj.data('key'), "value":value});

        // else add key/value to form data object
        //}else{
            if(obj.data('key') && obj.data('key').length > 0){
                objectSetDataToPath(formData, obj.data('key'), value || null)
            }
            //formData[obj.data('key')] = value || null;
        //}

    });

    $(e).find('.editor-value').each(function(){
                    
        // get the field
        var obj = $(this);

        // get the value of the field
        var value;
        
        if(obj.data('value') !== undefined )value = obj.data('value');
        else value = obj.html();
        

        // set value to text transform
        switch(obj.css('text-transform')){
            case 'uppercase': value = value.toUpperCase(); break;
            case 'lowercase': value = value.toLowerCase(); break;
            case 'capitalize': value = ucfirst(value); break;
        }

        if(obj.data('accept-on') && obj.data('accept-on') != value) return;

        // if the value need to be added to nested object, create object then add in key/value
        //if(obj.data('sub-key')){
        //    objectSetDataToPath(formData, obj.data('sub-key'), {"key":obj.data('key'), "value":value});

        // else add key/value to form data object
        //}else{
            if(obj.data('key')){
                objectSetDataToPath(formData, obj.data('key'), value || null)
            }
            //formData[obj.data('key')] = value || null;
        //}

    });

    return formData;
}


function fill_editor(e, data = {}){
    if(!data || typeof data != 'object')return;

    var root = typeof e == 'string' ? $(e) : e;

    var fill = function(key, value){
        
        if(typeof value == 'object'){
            for(const i in value){
                fill(key+'/'+i, value[i]);
            }
            return;
        }

        var item = root.find('[data-key="'+key+'"]');
        if(item.length == 0)return;


        if(item.is('input')){
            
            if(item.is(':checkbox'))item.prop('checked', value == 1);
            else item.val(value);
        }else if(item.is('select')){
            selectByValue(item, value)
        }else if(item.is('textarea')){
            item.val(value);
        }else {
            item.html(value);
        }

    }

    for(const key in data){
        
        var value = data[key];
        
        fill(key, value);
        
        

    }
}


/* ******* */



/* VIEWER */

// local viewer creator
function open_viewer(template, btnCb, e){

    if(!template)return false;

    // create viewer from given template
    var viewer = $(template);

    check_role(viewer);

    // Implement Button clicks
    viewer.on('click', function(e){
        var btn = $(e.target).closest('button');
        
        if(btn.closest('button').length == 0)return;

        if(btn.hasClass('action-custom')){if(btnCb)btnCb({action:"custom", value: btn.data('action')}); return;}
        else if(btn.hasClass('action-close')){if(btnCb)btnCb({action:"close", value: btn.data('action')}); return;}
        
    });

    viewer.on("hidden.bs.modal", function () {
        // put your default event here
        // viewer.modal("dispose");
        viewer.remove();
        if($('.modal').length > 0)
        {
            $('body').addClass('modal-open');
        }
        
    });

    
    // restart input functions
    init_inputs(viewer);

    // init help
    init_help(viewer);

    // create viewer     
    var parent = e ? $(e) : $('body');
    parent.append(viewer);
    

    // show viewer
    viewer.modal('show');
    
    $('.modal-backdrop').remove();

    return viewer;
}

// fetch modal with given url and call local viewer creator
function create_viewer(get_url, get_params, actionCb){


    var viewer;
    var cb = function(ret){
        
        //console.log(ret);
        
        // if editor form is submitter        
        if(actionCb)actionCb(ret, viewer)
    
            

    };

    

    // list all items
    var fetch = function(){
                            ajax_get(  get_url, (get_params || {}), function(resp){

                                    //console.log("list: ",resp);
                                    if(isJSON(resp)){
                                        resp = parseJSON(resp);
                                        if(resp.error){
                                            error(handleError(resp.error),'', default_error_alert_timeout);
                                            return;
                                        }
                                    }

                                    
                                    if(resp.length < 1){
                                        error("Erreur de récuperation des données.", "", default_error_alert_timeout);
                                        return;
                                    }
                                    
                                    
                                    // add any custom process
                                    //if(processFetchedIdData)resp = processFetchedIdData(resp);
                                    
                                    // create viewer window
                                    viewer = open_viewer(resp, cb);
                                    if(actionCb)actionCb({action:'loaded'}, viewer);
                                
                                    
                                    // update editor with id data
                                    //update_editor(editor, item);

                                }, 
                                function(data){ error("Erreur de récuperation des données.", "", default_error_alert_timeout);  console.log(data.responseText); },
                                true
                            );
                };

    fetch();
    

}

// update viewer elements
function update_viewer(viewer, data){
    
    if(!viewer)return;

    //console.log(data)

    // Format : { "key1": "value1", "key2":{} }
    try{
        for(var key in data){
            
            // foreach item with key and value
            var value = data[key];
            
            // get field in viewer with given key
            var item = viewer.find('.viewer-input[data-key="'+key+'"').html(value);
            

        }
    }catch(e){

    }
}

// close viewer
function close_viewer(viewer){
    if(!viewer)return;

    viewer.modal("hide");
}

/* ******* */



/* BARCODE VIEWER */

function view_barcode(barcode, type, e){

    var modal = $(barcode_form);

    // create picker     
    var parent = e ? $(e) : $('body');
    parent.append(modal);

    modal.find('.barcode').first().append(create_barcode(barcode, type));



    modal.modal("show");

}

function create_barcode(barcode, type, e){
    var type = parseInt(type);
    
    var types = ['azteccode', 'rationalizedCodabar', 'code11', 'code39', 'code93', 'code128', 'datamatrix', 'ean8', 'ean13', 'interleaved2of5', 'maxicode', 'pdf417', 'qrcode', 'isbn', 'upca', 'upce']

    if(isNaN(type) || type < 0 || type > types.length)return 'Erreur';

    var content = "";
    
    let canvas = document.createElement('canvas');
    try {
        // The return value is the canvas element
        canvas = bwipjs.toCanvas(e ? e : canvas, {
                bcid:        types[type],       // Barcode type
                text:        barcode,    // Text to encode
                includetext: true,            // Show human-readable text
                textxalign:  'center',        // Always good to set this
            });
        content = canvas;
    } catch (e) {
        // `e` may be a string or Error object
        content = e;
    }

    return content;

}


/* ************** */



/* GENERIC ELEMENT OPERATIONS */


function list_elements(e, formatter, get_url, get_params, set_url, headerFormatter, propCb){

    // init header
    print_table(e, null, [], function(header){ if(headerFormatter)headerFormatter(header, global_role); });
        
    // printing callback
    var cb = function(resp){
        
        //console.log(resp);

        if(resp && resp.error){
            error(handleError(resp.error));
            return;
        }

        var res = print_table(e, function(e,i){return formatter(e, i, global_role)}, resp);

        toggle_table_column(e+"_table", '.detailed', $(e+"_table").hasClass('.detailed'));
        

        
        if(!res)
            error("Erreur du formatteur");
        
        $(".dropdown-toggle").dropdown();

        
        
        $(e).find('.global-prop-chk').change(function(){
            var item = $(this);
            var isChecked = item.is(':checked');

            var id = item.data('id'), key = item.data('key');
            if(id === undefined || key === undefined)return;
            if(value === undefined)value = "";
            
            var l = loading('', item.closest('label'));

            var options = {}; options['id'] = id; options[key] = isChecked ? 1 : 0;
            set_property(set_url, options, function(resp){
                
                l.remove();
                if(resp && resp.error){
                    error(handleError(resp.error));
                    item.prop('checked', !isChecked)
                    return;
                }

                if(propCb)propCb(item);
                
            });

        });
        
        $(e).find('.global-prop-btn').click(function(){
            var item = $(this);
            
            var id = item.data('id'), key = item.data('key'), value = item.data('value');
            if(id === undefined || key === undefined)return;
            if(value === undefined)value = "";
            
            var l = loading('', item.closest('td'));

            var options = {'id': id}; options[key] = value;
            set_property(set_url, options, function(resp){
                
                l.remove();
                if(resp && resp.error){
                    error(handleError(resp.error));
                    return;
                }

                if(propCb)propCb(item, {'id': id, 'key': key, 'value': value});

                
            });

        })
    };

    // get list of students
    ajax_get(get_url, get_params, cb, function(data){ error("Erreur de récupération de la liste."); console.log(data.responseText); }, "", e)


}


function delete_elements(list, url, callback, showAlert, message){
    
    //console.log(list)

    var func = function(){
        ajax_post(url, list, function(resp){

                //console.log(resp);
                
                if(resp.success != undefined){
        
                    success("Deleted successfully !", null, default_success_alert_timeout);        
                    if(callback)callback(true, list);
                    return;
                }
        
                if(callback)callback(false, list);

                if(resp.error){
                    var errTxt;
        
                    if(typeof resp.error === 'string'){
                        errTxt = handleError(resp.error || resp.success);
                    }else{
                        errTxt = "One or more elements were not deleted!"                
                    }
                        
                    error(errTxt);
                    return;
                }else{
                    error(handleError());
                }
                        
        
            }, 
            function(data){ if(callback)callback(false, list); error("Deleting error.", "", default_error_alert_timeout);  console.log(data.responseText); }
        );
    }

    if(showAlert){
        alert(  '</br>Are you sure you want to delete these elements ?</br><b>WARNING</b>: <u>this operation is irreversible !</u>',
                'Delete', 0,
        [{text: 'Cancel', action:'cancel', classes: 'btn-danger'}, {text: 'Delete', action:'ok', classes: 'btn-success'}],
        function(action){
            if(action == 'ok'){
                func();
            }
        });
    }else{
        func();
    }

    
}


// change property from db
function set_property(url, data, cb, e){


    if(!e || e.length < 0 ){

        ajax_get_no_loading(url, data,  function(data){
                                                //console.log(data);
                                                if(cb)cb(data);
                                            }, 
                                        
                                        function(data){if(cb)cb(data.responseText)})

    }else{
        
        
        var disableItem = $('<div class="position-absolute w-100 h-100"></div>');
        disableItem.click(function(e){e.preventDefault();e.stopPropagation();});
        e.append(disableItem);
        ajax_get_no_loading(url, data,  function(data){
                                    //console.log(data);
                                    disableItem.remove();
                                    if(cb)cb(data);
                                }, 

                            function(data){disableItem.remove(); if(cb)cb(data.responseText)}, null, '</br></br>')

    }


}

/* ************* */



// take a path string and create nested object: a/b/c -> {a: {b: c:{}}}
function pathToObject(path, obj){
    var tmpPath = path.split("/");


    if(tmpPath.length < 1 || tmpPath[0].length < 1)return obj;
    
    if(tmpPath.length == 1){
        obj[tmpPath[0]] = '';
        return obj;
    }

    obj[tmpPath[0]] = {};
    //console.log(path, obj)

    return pathToObject(tmpPath.slice(1).join('/'), obj[tmpPath[0]]);

}

// check if object has a path: check a/b in {a: {b: {c:{}}, d:''}}
function objectHasPath(obj, path){
    
    var tmpPath = path.split("/");
    
    
    if(tmpPath.length == 1){
        return obj[tmpPath[0]] == undefined;
    }

    return objectHasPath(obj[tmpPath[0]], tmpPath.slice(1).join('/'));


}

// set data in a given path, override if true: set {e: 32} in a/b ({a: {b: {c:0}, d:''}}) -> override ? {a: {b: 32}} : {a: {b: {c:0, e:32}, d:''}}
function objectSetInPath(obj, path, data, override){
    
    var tmpPath = path.split("/");

    
    if(tmpPath.length < 1 || tmpPath[0].length < 1)return obj;
    

    if(tmpPath.length == 1){

        
        if(obj == undefined || obj[tmpPath[0]] == undefined)obj[tmpPath[0]] = isJSON(data) ? parseJSON(data) : (data || '');

        if(override){
            obj[tmpPath[0]] = isJSON(data) ? parseJSON(data) : (data || '');
        }else{
            var tmp = obj[tmpPath[0]];
            /*
            if(typeof tmp == 'object'){
                if(Array.isArray(tmp))obj[tmpPath[0]].push(data);
            }else{
                obj[tmpPath[0]] = data;
            }
            */
            obj[tmpPath[0]] = isJSON(data) ? {...obj[tmpPath[0]], ...parseJSON(data)} : (data || '');
        }

        return obj;
    }
    

    if(obj[tmpPath[0]] == undefined){
        var index = parseInt(tmpPath[1]);
        if(isNaN(index)){
            obj[tmpPath[0]] = {};
        }else{
            obj[tmpPath[0]] = [];
        }
        
    }
    
    var tmp = objectSetInPath(obj[tmpPath[0]], tmpPath.slice(1).join('/'), data, override);

    return tmp;
}

// create path first then set data
function objectSetDataToPath(obj, path, data, override){
    
    /*    
    if(!objectHasPath(obj, path)){
        obj = pathToObject(path, obj);
    }
    */
    //console.log('prepared: ', obj)

    var tmp = objectSetInPath(obj, path, data, override);

    //console.log(path, data, obj)
    
    return tmp;
}

// get value in obj from path: a/b/c in {a: {b: {c:32}, d:''}} -> 32 else null
function objectGetDataFromPath(obj, path){
    var tmpPath = path.split("/");
    

    var value = null;

    if(tmpPath.length == 1){
        value = obj[path] !== undefined ? obj[path] : null;
        return value;
    }

    value = objectGetDataFromPath(obj[tmpPath[0]], tmpPath.slice(1).join('/'));

    return value;
}

// get all paths in object: {a: {b: {c:0}, d:''}} -> [ a, a/b, a/b/c, a/d ] 
function objectGetPaths(obj){
    
    var paths = [];

    if(typeof obj != 'object' || Array.isArray(obj))return paths;
    

    for(const key in obj){
        
        paths.push(key);
        if(typeof obj[key] == 'object'){
            for(const p of objectGetPaths(obj[key]))
                paths.push(key+"/"+p);
        }
    }

    return paths;
}




function load_page(link, data = {}, cb){

    page_loading = loading();
    ajax_get_no_loading(link, data, function(data){

        page_loading.remove();


        if(checkError(data, true)){
            return;
        }else{            

            // console.log(data);
            
            var editor = open_editor(data, null, false, false);
            init_ui(editor);

            if(cb)cb(editor);
           
        }


    }, function(err){page_loading.remove(); error('Unknown Error'); console.log(err.responseText)}, true);


}