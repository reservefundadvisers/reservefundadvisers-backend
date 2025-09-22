function init_ui(e, cb){
    var root = $(e || 'body');

    var ui = [];

    root.find('.app-slider').each(function(){ ui.push(ui_slider(this, cb)) });
    root.find('.app-input').each(function(){ ui.push(ui_input(this, cb)) });
    root.find('.app-check').each(function(){ ui.push(ui_check(this, cb)) });

    return ui;
}


function ui_slider(e, cb, reset = false){
        
    //if(!args)args = {};
    
    // jQuery UI options
    var options = ["range", "min", "max", "values", "value", "step", "disabled"];
    
    // get slider element
    var slider = $( e );

    if(reset){
        slider.slider( "destroy" );
        slider.html('');
    }

    // get jQuery UI options from params or data-attributes
    
    var option = {}; // args.options || {};
    for(const d in slider.data()){
        if(has(options, d) !== false){
            option[d] = parseJSON(slider.data(d), true);
        }
    }
    

    //if(!cb)cb = function(){};
    
    

    // object type
    slider.type = "slider";
    slider.value = function(){};

    var value_format = slider.data('format') || '?';
    //var value_decimals = 0; if(slider.data('step')){ var step = slider.data('step'); var t = step.indexOf('.'); if(t >=0){ value_decimals = step.length - t; } } // get number of decimals from step: 0.01 -> 2
    
    // send events
    var event_cb = function( type, ui = {} ) {  execfunc(cb, {"event": type, "type": "slider", "key":slider.data('key'), "element":slider}, {"value": ui.value, "index": ui.handleIndex, "range": ui.values || [ui.value]}); }
    
    var format_value = function(val){ return value_format.replace('us?', formatMoney(val, 0,'.', ',')).replace('US?', formatMoney(val,2,'.', ',')).replace('n?', formatNumber(val, 0)).replace('N?', formatNumber(val)).replace('?', val); }
    var update_tooltip = function(ui){

                                                                
                                var selector = '.app-slider-tooltip' + (ui ? '[data-index="'+ui.handleIndex+'"]' : '');
                                slider.find(selector).each(function(){
                                    var tooltip = $(this);
                                    tooltip.css('left', (-1 * tooltip.width()/2) - 2);
                                    if(ui)tooltip.html(format_value(ui.value));
                                })

                                // only when range with 2 handles
                                if(ui && ui.values){
                                    slider.find('.app-slider-tooltip-joined').each(function(){
                                        var tooltip = $(this),
                                            val = ui.values[0] == ui.values[1] ? format_value(ui.values[0]) : format_value(ui.values[0]) + ' - ' + format_value(ui.values[1]);
                                        tooltip.html(val).css('margin-left', (-1 * tooltip.width()/2) - 2);
                                    });
                                }

                                

                                // check collision
                                var handles = slider.find('.ui-slider-handle');
                                var handlesSize = slider.data('handle-top'); 
                                
                                // set handles position to bar
                                if(handlesSize != undefined){                              
                                        handles.find('.app-slider-tooltip').css('top', handlesSize);
                                        slider.find('.app-slider-tooltip-joined').css('top', handlesSize);
                                }
                                
                                if(handles.length > 1){
                                    var width = handles.parent().width();
                                    var left = Math.floor(parseFloat(handles.eq(0).css('left')) + Math.abs(parseFloat(handles.eq(0).find('.app-slider-tooltip').css('left'))));
                                    var right = Math.floor(parseFloat(handles.eq(1).css('left')) - Math.abs(parseFloat(handles.eq(1).find('.app-slider-tooltip').css('left'))));
                                                                        
                                    var showJoined = left < ( right - 5);
                                    
                                    slider.find('.app-slider-tooltip-joined').toggleClass('invisible', showJoined)
                                    handles.find('.app-slider-tooltip').toggleClass('invisible', !showJoined)
                                }
                                

                            }

    option.change = function(event, ui){ update_tooltip(ui); if(isFunc(cb))event_cb("change", ui); };
    option.slide = function(event, ui){ update_tooltip(ui); if(isFunc(cb))event_cb("slide", ui); };   
    
    option.start = function(event, ui){ update_tooltip(ui); if(isFunc(cb))event_cb("start", ui); };   
    option.stop = function(event, ui){ update_tooltip(ui); if(isFunc(cb))event_cb("stop", ui); };         
    
    option.create = function(event, ui){ 
                        

                        // set/get value
                        slider.value = function(values){
                            
                            if(values === undefined || values === null){
                                var v = $( e ).slider( "values" );
                                if(!v)return null;

                                if(v.length == 1 )return v[0];
                                else if(v.length == 1 )return null;
                                else return v;
                            }

                            if(Array.isArray(values)){
                                $( e ).slider( "values", values );
                            }else{
                                $( e ).slider( "value", isNaN(values) ? 0 : values );
                            }
                        }

                        var i = 0, handles = slider.find('.ui-slider-handle'); 
                        handles.each(function(){
                            var tootip = $('<div class="app-slider-tooltip" data-index="'+(i++)+'">0</div>');                            
                            $(this).append(tootip);
                        });

                        // only when range with 2 handles
                        if(handles.length > 1){
                            var joined_tootip = $('<span class="app-slider-tooltip-joined invisible">0-0</span>');                            
                            slider.find('.ui-slider-range').append(joined_tootip);                                       
                            $(e).slider("values", [0, 0]);
                        }else{
                            $(e).slider("value", 0);
                        }

                        
                                
                        var show_limits = slider.data('limits') == true;
                        if(show_limits){
                            var min = $(e).slider("option").min || 0;
                            var max = $(e).slider("option").max || 100;

                            slider.prepend('<div class="app-slider-limit"><span class="app-slider-limit-left">'+format_value(min)+'</span><span class="app-slider-limit-right">'+format_value(max)+'</span></div>')
                        }
                        slider.toggleClass('ui-slider-with-limits', show_limits);

                        if($(e).data('value') && handles.length == 1){
                            $(e).slider("value", $(e).data('value'));
                        }else if($(e).data('values') && handles.length == 2){
                            $(e).slider("values", $(e).data('values'));
                        }
                         
                        update_tooltip();
                                
                        //console.log('created');
                        if(isFunc(cb))
                            event_cb("created"); 
                    };
    
    // init slider
    slider.slider(option);
       

    return slider;
}


