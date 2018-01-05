<?php
// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

function loadClass($className) 
{
    $fileName = '';
    $namespace = '';
    
    if (false !== ($lastNsPos = strripos($className, '\\'))) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) ;
    $fullFileName = PRIVPATH . strtolower($fileName) . '.php';  // always use lowercase path, even if namespaces and classes start with capitals

    if (file_exists($fullFileName)) {
        require $fullFileName;
    } else {
        eventLog("ERROR", "Class '" . $fileName . "' does not exist [autoloader]", true, true);
    }
}

spl_autoload_register('loadClass'); // Registers the autoloader