$(document).ready(function() {
    var validname = false;
    var validinst = false;
    var validemail = false;
    var validlic = false;

    $("#name").on('change', function () {
        if( $("#name").val().length < 2 ) {
            $("#name").removeClass('is-success');
            $("#name").addClass('is-danger');
            $("#namehelp").show();
            $("#namehelp").text("Please provide a valid name.");
            validname = false;
        } else {
            $("#name").removeClass('is-danger');
            $("#name").addClass('is-success');
            $("#namehelp").hide();
            validname = true;
        }
        $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
    });

    $("#institution").on('change', function () {
        if( $("#institution").val().length < 2 ) {
            $("#institution").removeClass('is-success');
            $("#institution").addClass('is-danger');
            $("#insthelp").show();
            $("#insthelp").text("Please provide a valid institution/university/company name.");
            validinst = false;
        } else {
            $("#institution").removeClass('is-danger');
            $("#institution").addClass('is-success');
            $("#insthelp").hide();
            validinst = true;
        }
        $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
    });

    $("#email").on('change', function () {
        var atpos = $("#email").val().indexOf("@");
        var dotpos = $("#email").val().lastIndexOf(".");
        if( atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= $("#email").val().length ) {
            $("#email").removeClass('is-success');
            $("#email").addClass('is-danger');
            $("#emailhelp").show();
            $("#emailhelp").text("Please provide a valid e-mail address.");
            validemail = false;
        } else {
            $("#email").removeClass('is-danger');
            $("#email").addClass('is-success');
            $("#emailhelp").hide();
            validemail = true;
        }
        $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
    });

    $("#license").on('change', function () {
        if( !$("#license").prop("checked") ) {
            $("#lichelp").show();
            $("#lichelp").text("Required.");
            validlic = false;
        } else {
            $("#lichelp").hide();
            validlic = true;
        }
        $("#btnsubmit").prop( "disabled", !(validname && validinst && validemail && validlic) );
    });
});

function updateFormAction(URL, Id) {
    if ($("#select" + Id).length) { 
        URL += '&i=' + $("#select" + Id).val(); 
    }

    $("#dlform").prop('action', URL);
}

function updateButtonHref(URL, Id) {
    URL += '&i=' + $("#select" + Id).val();
    $("#button" + Id).prop('href', URL);
}