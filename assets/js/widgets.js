function switch_w(state, cb, style, classes, dataAttr, label){

    state = (state == 1 || state == '1' || state == true || state == 'true'); // ? 'checked' : '';


    var s = $(' <label class="switch '+(style?'switch-'+style : '')+' ">   \
                    <input type="checkbox" class="'+classes+'" '+(state ? 'checked' : '')+'> \
                    <span class="switch-slider  rounded-circle" ></span>   \
                </label>');

    if(label){
        s.addClass('position-relative');
        var l = $('<span>'+label+'</span>');
        l.addClass('position-absolute font-weight-bold text-center text-nowrap').css({'top':'-1.25rem', 'left':'0px', 'right':'0px', 'font-size':'0.8rem'});
        s.append(l);
    }

    var chk = s.find('input:checkbox');

    chk.prop('checked', state);
    
    for(const k in dataAttr){
        chk.data(k, dataAttr[k]);
    }

    chk.change(function(){ 
                            var checked = $(this).is(':checked'); 
                            if(cb)cb(checked); 
                        });
        
    chk.bind( "updateState", function(){
                            var checked = chk.is(':checked'); 
                            if(cb)cb(checked); 
                        });

    return s;
        
}


function password_editor(value, callback, title){

    var required = arguments[3] || '';


    var edit_widget =    '            <div  class="form-row mt-3" align="center">'+
                                '            <div class="col px-4">'+
                                
                                '                        <input class="app-input editor-input" data-key="password" data-field="Enter Password" data-icon="eye button" data-icon-classes="hide-show-password" type="password" value="'+(value || '')+'" '+required+'>'+
                                
                                '            </div>'+
                        '            </div>';

    var edit_widget_js = function(e, data){

    };


    var cb = function(resp){
        
        if(resp.action == 'submit'){
            if(callback)callback(editor, resp.value.password);
        }
    };
    

    var template = editor_escapable_form; //.replace("%BODY%", edit_widget).replace("%TITLE%", ucwords(title || 'Password', true));
    

    var editor = open_editor(template, cb, false, false);
    
    editor.find('.modal-title').html(ucwords(title || 'Password', true));
    editor.find('.modal-body').append($(edit_widget));

    init_ui(editor);

    editor.find('.modal-dialog').addClass('modal-sm');

    editor.find('.hide-show-password').click(function(){
                                            var input = $(this).siblings('input').get(0);
                                            if(input.type == 'password'){
                                                input.type = 'text';
                                                $(this).removeClass('fa-eye');
                                                $(this).addClass('fa-eye-slash');
                                            }else{
                                                input.type = 'password';
                                                $(this).removeClass('fa-eye-slash');
                                                $(this).addClass('fa-eye');
                                            }
                                        });
    
    
    setTimeout(function(){ setFocus('.app-input'); }, 600);

    editor.close = function(){close_editor(editor)};
    return editor;

};


function text_editor(value, callback, title, size = 'sm', closeOnSubmit = true){

    var type = arguments[3] || 'text';
    var required = arguments[4] || '';


    var edit_widget =    '            <div  class="form-row my-0 py-0 mt-3" align="center">'+
                                
                                '                        <input class="app-input editor-input" data-key="value" type="'+type+'" value="'+(value || '')+'" '+required+'>'+
                                
                                '            </div>';

    var edit_widget_js = function(e, data){

    };


    var cb = function(resp){
        //console.log(resp)
        if(resp.action == 'submit'){
            if(callback)callback(resp.value.value, editor);
            if(closeOnSubmit)editor.close();
        }
    };

    var template = editor_escapable_form; //.replace("%BODY%", edit_widget).replace("%TITLE%", ucwords(title || 'Text', true));

    var editor = open_editor(template, cb, false, false);
    
    editor.find('.modal-title').html(ucwords(title || 'Text', true));
    editor.find('.modal-body').append($(edit_widget));
    
    init_ui(editor);

    editor.find('.modal-dialog').addClass('modal-'+size);
    editor.data('keyboard', true);

    setFocus(editor.find('input'), 2000);
    
    
    setTimeout(function(){setFocus(editor.find('.editor-input[data-key="value"]'));}, 600);

    editor.close = function(){close_editor(editor)};
    return editor;

};


