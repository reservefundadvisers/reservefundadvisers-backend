// set first caracter to upper case
function ucfirst(str, leaveResteAsIs){
    if(!str || str.length < 1 || typeof str != 'string')return '';

    var s = str.charAt(0).toUpperCase();

    s += leaveResteAsIs ? str.substr(1, str.length) : str.substr(1, str.length).toLowerCase();

    return s;
    
}

// set first caracter to upper case
function ucwords(str, leaveResteAsIs, sep = ' ', lineSep = '\n'){
    if(!str || str.length < 1 || typeof str != 'string')return '';

    var lines = str.split(lineSep);

    for(var j=0; j<lines.length; j++){

        var words = lines[j].split(sep);
        var s = '';
        
        for(var i=0; i<words.length; i++){
            
            words[i] = ucfirst(words[i], leaveResteAsIs);
        }

        lines[j] = words.join(' ')
    }

    return lines.join('\n');
    
}

// check string validity
function validString(str){
    
    return (!str) ? '' : str;
    
}

// format name
function formatName(fn, ln){
    var str = ucwords(fn) || "";
    if(str.length > 0)str += " ";
    str += (ln || "").toUpperCase();

    return str;
}

function formatAdr(data, sep = ','){
    var infos = ['address', 'city', 'zip', 'state'];

    //console.log(data);

    var str = [];
    for(const i of infos){
        var d = data[i];
        if(d)str.push(ucwords(d, true));
    }

    return str.join(sep);
}

function formatPhone(phone){
    if (!phone) return phone;


    const phoneNumber = phone.replace(/\D/g, ''); 

    const phoneNumberLength = phoneNumber.length; 

    if (phoneNumberLength < 4) return phoneNumber;
    
    if (phoneNumberLength < 7) {
        return '('+phoneNumber.slice(0, 3)+') '+phoneNumber.slice(3);
    }
    return '(' + phoneNumber.slice(0, 3) + ') ' + phoneNumber.slice(3,6) + ' - ' + phoneNumber.slice(6, 10);
}


// format date
function formatDate(timestamp){
    
    var date;
    var initAsToday = arguments[2];


    if(!initAsToday){
        if(!timestamp || isNaN(parseInt(timestamp)) )return timestamp;
        if(timestamp == 0 || timestamp == '0')return '';

        date = new Date(timestamp * 1000);

    }else{        
        if(!timestamp || isNaN(parseInt(timestamp)) || timestamp == 0) date = new Date();        
    }

    d = {
        "y": date.getFullYear(),
        "m": pad(date.getMonth()+1, '0', 2),
        "d": pad(date.getDate(), '0', 2),
        "h": pad(date.getHours(), '0', 2),
        "i": pad(date.getMinutes(), '0', 2),
        "s": pad(date.getSeconds(), '0', 2),
        "t": (date.getTime()/1000)
    };

    var tmp = "";
    if(arguments.length > 1){
        tmp = arguments[1];
        for(const k in d){
            tmp = tmp.replace((new RegExp(k, 'g')), d[k]);            
        }
    }else{
        tmp = d.d+"/"+d.m+"/"+d.y+" - "+d.h+":"+d.i+":"+d.s;
    }

    return tmp;
}

// get timestamp
function getTimestamp(date){
    var timestamp = parseInt( (new Date(date)).getTime()/1000 ) ;
    return isNaN(timestamp) ? 0 : timestamp;
}



// pad 
function pad(str, char, len){
    str += "";
    
    if(len <= str.length)return str;

    var tmp = "";
    for(var i=0; i<(len-str.length); i++){
        tmp += char;
    }

    tmp += str;
    return tmp;
}

// parse JSON
function parseJSON(json, retValOnError = false){
    if(typeof json == 'object' || typeof json == 'array')return json;
    try{
        var data = JSON.parse(json)
        return data;
    }catch(e){
        return retValOnError ? json : {};
    }
}

// is JSON
function isJSON(json){

    if(json == null || json == undefined  || json.length < 1 || typeof json != 'string')
        return false;

    try{
        var data = JSON.parse(json);
        return (typeof data == 'object');
    }catch(e){
        return false;
    }
}

