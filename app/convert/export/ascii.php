<?php

namespace Convert\Export;

class Ascii
{
    public $id;
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
     * __construct($id, $data, $meta = array(), $options = array(), $datatypes = array())
     * 
     * Reads data and metadata for conversion
     * - supported parameters:
     *     - wrapsymbol (default: "")           wrap data in symbols (e.g. " in CSV)
     *     - intraseparator (default: "\t")     separator between coordinates
     *     - interseparator (default: "\r\n")   separator between coordinate couples
     *     - commentsymbol (default: "# ")      symbols indicating a comment line
     *     - templatefile (default: null)       use template file for metadata
     * 
     * $datatypes is not used in Convert\Export\Ascii, but is required for compatibility
     * 
     * @param array $data data array
     * @param array $meta metadata array
     * @param array $options Specific parameters for this convertor
     */
    function __construct($id, $data, $meta = array(), $options = array(), $datatypes = array())
    {
        $this->id = $id;

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

        if (is_array($options)) {
            $options = array_change_key_case($options, CASE_LOWER);
            if (isset($options["wrapsymbol"])) {
                $this->wrapSymbol = $options["wrapsymbol"];
            }
            if (isset($options["intraseparator"])) {
                $this->intraSeparator = $options["intraseparator"];
            }
            if (isset($options["interseparator"])) {
                $this->interSeparator = $options["interseparator"];
            }
            if (isset($options["commentsymbol"])) {
                $this->commentSymbol = $options["commentsymbol"];
            }
            if (isset($options["templatefile"])) {
                // locate template
                if (file_exists(TEMPLATES_PATH . $options["templatefile"])) {
                    $this->templateFile = $options["templatefile"];
                } else {
                    $this->error = eventLog("WARNING", "ASCII template could not be not found: " . $options["templatefile"]);
                }
            }
        } else {
            $this->error = eventLog("WARNING", "Metadata is not in the correct format");
        }
    }

    public function getFile($getMeta = true)
    {
        try {
            // don't even start if the __construct gave an error
            if ($this->error) {
                throw new \Exception("Failed to export to ASCII because of incorrect data");
            }

            //open a temporary file
            $handle = tmpfile();
            
            // build metadata
            if ($getMeta) {
                if ($this->templateFile != null) {
                    fwrite($handle, $this->_metaTemplate($this->templateFile));
                } else {
                    fwrite($handle, $this->_meta());
                }
                fwrite($handle, "\r\n");
            }
            
            // stop if the metadata export went wrong
            if ($this->error) {
                throw new \Exception("Failed to export to ASCII because of error in generating the metadata");
            }

            //build data
            fwrite($handle, $this->_data());
        } catch (\Exception $e) {
            echo "CATCH<br>";
            $this->error = eventLog("WARNING", $e->getMessage());
            if (isset($handle))             
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
            $array[] = $this->wrapSymbol . $pair[0] . $this->wrapSymbol . $this->intraSeparator . $this->wrapSymbol . $pair[1] . $this->wrapSymbol;
        }
        
        return implode($this->interSeparator, $array);
    }

    private function _meta()
    {
        $flat = flattenArray($this->meta, false, 0);
        $array = array();
        
        foreach ($flat as $key => $value) {
            $array[] = $this->commentSymbol . $key . " = " . $value;
        }

        return implode("\r\n", $array);
    }

    private function _metaTemplate()
    {
        $specificCodes = array(
            "_id" => $this->id,
        );
        
        $lines = fillTemplateWithMeta(TEMPLATES_PATH . $this->templateFile, $this->meta, $specificCodes);

        if (is_array($lines)) {
            return implode("", $lines);
        } else {
            $this->error = eventLog("WARNING", "Failed to export to ASCII because of error in reading the metadata template file");
            return "";
        }
    }


    public function getError()
    {
        return $this->error;
    }
}