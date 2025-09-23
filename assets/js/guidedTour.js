
var GuidedTour = function(){

    function GuidedTour(tour_guide, root){

        this.root = root || 'body';
        this.tour_guide = tour_guide;

        this.tours = {};

        this.init();
        
    }

    
    
    GuidedTour.prototype.init = function(root){
            
        root = $(root || this.root);
        if(root.length == 0)return;

        var context = this;

        

        /**
         *  Data attributes:
         * 
         *      data-tour-<...>
         * 
         * 
         *      data-tour-name = name of the tour to take data from
         *      data-tour-step = step number --------  >= 0
         * 
         * 
         * 
         *  Tour Guide Object:
         * 
         *  { "tour_1" :    "init":{ started:{}, finished:{}, cancelled:{} }, 
         *                  "steps": [  'text only', 
         *                              {text: 'Text No Element', element: false},  // text full screen
         *                              {text: 'Text + Audio', audio: 'Audio URL'},
         *                              {text: 'Text + Video', video: 'Video URL'},
         *                              {Audio: 'Audio URL', video: 'Video URL'},   // Audio + Video
         *                              {Audio: 'Audio URL'},                       // Audio Only
         *                              {video: 'Video URL'}                        // Video Only
         *                           ],
         * 
         *      "tour_1" : [  'text only', 
         *                        {video: 'Video URL'}                        // Video Only
         *               ]
         *  }
         * 
         *  Step object:
         *      
         *      attachTo:       (DATA) element to attach to, if false show full screen
         *      config:         default tour config -------- {"overlay":true, "escExit":true, "scrollTo":true}
         * 
         *      text:           text to display
         *      audio:          audio to add 
         *      video:          video to add
         *      textHTML:       (DATA) use element inner html as text ------- TRUE/FALSE
         * 
         *      after:          JS to call after step
         *      before:         JS to call before step
         * 
         *      skipOn:         (DATA) skip on classes 
         *      skip:           (DATA) true to skip
         * 
         *      removeClasses:  (DATA) classes to remove if element has them, seperated by space --------   'invisible d-none'
         *      addClasses:     (DATA) classes to add if elemenent doesn't have them, seperated by space --------   'invisible d-none'
         *      showSize:       (DATA) only show this step in specified width -------- xs, sm, md, lg, xl 
         *      hideSize:       (DATA) only hide this step in specified width ------- xs, sm, md, lg, xl         
         *      contentAlign:   (DATA) how to align content inside element -------- center, left, right
         * 
         */

        root.find('.tour-start')
            .click(function(){
                var name = $(this).data('tour-name');
                if(!name)return;

                context.start(name);
            
            });


            

      
    };


    GuidedTour.prototype.start = function(name, checkAutoRun = false){


        if(!name)return;

        var canAutoRun = getStorage(name);
        if(checkAutoRun &&  canAutoRun == '0')return;


        var _tour = this.tour_guide[name];

        if(!_tour)return;

        
        var finishedCb = _tour.init.finished || '';  // tour finish callback
        var startedCb = _tour.init.started || '';  // tour finish callback
        var cancelledCb = _tour.init.cancelled || '';  // tour finish callback

        var defaultConfig = _tour.init.config || {}; // default tour config


        
        // check if size is ok
        var isSize = function(size){

            var winWidth = $(window).width();

            var ret = false;
            switch(size){
                case 'xs': ret = winWidth < 576 ; break;
                case 'sm': ret = winWidth >= 576 /* && winWidth < 768 */; break;
                case 'md': ret = winWidth >= 768 /* && winWidth < 992 */; break;
                case 'lg': ret = winWidth >= 992 /* && winWidth < 1200 */; break;
                case 'xl': ret = winWidth >= 1200; break;
                default: ret = true; break;
            }

            return ret;
        }



        const tour = new Shepherd.Tour({
            useModalOverlay:  defaultConfig.overlay == undefined ? true : defaultConfig.overlay,
            exitOnEsc: defaultConfig.escExit == undefined ? true : defaultConfig.escExit,
            defaultStepOptions: {
              classes: 'shadow-md bg-purple-dark',
              scrollTo: defaultConfig.overlay == undefined ? defaultConfig.scrollTo : $(window).width() < 992, // true,
            }
        });

        tour.on('start', function(){ if(finishedCb)startedCb(); });
        tour.on('complete', function(){ if(finishedCb)finishedCb(); });
        tour.on('cancel', function(){ if(cancelledCb)cancelledCb(); });
        
        // tour steps
        var tour_steps = {};

        // context
        var context = this;


        // step elements
        // var tour_steps_elements = $('[data-tour-name="'+name+'"][data-tour-step]');
        var total_steps = _tour.steps.length;


        for(var i=0; i<total_steps; i++){
            


            // step config
            var _step = _tour.steps[i];

            
                
            // step element
            var step_element = false,
                attachTo = _tour.steps[i].attachTo,
                dom_step_element = $('[data-tour-name="'+name+'"][data-tour-step="'+i+'"]');
            
            // if element not specified
            if(dom_step_element.length > 0){
               
               if(attachTo === undefined)step_element = dom_step_element; // attach to found element
               else if(attachTo && attachTo == 'parent')step_element = dom_step_element.parent().first();   // attach to parent
               else if(attachTo && (attachTo.indexOf('.') == 0 || attachTo.indexOf('#') == 0))step_element = $(attachTo).eq(attachTo_eq); // attach by selector
               else if(attachTo && attachTo.indexOf('<') == 0)step_element = dom_step_element.closest(attachTo.substring(1)).first(); // attach to closest element
            }

            // console.log(step_element);
            
            // check skip
            if( _step.skip || 
                (step_element && step_element.data('tour-skip') == true ) ||
                (_step.skipOn && step_element && step_element.hasClass(_step.skipOn)) ||
                (step_element && step_element.data('tour-skipon') && step_element.hasClass(step_element.data('tour-skipon'))) )
                
                { continue; }

                
            // step text
            var text = typeof _step == "string" ? _step : _step.text || ''; 
            if(step_element && (_step.textHTML || step_element.data('textHTML') == true)){
                text = step_element.html();
                step_element = false;
            }

            // init step buttons
            var btns = [{text: 'Exit', action: tour.cancel, classes: 'w3-red'}];
            if(total_steps > 1){
                if(i > 0)btns.push({text: 'Back', action: tour.back});
                if(i < total_steps)btns.push({text: 'Next', action: tour.next, classes: 'w3-green'});
            }
                            
            var step_options = {    id: "tour_"+name+"_step_"+i, 
                                    text: text,  
                                    attachTo: {element: step_element ? step_element[0] : false, on: 'bottom'},
                                    buttons: btns,
                                    arrow: false,
                                    canClickTarget: false                              
                                };

            
            // Shepard Step
            var step = new Shepherd.Step(tour, step_options);
           

            // Other Step Content
            step.audio = _step.audio || null,                               // audio
            step.video = _step.video || null;                               // video

            
            
            // add callbacks
            step.before = _step.before;
            step.after = _step.after;

            if(!text && !step.audio && !step.video)continue;
            

            if(step_element && step_element.length > 0){


            }else{

            }


            step.on('show', function(){ 
                    var content = $(this.el).find('.shepherd-text');

                    if(isFunc(this.before) && this.before(this.target, this, tour) === false){
                        tour.next();
                    }

                    // check for auto-start
                    var auto_start_el = $(this.el).find('input[type="checkbox"].tour-auto-start-chk');
                    if(auto_start_el.length > 0){
                        auto_start_el.change(function(){
                            var name = $(this).data('tour-name');
                            if(!name)return;

                            if(!$(this).is(':checked')){
                                setStorage(name, 0);
                            }else{
                                deleteStorage(name);
                            }

                        }).prop('checked', getStorage(name) != '0'); 
                    }
                    
            });

            step.on('hide', function(){ 
                    
                    if(isFunc(this.after))
                        this.after(this.target, this, tour);

                                        
            });

            


            tour.addStep(step);
        }
        
        /*
        tour_steps_elements.each(function(){
            
            var item = $(this),
                name = item.data('tour-name'),
                step = parseInt(item.data('tour-step')),
                isLast = step == (total_steps - 1),
                isFirst = step == 0,
                removeClasses = (item.data('tour-remove-classes') || '').split(' '),
                addClasses = (item.data('tour-add-classes') || '').split(' '), 
                showSize = item.data('tour-show-size'),
                hideSize = item.data('tour-hide-size'),
                content_align = item.data('tour-align'),
                attachTo = item.data('tour-attach-to'),
                attachTo_eq = item.data('tour-attach-to-eq') || 0,
                scroll_to = item.data('tour-scroll-to') || tour.options.defaultStepOptions.scrollTo,
                after = item.data('tour-after') || ''
                skipOn = item.data('tour-skip-on') || '';

            // skip element in no tour name
            if(!name || isNaN(step))return;

            // skip if incorrect screen size
            if(showSize && !isSize(showSize))return;
            else if(hideSize && isSize(hideSize))return;

            // skip if has class
            if(item.hasClass(skipOn))return;


            if(!tour_steps[name])tour_steps[name] = [];

            var prevRemoveClasses = [];
            var prevAddClasses = [];
            for(const c of item[0].classList){ 
                // only add class to remove if it exists in the item class list
                if(removeClasses.indexOf(c) >= 0)prevRemoveClasses.push(c); 

                // only add class to add if it exists in the item class list
                if(addClasses.indexOf(c) >= 0)prevAddClasses.push(c);                     
            }

            var stepText = context.tour_guide[name]['steps'][step], 
                stepAudio = null;

            if(typeof stepText == 'object'){
                stepAudio = stepText.audio || null;
                stepText = stepText.text || '';
            }

            

           
            // if element to attach to doesn't exist, skip
            if(attachToElement === undefined)return;


            // add step config to tour
            tour_steps[name][step] = {  id: "tour_"+name+"_step_"+step, 
                                        text: stepText,  
                                        audio: stepAudio,
                                        attachTo: {element: attachToElement, on: 'bottom'}, 
                                        // classes: 'w3-green w3-text-white',
                                        config:{  
                                                stepNum: step,
                                                last: isLast, first: isFirst,
                                                contentAlign: content_align,
                                                "removeClasses": prevRemoveClasses, "addClasses":prevAddClasses,
                                                scrollTo: scroll_to,
                                                "after": after
                                        }
                                        
                                    };


        });

        
        console.log(tour_steps);

        if(!tour_steps[name] || tour_steps[name].length == 0)return;

        for(const step_options of tour_steps[name] ){

            if(!step_options)continue;
            
            var config = step_options.config;

            
            var btns = [{text: 'Exit', action: tour.cancel, classes: 'w3-red'}];
            if(config.last || (!config.first && !config.last))btns.push({text: 'Back', action: tour.back});
            if((config.first || (!config.first && !config.last)) && (config.stepNum + 1 < tour_steps[name].length))btns.push({text: 'Next', action: tour.next, classes: 'w3-green'});
            // if(config.last)btns.push({text: 'Exit', action: tour.cancel});

            step_options.buttons = btns;
            step_options.scrollTo = config.scrollTo;

            var step = new Shepherd.Step(tour, step_options);


            step.config = config;
            step.audio = step_options.audio;

            step.on('show', function(){ 
                                    var content = $(this.el).find('.shepherd-text');

                                    var config = this.config;

                                    // remove classes
                                    if(config.removeClasses.length > 0)$(this.target).removeClass(config.removeClasses.join(' '));

                                    // align text
                                    if(config.contentAlign){
                                        content.addClass('text-'+config.contentAlign);
                                    }

                                    if(this.audio){
                                        // create and wrap audio player
                                        var audioPlayer = audioSimple(this.audio, {classes: 'ml-2', play: 'Play', pause: 'Pause'});
                                        audioPlayer.wrap('<div class="w-100 text-center py-2 mt-3 border-top border-dark"></div>');

                                        // add to content
                                        content.append(audioPlayer.parent());
                                    }
                                     
                            });
            step.on('hide', function(){ 
                                        if(this.config.removeClasses && this.config.removeClasses.length > 0)$(this.target).addClass(this.config.removeClasses.join(' ')); $(this.el).find('audio').remove(); 

                                        if(this.config.after)eval(this.config.after);
                                    });
            step.on('destroy', function(){ if(this.config.removeClasses && this.config.removeClasses.length > 0)$(this.target).addClass(this.config.removeClasses.join(' ')); });

            tour.addStep(step);
        }

        */

        tour.start();
        

    }




    return GuidedTour;

}();


