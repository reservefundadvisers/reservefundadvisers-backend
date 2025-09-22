function init_associations(e){

    if(!e)return;

	
    var clients = new Clients(e); 
    var search_input = undefined;
    clients.list();

	var cols = $('#columnToggle');
    cols.html('<h6 class="dropdown-header text-primary">More Columns</h6>');

    if(clients.table.table){
        for(const col of clients.table.table.getColumns()){
            if($(col.getElement()).hasClass('togglable')){
                var colDef = col.getDefinition();
                cols.append('<a class="dropdown-item font-weight-bold" href="#" data-col="'+btoa(colDef.field)+'"><i class="fa fa-check mr-2 invisible"></i>'+colDef.title+'</a>')
            }
        }
    }

    $('#dropdownMenuButton').dropdown();

    cols.find('a').click(function(){ 
        var field = $(this).data('col');
        if(!field || !clients.table.table)return;

        field = atob(field);

        var col = clients.table.table.getColumn(field);
        clients.table.table.toggleColumn(field); 

        $(this).find('i').toggleClass('invisible', !col.isVisible());

    });
	

    return clients;

}

function init_companies(e){

    if(!e)return;

	
    var clients = new Clients(e, 'company'); 
    var search_input = undefined;
    clients.list();

	var cols = $('#columnToggle');
    cols.html('<h6 class="dropdown-header text-primary">More Columns</h6>');

    if(clients.table.table){
        for(const col of clients.table.table.getColumns()){
            if($(col.getElement()).hasClass('togglable')){
                var colDef = col.getDefinition();
                cols.append('<a class="dropdown-item font-weight-bold" href="#" data-col="'+btoa(colDef.field)+'"><i class="fa fa-check mr-2 invisible"></i>'+colDef.title+'</a>')
            }
        }
    }

    $('#dropdownMenuButton').dropdown();

    cols.find('a').click(function(){ 
        var field = $(this).data('col');
        if(!field || !clients.table.table)return;

        field = atob(field);

        var col = clients.table.table.getColumn(field);
        clients.table.table.toggleColumn(field); 

        $(this).find('i').toggleClass('invisible', !col.isVisible());

    });
	

    return clients;

}


