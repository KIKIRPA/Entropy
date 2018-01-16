<?php

namespace Convert\Import;

class Ascii
{
    public $data = array();
    public $meta = array();
    public $error = false;
    
    /**
     * __construct($file, $options = array())
     * 
     * Converts an ascii-array into a 2D-array
     * - each line contains coordinates, many (repeated) delimiters supported
     * - supports comment lines (starting with a non-numeric character)
     * - supports inline comments (following the coordinates)
     * - supports only data extraction, no metadata extraction
     * - supported parameters: none
     * 
     * @param string $file Or a filename (including path), or an array of lines. 
     * @param array $options Specific parameters for this convertor
     */
    function __construct($file, $options = array())
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

        // sort data
        if (!orderData($this->data)) eventLog("WARNING", "ASCII import: could not sort the data: " . $file);
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