function number_editor(value, callback, title, size = 'sm', min, max, closeOnSubmit = true){

    var type = arguments[3] || 'text';
    var required = arguments[4] || '';


    var edit_widget =    '            <div  class="form-row my-0 py-0 mt-3" align="center">'+
                                
                                '                        <input type="number" step="any" '+(min ? 'min="'+min+'"' : '')+' '+(max ? 'max="'+max+'"' : '')+' class="app-input editor-input" data-key="value" type="'+type+'" value="'+(value || '')+'" '+required+'>'+
                                
                                '            </div>';

    var edit_widget_js = function(e, data){

    };


    var cb = function(resp){
        //console.log(resp)
        if(resp.action == 'submit'){
            if(callback)callback(resp.value.value, editor);
            if(closeOnSubmit)editor.close();
        }
    };

    var template = editor_escapable_form; //.replace("%BODY%", edit_widget).replace("%TITLE%", ucwords(title || 'Value', true));

    var editor = open_editor(template, cb, false, false);
    
    editor.find('.modal-title').html(ucwords(title || 'Value', true));
    editor.find('.modal-body').append($(edit_widget));
    
    init_ui(editor);

    editor.find('.modal-dialog').addClass('modal-'+size);
    editor.data('keyboard', true);

    setFocus(editor.find('input'), 2000);
    
    
    setTimeout(function(){setFocus(editor.find('.editor-input[data-key="value"]'));}, 600);

    editor.close = function(){close_editor(editor)};
    return editor;

};

function inline_editor(e, value = '', cb, type = 'text', closeOnEnter = true, attr, classes = []){

    e = $(e);
    if(e.length == 0)return;

    var parent = e.closest('div'); console.log(parent);

    parent.addClass('position-relative');

    var attrs = ""; for(const k in attr){ attrs += k + '="'+attr[k]+'"'; }

    var input = $('<input type="'+type+'" '+attrs+' class="position-absolute w-100 '+classes.join(' ')+'" style="font-size:1.2rem; left:0; right;0; top:0;"/>');
    
    input.close =   function(){ 
                        parent.removeClass('position-relative');
                        input.remove();
                    }


    input.val(value).keyup(function(event){ event.preventDefault(); event.stopPropagation(); 
                                 if(event.keyCode == 13){ 
                                        execfunc(cb, this.value); 
                                        if(closeOnEnter)input.close();
                                 } })
                    .on("focusout", function(){ input.close(); });
                        
    
    parent.append(input);

    input[0].focus();

    return input;
}


function view_modal(content, title, actionCb, escapable, size, keepFooter = true){
    
    var form = escapable == true ? editor_escapable_form : editor_form;

    var template = form; //.replace("%BODY%", content).replace("%TITLE%", ucwords(title || '', true));
    

    var editor = open_editor(template, actionCb, false, false);

    editor.find('.modal-title').html(ucwords(title || '', true));
    editor.find('.modal-body').append($(content));
    
    init_ui(editor);

    if(!keepFooter)editor.find('.modal-footer').remove();

    editor.close = function(){close_editor(editor)};

    editor.title = function(t){
                        if(!t)return title;

                        title = t;
                        editor.find('.modal-title').html(title);
                    }
    
    editor.find('.modal-dialog').addClass('modal-'+(size && size.length > 1 ? size : 'md'));

    //editor.find('.modal-body').append(content);

    return editor;

}

function view_info(desc, title, size, noucfirst, isHTML, cb){

    if(!isHTML){        
        desc = (noucfirst ? desc : ucfirst(desc, true));
        desc = desc.replace((new RegExp('\n', 'g')), '</br>');
        desc = desc.replace((new RegExp(' ', 'g')), '&nbsp; ');
        desc = '<p class="mt-3" style="font-size:1rem;">'+desc+'</p>';
    }

    return view_modal(desc, title || 'Information', cb, true, size, false);

}

