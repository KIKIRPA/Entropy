<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}
  

// load import php classes
foreach ($IMPORT as $class => $temp) {
    include_once(PRIVPATH . 'import/' . $class . '/class.php');
}


/**
 * checkUpload($uploadArr, $updir, $filename = null)
 * 
 * sanity checks for uploaded files and copy the file to $updir, and optionally renames it into $filename.
 * OUT: False if ok, errormsg if not    
 */

function checkUpload($upload, $updir, $filename = null)
{
    if ($filename === null) {
        $filename = $_FILES[$upload]["name"];
    }
  
    // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
    if (!isset($_FILES[$upload]['error']) || is_array($_FILES[$upload]['error'])) {
        return 'Invalid parameters.';
    }

    // Check $_FILES[$upload]['error'] value.
    switch ($_FILES[$upload]['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return 'No file sent.';
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Exceeded filesize limit [html limit].';
        default:
            return 'Unknown errors.';
    }

    // You should also check filesize here.
    if ($_FILES[$upload]['size'] > 2000000) {
        return 'Exceeded filesize limit [php limit].';
    }
      
    // move file to uploads
    if (!mkdir2($updir)) {
        return 'Could not create upload directory.';
    }
    if (!move_uploaded_file($_FILES[$upload]["tmp_name"], $updir . $filename)) {
        return 'Could not save ' . $filename . ' in the upload directory.';
    }
    
    return false;
}

/** 
 * checkMultiUpload($uploadArr, $pathPrefix)
 * 
 * version of checkUpload() for multiple uploads. $pathPrefix can be the path where to save
 * the files including trailing /, but can also include a prefix for the filename (e.g: ./lib/sop/upload/sampleid__)
 * OUT: False if ok, errormsg if not
 */
function checkMultiUpload($upload, $updir, $prefix = "")
{
    // Undefined | Multiple Files | $_FILES Corruption Attack
    // If this request falls under any of them, treat it invalid.
    if (!isset($_FILES[$upload]['error'])) {
        return 'Invalid parameters.';
    }

    // Check $_FILES[$upload]['error'] values.
    foreach ($_FILES[$upload]['error'] as $i => $value) {
        switch ($value) {
      case UPLOAD_ERR_OK:
        break;
      case UPLOAD_ERR_NO_FILE:
        return 'File ' . $i . ': No file sent.';
      case UPLOAD_ERR_INI_SIZE:
      case UPLOAD_ERR_FORM_SIZE:
        return 'File ' . $i . ': Exceeded filesize limit [html limit].';
      default:
        return 'File ' . $i . ': Unknown errors.';
    }
    }

    // You should also check filesize here.
    foreach ($_FILES[$upload]['size'] as $i => $value) {
        if ($value > 2000000) {
            return 'File ' . $i . ': Exceeded filesize limit [php limit].';
        }
    }
      
    // move files to uploads
    if (!mkdir2($updir)) {
        return 'Could not create upload directory.';
    }
  
    foreach ($_FILES[$upload]["tmp_name"] as $i => $value) {
        if (!move_uploaded_file($value, $updir . $prefix . sanitizeStr($_FILES[$upload]["name"][$i]))) {
            return 'Could not save ' . $prefix . sanitizeStr($_FILES[$upload]["name"][$i]) . ' in the upload directory.';
        }
    }
    
    return false;
}

/**
 * selectImportHelper(): select an import helper based on the supplied datatype and extension
 * 
 * It read the import.json configuration file and searches the listed import helpers based on
 * the two criteria: datatype and extension. Searching happens in the order the import helpers
 * are listed in the file, and only the first hit will be reported.
 * 
 * The optional $parameters array contains import helper parameters, as can be supplied in the CSV
 * metadata files ("_import:jcamp-dx:template" --> $parameters = $meta["_import"]), and will be
 * supplemented with parameters defined in the import.json file for the given extension and datatype.
 * If the same parameter with different value is defined in multiple places, than the value defined
 * in the metadata wins over the value in extension, which in turn wins over the value in datatype.
 * 
 * If a filter is found, this function returns an associative array; the first item (with key "helper")
 * contains the name of the helper, which can be used to include the helper php file. Next items
 * are the parameters for this helper (eg $parameters["template"] = "Raman785.dxt").
 * If no filter is found, an empty array is returned.
 */
function selectImportHelper($import, $datatype, $extension, $parameters = array())
{
    // cleanup parameters
    $datatype = trim(strtolower($datatype));
    $extension = trim(trim(strtolower($extension), "."));

    // search the import helpers until we find a helper that fits the requirements (datatype and extension)
    foreach ($import as $key => $requirements) {
        $condition = true;
        if (isset($requirements["datatypes"])) {
            $condition = ($condition and isset($requirements["datatypes"][$datatype]));
        }
        if (isset($requirements["extensions"])) {
            $condition = ($condition and isset($requirements["extensions"][$extension]));
        }
        if ($condition) {
            $helper = $key;
            break;
        }
    }
    
    // evaluate helper parameters. these can be supplied for specific datatypes, specific file extensions or supplied in the uploaded metadata
    // return array with first key "helper", followed by the parameters for this helper
    if (isset($helper)) {
        $parameters = array_change_key_case($parameters, CASE_LOWER);
        if (isset($parameters[$helper])) {
            // only keep the parameters for this helper
            $parameters = $parameters[$helper];
        }
        else {
            $parameters = array();
        }
        // add parameters from the extension (if they are not already set by the metadata)
        if (isset($import[$helper]["extensions"][$extension])) {
            $parameters = array_merge($import[$helper]["extensions"][$extension], $parameters);
        }
        // add parameters from the datatype (if they are not already set by the metadata or extension)
        if (isset($import[$helper]["datatypes"][$datatype])) {
            $parameters = array_merge($import[$helper]["datatypes"][$datatype], $parameters);
        }
         return array("helper" => $helper) + $parameters; 
    }
    else {
        // if no suitable helper found: return false
        return false;
    }
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
