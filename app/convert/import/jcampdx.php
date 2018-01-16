<?php

namespace Convert\Import;

class Jcampdx
{
    public $data = array();
    public $meta = array();
    public $error = false;
    
    /**
     * __construct($file, $options = array())
     * 
     * Converts an JCAMP-DX file into a 2D-array
     * - supports ##XYDATA with (X++(Y..Y)) (IRUG) or (XY..XY) (MaSC) formats
     * - no support for data compression (DIP, DUP...)!
     * - supports inline comments (following the coordinates)
     * - very rudimentary metadata extraction
     * - supported parameters: none
     * 
     * @param string $file Or a filename (including path), or an array of lines. 
     * @param array $options Specific parameters for this convertor
     * @param bool $fetchData Fetch numerical data
     * @param bool $fetchMeta Try to fetch metadata
     * @return array of coordinate arrays (X, Y)
     */
    function __construct($file, $options = array())
    {
        // if $file is a filename, open it as an array of lines
        if (!is_array($file)) {
            $file = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        // read tags
        list($dataTags, $this->meta) = $this->readTags($file);
        
        // read spectrum
        if (isset($dataTags["##XYDATA"])) {
            switch ($dataTags["##XYDATA"]) {
                case "(XY)":
                case "(XY..XY)":
                case "(X,Y)":           // JCAMP-DX draft 6.0 allows to define the delimiters; 2 examples
                case "(X,Y..X,Y)":      // but neglect spaces in these constructions (these have been removed in a previous step)   
                    $this->readDataXYXY($file, $dataTags);
                    break;    
                case "(X++(Y..Y))":
                    $this->readDataXYYY($file, $dataTags);
                    break;
                default:
                    $this->error = eventLog("WARNING", "Unsupported JCAMP-DX XYDATA format.");
                    break;
            }
        
            if (!empty($this->data)) {
                // final sanity checks
                $sanityCheck = array();
                if (isset($dataTags["##FIRSTX"])) {
                    if (!compareDecimals($this->data[0][0], $dataTags["##FIRSTX"])) {
                        $sanityCheck[] = "firstx";
                    }
                }
                if (isset($dataTags["##FIRSTY"])) {
                    if (!compareDecimals($this->data[0][1], $dataTags["##FIRSTY"])) {
                        $sanityCheck[] = "firsty";
                    }
                }
                if (isset($dataTags["##LASTX"])) {
                    if (!compareDecimals($this->data[count($this->data) - 1][0], $dataTags["##LASTX"])) {
                        $sanityCheck[] = "lastx";
                    }
                }
                if (isset($dataTags["##NPOINTS"])) {
                    if (count($this->data) != $dataTags["##NPOINTS"]) {
                        $sanityCheck[] = "npoints";
                    }
                }
                // we could also check on ##MINY and ##MAXY, but why?
                if (count($sanityCheck) > 0) {
                    $this->error = eventLog("WARNING", "Invalid information in JCAMP-DX file: " . implode(", ", $sanityCheck).".");
                }
                
                // ROUND DATA - do this after all calculations
                // first find number of decimals to round to
                if (isset($dataTags["##FIRSTX"])) {
                    $xDecimals = countDecimals($dataTags["##FIRSTX"]);
                } elseif (isset($dataTags["##LASTX"])) {
                    $xDecimals = countDecimals($dataTags["##LASTX"]);
                } else {
                    $xDecimals = 3;
                }
                if (isset($dataTags["##FIRSTY"])) {
                    $yDecimals = countDecimals($dataTags["##FIRSTY"]);
                } else {
                    $yDecimals = 3;
                }
                //&$couple is a reference; allows manipulating the array elements directly
                foreach ($this->data as &$couple) { 
                    if (isset($xDecimals)) {
                        $couple[0] = round($couple[0], $xDecimals);
                    } //round x
                    if (isset($yDecimals)) {
                        $couple[1] = round($couple[1], $yDecimals);
                    } //round y
                }
            }
        }

        // sort data
        if (!orderData($this->data)) eventLog("WARNING", "JCAMP-DX import: could not sort the data: " . $file);;
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

    private function readTags(&$lines)
    {
        $dataTags = array();
        $metaTags = array();
        $findDataTags = array("##DELTAX", "##XFACTOR", "##YFACTOR", "##MINY", "##MAXY", "##FIRSTX", "##LASTX", "##NPOINTS", "##FIRSTY", "##XYDATA");
            
        // reading tags until data comes (= whe xydata tag has been found - other types are not supported!)
        while (!isset($metaTags["##XYDATA"])) {
            $line = array_shift($lines);
            switch (substr($line, 0, 2)) {
                case "##":
                case "$$":
                    if (trim($line) == "##END=") {
                        eventLog("WARNING", "Unsupported JCAMP-DX file: no XYDATA.", false);
                        return array(array(), array());
                    }
                    
                    list($tag, $value) = explode("=", $line, 2);
                    $tag = strtoupper(str_replace(array(" ", "/", "-", "_"), "", $tag)); //remove spaces and such and uppercase it (allowed by JCAMP specs)
                    $metaTags[$tag] = trim($value);
                    break;
                default:
                    // line without tag: add text to previous tag-value
                    $metaTags[$tag] .= " " . trim($value);
                    break;
            }
        }

        //separate data-related tags from metadata tags
        foreach ($metaTags as $tag => $value) {
            if (in_array($tag, $findDataTags)) {
                $dataTags[$tag] = $value;
                unset($metaTags[$tag]);
            }
        }
        
        //add some default values
        if (!isset($dataTags["##XFACTOR"])) {
            $dataTags["##XFACTOR"] = 1;
        }
        if (!isset($dataTags["##YFACTOR"])) {
            $dataTags["##YFACTOR"] = 1;
        }

        return array($dataTags, $metaTags);
    }


    private function readDataXYYY(&$lines, $dataTags) {
        // Required parameters:
        if (!(isset($dataTags["##LASTX"]) or isset($dataTags["##FIRSTX"]) or isset($dataTags["##NPOINTS"]))) {
            $this->error = eventLog("WARNING", "Missing required tags in the JCAMP-DX file.");
            return;
        }
        
        $deltax = (floatval($dataTags["##LASTX"]) - floatval($dataTags["##FIRSTX"])) / (intval($dataTags["##NPOINTS"]) - 1);
        $firstx = $dataTags["##FIRSTX"];
        // check deltax:
        if (!isset($dataTags["##DELTAX"]) and !$compareDecimals($dataTags["##DELTAX"], $deltax)) {
            $this->error = eventLog("WARNING", "Invalid DELTAX in JCAMP-DX file.");
            return;
        }
        
        //read spectrum
        while (count($lines) > 0) {
            $line = array_shift($lines);

            // check end of data
            if (strtoupper(str_replace(array(" ", "/", "-", "_"), "", $line)) == "##END=") {
                break;
            }

            // decompose line into an array of values
            $values = getSpectrumValues($line);

            $x = array_shift($values) * $dataTags["##XFACTOR"];
            if (!compareDecimals($x, $firstx)) {
                $this->error = eventLog("WARNING", "JCAMP-DX (X++(Y..Y)) data is not evenly spaced (first X value: " . $x . " <-> calculated X value: " . $firstx . ").");
                return;
            }

            foreach ($values as $y) {
                $y *= $dataTags["##YFACTOR"];
                $this->data[] = array($x, $y);
                $x += $deltax;
                $firstx += $deltax;
            }
        }
    }


    private function readDataXYXY(&$lines, $dataTags)
    {
        // count number of lines with data (until ##END tag)
        $i = 0;
        while (count($lines) > 0) {
            if (strtoupper(str_replace(array(" ", "/", "-", "_"), "", $line)) == "##END=") {
                break;
            }
            $i++;
        }
        // reduce lines array to the data lines only
        array_splice($lines, $i + 1);
        //recombine the array of lines and make the values-array of the whole spectrum
        $values = getSpectrumValues(implode("\n", $lines));

        while (count($values) > 0) {  //read spectrum
            $x = array_shift($values) * $dataTags["##XFACTOR"];
            $y = array_shift($values) * $dataTags["##YFACTOR"];

            $this->data[] = array($x, $y);
        }
    }
}