function grid_modal(list, formatter, title, actionCb, btns, style, selectable, allowMultiple = false){


    var actions = $('<div class="col-12 my-2 text-right"></div>');
    if(btns){
        for(const btn of btns){
            var dataAttr = "";
            if(btn.data){ for(const d in btn.data)dataAttr += 'data-'+d+'="'+btn.data[d]+'"'; }
            var tmp = $('<div class="btn btn-sm btn-'+(btn.color || 'success')+' '+(btn.classes || '')+'" '+dataAttr+'>'+(btn.text || 'Action')+'</div>');
            if(btn.cb){
                tmp.on('click', function(){if(btn.cb)btn.cb()}); 
            }
            
            actions.append(tmp);
        }
    }
    
    
    var edit_widget =    '<div class="row grid" align="center"></div>';

    var edit_widget_js = function(e, data){

    };

    var itemsList = list;

    

    var template = editor_escapable_form; //.replace("%BODY%", edit_widget).replace("%TITLE%", ucwords(title || '', true));
    
    var _actionCb = function(action){

        if(selectable && action.action == "submit"){
            var selected = [];
            editor.find('.selectable.selected').each(function(){ selected.push(itemsList[parseInt($(this).data('idx'))])}); // using data-idx fr list index because data-id="0" prevent first element from toggling selection
            if(actionCb)actionCb({action:"selection", value: selected});
            editor.close();
        }else{
            if(actionCb)actionCb(action)
        }
    }


    var editor = open_editor(template, _actionCb, false, false);
    
    editor.find('.modal-title').html(ucwords(title || '', true));
    editor.find('.modal-body').append($(edit_widget));


    editor.find('.modal-body').prepend(actions);
    
    // check role
    check_role(editor);

    var resize_editor = function(size){ editor.find('.modal-dialog').removeClass("modal-xs modal-sm modal-md modal-lg modal-xl").addClass('modal-'+size); }

    if(!selectable)editor.find('.modal-footer').remove();
    editor.close = function(){close_editor(editor)};

    editor.refresh = function(_list){
                        var grid = editor.find('.grid');
                        grid.html('');
                        
                        var cols = style.cols || 1;
                        cols = 12 / cols; 

                        if(!_list || _list.length <= 1){cols = 12; resize_editor(style.minSize || 'md'); }
                        else if(_list.length > 1){ resize_editor(style.size || 'lg'); }
                        
                        var changeSelect = null;

                        itemsList = _list || [];

                        var toggleSelectables = function(onOff, exclude){
                            
                            var all = grid.find('.selectable');

                            

                            all.each(function(){
                                var item = $(this);
                                if(exclude && item.data('id') == exclude)return;

                                changeSelect(item, onOff);
                            })
                            //if(onOff == true)all.addClass('selected');
                            //else all.removeClass('selected');
                            //else all.each(function(){var iteùm = $(this); if(item.hasClass('selected')) });
                        };

                        if(_list){
                            
                            for(var i=0; i<_list.length; i++){
                                var item = _list[i]; 
                                
                                var col = $('<div class="col-md-'+cols+' my-1" ></div>')
                                var child = formatter ? formatter(item, selectable) : item;

                                // check role
                                check_role(child);

                                if(selectable){
                                    if(changeSelect == null)changeSelect = child.changeSelect;
                                    child.find('.selectable').attr('data-id', (i+1)).attr('data-idx', i).click(function(){   // don't use data-id='0' for toggeling selection 
                                                                        var item = $(this);
                                                                        if(!allowMultiple)
                                                                            toggleSelectables(false, item.data('id'));
                                                                        
                                                                        changeSelect(item, !item.hasClass('selected'));
                                                                        
                                                                    });
                                                                    
                                }
                                col.append( child );
                                grid.append(col);
                            }
                        }



                        
                     }
    editor.title = function(t){
                        if(!t)return title;

                        title = t;
                        editor.find('.modal-title').html(title);
                    }
                     
    editor.refresh(itemsList);


    return editor;

};



function audioSimple(file, options, e){

    var classes = options.classes || "",
        playText = options.play != undefined ? options.play : 'Play',
        pauseText = options.pause != undefined ? options.pause : 'Pause',
        loadingText = options.loading != undefined ? options.loading : 'Loading';

    playText = '<i class="fa fa-play '+(playText.length > 0 ? 'mr-1' : '')+'"></i> '+playText;
    pauseText = '<i class="fa fa-pause '+(pauseText.length > 0 ? 'mr-1' : '')+'"></i> '+pauseText;
    loadingText = '<i class="fa fa-spinner '+(playText.length > 0 ? 'mr-1' : '')+'"></i> '+loadingText;
    
    
    var btn = $('<label class="btn btn-sm btn-success '+classes+' " data-status="stopped"><span>'+playText+'</span><audio src=""></audio></label>');
    var audio = btn.find('audio')[0]; //new Audio('download.php?file='+encodeURIComponent(file));

    audio.src = 'download.php?file='+encodeURIComponent(file);

    audio.addEventListener('play', function(){
        btn.find('span').html(pauseText);
        btn.data('status', 'playing');
    });
    audio.addEventListener('pause', function(){
        btn.find('span').html(playText);
        btn.data('status', 'paused');
    });
    audio.addEventListener('progress', function(){
        // console.log('Progress while: '+ btn.data('status'));
        // btn.find('span').html(btn.data('status') != 'playing' ? playText : loadingText);
        // btn.data('status', 'loading');
    });
    audio.addEventListener('ended', function(){
        btn.find('span').html(playText);
        btn.data('status', 'stopped');
    });

    var btnClickTimer = null;
    btn.click(function(){
        if(btnClickTimer)return;

        btnClickTimer = setTimeout(function(){
            var status = btn.data('status');
            switch(status){
                case 'stopped': audio.play(); break;
                case 'paused': audio.play(); break;
                default: audio.pause();
            }
            btnClickTimer = null;
        }, 200);
        
    });


    if(e)$(e).append(btn);

    return btn

}