function has(obj, element){

    if(Array.isArray(obj)){
        var pos = obj.indexOf(element);
        return pos < 0 ? false : pos;        
    }else if(typeof obj == "object"){
        return obj[element] != undefined;
    }else{
        return false;
    }
}

function basename(path) {
    return path.split('/').reverse()[0];
 }

function isFunc(cb){
    return cb && typeof cb == "function";
}

function isObject(cb){
    return cb && typeof cb == "object";
}

function isArray(obj){
    return obj && typeof obj == "object" && Array.isArray(obj);
}

 function execfunc(){
    if(arguments.length < 1 || typeof arguments[0] != 'function') return;

    var args = []; for(var i=1; i<arguments.length; i++)args.push(arguments[i]);
    var cb = arguments[0];
    return cb.apply(null, args);
 }


 

// money formatter
function formatMoney(amount, decimalCount = 2, decimal = ".", thousands = ",", symbol = "$") {
    try {

        decimal = !decimal ? '.' : decimal;
        thousands = !thousands ? ',' : thousands;

        decimalCount = Math.abs(decimalCount);
        decimalCount = isNaN(decimalCount) ? 2 : decimalCount;
    
        const negativeSign = amount < 0 ? "-" : "";
    
        let i = parseInt(amount = Math.abs(Number(amount) || 0).toFixed(decimalCount)).toString();
        let j = (i.length > 3) ? i.length % 3 : 0;
    

        var res = 
          negativeSign + symbol +
          (j ? i.substr(0, j) + thousands : '') +
          i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands) +
          (decimalCount ? decimal + Math.abs(amount - i).toFixed(decimalCount).slice(2) : "");

          
        return res;

      } catch (e) {
        //console.log(e)
        return amount;
      }
}

// number formatter
function formatNumber(amount, decimalCount = 2, decimal = ".", thousands = ",", symbol = "") {
    return formatMoney(amount, decimalCount, decimal, thousands, symbol)
}

// just return %
function formatPercent(value, frac = true, decimals){
    var str = '0.00%';

    if(!isNaN(parseFloat(value))) str = formatNumber(frac ? value*100 : value, decimals)+"%";

    return str;
}


//
function formatNegatif(number, zero = '0', decimals = 2, prefix = '', negatifColor = 'red', bold = false, positifColor = '', classes = '', styles = ''){
    
    
    var  num = parseFloat(number);
    if(isNaN(num))num = 0;

    if(zero == null || zero == undefined)zero = 0;


    negatifColor = 'w3-text-'+negatifColor;
    positifColor = positifColor ? 'w3-text-'+positifColor : '';


    if(num > 0){
        return '<span class="'+positifColor+' '+(bold ? 'font-weight-bold': '')+' '+classes+'" style="'+styles+'">'+prefix+formatNumber(number, decimals)+'</span>'
    }else if(num == 0){
        if(zero.length < 1)prefix = '';
        return '<span class="'+negatifColor+' '+(bold ? 'font-weight-bold': '')+' '+classes+'" style="'+styles+'">'+prefix+(zero == '0' ? parseInt(zero).toFixed(decimals) : zero)+'</span>'
    }else{
        number = Math.abs(number); prefix = '-'+prefix;
        return '<span class="'+negatifColor+' '+(bold ? 'font-weight-bold': '')+' '+classes+'" style="'+styles+'">'+prefix+formatNumber(number, decimals)+'</span>'
    }
}


function floatDecimals(n, d=2){
    
    return parseInt(n * Math.pow(10, d)) / Math.pow(10, d);
}

function validate(str, pattern, isRegex = false){
    if(!str)return true;
    if(isRegex){
        const regex = new RegExp(pattern);
        return pattern.test(str);
    }
}


function cloneObj(obj){
    /*
    var new_obj = {};
    
    if(typeof obj == 'object'){
        
        for(const k in obj){var tmp = obj[k]; new_obj[k] = tmp; }
    }*/

    return parseJSON(JSON.stringify(obj || {}));
}



