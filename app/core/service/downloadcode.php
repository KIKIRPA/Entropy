<?php

namespace Core\Service;

/*

TODO
- setPath: allowedExtensions (true, false or array)
- setConversion
- buttongenerator (single/multi, direct/modal)
*/


class DownloadCode
{
    public $code = null;
    public $value = array();

    /**
     * generateCode($length = 6)
     * 
     * generate a random downloadcode
     * (used mainly internally by storePath())
     */
    public function generateCode($length = 6)
    {
        $this->code = Randomizer::generateString($length);
        
        //extremely small chance, but check if this code does not yet exist
        if (isset($_SESSION['downloadcodes'][$this->code])) {
            $this->generateCode($length);
        }
    }

    /**
     * setPath($path, $prefix = null, $conditions = array(), $allowedExtensions = true)
     * 
     * sets a (relative) file path or an (absolute) url in the downloadcode register.
     * If the path starts with http:// or https://, it is treated as an url, in all other cases as a file path
     * A file path is always interpreted relative; if a prefix is given, it is prepended
     * 
     * returns how many values have been created (url/path = 1, paths>1, or 0)
     */
    public function setPath($path, $prefix = null, $conditions = array(), $allowedExtensions = true)
    {
        $this->generateCode();
        
        $this->value = array(); //reset the value
        try {
            if ((substr(strtolower($path), 0, 7) === 'http://') or (substr(strtolower($path), 0, 8) === 'https://')) {
                if (filter_var($path, FILTER_VALIDATE_URL) === false) {
                    throw new \Exception("Invalid URL.");
                }
                $this->value["url"] = $path;
                $count = 1;
            } else {
                //make an absolute file path
                $path = str_replace("file://", "", $path);
                $path = ltrim($path, "./\\"); //prevent escaping from our "jail" using ../
                $path = str_replace("..", "", $path);
                if (isset($prefix)) {
                    $path = $prefix . "/" . $path;
                }

                //check if file (or files in case of wildcards!) exist(s)
                if ($allowedExtensions === false) {
                    $array = array();
                } elseif ($allowedExtensions === true) {
                    $array = glob($path);
                } else {
                    $array = glob($path);
                    foreach ($array as $i => $temp) {
                        if (!in_array(strtolower(pathinfo($temp, PATHINFO_EXTENSION)), $allowedExtensions)) {
                            unset($array[$i]);
                        }
                    }
                } 

                // fill in the fields in the downloadcode
                if (count($array) == 1) {
                    $this->value["path"] = $array[0];
                    $count = 1;
                } elseif (count($array) > 1) {
                    $this->value["paths"] = $array;
                    $count = count($array);
                } else {
                    $count = 0;
                }
            }

            // take care of conditions, if given
            if (is_array($conditions))
                if (count($conditions) > 0)
                    $this->setConditionsArray($conditions);

        } catch (\Exception $e) {
            $errormsg = $e->getMessage();
            eventLog("ERROR", $errormsg  . " [Core\\Service\\DownloadCode]");
            $count = 0;
        }

        // return number of paths
        $this->value["count"] = $count;
        return $count;
    }

    /**
     * setConversion($format, $conditions)
     * 
     */
    public function setConversion($format, $conditions = array())
    {
        $this->generateCode();
        
        $this->value = array(); //reset the value
        $this->value["conv"] = $format;
        $this->value["count"] = 1;

        // take care of conditions, if given
        if (is_array($conditions))
            if (count($conditions) > 0)
                $this->setConditionsArray($conditions);

        return $this->value["count"];
    }

    /**
     * store()
     * 
     * stores the downloadcode into the session
     */
    public function store()
    {
        if (isset($this->code) and !empty($this->value)) {
            if ($this->value["count"] > 0) {
                $_SESSION['downloadcodes'][$this->code] = $this->value;
                return $this->code;
            } else {
                return null;
            }
        } else {
            eventLog("ERROR", "Failed to store downloadcode to the session [Core\\Service\\DownloadCode]");
            return null;
        }
    }


    /**
     * retrieve($code)
     * 
     * retrieves downloadcode from the session and fills in the type, value, options...
     * returns true if code is found/retrieved, false if not
     */
    public function retrieve($code)
    {
        if (isset($_SESSION['downloadcodes'][$code])) {
            $this->code = $code;
            $this->value = $_SESSION['downloadcodes'][$code];
            return true;
        } else {
            eventLog("ERROR", "Downloadcode $code was not found in the session [Core\\Service\\DownloadCode]");
            $this->value = array();
            $this->code = null;
            return false;
        }
    }


