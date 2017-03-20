// Form validation
function validate()
{
  //name
  if( document.dlpopup.name.value == "" )
  {
    alert( "Please provide your name!" );
    document.dlpopup.name.focus() ;
    return false;
  }
  
  //institution
  if( document.dlpopup.institution.value == "" )
  {
    alert( "Please provide your institution!" );
    document.dlpopup.institution.focus() ;
    return false;
  }
  
  //email
  var x = document.dlpopup.email.value;
  var atpos = x.indexOf("@");
  var dotpos = x.lastIndexOf(".");
  if (atpos< 1 || dotpos<atpos+2 || dotpos+2>=x.length) 
  {
    alert("Not a valid e-mail address!");
    document.dlpopup.email.focus() ;
    return false;
  }

  //licence
  if (!document.dlpopup.licence.checked) 
  {	
    alert("You must comply to the licence!");
    document.dlpopup.licence.focus() ;
    return false; 
  }
  
  //"close" popup
  document.getElementById('light').style.display='none';
  document.getElementById('fade').style.display='none'
  
  return( true );
}