function setFocus(e, timeout = 5){
    $(e).each(function(){   var el = this; el.focus(); 
                            if(!$(el).is('input') || $(el).attr('type') == 'number')return;
                            setTimeout(function(){ el.selectionStart = el.selectionEnd = 10000; }, 50);});
    
}


function selectByValue(e, value){
    value = value || e.data('load-value');
    var el = e.find('option[value="'+value+'"]'); 
    if(el.length > 0){
        e.find('option').removeAttr('selected');
        el.attr('selected', 'true');
    }
}


function selectOption(e){
    e = $(e);
    return e.find('option:selected');     
}



function fillSelect(e, values, append=false){
    e = $(e);
    if(!e.is('select'))return;

    if(!append)e.html('');

    // v: value, t: text, s: selected, d: disabled, data: data-attr, h: header (optgroup)

    var add_option = function(option){
                
        // add data-attr
        var dataAttr = ""; if(option.data){for(const d in option.data)dataAttr += 'data-'+d+'="'+option.data[d]+'"';}

        // add option
        if(option.h)e.append('<optgroup label="'+option.t+'"></optgroup>');
        else e.append('<option value="'+option.v+'" '+dataAttr+' '+(option.s ? 'selected':'')+' '+(option.d ? 'disabled':'')+'>'+ucwords(option.t, true)+'</option>');
    }

    if(Array.isArray(values)){
        for(const value of values){   
            add_option(value);
        }
    }else if(typeof values == 'object'){   
        add_option(values);
    }
}



// check if object is empty
function objEmpty(obj, except){
        
    var empty = true;
    
    if(Array.isArray(obj))return obj.length == 0;

    if(obj != undefined){
        for(const key in obj){
            
            if(except && except.indexOf(key) >= 0)continue;

            if (typeof obj[key] != 'object' && obj[key]+"" != null && obj[key]+"" != ""){                                        
                empty = false;
                break;                        
            }else if(typeof obj[key] == 'object' && !objEmpty(obj[key], except)){                                        
                empty = false;
                break;
            }
            
        }
    }
    return empty;
}  

function update_global(name, data){
    if(!name || typeof window[name] == 'undefined')
        return false; 
        
    window[name] = data;
    return true;
}

function getURLparam(param){
    var url = window.location.search;
    if(!url)return undefined;

    url = url.substr(1).split('&');
    params = {};

    for(const u of url){
        
        var c = u.split('=');

        if(c.length < 1)continue;
        params[c[0]] = decodeURIComponent(c[1] || '');
    }

    console.log(param);

    if(param)return params[param] || undefined;
    else return params;

}

function replaceAll(str, find, replace){
    return str.replace(new RegExp(find, 'g'), replace);
}


function setCookie(cname, cvalue, exdays) {
    if(!cname)return false;

    const d = new Date();
    d.setTime(d.getTime() + ((exdays ? exdays : 9999)*24*60*60*1000));
    let expires = "expires="+ d.toUTCString();

    document.cookie = cname + "=" + cvalue + ";" + expires + ";path="+global_base;
    
    return true;
}

function getCookie(cname) {
    if(!cname)return "";
    let name = cname + "=";
    let ca = document.cookie.split(';');
    for(let i = 0; i < ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }


function setStorage(name, value){
    window.localStorage.setItem(name, value);
}
function getStorage(name){
    return window.localStorage.getItem(name) || '';
}
function deleteStorage(name){
    window.localStorage.removeItem(name);
}


function check_email(email){
    return String(email)
        .toLowerCase()
        .match(
        /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|.(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
        );
}

function generateId(passwordLength = 12, alphaNumOnly=true) {
    
    return generatePassword(passwordLength, alphaNumOnly);
}

function generatePassword(passwordLength = 6, alphaNumOnly=false) {
    var chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var password = "";

    if(!alphaNumOnly){
        chars += "!@#$%&*_";
    }
    
    for (var i = 0; i <= passwordLength; i++) {
        var randomNumber = Math.floor(Math.random() * chars.length);
        password += chars.substring(randomNumber, randomNumber +1);
    }
        
    return password;
 }