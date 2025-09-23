function init_users(e){

    if(!e)return;
	
    var users = new Users(e);

    
    var guidedTour = new GuidedTour(users.tour_guide);


    var search_input = undefined;
    users.list();
	

    return users;

}


var Users = function(){

    function Users(parent){

        this.parent = parent;
        this.root = $(parent);
        this.table = null;
    }

    
    
    Users.prototype.data_formatter = function(items){
            
        //console.log(items);

        for(const item of items){
            

            item.name = formatName(item.fn, item.ln);
            item.date = formatDate(item.date, 'm/d/y');    
            
            //console.log(item);           

        }

        return items;
    };
    
    Users.prototype.init_table = function(callbacks){ 
        var row_formatter = function(row, updateCb){
            
            
            var context = this;

            var data = row.getData();
            var cells = row.getCells();


            var adr_show = '';
            if(data.adr){
                adr_show = $('<a class="link text-primary font-weight-bold" href="javascript:void(0);"> <i class="fa fa-map-marker mr-2"></i>Address</a>');
                adr_show.click(function(){ view_info(ucwords(data.adr, true), 'Address', 'sm'); });
            }
    
            var edit_btn = $('<button class="btn btn-dark btn-sm" title="" type="button" >Edit</button>');
            edit_btn.click(function(){ context.edit(data.id); });

            var pass_editor = $('<a class="link text-danger font-weight-bold" href="javascript:void(0);"> <i class="fa fa-lock mr-2"></i>Reset</a>');
            pass_editor.click(function(){  
                                context.resetPassword(data.id);
                            });

            var active_item = '<span class="badge w3-green">Enabled</span>';
            if(!data.is_self) active_item = switch_w(data.active, function(state){}, null, 'global-prop-chk', {"id":data.id, "key":"active"});                

                    
            //console.log(data)
            var role = '<span class="badge '+(data.role_color || 'w3-red')+'">'+ucwords(data.role_name || 'Unspecified', true)+'</span>';
            
            
            for(var cell of cells){
    
                var field = cell.getField();
                element = $(cell.getElement());
                
        
                
    
                switch(field){
                    
                    
                    case 'name': element.addClass('font-weight-bold link').click(function(){ context.edit(data.id); }); break;
                    case 'username': element.addClass('font-weight-bold w3-text-indigo link').click(function(){ context.edit(data.id); }); break;
                    case 'role': element.html(role); break;
                    
                    case 'phone': element.html(formatPhone(data.phone)); break;
                    
                    case 'reset_password': element.html(pass_editor); break;
                    
                    case 'active': element.html(active_item); break;
                    case 'action': element.html(edit_btn); break;
    
                }
            }
            
        }.bind(this);

            this.table = list_pagination(this.parent, {rowFormatter: row_formatter, dataFormatter: this.data_formatter}, {persistenceID: 'users', ajaxFiltering: true}, "modules/users/app/users.php", {cmd: "get"}, "modules/users/app/users.php?cmd=set", "modules/users/app/users.php?cmd=delete", callbacks);
    };

    Users.prototype.list = function(callbacks){ if(!this.table){ this.init_table(callbacks); }else{ this.table.refresh(true); this.table.clearFilter(true); } }
    
                        
    Users.prototype.search =   function (search){ 
        if(this.table)this.table.remoteSearch(search);
    }

    Users.prototype.filter =   function (filters){
                                    if(this.table)this.table.remoteFilter(filters);
                                }


    Users.prototype.edit = function(id, cb, beforeCb){
                                edit_element("modules/users/app/users.php?cmd=edit", { "id":id}, "modules/users/app/users.php?cmd=save",
                                                function(e, context){
                                                    
                                                    context.list();

                                                },
                                                null,
                                                function(data){
                                                    
                                                    //console.log(data);                                                   
                                                    
                                                    //return false;
                                                },
                                                function(action){}, this);
    }
    
    Users.prototype.get =   function(cb){
                        
        ajax_get_no_loading("modules/users/app/users.php?cmd=get", {},
                function(resp){ if(!checkError(resp, true)){ execfunc(cb, resp); } }, function(resp){error('Error while retrieving items !'); console.log(resp.responseText); });
        
    };

    
    
    Users.prototype.resetPassword =   function(id, cb){ 
        
                  ajax_post("modules/users/app/users.php?cmd=set", {"id": id, password: "*****"},
                              function(resp){ if(!checkError(resp, true)){ success(resp.success, null, default_success_alert_timeout); if(cb)cb(); } }, function(resp){error('Error while changing password !'); console.log(resp.responseText); });

    };

    
    Users.prototype.delete =   function(){
                        
        this.table.delete();
        
    };

    
    

    Users.prototype.tour_guide = { "main": [            {text: '<span class="font-weight-bold h5">Hi !<br>Welcome to the user management page.</span><br><br> Here you can <b>Add, Edit, Delete or Disable/Enable Users</b> of your association. If you want to <b>Create a New User</b>, simply <b>Click on the New button below</b>. to <b>Create it Later</b>, simply click on the The <b>New button</b> in the top right side of this page. \
    <div class="w-100 mt-3"><button class="d-inline-block btn btn-sm btn-primary shadow-sm ml-1 " onclick="users.edit()">New</button></div>', 
                                                            audio: 'users/help/audio/CU - 1.m4a', element: false},

                                                        {text: 'To <b>Create a User</b>, simply click on this button, and enter the <b>User’s Data</b> in the form', 
                                                            audio: 'users/help/audio/CU - 2.m4a'},

                                                        {text: 'You <b>Search</b> for any user’s data by entering a keyword in this input', 
                                                            audio: 'users/help/audio/CU - 8.m4a'},

                                                        {text: 'You can <b>Filter Users</b> by field by entering keywords in the inputs in the table’s header', 
                                                            audio: 'users/help/audio/CU - 7.m4a'},

                                                        {text: 'To <b>Delete a User</b>, simply check this checkbox then click on <label class="btn btn-sm btn-danger">Delete Selection</label> in the top left side of this page', 
                                                            audio: 'users/help/audio/CU - 3.m4a'},

                                                        {text: 'To <b>Edit a User</b>, simply click on this button and <b>Edit the User’s Data</b> using the form. Once done, click on <label class="btn btn-sm btn-success">Save</label> to <b>Save the new data.</b>', 
                                                            audio: 'users/help/audio/CU - 4.m4a'},

                                                        {text: 'To <b>Reset a User’s Password</b>, click here. This will automatically send an email to that user with a <b>Reset Link</b>', 
                                                            audio: 'users/help/audio/CU - 5.m4a'},

                                                        {text: 'To <b>Disable</b> or <b>Enable a User</b>, click here to toggle it', 
                                                            audio: 'users/help/audio/CU - 6.m4a'}
                                                 ] };


    return Users;

}();