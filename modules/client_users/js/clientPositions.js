
var ClientPositions = function(){

    function ClientPositions(client_id){

        this._client = client_id;
        this.root = null;
        this.table = null;
    }

    ClientPositions.prototype.client = function(client){
            
        if(!client)return this._client;
        else this._client = client;

    };
    
    
    ClientPositions.prototype.list = function(callbacks){ 
                        
                        var context = this;
                        var client_id = this._client;

                        this.get(null, client_id, function(list){
                            
                            var content = '<div class="row positions-list"><div class="col-12 border-top border-dark mt-2 pt-1 position-edit">Click on <u>Position Name</u> to <b>Edit</b></div><div class="col-10 border-top border-dark mt-2 pt-1"><input type="text" class="app-input editor-input" data-field="Position Name" data-key="position"/></div><div class="col-2 border-top border-dark mt-2 pt-1"><button type="submit" class="btn btn-sm btn-success w-100 add" style="margin-top: 1.3rem;">Add</button></div></div>';
                            
                            // add a new rew
                            var add_item = function(editor, item){
                                
                                var row = '';
                                if(item.can_edit == 1)
                                    row = $('<div class="col-1 my-1 border-right border-dark" data-id="'+item.id+'"><i class="fa fa-minus w3-red p-1 pointer rounded del" ></i></div><div class="col-11 my-1" data-id="'+item.id+'"><span class="h6 font-weight-bold link">'+ucwords(item.value, true)+'</span></div>');
                                else
                                    row = $('<div class="col-1 my-1 border-right border-dark"></div><div class="col-11 my-1"><span class="h6 font-weight-bold">'+ucwords(item.value, true)+'</span></div>');

                                
                                // delete 
                                row.find('i.del').data('id', item.id).click(function(){
                                    var row = $(this).parent();
                                    var id = $(this).data('id');
                                    
                                    if(!id)return;

                                    context.delete(id, client_id, function(success){if(success){ editor.find('div[data-id="'+id+'"]').remove(); }})
                                });

                                // update position name
                                row.find('span').data('id', item.id).click(function(){
                                    var pos = $(this);
                                    if(!pos.data('id'))return;

                                    context.edit(pos.data('id'), pos.html(), function(resp, new_value){
                                        pos.html(ucwords(new_value, true));
                                    });
                                })


                                editor.find('.position-edit').before(row);
                            }

                            var editor = view_modal(content, 'Positions', function(action){
                                
                                    if(action.action == "cancel"){
                                        execfunc(callbacks);
                                    }

                                    else if(action.action == "submit" && action.value.position){
                                        var new_position = action.value.position;

                                        // save new position and add to row
                                        context.save(null, client_id, new_position, function(resp){
                                            add_item(editor, {"id":resp.success, "value":new_position, "can_edit":1 });
                                            reset_editor(editor);
                                        })
                                    }
                            }, true, 'md', false);
                            
                            for(var i=0; i < list.length; i++){
                                
                                // skip global positions
                                //if(!list[i].client_id)continue;
                                
                                add_item(editor, list[i]);
                                
                            }

                        });

                        
    }
    

    ClientPositions.prototype.edit = function(id, position, cb, beforeCb){

                                text_editor(position, function(value, editor){
                                    ajax_post("modules/client_users/app/clientUsers.php?cmd=save_position", {"id":id, "value": value},
                                        function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp, value); editor.close(); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });


                                }, 'Position Name', 'md', false);
                                
    }

    
    
    ClientPositions.prototype.get =   function(id, client_id, cb){
        
        if(!client_id)client_id = this._client;

        ajax_get_no_loading("modules/client_users/app/clientUsers.php?cmd=get_position", {"id": id, "client_id":client_id},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    
    ClientPositions.prototype.save =   function(id, client_id, position, cb){
        
        if(!position && !id)return;

        if(!client_id)client_id = this._client;

        ajax_get("modules/client_users/app/clientUsers.php?cmd=save_position", {"id": id, "client_id":client_id, "value":position},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    ClientPositions.prototype.delete =   function(id, client_id, cb){ 
        
        if(!Array.isArray(id))id = [id];

        if(!client_id)client_id = this._client;

        delete_elements({'id':id}, "modules/client_users/app/clientUsers.php?cmd=delete_position&client_id="+this.client_id, 
                        function(success){if(cb)cb(success);}, true); 
    };
    

    return ClientPositions;

}();