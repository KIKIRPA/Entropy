<?php

  // prevent direct access to this file (thus only when included)
  if (count(get_included_files()) == 1) 
  {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
  }
  

  /*  IMPORT FILTERS FUNCTIONS
      
      
      importfilter($filepath, $datatype = null) open a measurement data file, 
                find out what format it is and converts it into json data using an internal
                or external convertors. In the future a datatype can be used to help using the
                right convertor (XY, TXY, imaging...)
                
      importfilter_anno($filepath, $datatype = null) open an annotation file, and
                convert it into json, as defined by (datatype=)dygraphs
      
      readAscii($lines, $coordinates = 0) converts an ascii-array into a 2D-array
         INPUT: array of lines of an ASCII file
                  - each line contains coordinates, many (repeated) delimiters supported
                  - supports comment lines (starting with a non-numeric character) 
                  - supports inline comments (following the coordinates)
                coordinates: expect number of coordinates (default = 0 = try to find it ourselves)
         OUTPUT: array of coordinate arrays (X, Y, Z...)
      
      
      readJcamp($jcamp) converts an jcamp-array (spectral data) into a 2D-array
          INPUT: reads array of lines of a JCAMP file (only spectral data)
                  - supports ##XYDATA with (X++(Y..Y)) (IRUG) or (XY..XY) (MaSC) formats
                  - data compression (DIP, DUP...) is not supported!
                 batch: optional; boolean: don't die on fatal errors but return false (default = false)
          OUTPUT: 2D-array of (X,Y) couples
          NOTE/TODO only specific formats are supported
            !!!!!   maybe better to do this externally? 
            !!!!!   github.com/nzhagen/jcamp , github.com/fracpete/jcamp-dx ...
          
            
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
  
  
  function importfilter($filepath, $datatype = null)
  {
    # NOTE TODO
    # placeholder for a more complete function
    # at the moment it just redirects to the builtin ascii and jcamp-dx convertors
    # based on the file extension; $datatype is not used
    
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); 
    
    switch(strtolower(pathinfo($filepath,PATHINFO_EXTENSION)))
    {
      case "dx":
      case "jdx":
        return readJcamp($lines);
        break;
      case "txt":
      case "csv":
      case "asc":
      default:
        return readAscii($lines);    
    }    
  }
  
  
  function importfilter_anno($filepath, $datatype = null)
  {
    // annotations should be supplied in the form of ascii files with per line an "x" value,
    // shortText and text (cf. documentation dygraphs) separated with whitespaces
    // --> safe in json format: array of array('x'=>, 'shortText'=>, 'text'=>)
    // $datatype is not used at the moment; only X
    
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); 
    $j = 0;
    
    foreach ($lines as $i => $line)
    {
      if (is_numeric(substr($line, 0, 1)))            // don't include comments (only lines beginning with a number are considered)
      {
        $line = preg_replace('/\s+/', ' ', $line);
        $line = explode(" ", $line, 3);
        if ((count($line) >= 2) and is_numeric($line[0]))
        {
          $json[$j]["x"] = $line[0];
          $json[$j]["shortText"] = $line[1];
          if (count($line) == 3) $json[$j]["text"] = $line[2];
          $j++;
        }
      }
    }
    
    if (count($json) == 0) 
      return eventLog("WARNING", "no spectral data found [common_importfilters.importfilter_anno]", False);
    return $json;
  }
  
  
  
  /* ***************
       readAscii()
     *************** */
  
  function readAscii($lines, $coordinates = 0)
  {
    $array = array();
    $i = 0;
    
    // we will decompose the file line by line in order to cope with comments (entire lines and following the data)
    foreach ($lines as $line)
    {
      if (is_numeric(substr($line, 0, 1)))            // don't include comments (only lines beginning with a number are considered)
      {
        $temp = getSpectrumValues($line);             // decompose line into an array of values
        foreach ($temp as $j => $val)                 // iterate through this array
        {
          if (!is_numeric($val)) break;               // stop on the first non-numeric
          if (($coordinates > 0) and ($j + 1 > $coordinates)) break;  // stop on number of coordinates
          $array[$i][$j] = floatval($val);
        }
        
        $i++;
        
        if (($coordinates > 0) and (count($array[$i]) != $coordinates))
          return eventLog("WARNING", "inconsistent data; expected " . $coordinates . " coordinates  [common_importfilters.readAscii]", False);
        if ($coordinates = 0)
        {
          if (!isset($n)) $n = count($array[$i]);
          if ($n != count($array[$i]))
            return eventLog("WARNING", "inconsistent data; varying number of coordinates  [common_importfilters.readAscii]", False);
        }
      }
    }
    
    if (count($array) == 0) 
      return eventLog("WARNING", "no spectral data found [common_importfilters.readAscii]", False);
    return $array;
  }

  
  /* ***************
       readJcamp()
     *************** */

  function readJcamp($lines)
  {    
    $array = array();

    $parameters = array();
    $paramList = array("##DELTAX", "##XFACTOR", "##YFACTOR", "##MINY", "##MAXY", "##FIRSTX", "##LASTX", "##NPOINTS", "##FIRSTY", "##XYDATA");
    $dataFormats = array("(X++(Y..Y))", "(XY..XY)");
    
    // PARAMETERS
    while (!isset($parameters["##XYDATA"]))
    {
      $line = array_shift($lines);
      switch (substr($line, 0, 2))
      {
        case "##":
        case "$$":
          if (trim($line) == "##END=") 
            return eventLog("WARNING", "unsupported JCAMP-DX file: no XYDATA [convert_readjcamp.readJcamp]", False);
          
          list($parameter, $value) = explode("=", $line, 2);
          $parameter = strtoupper(str_replace(array(" ", "/", "-", "_"), "", $parameter)); //remove spaces and such and uppercase it (allowed by JCAMP specs)
          if (in_array($parameter, $paramList)) $parameters[$parameter] = trim($value);
          break;
        default:
          // $parameter from the previous line (with ## or $$) is still valid!
          if (in_array($parameter, $paramList)) $parameters[$parameter] = $parameters[$parameter]." ".trim($value);
          break;
      }
    }
    
    if (!isset($parameters["##XFACTOR"])) $parameters["##XFACTOR"] = 1;
    if (!isset($parameters["##YFACTOR"])) $parameters["##YFACTOR"] = 1;
      
    // SPECTRUM
    switch ($parameters["##XYDATA"])
    {
      case "(X++(Y..Y))":
        // Required parameters:
        if (!(isset($parameters["##LASTX"]) OR isset($parameters["##FIRSTX"]) OR isset($parameters["##NPOINTS"]))) 
          return eventLog("WARNING", "missing spectra parameters in the JCAMP-DX file [convert_readjcamp.readJcamp]", False);
        $deltax = (floatval($parameters["##LASTX"]) - floatval($parameters["##FIRSTX"])) / (intval($parameters["##NPOINTS"]) - 1);
        $firstx = $parameters["##FIRSTX"];
        
        // check deltax:
        if (!isset($parameters["##DELTAX"]) AND !compareDecimals($parameters["##DELTAX"], $deltax)) 
          return eventLog("WARNING", "faulty information in JCAMP-DX file: deltax [convert_readjcamp.readJcamp]", False);
        
        while (count($lines) > 0)  //read spectrum
        {
          $line = array_shift($lines); 
          if (strtoupper(str_replace(array(" ", "/", "-", "_"), "", $line)) == "##END=") break;
          
          $values = getSpectrumValues($line);             // decompose line into an array of values
          
          $x =  array_shift($values) * $parameters["##XFACTOR"];
          if (!compareDecimals($x, $firstx)) 
            return eventLog("WARNING", "faulty information in JCAMP-DX file: firstx (".$x." <-> ".$firstx.") [convert_readjcamp.readJcamp]", False);
            
          foreach ($values as $y)
          {
            $y = $y * $parameters["##YFACTOR"];
            array_push($array, array($x, $y));
            $x = $x + $deltax;                          
            $firstx = $firstx + $deltax;
          }
        }
        break;
        
      case "(XY)":
      case "(XY..XY)":
      case "(X,Y)":           // JCAMP-DX draft 6.0 allows to define the delimiters; 2 examples
      case "(X,Y..X,Y)":      // but neglect spaces in these constructions (these have been removed in a previous step)
        $i = 0;
        while (count($lines) > 0)  //read spectrum
        {
          if (strtoupper(str_replace(array(" ", "/", "-", "_"), "", $line)) == "##END=") break;
          $i++;
        }       
        array_splice($lines, $i + 1);
  
        $temp = getSpectrumValues(implode("\n", $lines)); //recombine the array of lines and make the values-array of the whole spectrum 
        
        while (count($temp) > 0)  //read spectrum
        {
          $x = array_shift($temp) * $parameters["##XFACTOR"];
          $y = array_shift($temp) * $parameters["##YFACTOR"];
          
          array_push($array, array($x, $y));          
        }
        break;
        
      default:
        return eventLog("WARNING", "unsupported JCAMP-DX file (XYDATA format) [convert_readjcamp.readJcamp]", False);
        break;
    }
    
    // final sanity checks
    $sanityCheck = array();
    if (isset($parameters["##FIRSTX"])) if (!compareDecimals($array[0][0], $parameters["##FIRSTX"])) array_push($sanityCheck, "firstx");
    if (isset($parameters["##FIRSTY"])) if (!compareDecimals($array[0][1], $parameters["##FIRSTY"])) array_push($sanityCheck, "firsty");
    if (isset($parameters["##LASTX"])) if (!compareDecimals($array[count($array) - 1][0], $parameters["##LASTX"])) array_push($sanityCheck, "lastx");
    if (isset($parameters["##NPOINTS"])) if ((count($array) != $parameters["##NPOINTS"]) OR (count($array) ==0)) array_push($sanityCheck, "npoints");
    // we could also check on ##MINY and ##MAXY, but atm I'm totally fed up with this function ;-)
    if (count($sanityCheck) > 0)  
      eventLog("WARNING", "faulty information in JCAMP-DX file: ".implode(", ", $sanityCheck)." [convert_readjcamp.readJcamp]", false);
    
    // ROUND DATA
    // do this after all calculations
    if (isset($parameters["##FIRSTX"])) $nod_x = nod($parameters["##FIRSTX"]);
    elseif (isset($parameters["##LASTX"])) $nod_x = nod($parameters["##LASTX"]);
    else 
    {
      $nod_x = 3;
      eventLog("WARNING", "could not round the X-values [convert_readjcamp.readJcamp]", false);
    }
   
    if (isset($parameters["##FIRSTY"])) $nod_y = nod($parameters["##FIRSTY"]);
    else 
    {
      $nod_y = 3;
      eventLog("WARNING", "could not round the Y-values [convert_readjcamp.readJcamp]", false);
    }
    
    foreach ($array as &$couple)        //&$couple is a reference; allows manipulating the array elements directly
    {
      if (isset($nod_x)) $couple[0] = round($couple[0], $nod_x); //round x
      if (isset($nod_y)) $couple[1] = round($couple[1], $nod_y); //round y
    }
    
    unset($couple);  //unset reference    
    return $array;
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
  
   
  function nod($number)  // NOTE used in readJcamp
  {
    // reads a value as a string
    // outputs the number of decimals (integer)
    return strlen(substr(strrchr($number, "."), 1));
  } 
?>
