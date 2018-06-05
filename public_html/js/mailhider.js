function str_rot13(str){
    return (str+'').replace(/[a-zA-Z]/gi,function(s){
        return String.fromCharCode(s.charCodeAt(0) + (s.toLowerCase() < 'n' ? 13 : -13))
    })
}

function decrypt(str1, str2, id, replace) {
    var str3 = str_rot13("znvygb") + ":";
    var str4 = str_rot13(str1 + "@" + str2);

    $("#" + id).prop("href", str3 + str4);
    if (replace) {
        $("#" + id).text(str4);
    }
}