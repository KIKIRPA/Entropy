<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
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