var Tooltips = function(){

    function Tooltips(tooltips, root, showAsNumber = false){
        this.tooltips = tooltips;
        this.root = root || 'body';
        this.showAsNumber = showAsNumber;

        this.init();
    }

    
    
    Tooltips.prototype.init = function(otherRoot){
        var context = this;


        var root = $(otherRoot || context.root);
        
        if(root.length == 0)return;

        // get all tooltips elements
        var elements = root.find('[data-tooltip]:not(.tooltip-created)');
        if(elements.length == 0)return;


        elements.each(function(){
            var parent = $(this); 

            context.init_tooltip(parent);

            
        });
    }

    Tooltips.prototype.init_tooltip = function(element, text){

        element = $(element);
        if(element.hasClass('tooltip-created') || (text == undefined && this.tooltips == undefined))return;

        var key = parseInt(element.data('tooltip'));

        if(text == undefined){
            text = this.tooltips[key] || '';
        }

        if(!this.showAsNumber){
            // question mark
            var item = $('<i class="help-btn fa fa-question ml-1" data-toggle="tooltip" data-html="true"  title=""></i>');
        }else{
            // add number to identify the element
            var item = $('<b class="help-btn h5">'+key+'</b>');
        }
        
        
        item.attr('title', text);
        item.attr('data-html', true);
        element.append(item);
        item.tooltip();

        element.addClass('tooltip-created');

    }

    return Tooltips;

}();

function tour_add_step_to_table(table, tour_table_steps){

    for(const c of table.getRows()[0].getCells()){
        var step = tour_table_steps[c.getField()];
        if(step){
            var element = $(c.getElement());
            if(step.tourName)element.attr('data-tour-name', step.tourName);
            if(step.step)element.attr('data-tour-step', step.step);
        }
    }


}




var PopupTips = function(){
    
    function PopupTips(root){
        this.root = root || 'body';
        
        this.init();
    }

    
    
    PopupTips.prototype.init = function(otherRoot){
        var parents = $(otherRoot || this.root).find('.popuptip-parent');

        var tmpl = '<div class="w-100 w3-red p-2 overflow-auto" style="max-height:60vh; min-height:200px;"></div>';
        var nav_tmpl = '<div class="w-100 mt-2"><input class="app-input checkbox" type="checkbox" data-label="Don\'t show again"/><div class="popuptip-nav"></div></div>';

        parents.each(function(){
            var parent = $(this);

            var content = $(tmpl);

            content.append(parent.html());
            content.append(nav_tmpl);

            init_ui(content);

            view_info(content, 'Tips', 'lg', false, true);
        });


        
    }

    
    return PopupTips;


}();