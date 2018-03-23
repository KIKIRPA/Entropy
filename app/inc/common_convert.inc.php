<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}


/**
 * fillTemplateWithMeta($filepath, $meta)
 */
function fillTemplateWithMeta($filepath, $meta, $otherCodes = array(), $preserveEmptyLines = false)
{
    $array = array();

    if (file_exists($filepath)) {
        $handle = fopen($filepath, "r");
        if ($handle) {
            // read the template file line by line
            while (($line = fgets($handle)) !== false) {
                // remove newlines first
                $line = str_replace(array("\r\n", "\n", "\r"), "", $line);
                // find {codes} and replace them
                $codes = findCodes($line);
                foreach ($codes as $code) {
                    $search = "{" . $code . "}";
                    // search this code in $otherCodes first, then try to find it in the metadata ($meta)
                    if (isset($otherCodes[strtolower($code)])) {
                        $replace = $otherCodes[strtolower($code)];
                    } else {
                        $replace = getMeta($meta, $code, "; ", false);
                    }
                    $line = str_replace($search, $replace, $line);
                }          
                // only save lines that contain more than whitespace                
                if ($preserveEmptyLines || (trim($line) !== "")) {
                    $array[] = $line;
                }
            }
            fclose($handle);
        } else {
            eventLog("WARNING", "Template file could not be read: " . $filepath . " [fillTemplateWithMeta()]");
            return false;
        } 

    } else {
        eventLog("WARNING", "Template file not found: " . $filepath . " [fillTemplateWithMeta()]");
        return false;
    }

    return $array;
}


function findCodes($string, $openTag = "{", $closeTag = "}")
{
    $close = 0;
    $codes = array();

    // loop over the string and find all openTags
    do {
        $open = stripos($string, $openTag, $close);
        if ($open !== false)
        {
            // if an opentag is found at position 'open', find a closeTag starting from that position
            $close = stripos($string, $closeTag, $open);
            if ($close !== false) {
                // if closetag is found, add the contents between openTag and closeTag to codes array
                $codes[] = substr($string, $open + 1, $close - $open - 1);
            } else {
                // if closeTag is not found, break loop
                break;
            }
        }
    } while ($open !== false); // if an openTag was found, try to find another; if not, stop loop

    return $codes;
}

/**
 * getSpectrumValues($spectrum)
 * $spectrum is a (part of) delimited spectral data (line or complete spectrum)
 * returns an array of values
 */
function getSpectrumValues($spectrum) 
{
    // non-whitespace delimiters; lets hope "," was not used as decimal separator!
    $delimiters = array(",", ";", ":", "|");

    // replace non-whitespece delimiters by spaces
    $spectrum = str_replace($delimiters, " ", $spectrum);
    // replace (single or multiple) whitespaces (space, tab, newline) with a single space
    $spectrum = preg_replace('/\s+/', ' ', $spectrum);
    // some implementations put "" around the fields, which seems not necessary for our stuff
    $spectrum = str_replace("\"", "", $spectrum);

    // spilt line into an array, which we return
    return explode(" ", $spectrum);
}



/**
 * countDecimals($number)
 * $number = string representing a floatval
 * returns the number of decimals
 */
function countDecimals($number)
{
    return strlen(substr(strrchr($number, "."), 1));
}


/**
 * getMaxDecimals($data)
 * $data = array of (x, y) arrays
 */
function getMaxDecimals($data)
{
    // retrieve the maximum number of decimals of x and y
    $xDecimals = countDecimals($data[0][0]);
    $yDecimals = countDecimals($data[0][1]);
    foreach($data as $couple) {
        $d = countDecimals($couple[0]); 
        if ($d >= $xDecimals) $xDecimals = $d;
        $d = countDecimals($couple[1]); 
        if ($d >= $yDecimals) $yDecimals = $d;
    }

    return array($xDecimals, $yDecimals);
}


/**
 * makeEvenSpaced($data)
 * $data = array of (x, y) arrays
 * 
 * Multiple approaches are possible to make a data series even spaced, with advantages and disadvantages for each. 
 * 
 * This implementation preserves the total number of points, which determines the fixed step between subsequent x-values.
 * Corresponding y-values are calculated by interpolation between the origal surrounding points.
 * This is a good solution in case the variable step in the original data is not too big and peaks are not too sharp;
 * if not, peak intensities can be drastically reduced.
 * 
 * (Alternative implementations, taking the minimum step as a reference could drastically increase the total number of points)
 */
function makeEvenSpaced($data)
{
    $newData = array();
    $n = count($data);
    // dermine the new step between subsequent x values
    $xStep  = ($data[$n-1][0] - $data[0][0]) / ($n - 1);

    // make sure the data is sorted ascending
    // NOTE: during importing the data, data should be sorted (either ascending or descending); now we can just reverse if necessary
    $reversed = ($xStep < 0) ? true: false;
    if ($reversed) $data = array_reverse($data);

    // first couple of the new data ==  first couple of the old dat
    $newData[] = $data[0];

    // next couples: linear interpolation
    $xNew = $data[0][0];
    for ($i = 1; $i < $n - 1; $i++) {
        // next x value
        $xNew += $xStep; 
        
        // find surrounding x values: search the x-value just above xNew
        for ($j = 1; $j <= $n - 1; $j++) {
            if ($xNew >= $data[$j][0]) {
                $d = ($xNew - $data[$j - 1][0]) / ($data[$j][0] - $data[$j - 1][0]);
                $yNew = $data[$j - 1][1] * (1 - $d) + $data[$j][1] * $d;
                $newData[] = array($xNew, $yNew);
                break;
            }
        }
    }

    // last couple of the new data ==  first couple of the old dat
    $newData[] = $data[$n-1];

    // if we reversed the dataset, reverse the new dataset
    if ($reversed) $newData = array_reverse($newData);

    // test
    if (count($newData) != $n) {
        eventLog("WARNING", "Not the same number of data points after even spacing!", false);
    }

    return $newData;
}


/**
 * checkEvenSpaced($data)
 * 
 * Simple check if the first delta_x is equal to the last delta_x
 */
function checkEvenSpaced($data)
{
    $n = count($data);
    $delta1 = round($data[1][0] - $data[0][0], countDecimals($data[0][0]));
    $delta2 = round($data[$n - 1][0] - $data[$n - 2][0], countDecimals($data[$n - 1][0]));
    
    return compareDecimals($delta1, $delta2);
}


/**
 * compareDecimals($val1, $val2)
 * 
 * returns true if equal
 */
function compareDecimals($val1, $val2)
{
    //return true if equal
    $val1 = floatval($val1);    // if they are strings, convert them to floats
    $val2 = floatval($val2);    // --> we don't want to compare E-formatted strings (1.0E-3)
    
    $precision1 = strlen(substr(strrchr($val1, "."), 1));     // # numbers after the decimal
    $precision2 = strlen(substr(strrchr($val2, "."), 1));
    
    if ($precision1 > $precision2) {
        $precision1 = $precision2;
    } // use the smallest precision
    
    // the diff should be smaller than 10^(-precision); eg abs(0.33 - 0.35) = 0.02 > 10E-2 = 0.01
    return (abs($val1 - $val2) <= pow(10, -$precision1));
}