var Clients = function(){

    function Clients(parent, type = 'client'){

        this.parent = parent;
        this.root = $(parent);
        this.table = null;
        this.type = type;

        this.users = new ClientUsers(); 
    }

    
    
    Clients.prototype.data_formatter = function(items){
            
        //console.log(items);

        for(const item of items){
            

            item.date = formatDate(item.date, 'm/d/y');    
            
            //console.log(item);           

        }

        return items;
    };
    
    Clients.prototype.init_table = function(callbacks){ 
        var row_formatter = function(row, updateCb){
            
            
            var context = this;

            var data = row.getData();
            var cells = row.getCells();

            

            var adr_show = '';
            var address = formatAdr(data, '\n');
            if(address){
                adr_show = $('<a class="link text-primary font-weight-bold" href="javascript:void(0);"> <i class="fa fa-map-marker mr-2"></i>Address</a>');
                adr_show.click(function(){ view_info(address, 'Address', 'sm'); });
            }
    
            var edit_btn = $('<button class="btn btn-dark btn-sm" title="" type="button" >Edit</button>');
            edit_btn.click(function(){ context.edit(data.id); });

            var users_btn = $('<a class="link text-primary font-weight-bold" href="javascript:void(0);"> <i class="fa fa-users mr-2"></i>Users</a>');
            users_btn.click(function(){  
                                context.users.client(data.id);
                                context.users.list();
                            });

            var active_item = switch_w(data.active, function(state){}, null, 'global-prop-chk', {"id":data.id, "key":"active"});                
            
            for(var cell of cells){
    
                var field = cell.getField();
                element = $(cell.getElement());
                
                        
    
                switch(field){
                    
                    
                    case 'association': element.html(ucwords(data.association, true)).addClass('font-weight-bold').click(function(){ context.edit(data.id); }); break;
                    case 'company': element.html(ucwords(data.company, true)).addClass('font-weight-bold').click(function(){ context.edit(data.id); });; break;
                    
                    case 'phone': element.html(formatPhone(data.phone)); break;

                    case 'address': element.html(ucwords(data.address, true)); break;
                    case 'address2': element.html(ucwords(data.address2, true)); break;
                    case 'city': element.html(ucwords(data.city, true)); break;
                    case 'zip': element.html(ucwords(data.zip, true)); break;
                    case 'state': element.html(ucwords(data.state, true)); break;

                    case 'users': element.html(users_btn); break;
                    
                    case 'active': element.html(active_item); break;
                    case 'action': element.html(edit_btn); break;
    
                }
            }
            
        }.bind(this);

            this.table = list_pagination(this.parent, {rowFormatter: row_formatter, dataFormatter: this.data_formatter}, {persistenceID: 'clients', ajaxFiltering: true}, "modules/clients/app/clients.php", {cmd: "get_"+this.type}, "modules/clients/app/clients.php?cmd=set_"+this.type, "modules/clients/app/clients.php?cmd=delete_"+this.type, callbacks);
    };

    Clients.prototype.list = function(callbacks){ if(!this.table){ this.init_table(callbacks); }else{ this.table.refresh(true); this.table.clearFilter(true); } }
    
                        
    Clients.prototype.search =   function (search){ 
        if(this.table)this.table.remoteSearch(search);
    }

    Clients.prototype.filter =   function (filters){
                                    if(this.table)this.table.remoteFilter(filters);
                                }


    Clients.prototype.toggleColumns =   function (){
                                    if(this.table)this.table.toggleColumns();
                                }

    Clients.prototype.edit = function(id, cb, beforeCb){
                                edit_element("modules/clients/app/clients.php?cmd=edit_"+this.type, { "id":id}, "modules/clients/app/clients.php?cmd=save_"+this.type,
                                                function(e, context){
                                                    
                                                    execfunc(cb, e);
                                                    context.list();

                                                },
                                                null,
                                                function(data){
                                                    
                                                    //console.log(data);                                                   
                                                    
                                                    //return false;
                                                },
                                                function(action){}, this);
    }

    
    Clients.prototype.get =   function(cb){
                        
        ajax_get_no_loading("modules/clients/app/clients.php?cmd=get_"+this.type, {},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    Clients.prototype.delete =   function(){
                        
        this.table.delete();
        
    };


    return Clients;

}();





var ClientUsers = function(){

    function ClientUsers(client_id){

        this._client = client_id;
        this.root = null;
        this.table = null;
    }

    ClientUsers.prototype.client = function(client){
            
        if(!client)return this._client;
        else this._client = client;

    };
    
    
    ClientUsers.prototype.list = function(type = 'edit', callbacks){ 
                        
                        var context = this;
                        var client_id = this._client;

                        var user_formatter = function(item){

                            var username    = item.username ?      '<div class="h6 mb-0 mt-2 font-weight-bold text-black row"><div class="col-auto pr-0"><i class="fa fa-user mr-2" style="color:var(--primary-hover)">   </i></div><div class="col pl-0">\&nbsp;'+(item.username || '')+'</div></div>' : '';                            
                            var phone       = item.phone    ?      '<div class="h6 mb-0 mt-2 font-weight-bold text-black row"><div class="col-auto pr-0"><i class="fa fa-phone mr-2" style="color:var(--primary-hover)">   </i></div><div class="col pl-0">'+formatPhone(item.phone || '')+'</div></div>' : '';
                            var email       = item.email    ?      '<div class="h6 mb-0 mt-2 font-weight-bold text-black row"><div class="col-auto pr-0"><i class="fa fa-envelope mr-2" style="color:var(--primary-hover)"></i></div><div class="col pl-0">'+(item.email || '')+'</div></div>' : '';
                            
                            var edit_user = '  <button data-role="admin" class="btn btn-dark btn-circle btn-sm action-custom float-right mr-2" data-action="edit" data-id="%ID%" title="" type="button">    \
                                                                                    <i class="fas fa-pen"></i>    \
                                                                                </button>';

                            var del_user = '   <button data-role="admin" class="btn btn-danger btn-circle btn-sm action-custom float-right mr-2" data-action="delete" data-id="%ID%" title="" type="button">    \
                                                    <i class="fas fa-trash"></i>    \
                                                </button>';

                            var reset_pass = '   <button class="btn w3-green btn-circle btn-sm action-custom float-left ml-3" data-action="reset_pass" data-id="%ID%" title="" type="button">    \
                                                    <i class="fas fa-lock"></i>    \
                                            </button>';

                            var active_user = switch_w(item.active, function(state){}, null, 'action-custom', {"id":'%ID%', "action":"active", }).addClass('mt-1 float-left')[0].outerHTML;

                            var user_edit = type == 'edit' ? (edit_user || '').replace('%ID%', item.id) : '';
                            var user_del  = type == 'edit' ? (del_user || '').replace('%ID%', item.id) : '' ;
                            var user_reset  = type == 'edit' ? (reset_pass || '').replace('%ID%', item.id) : '' ;
                            var user_active  = type == 'edit' ? (active_user || '').replace('%ID%', item.id) : '' ;


                            var card = $('<div class="card border-left-primary shadow h-100 py-2 position-relative">    \
                                            <div class="card-body py-0"> \
                                                <div class="row no-gutters align-items-center" style="min-height:50px;"> \
                                                    <div class="col-md-10 pr-2 text-left">  \
                                                        <div class="text-lg font-weight-bold text-primary mb-0">'+formatName(item.fn, item.ln)+'</div>    \
                                                        <div class="text-sm font-weight-bold text-secondary mb-3">'+ucwords(item.position || '', true)+'</div>    \
                                                        '+username+' \
                                                        '+phone+' \
                                                    </div>  \
                                                    <div class="col-md-2 pt-3">  \
                                                        <i class="fas fa-address-card fa-2x text-gray-500"></i> \
                                                    </div>  \
                                                </div>  \
                                            </div>  \
                                            <div class="text-left pl-3 my-2 mt-3 pr-2">'+user_active+' '+user_reset+' '+user_edit+' '+user_del+'</div>    \
                                        </div>');
                            
                            card.find('input:checkbox').data({"id":item.id, "action":"active"});

                            return card;
                        }

                        
                        var grid;
                        var create_grid = function(resp){
                            
                                                var create_user_btns = [{'text':'<i class="fa fa-plus mr-1"></i>New', 'color':'primary', 'data':{'role':'admin,manager,company_admin'}, 'cb':function(){ context.edit('', function(){ fetch(); }) } }];

                                                context.grid = grid_modal(  resp, user_formatter, 'Users ('+ (resp.length || '0') +')', 
                                                                    function(action){ 
                                                                                
                                                                                if(action.action == 'custom'){
                                                                                    
                                                                                    if(action.value == "edit")context.edit(action.data.id, fetch);
                                                                                    else if(action.value == "delete") context.delete(action.data.id, null, fetch);
                                                                                    else if(action.value == "reset_pass") context.resetPassword(action.data.id, fetch);
                                                                                    else if(action.value == "active") set_property("modules/clients/app/clientUsers.php?cmd=set", {"id": action.data.id, "active": action.data.state }, fetch);   
                                                                                
                                                                                }else if(action.action == 'cancel'){
                                                                                    context.grid = undefined;
                                                                                }
                                                                            },
                                                                    create_user_btns, 
                                                                    {minSize:'md', size:'lg', cols:2});
                                                
                                            
                                        }

                        var fetch = function(){
                            
                                        ajax_get("modules/clients/app/clientUsers.php?cmd=get", {"client_id":client_id}, function(resp){  
                                            
                                            if(checkError(resp, true))return; 

                                            if(!context.grid)create_grid(resp);
                                            else context.grid.refresh(resp);

                                            context.grid.title('Users ('+ (resp.length || '0') +')')
                                                    
                                        }, function(err){console.log(err.responseText); error(handleError(err));});
                                    };
                        fetch();




    }
    
                        
    ClientUsers.prototype.search =   function (search){ 
        if(this.table)this.table.remoteSearch(search);
    }

    ClientUsers.prototype.filter =   function (filters){
                                    if(this.table)this.table.remoteFilter(filters);
                                }


    ClientUsers.prototype.edit = function(id, cb, beforeCb){
                                edit_element("modules/clients/app/clientUsers.php?cmd=edit", { "id":id, "client_id":this._client}, "modules/clients/app/clientUsers.php?cmd=save",
                                                function(e, context){
                                                    
                                                    execfunc(cb, resp);
                                                    context.list();

                                                },
                                                null,
                                                function(data){
                                                    
                                                    //console.log(data);                                                   
                                                    
                                                    //return false;
                                                },
                                                function(action){}, this);
    }

    
    
    ClientUsers.prototype.get =   function(cb){
                        
        ajax_get_no_loading("modules/clients/app/clientUsers.php?cmd=get", {},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };
    
    ClientUsers.prototype.delete =   function(id, client_id, cb){ 
        
        if(!Array.isArray(id))id = [id];

        if(!client_id)client_id = this._client;

        delete_elements({'id':id}, "modules/clients/app/clientUsers.php?cmd=delete&client_id="+this.client_id, 
                        function(){if(cb)cb();}, true); 
    };
    
    
    ClientUsers.prototype.resetPassword =   function(id, cb){ 
        
                  ajax_post("modules/clients/app/clientUsers.php?cmd=set", {"id": id, password: "*****"},
                              function(resp){ if(!checkError(resp, true)){ success(resp.success, null, default_success_alert_timeout); if(cb)cb(); } }, function(resp){error('Error while changing password !'); console.log(resp.responseText); });

    };
    

    ClientUsers.prototype.generatePassword =   function(len = 8){ 
        var charset = "abcdefghijklmnopqrstuvwxyz!&$@.ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
            retVal = "";
        for (var i = 0, n = charset.length; i < len; ++i) {
            retVal += charset.charAt(Math.floor(Math.random() * n));
        }
        return retVal;        
    };

    return ClientUsers;

}();