function ui_input(e, cb){
        
    
    var el = $(e);
    if(el.length < 0 || (!el.is('input') && !el.is('select') && !el.is('textarea') && !(el.is('div') && el.hasClass('app-div'))) )
        return null
    
    // object type
    el.type = "input";

    var event_cb = function( type, value = '' ) { execfunc(cb, {"event": type, "type": "input", "key":el.data('key'), "element":el}, value); }


    var format_input = function (element){

        if(element.closest('.app-input-group').length > 0)return;

        if( (!element.is('input') && !element.is('select') && !element.is('textarea') && !element.is('div')) || element.data('skip'))return;
        
        if( !element.parent().is('div') || !element.parent().hasClass('app-input-group')){
            
            if(element.is('textarea'))element.addClass('pt-2');

            element.wrap('<div class="app-input-group '+(element.data("classes") || '')+'"  style="'+(element.data("style") || '')+'"></div>');
            var parent = element.parent();
                        
            var field = (element.data('field') || '');

            element.wrap('<div class="app-input-box" ></div>');
            parent.prepend('<span class="app-input-label " >'+field+(element.is(':required') ? '<span class="w3-text-red">*</span>' : '')+'</span>');
            element.after('<div class="invalid-feedback" >'+(element.data('invalid') || '$? Invalid !').replace(/\$\?/g, field)+'</div>');
            
            element.after('<span class="app-input-helper">'+(element.data('helper') || '')+'</span>');
            
            if(element.data('icon')){
                var ic_action = element.data('icon-action');
                var ic = $('<i class="m-0 p-0 fa fas fa-'+(element.data('icon') || '')+' '+(element.data('icon-classes') || '')+' "></i>');                
                element.parent().append(ic);

            }else{
                element.css('padding-right', '0.4rem');
            }
            
            // disbable without disabled attribute
            if(element.data('disabled') == true){
                var disable_div = $('<div class="position-absolute" style="top:0; left:0; width:100%; height:100%;"></div>');
                disable_div.click(function(){ e.preventDefault; return false; });
                parent.append(disable_div);
            }
        }
    }
    
    format_input(el)  

    var label = el.closest('.app-input-group').find('.app-input-label');
    var ic = el.siblings('i:not(.button)');
    var ic_btn = el.siblings('i.button');

    

    if(el.is('input')){
        

        if(el.attr('type') == 'date' || el.attr('type') == 'month')return;

        var hasPattern = el.attr('pattern');
                
        el.on("input", function(){ event_cb("change", this.value); });
        el.on("focusout", function(){ event_cb("focusout", this.value);  label.removeClass('app-input-label-hover'); ic.removeClass('app-input-label-hover');});
        //el.on("change", function(){ event_cb("change2", this.value); });
        el.on("keyup", function(event){ if(event.keyCode == 13)event_cb("enter", this.value); });
        el.on("focusin", function(){ if(el.data('disabled') == true){el[0].blur(); return;} event_cb("focusin", this.value); label.addClass('app-input-label-hover'); ic.addClass('app-input-label-hover'); });

        ic_btn.click(function(){event_cb(el.data('icon-action') || 'button', el.val() );});
        el.bind('update', function(){ el.trigger('change')});
        
    }else if(el.is('select')){
        el.on("focusout", function(){ event_cb("focusout", this.value);  label.removeClass('app-input-label-hover'); ic.removeClass('app-input-label-hover');});
        el.on("change", function(){ event_cb("select", this.value); });
        el.on("focusin", function(){ event_cb("focusin", this.value); label.addClass('app-input-label-hover'); ic.addClass('app-input-label-hover'); });

    }else if(el.is('textarea')){


        // first size 
        var height = (el[0].scrollHeight > 35 ? el[0].scrollHeight : 35); if(height < 35)height = 35;
        el[0].setAttribute("style", "height:" + height + "px;overflow-y:hidden;");
        
        // resize on input
        el.on("input change", function () {
                    
                    this.style.height = "auto";
                    if(this.scrollHeight < 150 ){
                        this.style.height = (this.scrollHeight < 35 ? 35 : this.scrollHeight) + "px";
                        this.style.overflowY = 'hidden';
                    }else{                
                        this.style.height = "200px";
                        this.style.overflowY = 'scroll';
                    }

                    event_cb("change", this.value); });

        el.on("focusout", function(){ event_cb("focusout", this.value);  label.removeClass('app-input-label-hover'); ic.removeClass('app-input-label-hover');})
        el.on("focusin", function(){ event_cb("focusin", this.value); label.addClass('app-input-label-hover'); ic.addClass('app-input-label-hover'); });
        
        setTimeout(function(){ el.trigger('change') }, 100);

    }
    

    event_cb('created', el);
    
    var ret = el.closest('.app-input-group');
    ret.type = 'input';
    return ret;
}



function ui_check(e, cb){
        
    
    var el = $(e);
    if(el.length == 0 || !el.is('input:checkbox') )
        return null
    
    // object type
    el.type = "check";

    var event_cb = function( type, value = '' ) { execfunc(cb, {"event": type, "type": "check", "key":el.data('key'), "element":el}, value); }


    var format_input = function (element){

        if(element.closest('.app-check-group').length > 0)return;

        if( !element.is('input:checkbox') || element.data('skip'))return;
        
        if( !element.parent().is('label') || !element.parent().hasClass('app-check-group')){
            
            element.wrap('<label class="app-check-group '+(element.data("classes") || '')+'"  style="'+(element.data("style") || '')+'"></div>');
            var parent = element.parent();
                        
            element.wrap('<div class="app-check-box"></div>');
            parent.append('<div class="app-check-label " >'+(element.data('field') || '')+(element.is(':required') ? '<span class="w3-text-red">*</span>' : '')+'</div>');
            
                      
        }
    }
    
    format_input(el)  

    el.change(function(){ event_cb("check", $(this).is(":checked")); })
    

    event_cb('created', el);
    
    var ret = el.closest('.app-check-group');
    ret.type = 'check';
    return ret;
}