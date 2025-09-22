function init_blank(e){

    if(!e)return;
	
    var blank = new Blank(e);
    var search_input = undefined;
    blank.list(function(ev){
        if(ev && ev.event == 'ajax'){
            if(!ev.params.search)search_input.val('');
        }
    });
	
	
    var ui = init_ui(e, function(ev, value, cb){
            
            if(ev.key == 'search'){
                if(ev.event == 'enter' || ev.event == 'search' )
                    blank.search( value );
                else if (ev.event == 'created')
                    search_input = value;
            }
      });

    return blank;

}


var Blank = function(){

    function Blank(parent){

        this.parent = parent;
        this.root = $(parent);
        this.table = null;
    }

    
    
    Blank.prototype.data_formatter = function(items){
            
        //console.log(items);

        for(const item of items){
            

            item.name = formatName(item.fn, item.ln);
            item.date = formatDate(item.date, 'm/d/y');    
            
            //console.log(item);           

        }

        return items;
    };
    
    Blank.prototype.init_table = function(callbacks){ 
        var row_formatter = function(row, updateCb){
            
            
            var context = this;

            var data = row.getData();
            var cells = row.getCells();


            
            var edit_btn = $('<button class="btn btn-dark btn-sm" title="" type="button" >Edit</button>');
            edit_btn.click(function(){ context.edit(data.id); });

            
            
            for(var cell of cells){
    
                var field = cell.getField();
                element = $(cell.getElement());
                
        
                
    
                switch(field){
                    
                    
                    
                    case 'action': element.html(edit_btn); break;
    
                }
            }
            
        }.bind(this);

            this.table = list_pagination('.blank', {rowFormatter: row_formatter, dataFormatter: this.data_formatter}, {persistenceID: 'blank'}, "app/modules/blank.php", {cmd: "get"}, "app/modules/blank.php?cmd=set", "app/modules/blank.php?cmd=delete", callbacks);
    };

    Blank.prototype.list = function(callbacks){ if(!this.table)this.init_table(callbacks); else this.table.refresh(true); }
    
                        
    Blank.prototype.search =   function (search){ 
        if(this.table)this.table.remoteSearch(search);
    }

    Blank.prototype.filter =   function (filters){
                                    if(this.table)this.table.remoteFilter(filters);
                                }


    Blank.prototype.edit = function(id, cb, beforeCb){
                                edit_element("app/modules/blank.php?cmd=edit", { "id":id}, "app/modules/blank.php?cmd=save",
                                                function(e, context){
                                                    
                                                    context.list();

                                                },
                                                null,
                                                function(data){
                                                    
                                                    console.log(data);                                                   
                                                    
                                                    //return false;
                                                },
                                                function(action){}, this);
    }

    
    Blank.prototype.delete =   function(){
                        
        this.table.delete();
        
    };

    return Blank;

}();