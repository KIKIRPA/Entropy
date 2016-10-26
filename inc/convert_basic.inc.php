<?php

/****************************
*                           *
*  DEPRECATED               *
*  use common_importfilters *
*                           *
****************************/  





  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  

  /*  CONVERT LIBRARY - BASIC FUNCTIONS
      
      readAscii($lines, $coordinates = 0) converts an ascii-array into a 2D-array
         INPUT: array of lines of an ASCII file
                  - each line contains coordinates, many (repeated) delimiters supported
                  - supports comment lines (starting with a non-numeric character) 
                  - supports inline comments (following the coordinates)
                coordinates: expect number of coordinates (default = 0 = try to find it ourselves)
         OUTPUT: array of coordinate arrays (X, Y, Z...)
      
      writeDygraph($2D) converts a 2D-arry into a Dygraph-formatted string
         INPUT: 2D-array of (X,Y) couples
         OUPUT: a string in the native format of Dygraphs
            
      INTERNAL FUNCTIONS:
        getSpectrumValues($spectrum)
            $spectrum is a (part of) delimited spectral data (line or complete spectrum)
            returns an array of values
        compareDecimals($val1, $val2)
            returns true if equal
        nod($number)
            $number = string representing a floatval
            returns the number of decimals
  */
  
  
  require_once('./inc/common_basic.inc.php');      //error handling!
  
  
  /* ***************
       readAscii()
     *************** */
  
  function readAscii($lines, $coordinates = 0)
  {
    $array = array();
    
    // we will decompose the file line by line in order to cope with comments (entire lines and following the data)
    foreach ($lines as $i => $line)
    {
      if (is_numeric(substr($line, 0, 1)))            // don't include comments (only lines beginning with a number are considered)
      {
        $temp = getSpectrumValues($line);             // decompose line into an array of values
        foreach ($temp as $j => $val)                 // iterate through this array
        {
          if (!is_numeric($val)) break;               // stop on the first non-numeric
          if (($coordinates > 0) and ($j + 1 > $coordinates)) break;  // stop on number of coordinates
          $array[$i][$j] = $val;
        }
        
        if (($coordinates > 0) and (count($array[$i]) != $coordinates))
          return eventLog("WARNING", "inconsistent data; expected " . $coordinates . " coordinates  [convert_basic.readAscii]", False);
        if ($coordinates = 0)
        {
          if (!isset($n)) $n = count($array[$i]);
          if ($n != count($array[$i]))
            return eventLog("WARNING", "inconsistent data; varying number of coordinates  [convert_basic.readAscii]", False);
        }
        //if (count($temp) >= $coordinates)
        //  $array[$i] = array_slice($temp, 0, $coordinates); // add the number of coordinates, anything else is considered comments
      }
    }
    
    if (count($array) == 0) 
      return eventLog("WARNING", "no spectral data found [convert_basic.readAscii]", False);
    return $array;
  }
  


  /* ************************
       writeDygraph($array)
     ************************ */
  
  function writeDygraph($array)
  {
    $dygraph = "[";
    
    foreach ($array as $coordinates) 
      $dygraph = $dygraph."[".$coordinates[0].",".$coordinates[1]."],";

    return rtrim($dygraph,",")."]";
  }
 
 
 
  /* **********************************************
       the following functions should be internal
     ********************************************** */

  function getSpectrumValues($spectrum)       //$spectrum is a (part of) delimited spectral data (line or complete spectrum); returns an array of values
  {
    $delimiters = array(",", ";", ":", "|");  // non-whitespace delimiters; lets hope "," was not used as decimal separator!
    
    $spectrum = str_replace($delimiters, " ", $spectrum); // replace non-whitespece delimiters by spaces
    $spectrum = preg_replace('/\s+/', ' ',$spectrum);     // replace (single or multiple) whitespaces (space, tab, newline) with a single space
    $spectrum = str_replace("\"", "", $spectrum);         // some implementations put "" around the fields, which seems not necessary for our stuff

    return explode(" ", $spectrum);           // spilt line into an array, which we return
  }  

 
  function compareDecimals($val1, $val2)
  {
    //return true if equal
    $val1=floatval($val1);    // if they are strings, convert them to floats
    $val2=floatval($val2);    // --> we don't want to compare E-formatted strings (1.0E-3) 
    
    $precision1 = strlen(substr(strrchr($val1, "."), 1));     // # numbers after the decimal
    $precision2 = strlen(substr(strrchr($val2, "."), 1));
    
    if ($precision1 > $precision2) $precision1 = $precision2; // use the smallest precision
    
    // the diff should be smaller than 10^(-precision); eg abs(0.33 - 0.35) = 0.02 > 10E-2 = 0.01
    return (abs($val1 - $val2) <= pow(10, -$precision1));
  }
  
   
  function nod($number)  
  {
    // reads a value as a string
    // outputs the number of decimals (integer)
    return strlen(substr(strrchr($number, "."), 1));
  } 
?>