    /**
     * getType()
     * 
     * returns string "path", "paths", url" or "conv" (or null)
     */
    public function getType()
    {
        foreach (array("path", "paths", "url", "conv") as $type) {
            if (isset($this->value[$type]))
                return $type;
        }
        
        // fallback: return null
        return null;
    }

    /**
     * getValue($type = null, $i = null)
     */
    public function getValue($type = null, $i = null)
    {
        if (is_null($type)) {
            return null;
        } elseif (isset($this->value[$type])) {
            if (is_array($this->value[$type])) {
                // in case of an array (paths): if $i is given, return that value or null if not found, if no $i supplied, give the first value
                if (isset($i)) {
                    if (isset($this->value[$type][$i])) {
                        return $this->value[$type][$i];
                    }
                } else {
                    return array_values($this->value[$type])[0];
                }
            } else {
                // if not an array (path, url, conv), return the value
                return $this->value[$type]; 
            }
        }
        // if no previous return
        return null;
    }

    public function setCondition($condition, $value)
    {
        $this->value["conditions"][$condition] = $value;
    }

    public function setConditionsArray($conditions)
    {
        $this->value["conditions"] = $conditions;
    }   

    public function checkCondition($condition = null)
    {
        if (is_null($condition)) {
            // if no condition given, return true is conditions exist in the downloadcode, false if not
            if (isset($this->value["conditions"])) {
                if (count($this->value["conditions"]) > 0) {
                    return true;
                }
            }
            return false;
        } elseif (isset($this->value["conditions"][$condition])) {
            // if condition is given, return its value, or false if not set
            return $this->value["conditions"][$condition];
        } else {
            return false;
        }
    }

    public function count() 
    {
        if ($this->value["count"]) return $this->value["count"];
        else return 0;
    }
    
    public function makeButtonCode($buttonText, $buttonColor, $callModal = false, $outlined = false, $icon = "download", $indentation = 28)
    {
        // indentation spaces
        $indentation = str_repeat(" ", $indentation);

        // translate outlined/callmodal into html and bulma-ish
        $outlined = $outlined ? " is-outlined" : "";
        $modalClass = $callModal ? " modal-button" : "";

        // basic parts of the URL (without i)
        $baseURL = $_SERVER["PHP_SELF"] . "?";
        foreach ($this->value["conditions"] as $key => $value) {
            $baseURL .= "$key=$value&";
        }
        $baseURL .= "dl=" . $this->code;

        // single button or select/button combination?
        if ($this->value["count"] === 1) {
            // button action (href or onclick)
            if ($callModal) {
                $buttonAction ="data-target=\"dlmodal\" onclick=\"updateFormAction('$baseURL');\"";
            } else {
                $buttonAction = "href=\"" . $baseURL ."\"";
            }
            
            // html code for simple button
            $html = $indentation . "<div class=\"field\">\n"
                  . $indentation . "    <a $buttonAction class=\"button is-fullwidth ${buttonColor}${outlined}${modalClass}\">\n"
                  . $indentation . "        <span class=\"icon\"><i class=\"fa fa-$icon\"></i></span>\n"
                  . $indentation . "        <span>$buttonText</span>\n"
                  . $indentation . "    </a>\n"
                  . $indentation . "</div>\n";
        } elseif ($this->value["count"] > 1) {
            // button action (href or onclick)
            if ($callModal) {
                $buttonAction ="data-target=\"dlmodal\" onclick=\"updateFormAction('$baseURL', '$this->code');\"";
                $selectAction = "";
            } else {
                $buttonAction = "href=\"" . $baseURL . "\"";
                $selectAction = "onchange=\"updateButtonHref('$baseURL', '$this->code');\"";
            }
            
            // html for button with multiple files
            $html = $indentation . "<div class=\"field has-addons\">\n"
                  . $indentation . "    <div class=\"control is-expanded\">\n"
                  . $indentation . "        <div class=\"select is-fullwidth ${buttonColor}\">\n"
                  . $indentation . "            <select id=\"select$this->code\" $selectAction>\n";
            foreach ($this->value["paths"] as $i => $path) {
                $html .= $indentation . "                <option value=\"$i\">" . basename($path) . "</option>\n";
            }
            $html .= $indentation . "            </select>\n"
                   . $indentation . "        </div>\n"
                   . $indentation . "    </div>\n"
                   . $indentation . "    <div class=\"control\">\n"
                   . $indentation . "        <a $buttonAction class=\"button ${buttonColor}${outlined}${modalClass}\" id=\"button$this->code\">\n"
                   . $indentation . "            <span class=\"icon\"><i class=\"fa fa-$icon\"></i></span>\n"
                   . $indentation . "        </a>\n"
                   . $indentation . "    </div>\n"
                   . $indentation . "</div>\n";
        }
        else $html = false;

        return $html;
    }
}