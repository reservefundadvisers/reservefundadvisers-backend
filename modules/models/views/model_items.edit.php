<div class="modal fade" id="<?=$arand;?>_modal" data-backdrop="false" data-keyboard="false" style="background:rgba(0,0,0,0.2)" tabindex="-1" role="dialog" aria-labelledby="editorModalLabel" aria-hidden="true"> 
        <div class="modal-dialog modal-dialog-centered" role="document" style="width: 70% !important; max-width:70% !important;"> 
        <div class="modal-content shadow"> 
            <div class="modal-header pb-2"> 
            <h5 class="modal-title" style="font-size:20px;" qompta-tr="user">Model Items</h5> 
            <button type="button" class="close action-cancel" data-dismiss="modal" aria-label="Close"> 
                <span aria-hidden="true">×</span> 
            </button> 
            </div> 

            <div class="modal-body pt-0">  

                <div class="w-100 overflow-auto text-right">
                    <label class="btn btn-sm w3-green mt-2 mr-3" id="item_template"><i class="fa fa-file" style="font-size:0.7rem;"></i> Download Template</label>
                    <label class="btn btn-sm w3-indigo mt-2" id="item_import"><i class="fa fa-upload" style="font-size:0.7rem;"></i> Import Data</label>
                    <label class="btn btn-sm w3-purple mt-2" id="item_export"><i class="fa fa-download" style="font-size:0.7rem;"></i> Export Data</label>
                </div>

                <div class="w-100 h6 mt-2 mb-2 pb-4 border-bottom border-dark font-weight-bold">
                    Estimated Items (<span class="items_count">0</span>)
                    
                    <label class="float-right pt-2 ml-4 pl-3 pr-2 border-left border-dark"><input type="checkbox" id="item_select_all" /> </label>
                    <span class="w3-red p-2 rounded pointer invisible item-del-all float-right" title="Delete" style="font-size:0.9rem;">Delete</span> 
                    <!-- <span class="w3-green mr-2 p-2 rounded pointer invisible item-join-all float-right" title="Join" style="font-size:0.9rem;">Join</span>  -->
                    
                </div>      

                <template class="item-tmpl">
                    
                    <div class="col-md-5 col-lg-6 pt-2 pb-2 border-bottom border-dark position-relative item item-left" style="font-size:1.0rem;">  
                        <div class="d-md-inline-block d-none position-absolute pt-0" style="font-size:0.7rem;">                                                          
                            <!-- <i class="w3-indigo fa fa-exchange-alt fa-rotate-90 px-2 py-1 rounded pointer item-switch" ></i>                                            -->
                            <!-- <i class="w3-green fa fa-compress-alt px-1 py-2 rounded pointer item-join" ></i>                                            -->
                            <!-- <label class="w3-indigo px-2 py-2 m-0 rounded pointer item-split font-weight-bold" >Split</label> -->
                        </div>
                        
                        <!-- <p class="ml-md-5 item-name"></p> -->
                        <input class="app-input item-name" data-classes="ml-md-0 pr-md-0" type="text" />
                        <label class="mt-2 ml-2 font-weight-bold pointer"><input type="checkbox" class="item-sirs"> Is SIRS ?</label>

                    </div>
                    
                    <div class="col-md-7 col-lg-6 col-md-2 px-0 border-bottom border-dark item item-right">
                        <div class="row h-100 m-0 p-0 pb-2 text-center">
                            <div class="col-4 pl-2 pt-2 d-block d-md-none border-bottom border-dark font-weight-bold ">Expected Life </div>
                            <div class="col-4 pl-2 pt-2 d-block d-md-none border-bottom border-dark  font-weight-bold ">Remaining Life </div>
                            <div class="col-4 pl-2 pt-2 d-block d-md-none border-bottom border-dark font-weight-bold text-center">Replacement Cost </div>
                            

                            <div class="col-4 col-md-3 pl-2 pt-2 font-weight-bold text-nowrap position-relative" >
                                <input class="app-input item-redundancy" type="number" min="0" />
                            </div>
                            <div class="col-4 col-md-4 pl-2 pt-2 font-weight-bold text-nowrap position-relative" >
                                <input class="app-input item-remaining_life" type="number" min="0" />
                            </div>
                            <div class="col-4 col-md-4 pl-2 pt-2 font-weight-bold text-nowrap text-right position-relative" >
                                <input class="app-input item-cost" type="number" min="0" />
                            </div>
                            <div class="col-12 col-md-1 pl-2 pt-2 pt-xs-4 " >
                                <!-- <i class="d-md-none d-inline-block w3-indigo fa fa-exchange-alt fa-rotate-90 px-2 py-1 rounded pointer item-switch float-left" ></i> -->
                                <!-- <i class="d-md-none d-inline-block w3-green fa fa-compress-alt px-1 py-2 rounded pointer item-join float-left" ></i> -->
                                <!-- <label class="d-md-none d-inline-block w3-indigo px-1 py-2 m-0 rounded pointer item-split float-left ml-2" >Split</label> -->
                                <input type="checkbox" class="item-del float-right mt-2 mt-md-1" /> 
                            </div>

                        </div>
                    </div>


                    <div class="col-12 py-3 d-block d-md-none item-sep"></div>
                

                </template>
                
                
                <div  class="form-row mt-3 "> 

                
                    <div class="col-md-5 col-lg-6 pb-2 pr-4 text-left font-weight-bold">
                        <input type="text" placeholder="Search for Item" class="float-left  item-filter" style="width:80%" />
                        <i class="fa fa-times w3-text-red p-2 rounded pointer item-filter-reset float-left ml-1" style="font-size:1rem;"></i>
                    </div>
                    
                    <div class="col-md-7 col-lg-6 col-md-2 d-none d-md-block px-0">
                        <div class="row m-0 p-0 text-center">
                            <div class="col-3 pl-2 font-weight-bold border-bottom border-dark ">Expected Life </div>
                            <div class="col-4 pl-2 font-weight-bold border-bottom border-dark ">Remaining Life </div>
                            <div class="col-4 pl-2 font-weight-bold border-bottom border-dark  text-center">Replacement Cost </div>
                            <div class="col-1 pl-2 " >
                                
                            </div>
                        </div>
                    </div>

                </div>

                <div class="position-relative" style="height:300px; overflow:auto;">
                    <div  class="form-row mt-3 mx-0 item-list" > 

                    
                        

                    </div> 
                </div>

                <!-- Add Items -->
                <div class="form-row mt-4">

                    <div class="col-md-6">
                        <input type="text" maxlength="125" class="app-input item-input item-focus" data-key="name" data-field="Item Name" data-helper="Press <b>Enter</b> or click on <b>Add Item</b> to add !" />
                    </div>
                    
                    <div class="col-md-6 col-md-2 ">
                        <div class="row h-100 m-0">
                            
                            <div class="col-4 pl-2 " ><input type="number" min="0" class="app-input item-input" data-key="redundancy" data-field="Expected Life<span class='invisible'>aa aaa</span>" /></div>
                            <div class="col-4 pl-2 " ><input type="number" min="0" class="app-input item-input" data-key="remaining_life" data-field="Remaining Life <span class='invisible'>aa</span>" /></div>
                            <div class="col-4 pl-2 " ><input type="number" min="0" class="app-input item-input" data-key="cost" data-field="Replacement Cost<span class='invisible'>aa</span>" /></div>                            
                        </div>
                    </div>
                    <div class="col-12 text-right pr-4" ><div class="btn btn-sm btn-primary" id="item_add">Add Item</div></div>

                    
                    

                </div>
                

                <div class="invalid-global-feedback d-none text-sm text-danger mt-3 mb-0 font-weight-bold">* Please check errors before proceeding !</div>
        


                



                <!-- MODAL BODY END -->    
            </div> 

            <form class="needs-validation" novalidate> 
            <div class="modal-footer"> 
                <button type="button" class="btn btn-sm btn-danger action-cancel" data-dismiss="modal"  qompta-tr="cancel">Cancel</button> 
                <button type="submit" class="btn btn-sm btn-success text-white action-submit"  qompta-tr="save">Save</button> 
            </div> 
            </form> 

            <?php echo print_js(['clients']); ?>
            <script>
                function <?=$arand;?>_init(){
                    var root = $('#<?=$arand;?>_modal');
                    var items = <?= json_encode($vars['items']); ?>,
                        model = <?= json_encode($vars['model']); ?>,
                        editor_variable_name = root.data('editor-var'), 
                        filtered = [];

                    

                    //var items = [];
                    var list = root.find('.item-list'), list_count = root.find('.items_count'),
                        item_del_all = root.find('.item-del-all'),
                        item_select_all = root.find('#item_select_all'),
                        item_join_all = root.find('.item-join-all');

                    var reset_filter = function(withAppenItems){
                        
                                            filtered = [];
                                            root.find('.item-filter').val('');

                                            if(withAppenItems)append_items();
                                        }


                    // add filtering
                    var filter_timeout = null;
                    root.find('.item-filter-reset').click(function(){ clearTimeout(filter_timeout); event.preventDefault(); reset_filter(true); });
                    root.find('.item-filter').on("input change", function(){
                        var filter = this.value;
                        clearTimeout(filter_timeout);
                        filter_timeout = setTimeout(function(){ 
                                            filtered = [];
                                            for(var i=0; i<items.length; i++){
                                                
                                                if((items[i].name.toLowerCase().indexOf(filter.toLowerCase()) !== -1))continue;
                                                filtered.push(i);
                                            }
                                            
                                            append_items();

                                         }, 200);
                    });
                    
                    //list.on("scroll", function(e){ console.log($(this).scrollTop()); });

                    // switch two items by indices
                    var switch_item = function(item){
                        
                        item = $(item);
                        
                        var chosen = list.find('.item-switch.chosen');
                        if(chosen.length == 0){
                            item.addClass('chosen w3-deep-orange');
                            return;
                        }

                        var from = item.data('index');
                        var to = chosen.first().data('index');
                        
                        if(to >= items.length || to < 0)return;
                        
                        var f = items[from], t = items[to];
                        items[to] = f; items[from] = t;

                        chosen.removeClass('chosen w3-deep-orange')

                        append_items();
                        
                    }



                    // switch two items by indices
                    var join_item = function(indices_to_join){
                        
                        if(indices_to_join.length < 2){
                            error('Please select at least 2 items to join !', 'Join', default_error_alert_timeout);
                            return;
                        }

                        var total_remaining_life = 0, total_cost = 0;

                        // check each index with next one to see if same parent_id
                        for(var i=0; i<indices_to_join.length-1; i++){
                            var item = items[indices_to_join[i]], next_item = items[indices_to_join[i+1]];
                            
                            if(!item.parent_id || item.parent_id != next_item.parent_id){
                                error('These Items cannot be joined, they must correspond to the same parent item !', 'Join', default_error_alert_timeout);
                                return;
                            }
                            
                            total_remaining_life += parseFloat(item.remaining_life);
                            total_cost += parseFloat(item.cost);
                        }

                        // add last item values since it hasn't been added
                        var last_index = indices_to_join[indices_to_join.length - 1];
                        total_remaining_life += parseFloat(items[last_index].remaining_life);
                        total_cost += parseFloat(items[last_index].cost);
                        

                        number_editor(total_remaining_life, function(new_remaining_life){

                                // join items into the first Item from index list
                                var first_item = items[indices_to_join.shift()];
                                first_item['remaining_life'] = new_remaining_life;
                                first_item['cost'] = total_cost;

                                // remove second item
                                var tmp = [];
                                for(var i=0; i<items.length; i++){
                                    if(indices_to_join.indexOf(i) >= 0)continue; //skip items to remove
                                    tmp.push(items[i]);
                                }
                                items = tmp;

                                // check for other child
                                var parent_id = first_item.parent_id;
                                var same_parent = 0;
                                for(const i of items){                            
                                    if(i.parent_id == first_item.parent_id)same_parent++;
                                }

                                if(same_parent == 1)first_item.parent_id = null;

                                append_items();


                        }, 'Remaining Life', true);

                        
                        
                    }

                    var split_item = function(el){

                        
                        var item = items[$(el).data('index')];

                        if(!item.id && !item.parent_id){
                            error('Please save new items before splitting !', '', default_error_alert_timeout);
                            return;
                        }

                        /*
                        // Split only when 2 and above
                        if(item.remaining_life < 2){
                            error('You can only split items with <b>Remaining Life</b> Greater or Equal to <b>2</b>', '', default_error_alert_timeout);
                            return;
                        }
                        */

                        var parent_id = item.parent_id || item.id;

                        var remaining_life = item.remaining_life || 0;
                        var cost = item.cost || 0;


                        // get all splitted items of the parent item
                        var input_tmp = '<div class="col-5 input-col mt-1 ">   \
                                            <input type="number" value="" data-id="%ID%" class="app-input editor-input year" data-icon="" data-field="Year" data-classes="" data-invalid="Value must be Integer >= 0" min="0" required /> \
                                         </div> \
                                         <div class="col-6 input-col mt-1" >   \
                                            <input type="number" step="any" value="" data-id="%ID%" class="app-input editor-input cost" data-icon="" data-field="Amount" data-classes="" data-invalid="Value must be Integer >= 1" min="1"  required /> \
                                         </div> \
                                         <div class="col-auto input-col mt-1 pointer">   \
                                            <i class="fa fa-times w3-text-red mx-2" data-id="%ID%" style="font-size: 1.2rem; margin-top: 1.7rem;"></i>    \
                                         </div>';


                        var content = $('<div class="form-row m-0">   \
                                            <div class="col-12 mt-3 pb-2 h6 border-bottom border-dark">'+(ucwords(item.name || ''))+'</div>    \
                                            <div class="col-6 mt-1 font-weight-bold text-nowrap border-bottom border-dark">Remaining Life: <h5>'+(formatNumber(remaining_life, 0))+'</h5></div>    \
                                            <div class="col-6 mt-1 font-weight-bold text-nowrap border-bottom border-dark">Cost: <h5>'+(formatMoney(cost, 0))+'</h5></div>    \
                                            <div class="col-12 my-2">   \
                                                <div class="form-row m-0 splits">   \
                                                        <div class="col-5 input-col mt-1 ">   \
                                                            <input type="number" value="'+remaining_life+'" class="app-input editor-input year master" data-id="master" data-icon="" data-field="Year"  data-classes="" data-invalid="Value must be Integer >= 0" min="0"/> \
                                                        </div> \
                                                        <div class="col-6 input-col mt-1 ">   \
                                                            <input type="number" step="any" value="'+cost+'" class="app-input editor-input cost master" data-id="master" data-icon="" data-field="Amount"  data-classes="" data-invalid="Value must be Integer >= 1" min="1"/> \
                                                        </div> \
                                                </div>  \
                                            </div>  \
                                            <div class="col-12 mt-1 text-right" style=""><label class="btn btn-sm btn-primary">Add</label></div>    \
                                         </div>');

                        var i = 1;
                        var master_cost = content.find('.cost.master'); 
                        content.find('label.btn').click(function(){
                                
                                var new_input = $(input_tmp.replace(/%ID%/g, i));

                                // get remaining amount
                                var remaining = cost;
                                content.find('input.cost').each(function(){ remaining -= this.value; });                                
                                if(remaining <= 0){
                                    error('You have Reached the total amount of: <b>'+formatMoney(cost, 0)+'</b> !', 'Limit Reached', default_error_alert_timeout);
                                    return;
                                }
                                new_input.find('input.cost').val(remaining);

                                var year = 0;
                                content.find('input.year').each(function(){ if(this.value > year)year = parseInt(this.value); });                                 
                                new_input.find('input.year').val(year + 1);                               


                                init_ui(new_input); content.find('.splits').append(new_input);
                                
                                
                                new_input.find('i').click(function(){ 
                                                            var input_cost = floatDecimals(content.find('input.cost[data-id="'+$(this).data('id')+'"]').val() || 0, 2); 
                                                            content.find('[data-id="'+$(this).data('id')+'"]').closest('div.input-col').remove();
                                                            master_cost.val(floatDecimals(master_cost.val(), 2) + input_cost);
                                                    });
                                
                                
                                i++;
                                
                        });



                        var modal = view_modal(content, 'Split Item', function(action){
                                                    if(action.action != 'submit')return;

                                                    var splits = modal.find('input.year');

                                                    var splited_years = [], total_splitted = 0;
                                                    for(var i=0; i<splits.length; i++){
                                                        
                                                        var e = splits.eq(i);
                                                        var new_year = e.val() || 0;
                                                        total_splitted += floatDecimals(content.find('input.cost[data-id="'+e.data('id')+'"]').val() || 0);

                                                        
                                                        if(splited_years.indexOf(new_year) >= 0){
                                                            error('The year <b>'+new_year+'</b> is added multiple times !', '', default_error_alert_timeout);
                                                            return;                                                    
                                                        }

                                                        splited_years.push(new_year);

                                                    }

                                                    console.log(splited_years, total_splitted);

                                                    if(total_splitted != cost){
                                                        error('Please make sure splits add-up to the total cost of  <b>'+formatMoney(cost, 0)+'</b> ! <br>Remaining: <b>'+formatMoney(cost - total_splitted, 0)+'</b>', '');                                                    
                                                        return;
                                                    }

                                                    
                                                    splits.each(function(){
                                                        var new_year = $(this).val() || 0;
                                                        var new_cost = content.find('input.cost[data-id="'+$(this).data('id')+'"]').val() || 0;

                                                        if(new_year < 0 || new_cost < 0)return;

                                                        if($(this).hasClass('master')){
                                                            item.remaining_life = new_year;
                                                            item.cost = new_cost;
                                                            item.parent_id = parent_id;
                                                        }else{
                                                            var new_item = cloneObj(item);
                                                            new_item.id = '';
                                                            new_item.parent_id = parent_id;
                                                            new_item.remaining_life = new_year;
                                                            new_item.cost = new_cost;

                                                            items.push(new_item);
                                                        }
                                                    })

                                                
                                                    modal.close();

                                                    append_items();

                                            }, true);


                    }

                    // update data
                    var update_data = function(e){
                        
                        e = $(e);
                        var value = e.val();

                        if(e.attr('type') == 'number'){
                            if(e.val() < e.attr('min'))e.val('');
                            value = (Math.ceil(value)); 
                        }else if(e.attr('type') == 'checkbox'){
                            value = e.is(':checked') ? 1 : 0;
                        }


                        var key = e.data('key');
                        var index = e.data('index');
                        
                        if(items[index][key] == undefined)return;

                        items[index][key] = value;
                    }

                    // append each item and add listeners
                    var append_item = function(index){
                        
                        // get item by index, and template
                        var item = items[index],
                            tmpl = $(root.find('.item-tmpl').html());

                        // get values
                        item.redundancy = parseFloat(item.redundancy);
                        item.remaining_life = parseFloat(item.remaining_life);
                        item.cost = parseFloat(item.cost);
                        

                        // get current number if related to other items
                        var prefix = "";
                        /*
                        if(item.parent_id){
                            var count = 1;
                            for(var i=0; i<items.length; i++){
                                var tmp = items[i];
                                if(i == index)break;
                                if(tmp.parent_id == item.parent_id)
                                    count++;
                            }

                            prefix = '<b class="ml-1 w3-text-pink">('+count+')</b>';
                        }
                        */
                                            

                        tmpl.find('.item-name').data('key', 'name').attr({'data-key':'name', 'data-index':index}).val(ucfirst(item.name, true)).on('input', function(){ update_data(this); });

                        tmpl.find('.item-redundancy').attr({'data-key':'redundancy', 'data-index':index}).val(item.redundancy).on('input', function(){ update_data(this); });
                        tmpl.find('.item-remaining_life').attr({'data-key':'remaining_life', 'data-index':index}).val(item.remaining_life).on('input', function(){ update_data(this); });
                        tmpl.find('.item-cost').attr({'data-key':'cost', 'data-index':index}).val(item.cost).on('input', function(){ update_data(this); });
                        tmpl.find('.item-sirs').attr({'data-key':'is_sirs', 'data-index':index}).prop('checked', item.is_sirs == 1).on('change', function(){ update_data(this); });
                        
                    
                        

                        //inp.val('');
                    

                        
                        //tmpl.find('.item-del').click(function(){  confirm('Remove this Item ?', '', null, function(ev){ if(ev == 'ok'){ items.splice(index,1); append_items(); } })  });
                        tmpl.find('.item-del').attr('data-index', index).change(function(){ var chk = $(this);  list.find('.item[data-index="'+chk.data('index')+'"]').toggleClass('w3-light-gray', chk.is(':checked')); });
                        //tmpl.find('.item-switch').click(function(){ switch_item(this); }).data('index', index);
                        //tmpl.find('.item-split').click(function(){ split_item(this); }).data('index', index); //.toggleClass('invisible', (item.remaining_life < 2) );
                        //tmpl.find('.item-join').click(function(){ join_item(this); }).data('index', index);

                        
                        init_ui(tmpl);
                        
                        tmpl.filter('.item').attr('data-index', index);


                        list.append(tmpl);
    
                    }

                    var item_compare = function ( a, b ) {
                            

                            return a.name.toLowerCase().localeCompare(b.name.toLowerCase());

                            // sort by remaining life
                            var r1 = parseFloat(a.remaining_life), r2 = parseFloat(b.remaining_life);
                            if ( r1 < r2 ){
                                return -1;
                            }
                            if ( r1 > r2 ){
                                return 1;
                            }
                            return 0;
                    }

                    // append all items to list
                    var append_items = function(clear = true){
                        
                        if(clear)list.html('');

                        // hide if no items available
                        item_del_all.toggleClass('invisible', items.length == 0);
                        item_select_all.toggleClass('invisible', items.length == 0);
                        item_join_all.toggleClass('invisible', items.length == 0);

                        // sort items
                        items.sort( item_compare );
                        

                        var new_item = -1;

                        // filter items by search text
                        for(var index=0; index<items.length; index++){
                            
                            if(filtered.indexOf(index) != -1)continue;

                            // is new item added via inputs
                            if(items[index].new != undefined){ new_item = index; delete items[index].new; }
                            
                            append_item(index);
                        }

                        var new_element = list.find('.item[data-index="'+new_item+'"]');     
                                         
                        if(new_element.length > 0){
                            var position = new_element.first().position().top + list.scrollTop();
                            list.parent().animate({
                                scrollTop: position,                                
                            }, 700);

                            var prev_color = new_element.css('color');

                            new_element.css({'background-color':'var(--orange)', 'color':'#fff'});
                            setTimeout(function(){
                                new_element.animate({
                                                    backgroundColor: "#fff",  
                                                    color: prev_color,                              
                                                }, 500);
                            }, 1100)
                                        
                        }
                
                        update_global(editor_variable_name, items);

                        list_count.html(items.length);

                    }

                    // get info from inputs and add to items
                    var add_new_item = function(){
                        
                        var item = {},
                            inputs = root.find('.item-input');
                            
                        for(var i=0; i<inputs.length; i++){
                            // get input
                            var inp = inputs.eq(i), key = inp.data('key'), val = inp.val() || '';

                            // check empty
                            if((key == 'name' || key == 'cost') && !val){error('Please enter a valid <b>'+inp.data('field')+'</b> !', '', default_error_alert_timeout); return;}
                            item[key] =  val;

                            // format
                            if(key != 'name')
                                val = key == 'cost' ? formatMoney(val) : formatNumber(val, 0);                                                        
                            
                        }

                        item.new = true;
                        
                        items.push(item);
                        inputs.val('');
                        inputs.filter('.item-focus').focus();

                        reset_filter();
                        append_items();
                    }

                    
                    // add item on enter or button add
                    root.find('.item-input').keydown(function(event){ if(event.keyCode == 13){event.preventDefault; add_new_item(); return false;} });
                    root.find('#item_add').click(add_new_item);

                    item_select_all.change(function(){ list.find('.item-del').prop('checked', $(this).is(':checked')); list.find('.item').toggleClass('w3-light-gray', $(this).is(':checked')); });

                    // delete btn
                    item_del_all.click(function(){
                        
                        var items_to_del = list.find('.item-del:checked');
                        //console.log(items_to_del.length);

                        if(items_to_del.length == 0){
                            confirm('Remove All Items ?', '', null, function(ev){ if(ev == 'ok'){ items = []; append_items(); item_select_all.prop('checked', false);  } }); 
                        }else{
                            confirm('Remove Selected Items ?', '', null, function(ev){ 
                                    if(ev == 'ok'){ 
                                        
                                        var tmp = [];
                                        for(var i=0; i<items.length; i++){
                                            var rm = items_to_del.filter('[data-index="'+i+'"]');
                                            if(rm.length > 0)continue;

                                            tmp.push(items[i]);
                                        }
                                       

                                        items = tmp;
                                        append_items();
                                        
                                        item_select_all.prop('checked', false); 
                                    }
                            });
                        }
                    });

                    
                    // join btn
                    item_join_all.click(function(){
                        
                        var items_to_join = list.find('.item-del:checked');
                        //console.log(items_to_del.length);

                        if(items_to_join.length < 2 ){
                            error('Please select at least 2 items to join !'); 
                        }else{
                            confirm('Join the Selected Items ?', '', null, function(ev){ 
                                    if(ev == 'ok'){ 
                                        
                                        var tmp = [];
                                        for(var i=0; i<items_to_join.length; i++){
                                            
                                            tmp.push(items_to_join.eq(i).data('index'));
                                        }
                                       

                                        join_item(tmp);
                                    }
                            });
                        }
                    });

                    root.find('#item_template').click(function(){

                        window.open(global_base+'/files/model_items_template.xls', '_blank');
                    });

                    root.find('#item_import').click(function(){
                            
                            var file_dialog = $('<input type="file"  accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel"/>');

                            
                            var context = this;

                            file_dialog.change(function(e) {

                                    
                                    // check if no file is selected
                                    if(e.target.files.length < 1)return;
                                    
                                    // get selected file
                                    f = e.target.files[0];
                                    var reader = new FileReader();
                                    var name = f.name;

                                    // on file read
                                    reader.onload = function(e) {

                                        var l = loading();

                                        // get all data read
                                        var data = e.target.result;

                                        // create a EXCEL WORKBOOK
                                        var workbook;
                                        if(rABS) {
                                            /* if binary string, read with type 'binary' */
                                            workbook = XLSX.read(data, {type: 'binary'});
                                        } else {
                                            /* if array buffer, convert to base64 */
                                            var arr = fixdata(data);
                                            workbook = XLSX.read(btoa(arr), {type: 'base64'});
                                        }

                                        // if empty, return
                                        if(workbook.SheetNames.length < 1){
                                            error('File Empty or Not an Excel/CSV File !');
                                            return;
                                        }

                                        // get first SHEET of EXCEL
                                        var sheet = workbook.Sheets[workbook.SheetNames[0]];
                                        //var data = XLSX.utils.sheet_to_json (sheet, {header: ['num', 'company_name', 'individual_fn', 'individual_ln', 'type']});


                                        // read sheet as CSV file
                                        var rows = XLSX.utils.sheet_to_csv(sheet, {FS:";", strip:true, blankrows: true}).split('\n');

                                        if(rows.length < 1){
                                            error('This file is empty !');
                                            return;
                                        }

                                        setTimeout(function(){l.remove(); }, 500);

                                        var process = function(){

                                                var l = loading($('#<?=$arand;?>_modal').find('form'));

                                                for(var i=1; i<rows.length; i++){
                                                    
                                                    // skip if line is empty
                                                    if(rows[i].length < 1)continue;

                                                    
                                                    // split csv line and skip if empty
                                                    var cells = rows[i].split(';');
                                                    if(cells.length < 4)continue;

                                                    //console.log(cells);

                                                    items.push({    "name":cells[0], 
                                                                    "redundancy":parseInt((cells[1] || '').replace(/\D/g, '')), 
                                                                    "remaining_life":parseInt((cells[2] || '').replace(/\D/g, '')), 
                                                                    "cost":parseFloat((cells[3] || '').replace(/\D/g, '')), 
                                                                    "is_sirs":parseFloat((cells[4] || '').replace(/\D/g, ''))
                                                                });

                                                }

                                                append_items();

                                                setTimeout(function(){l.remove(); }, 500);
                                        }

                                        if(items.length > 0)
                                            confirm('Would you like to <b>Overwrite</b> or <b>Append</b> to current data ?', '', [{text: 'Cancel', action:'cancel', classes: 'btn-danger'}, {text: 'Overwrite', action:'overwrite', classes: 'w3-deep-orange'}, {text: 'Append', action:'append', classes: 'w3-green'}],
                                                            function(ret){
                                                                
                                                
                                                                if(ret == 'cancel')return;
                                                                else if(ret == 'overwrite')items = [];
                                                                
                                                                process();

                                                            });
                                        else
                                            process();
                                        
                                    }
                                    
                                    reader.readAsBinaryString(f);
                            });

                            
                            
                            file_dialog.trigger('click');

                    });


                    root.find('#item_export').click(function(){
                        var wb = XLSX.utils.book_new();


                        wb.SheetNames.push("Model Items");
                        ws_data = [['Items','Expected Life', 'Remaining Life', 'Cost']];

                        for(const item of items){
                            var tmp = [];
                            
                            tmp.push(item.name); 
                            tmp.push(item.redundancy); 
                            tmp.push(item.remaining_life); 
                            tmp.push(item.cost);
                            tmp.push(item.is_sirs);

                            ws_data.push(tmp);
                        }

                        var rows = XLSX.utils.aoa_to_sheet(ws_data);
                        
                        wb.Sheets["Model Items"] = rows;

                        var wbout = XLSX.writeFile(wb, ( ucwords( model.name || '', true) + ( model.association ? " (" + ucwords(model.association, true) + ")" : "" ) +  " - Model Items.xls" ));



                        
                    });

                    append_items();
                    
                }

                <?=$arand;?>_init();
                
                
                //init_ui(".modal", function(ev, val){console.log(ev, val)} );
            </script>

        </div> 
        </div> 
    </div>