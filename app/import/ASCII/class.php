<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

class ImportASCII
{
    public $data = array();
    public $meta = array();
    public $error = false;
    
    /**
     * __construct($file, $parameters = array())
     * 
     * Converts an ascii-array into a 2D-array
     * - each line contains coordinates, many (repeated) delimiters supported
     * - supports comment lines (starting with a non-numeric character)
     * - supports inline comments (following the coordinates)
     * - supports only data extraction, no metadata extraction
     * - supported parameters: none
     * 
     * @param string $file Or a filename (including path), or an array of lines. 
     * @param array $parameters Specific parameters for this helper
     */
    function __construct($file, $parameters = array())
    {
        // if $file is a filename, open it as handle and read line by line
        // if $file is an array, foreach through it
        if (!is_array($file)) {
            $handle = fopen($file, "r");
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    $couple = $this->readAsciiLine($line);
                    if (is_array($couple)) {
                        // add couple to the data-array
                        $this->data[] = $couple;
                    }
                }
                fclose($handle);
            } else {
                // error opening the file
                $this->error = eventLog("WARNING", "ASCII import: could not open the source file: " . $file);
            }
        } else {
            foreach ($file as $line) {
                $couple = $this->readAsciiLine($line);
                if (is_array($couple)) {
                    // add couple to the data-array
                    $this->data[] = $couple;
                }
            }
        }
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function getError()
    {
        return $this->error;
    }

    private function readAsciiLine($line)
    {
        if (is_numeric(substr($line, 0, 1))) {            // don't include comments (only lines beginning with a number are considered)
            $line = getSpectrumValues($line);             // decompose line into an array of values
            if (count($line) >= 2) {
                if (is_numeric($line[0]) and is_numeric($line[1])) {
                    $line[0] = floatval($line[0]);
                    $line[1] = floatval($line[1]);
                    return array_slice($line, 0, 2);
                }
            } // inconsisten data: return false but and log warning
            $this->error = eventLog("WARNING", "Inconsistent ASCII data.");
            return false;
        } // comment line: return false but no warning
        return false;
    }

}