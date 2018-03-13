<?php

namespace Convert\Export;

class Jcampdx
{
    public $id;
    public $data = array();
    public $meta = array();
    public $error = false;  // last error msg

    public $datatypes;      // TODO: remove this dependency by using a static datatypes class, and accessing it directly!!!

    // parameters
    public $dataEncoding = "(X++(Y..Y))";
    public $templateFile = "default.dx";
    public $wordWrap = 80;
    public $softwareString = "ENTROPY 20180116";
    public $normalizeX = 0;
    public $normalizeY = 32767;
    public $forceEqualSpacing = false;

    // internal
    private $xFactor = 1;
    private $yFactor = 1;
    private $xDecimals = 3;
    private $yDecimals = 3;


    /**
     * __construct($id, $data, $meta = array(), $options = array(), $datatypes = array())
     * 
     * Reads data and metadata for conversion
     * - supported parameters:
     *     - dataencoding       encoding of the data (supported values: "(X++(Y..Y))" [default], "(XY..XY)")
     *     - templatefile       use an alternative template file for metadata
     *     - wordwrap           max number of characteris in a line [default: 80], 0 disables word wrapping
     *     - softwarestring     software name and version that will be embedded in the generated JCAMP-DX files
     *     - normalizex         normalisation of the X values 
     *                            - 0: keep numbers as is [default]
     *                            - 1: use XFACTOR to have integer x values  (eg. 123.45 --> 12345 * 0.01) 
     *                            - any other number: normalisation between -normalizex and +normalizex
     *                          JCAMP-DX 4.24 specs recommend 32767 (not required, and not observed in other implementations)
     *     - normalizey:        normalisation of the Y values.  [default: 32767]
     *     - forceequalspacing  force equal spacing for (XY..XY) encoding (note: for (X++(Y..Y)) equal spacing is required!)
     *                          O == false, all other values == true
     * 
     * @param array $data data array
     * @param array $meta metadata array
     * @param array $options Specific parameters for this convertor
     */
    function __construct($id, $data, $meta = array(), $options = array(), $datatypes = array())
    {
        $this->id = $id;
        $this->datatypes = $datatypes;

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
            if (isset($options["dataencoding"])) {
                // supported data encoding?
                if (in_array($options["dataencoding"], array("(X++(Y..Y))", "(XY..XY)"))) {
                    $this->dataEncoding = $options["dataencoding"];
                } else {
                    $this->error = eventLog("WARNING", "Unsupported data encoding for JCAMP-DX convertor: " . $options["dataencoding"]);
                }
            }
            if (isset($options["templatefile"])) {
                // locate and read JCAMP-DX template
                if (file_exists(\Core\Config\App::get("templates_path") . $options["templatefile"])) {
                    $this->templateFile = $options["templatefile"];
                } else {
                    $this->error = eventLog("WARNING", "JCAMP-DX template could not be not found: " . $options["templatefile"]);
                }
            }
            if (isset($options["wordwrap"])) {
                // wordwrap must be a positive int
                $i = $options["wordwrap"];
                if (!is_numeric($i) || $i < 0 || $i != round($i)) {
                    $this->error = eventLog("WARNING", "Invalid value for wordwrap: " . $i);
                } else {
                    $this->wordWrap = (int)$i;
                }
            }
            if (isset($options["softwarestring"])) {
                $this->softwareString = $options["softwarestring"];
            }
            if (isset($options["normalizex"])) {
                // normalizex must be a positive int
                $i = $options["normalizex"];
                if (!is_numeric($i) || $i < 0 || $i != round($i)) {
                    $this->error = eventLog("WARNING", "Invalid value for normalizex: " . $i);
                } else {
                    $this->normalizeX = (int)$i;
                }
            }
            if (isset($options["normalizey"])) {
                // normalizey must be a positive int
                $i = $options["normalizey"];
                if (!is_numeric($i) || $i < 0 || $i != round($i)) {
                    $this->error = eventLog("WARNING", "Invalid value for normalizey: " . $i);
                } else {
                    $this->normalizeY = (int)$i;
                }
            }
            if (isset($options["forceequalspacing"])) {
                $this->forceEqualSpacing = ($options["forceequalspacing"] ? true : false);
            }
        } else {
            $this->error = eventLog("WARNING", "Metadata is not in the correct format");
        }   
    }

    public function getFile($getMeta = true) //getmeta is discarded, JCAMP-DX will always include metadata
    {
        try {
            // don't even start if the __construct gave an error
            if ($this->error)
            throw new \Exception("Failed to export to JCAMP-DX because of incorrect data");       
            
            // build metadata from template
            $lines = $this->_metaTemplate();
            
            // stop if the metadata export went wrong
            if ($this->error)
            throw new \Exception("Failed to export to JCAMP-DX because of error in generating the metadata");
            
            // get the max number of decimals for the dataset
            list($xDecimals, $yDecimals) = getMaxDecimals($this->data); 
            
            //recalculate to equal spacing data
            // --> if requested ($forceEqualSpacing === true or $dataEncoding === "(X++(Y..Y))"
            // --> if the data is not yet equally spaced
            if ($this->forceEqualSpacing || $this->dataEncoding === "(X++(Y..Y))") {
                if (!checkEvenSpaced($this->data)) {
                    $this->data = makeEvenSpaced($this->data);
                }
            }
            
            // calculate metadata that depend on the data
            $lines = array_merge($lines, $this->_dataDependentMeta());
            
            // word wrapping on the metadata (the data itself should take care of its lines)
            if ($this->wordWrap > 0) {
                foreach ($lines as &$ref) 
                $ref = wordwrap($ref, $this->wordWrap, "\r\n", true);
            }
            
            // build data series
            $lines = array_merge($lines, $this->_data());

            // and put the end tag
            $lines[] = "##END=";
            
            // open a temporary file
            $handle = tmpfile();
            fwrite($handle, implode("", $lines));

        } catch (\Exception $e) {
            $this->error = eventLog("WARNING", $e->getMessage());
            if (isset($handle))             
                fclose($handle); // this removes the file
            return false;
        }
        
        // put the pointer back to zero and return the handle
        fseek($handle, 0);
        return $handle;
    }

    public function getError()
    {
        return $this->error;
    }

    private function _metaTemplate()
    {
        $specificCodes = array(
            "_id"               => $this->id,
            "_datatype"         => findDataType($this->meta["type"], $this->datatypes, "jcampdx"),
            "_license"          => "",
            "_softwarestring"   => $this->softwareString,
            "_xunits"           => "1/CM",              //TODO: findDataTypeUnits($this->meta["type"], $this->datatypes, "jcampdx", $this->units), // there is no $this->units yet; damn, I need that measurement class!
            "_yunits"           => "RELATIVE INTENSITY" //TODO
        );
        
        $lines = fillTemplateWithMeta(\Core\Config\App::get("templates_path") . $this->templateFile, $this->meta, $specificCodes);

        if (!$lines) {
            $this->error = eventLog("WARNING", "Failed to export to JCAMP-DX because of error in reading the metadata template file");
            return "";
        }

        return $lines;
    }


    private function _dataDependentMeta()
    {
        // required: ##NPOINTS, ##FIRSTX, ##FIRSTY, ##LASTX, ##XFACTOR, ##YFACTOR
        // optional: ##MAXX, ##MINX, ##MAXY, ##MINY, ##DELTAX
        
        // the simple ones:
        $array["##NPOINTS"] = count($this->data);
        $array["##FIRSTX"]  = $this->data[0][0];
        $array["##FIRSTY"]  = $this->data[0][1];
        $array["##LASTX"]   = $this->data[$array["##NPOINTS"] - 1][0];
        $array["##DELTAX"]  = ($array["##LASTX"] - $array["##FIRSTX"]) / ($array["##NPOINTS"] - 1);
        
        // max, min values
        $array["##MAXX"] = $array["##MINX"] = $this->data[0][0];
        $array["##MAXY"] = $array["##MINY"] = $this->data[0][1];
        
        foreach($this->data as $couple) {
            if ($couple[0] >= $array["##MAXX"]) $array["##MAXX"] = $couple[0];
            if ($couple[0] <= $array["##MINX"]) $array["##MINX"] = $couple[0];
            if ($couple[1] >= $array["##MAXY"]) $array["##MAXY"] = $couple[1];
            if ($couple[1] <= $array["##MINY"]) $array["##MINY"] = $couple[1];
        }
        
        // xfactor and yfactor
        switch ($this->normalizeX) {
            case 0:
                $this->xFactor = $array["##XFACTOR"] = 1;
                break;
            case 1:
                $this->xFactor = $array["##XFACTOR"] = 1 / (10 ** $this->xDecimals);
                break;
            default:
                if (abs($array["##MAXX"]) > abs($array["##MINX"]))
                    $this->xFactor = $array["##XFACTOR"] = abs($array["##MAXX"]) / abs($this->normalizeX);
                else
                    $this->xFactor = $array["##XFACTOR"] = abs($array["##MINX"]) / abs($this->normalizeX);  
        }
        
        switch ($this->normalizeY) {
            case 0:
                $this->yFactor = $array["##YFACTOR"] = 1;
                break;
            case 1:
                $this->yFactor = $array["##YFACTOR"] = 1 / (10 ** $this->yDecimals);
                break;
            default:
                if (abs($array["##MAXY"]) > abs($array["##MINY"]))
                    $this->yFactor = $array["##YFACTOR"] = abs($array["##MAXY"]) / abs($this->normalizeY);
                else
                    $this->yFactor = $array["##YFACTOR"] = abs($array["##MINY"]) / abs($this->normalizeY);  
        }

        switch (strtoupper($this->dataEncoding)) {
            case "(XY..XY)":
                $array["##XYDATA"] = "(XY..XY)";
                break;
            case "(X++(Y..Y))":
            default:
                $array["##XYDATA"] = "(X++(Y..Y))";
        }

        $lines = array();
        foreach ($array as $key => $value) array_push($lines, $key . "=" . $value . "\r\n");

        return $lines;
    }


    private function _data()
    {  
        $lines = array();

        // reverse the array to be able to use array_pop (instead of array_shift
        // which is much more demanding as it needs reindexing every time
        $queue = array_reverse($this->data);

        // wordwrap is inherited from $this->wordwrap, but contrary to the latter,
        // wordwrapping cannot be disabled in the data part, because (X++(Y..Y)) data would be written all on the same line
        // therefore: if $this->wordwrap === 0 , then $dataWW = 80
        $dataWW = ($this->wordWrap === 0 ? 80 : $this->wordWrap);
                                             
        switch (strtoupper($this->dataEncoding)) {
            case "(XY..XY)":
                for ( ; count($queue) > 0; ) { 
                    list($x, $y) = array_pop($queue);
                    if ($this->normalizeX != 1) $x = strval(round($x / $this->xFactor));
                    if ($this->normalizeY != 1) $y = strval(round($y / $this->yFactor));
                    $lines[] = $x . " " . $y . "\r\n";
                }
                break;
                
            default:
            case "(X++(Y..Y))":
                // initialise the very first line with the first X value
                $newLine = $queue[count($queue) - 1][0]; 
                
                // fill the first line and the next lines
                for ( ; count($queue) > 0; ) { 
                    list($x, $y) = array_pop($queue);
                    
                    //normalise (if requested)
                    if ($this->normalizeX != 1) $x = strval(round($x / $this->xFactor));
                    if ($this->normalizeY != 1) $y = strval(round($y / $this->yFactor));
                    
                    $currentLength = strlen($newLine);              //length of the X Y Y... line as it is before adding
                    $addLength  = strlen($y) + 1;                   //lenght of the Y value (plus a space that separates it from the previous line)
            
                    if ($currentLength + $addLength <= $dataWW) {   //if enough space on the $line: add current Y value
                        $newLine .= " " . $y;
                    } else {                                        //if not enough space: add $line to $lines, and begin a new $line
                        $lines[] = $newLine . "\r\n";
                        $newLine = $x . " " . $y;
                    }
                }
                break;
        }
         
        // check wordwrap (debug - to be commented out after testing?)
        $tooLong = 0;
        foreach ($lines as $line) 
            if (strlen($line) > $dataWW + 2) $tooLong++;    // +2 because we don't count \r\n in $dataWW
        if ($tooLong) 
            eventLog("WARNING", $tooLong . " data line(s) exceed the wordwrap limit.", false);
    
        return $lines;
    }
}