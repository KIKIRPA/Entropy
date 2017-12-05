<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

class ExportASCII
{
    public $data = array();
    public $meta = array();
    public $error = false;  // last error msg

    // parameters
    public $wrapSymbol = "";
    public $intraSeparator = "\t";
    public $interSeparator = "\r\n";
    public $commentSymbol = "# ";
    public $templateFile = null;

    
    /**
     * __construct($data, $meta = array(), $parameters = array())
     * 
     * Reads data and metadata for conversion
     * - supported parameters:
     *     - wrapsymbol (default: "")           wrap data in symbols (e.g. " in CSV)
     *     - intraseparator (default: "\t")     separator between coordinates
     *     - interseparator (default: "\r\n")   separator between coordinate couples
     *     - commentsymbol (default: "# ")      symbols indicating a comment line
     *     - templatefile (default: null)       use template file for metadata
     * 
     * @param array $data data array
     * @param array $meta metadata array
     * @param array $parameters Specific parameters for this convertor
     */
    function __construct($data, $meta = array(), $parameters = array())
    {
        if (is_array($data) and !empty($data)) {
            $this->data = $data;
        } else {
            $this->error = eventLog("WARNING", "No data or not in the correct format");
        }

        if (is_array($meta)) {
            $this->meta = $meta;
        } else {
            $this->error = eventLog("WARNING", "Metadata is not in the correct format");
        }

        if (is_array($parameters)) {
            $parameters = array_change_key_case($parameters, CASE_LOWER);
            if (isset($parameters["wrapsymbol"])) {
                $this->wrapSymbol = $parameters["wrapsymbol"];
            }
            if (isset($parameters["intraseparator"])) {
                $this->intraSeparator = $parameters["intraseparator"];
            }
            if (isset($parameters["interseparator"])) {
                $this->interSeparator = $parameters["interseparator"];
            }
            if (isset($parameters["commentsymbol"])) {
                $this->commentSymbol = $parameters["commentsymbol"];
            }
            if (isset($parameters["templatefile"])) {
                $this->templateFile = $parameters["templatefile"];
            }

        } else {
            $this->error = eventLog("WARNING", "Metadata is not in the correct format");
        }
    }

    public function getFile($getMeta = true) {
        try {
            //open a temporary file
            $handle = tmpfile();

            // don't even start if the __construct gave an error
            if ($this->error) {
                throw new Exception("Failed to export to ASCII because of incorrect data");
            }

            // build metadata
            if ($getMeta) {
                if ($this->templateFile != null) {
                    fwrite($handle, _metaTemplate($this->templateFile));
                } else {
                    fwrite($handle, _meta());
                }
                fwrite($handle, "\r\n");
            }

            // stop if the metadata export went wrong
            if ($this->error) {
                throw new Exception("Failed to export to ASCII because of error in generating the metadata");
            }

            //build data
            fwrite($handle, _data());
        } catch (Exception $e) {
            $this->error = eventLog("WARNING", $e->getMessage());
            fclose($handle); // this removes the file
            return false;
        }
        
        // put the pointer back to zero and return the handle
        fseek($handle, 0);
        return $handle;
    }

    private function _data()
    {
        $array = array();
        foreach ($this->data as $pair) {
            $array[] = $this->wrapSymbol . $pair[0] . $this->$wrapSymbol . $this->intraSeparator . $this->wrapSymbol . $pair[1] . $this->wrapSymbol;
        }
        
        return implode($this->interSeparator, $array);
    }

    private function _meta()
    {
        $flat = flattenArray($this->meta, false, 0);
        $array = array();
        
        foreach ($flat as $key => $value) {
            $array[] = $commentSymbol . $key . " = " . $value . "\r\n";
        }

        return $array;
    }

    private function _metaTemplate($filename)
    {
        $array = array();

        if (file_exists(TEMPLATES_PATH . $filename)) {
            $handle = fopen(TEMPLATES_PATH . $filename, "r");
            if ($handle) {
                // read the template file line by line
                while (($line = fgets($handle)) !== false) {
                    // find {codes} and replace them
                    $codes = _findCodesInTemplate($line);
                    foreach ($codes as $code) {
                        $search = "{" . $code . "}";
                        $replace = getMeta($this->meta, $code);
                        $line = str_replace($search, $replace, $line);
                    }          
                    // only save lines that contain more than whitespace
                    if (trim($line) != "") {
                        $array[] = $line;
                    }
                }
                fclose($handle);
            } else {
                $this->error = eventLog("WARNING", "ASCII export template could not be read: " . $filename);
            } 

        } else {
            $this->error =  eventLog("WARNING", "ASCII export template not found: " . $filename);
        }

        return $array;
    }

    private function _findCodesInTemplate($string, $openTag = "{", $closeTag = "}")
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

    public function getError()
    {
        return $this->error;
    }
}