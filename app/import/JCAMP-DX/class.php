<?php

// prevent direct access to this file (thus only when included)
if (count(get_included_files()) == 1) {
    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
    header("Status: 404 Not Found");
    exit("Direct access not permitted.");
}

class ImportJCAMPDX
{
    public $data = array();
    public $meta = array();
    public $error = false;
    
    /**
     * __construct($file, $parameters = array())
     * 
     * Converts an JCAMP-DX file into a 2D-array
     * - supports ##XYDATA with (X++(Y..Y)) (IRUG) or (XY..XY) (MaSC) formats
     * - no support for data compression (DIP, DUP...)!
     * - supports inline comments (following the coordinates)
     * - very rudimentary metadata extraction
     * - supported parameters: none
     * 
     * @param string $file Or a filename (including path), or an array of lines. 
     * @param array $parameters Specific parameters for this helper
     * @param bool $fetchData Fetch numerical data
     * @param bool $fetchMeta Try to fetch metadata
     * @return array of coordinate arrays (X, Y)
     */
    function __construct($file, $parameters = array())
    {
        // if $file is a filename, open it as an array of lines
        if (!is_array($file)) {
            $file = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        // read tags
        list($dataTags, $this->meta) = $this->readJcampDxTags($file);
        
        // read spectrum
        if (isset($dataTags["##XYDATA"])) {
            switch ($dataTags["##XYDATA"]) {
                case "(XY)":
                case "(XY..XY)":
                case "(X,Y)":           // JCAMP-DX draft 6.0 allows to define the delimiters; 2 examples
                case "(X,Y..X,Y)":      // but neglect spaces in these constructions (these have been removed in a previous step)
                    echo "simpel<br><br>";    
                    $this->readJcampDxDataSimple($file, $dataTags);
                    break;    
                case "(X++(Y..Y))":
                    echo "multi<br><br>";
                    $this->readJcampDxDataMulti($file, $dataTags);
                    break;
                default:
                    $this->error = eventLog("WARNING", "Unsupported JCAMP-DX XYDATA format.");
                    break;
            }
        
            if (!empty($this->data)) {
                // final sanity checks
                $sanityCheck = array();
                if (isset($dataTags["##FIRSTX"])) {
                    if (!$this->compareDecimals($this->data[0][0], $dataTags["##FIRSTX"])) {
                        $sanityCheck[] = "firstx";
                    }
                }
                if (isset($dataTags["##FIRSTY"])) {
                    if (!$this->compareDecimals($this->data[0][1], $dataTags["##FIRSTY"])) {
                        $sanityCheck[] = "firsty";
                    }
                }
                if (isset($dataTags["##LASTX"])) {
                    if (!$this->compareDecimals($this->data[count($this->data) - 1][0], $dataTags["##LASTX"])) {
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
                    $nod_x = $this->nod($dataTags["##FIRSTX"]);
                } elseif (isset($dataTags["##LASTX"])) {
                    $nod_x = $this->nod($dataTags["##LASTX"]);
                } else {
                    $nod_x = 3;
                }
                if (isset($dataTags["##FIRSTY"])) {
                    $nod_y = $this->nod($dataTags["##FIRSTY"]);
                } else {
                    $nod_y = 3;
                }
                //&$couple is a reference; allows manipulating the array elements directly
                foreach ($this->data as &$couple) { 
                    if (isset($nod_x)) {
                        $couple[0] = round($couple[0], $nod_x);
                    } //round x
                    if (isset($nod_y)) {
                        $couple[1] = round($couple[1], $nod_y);
                    } //round y
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

    private function readJcampDxTags(&$lines)
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


    private function readJcampDxDataMulti(&$lines, $dataTags) {
        // Required parameters:
        if (!(isset($dataTags["##LASTX"]) or isset($dataTags["##FIRSTX"]) or isset($dataTags["##NPOINTS"]))) {
            $this->error = eventLog("WARNING", "Missing required tags in the JCAMP-DX file.");
            return;
        }
        
        $deltax = (floatval($dataTags["##LASTX"]) - floatval($dataTags["##FIRSTX"])) / (intval($dataTags["##NPOINTS"]) - 1);
        $firstx = $dataTags["##FIRSTX"];
        // check deltax:
        if (!isset($dataTags["##DELTAX"]) and !$this->compareDecimals($dataTags["##DELTAX"], $deltax)) {
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
            if (!$this->compareDecimals($x, $firstx)) {
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


    private function readJcampDxDataSimple(&$lines, $dataTags)
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

    /**
     * compareDecimals($val1, $val2)
     * returns true if equal
     */
    private function compareDecimals($val1, $val2)
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

    /**
     * nod($number)
     * $number = string representing a floatval
     * returns the number of decimals
     */
    private function nod($number)
    {
        return strlen(substr(strrchr($number, "."), 1));
    }
}