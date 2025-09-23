
function checkError(data, showAlert){
    
    //var showAlert = (arguments.length > 1 && arguments[1] === true)

    var err = null;

    if(typeof data == 'string' && isJSON(data)){
        var tmp = parseJSON(data);

        if(tmp.error != undefined){
            err = tmp.error;
        }
    }else if(typeof data == 'object'){
        if(data.error != undefined){
            err = data.error;
        }
    }


    if(err != null){
        if(showAlert){
            error(handleError(err));
            return true;
        }else return err;
    }else{
        return false;
    }
}

function handleError(code){
    console.log(code);
    return (code && code.length > 0 ? code : "Erreur Inconnue !");

}

function getErrorField(fieldName, parent, error, bold){

    var labels = ['label', 'span'];

    var label = "";
    for(const l of labels){
        var field = parent.find(l+'[for="'+fieldName+'"]').first();
        if(field.length > 0){
            label = field.html();
            break;
        }
    }
    
    if(bold)label = "<strong>"+label+"</strong>";

    return ((label.length > 0) ? label+" "+error : error);
}