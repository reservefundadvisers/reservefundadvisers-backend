function check_role(e = 'body', retAsHtml = false){
    var root = $($(e));

    root.find('[data-role]').each(function(){
        var item = $(this);
        var item_role = item.data('role');

        if(!item_role)return;

        var item_roles = item_role.split(',');
                
        if(item_roles.indexOf(global_role) < 0)item.remove();
        else item.remove('d-none');
    });

    return retAsHtml ? root[0].outerHTML : root ;
}

check_role();   