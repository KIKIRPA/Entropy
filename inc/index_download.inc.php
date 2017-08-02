<?php
  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }


// dump old code from index_view

// cookie stuff
  // option 1: cookie exists and is valid   --> $cookie = array(name, inst, email) + update cookie
  // option 2: cookie exists but is invalid --> $cookie = False
  // option 3: no cookie, but cookie form field is checked and data is valid 
  //                                        --> $cookie = array(name, inst, email) + make cookie
  // option 4: no cookie, and cookie form field is unchecked or data invalid
  //                                        --> $cookie = False
 
  if (isset($_COOKIE[COOKIE_NAME])) 
  {
    $cookie = verifycookie($_COOKIE[COOKIE_NAME]);   // in case of false data, this will output False
    if ($cookie) $cookie = makecookie($cookie);      // if not false: update cookie; will output True
    else removecookie();                             // remove invalid cookie TODO: invalid cookie notification!
  }
  elseif (isset($_REQUEST["cookie"])
              and isset($_REQUEST["name"]) 
              and isset($_REQUEST["institution"]) 
              and isset($_REQUEST["email"])
          )
  { 
    $cookie = verifycookie($_REQUEST["name"], $_REQUEST["institution"], $_REQUEST["email"]);
    if ($cookie) $cookie = makecookie($cookie);
  }
  else 
    $cookie = false;     // if false: show dl form

?>