var editor_form =  '<div class="modal fade" id="" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true">'+
                        '  <div class="modal-dialog modal-dialog-centered" role="document">'+
                        '    <div class="modal-content shadow">'+
                        '      <div class="modal-header pb-2">'+
                        '        <span class="modal-title" style="font-size:17px;"></span>'+
                        '        <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close">'+
                        '          <span class="font-weight-bold h5" aria-hidden="true"><i class="fa fa-times mr-2"></i>Close</span>'+
                        '        </button>'+
                        '      </div>'+

                        '      <form class="needs-validation" novalidate>'+
                        '      <div class="modal-body p-0">'+ 
                                       
                                 
                        '      </div>'+

                        '      <div class="modal-footer">'+
                        '           <button type="button" class="btn btn-sm btn-danger action-cancel" data-dismiss="modal">Cancel</button>'+
                        '           <button type="submit" class="btn btn-sm bg-success text-white action-submit">Confirm</button>'+
                        '      </div>'+
                        '      </form>'+

                        '    </div>'+
                        '  </div>'+
                        '</div>';

var editor_escapable_form =  '<div class="modal fade" id="" data-backdrop="false" data-keyboard="true" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true">'+
                                '  <div class="modal-dialog modal-dialog-centered" role="document">'+
                                '    <div class="modal-content shadow">'+
                                '      <div class="modal-header pb-2">'+
                                '        <span class="modal-title" style="font-size:17px;"></span>'+
                                '        <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close">'+
                                '          <span class="font-weight-bold h5" aria-hidden="true"><i class="fa fa-times mr-2"></i>Close</span>'+
                                '        </button>'+
                                '      </div>'+

                                '      <form class="needs-validation" novalidate>'+
                                '      <div class="modal-body pt-0">'+ 
                                                                            
                                 
                                '      </div>'+

                                '      <div class="modal-footer">'+
                                '           <button type="button" class="btn btn-sm btn-danger action-cancel" data-dismiss="modal">Cancel</button>'+
                                '           <button type="submit" class="btn btn-sm bg-success text-white action-submit">Confirm</button>'+
                                '      </div>'+
                                '      </form>'+

                                '    </div>'+
                                '  </div>'+
                                '</div>';
                   

var picker_form =  '<div class="modal fade" id="" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true">'+
                        '  <div class="modal-dialog modal-dialog-centered" role="document">'+
                        '    <div class="modal-content shadow">'+
                        '      <div class="modal-header pb-2">'+
                        '        <span class="modal-title" style="font-size:17px;">%TITLE%</span>'+
                        '        <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close">'+
                        '          <span class="font-weight-bold h5" aria-hidden="true"><i class="fa fa-times mr-2"></i>Close</span>'+
                        '        </button>'+
                        '        </button>'+
                        '      </div>'+

                        '      <div class="modal-body pt-0">'+ 
                        

                        '           %BODY%  '+                                   
                        '      </div>'+

                        '      <div class="modal-footer">'+
                        '           <button type="button" class="btn btn-danger action-cancel" data-dismiss="modal">Annuler</button>'+
                        '           <button type="submit" class="btn bg-success text-white action-submit">Confirmer</button>'+
                        '      </div>'+

                        '    </div>'+
                        '  </div>'+
                        '</div>';


var viewer_form =  '<div class="modal fade" id="" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true">'+
                        '  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">'+
                        '    <div class="modal-content shadow">'+
                        '      <div class="modal-header pb-2">'+
                        '        <span class="modal-title" style="font-size:17px;">%TITLE%</span>'+
                        '        <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close">'+
                        '          <span class="font-weight-bold h5" aria-hidden="true"><i class="fa fa-times mr-2"></i>Close</span>'+
                        '        </button>'+
                        '        </button>'+
                        '      </div>'+

                        '      <form class="needs-validation" novalidate>'+
                        '      <div class="modal-body pt-0">'+ 

                        '           %BODY%  '+                                   
                        '      </div>'+

                        '      </form>'+

                        '    </div>'+
                        '  </div>'+
                        '</div>';                   

var barcode_form =  
                        '<div class="modal fade" id="" data-backdrop="false" data-keyboard="true" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true">'+
                        '  <div class="modal-dialog modal-dialog-centered" role="document">'+
                        '    <div class="modal-content shadow">'+
                        '      <div class="modal-header pb-2">'+
                        '        <span class="modal-title" style="font-size:20px;">Code Barre</span>'+
                        '        <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close">'+
                        '          <span class="font-weight-bold h5" aria-hidden="true"><i class="fa fa-times mr-2"></i>Close</span>'+
                        '        </button>'+
                        '      </div>'+
    
                        '      <div class="modal-body pt-0">'+ 
    
                        '           <div class="p-2 text-center" style="width:100%"><canvas class="barcode" style="max-width:100%"></canvas></div>  '+                                   
                        '      </div>'+
    
                        '    </div>'+
                        '  </div>'+
                        '</div>';