<?php

namespace Convert\Import;

class Annotations
{
    public $anno = array();
    public $error = false;
    
    /**
     * __construct($file, $viewer = "xy")
     * 
     * Converts an annotation text file into an array for the selected viewer
     * 
     * @param string $file Or a filename (including path), or an array of lines. 
     * @param string $viewer The selected viewer component
     * @return array formatted annotation array 
     */
    function __construct($file, $viewer = "xy")
    {
        // if $file is a filename, open it as an array of lines
        if (!is_array($file)) {
            $file = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        
        if (strtolower($viewer) == "xy") {
            /* annotations for the xy viewer (dygraphs based) should be supplied in the form of ascii files 
            * with a numberical x value starting each line, followed by a short description (label) and 
            * (optionally) a longer description separated with whitespaces
            * The result will be an indexed array of associative arrays ('x'=>, 'shortText'=>, 'text'=>)
            * (cf. documentation dygraphs)
            */
            foreach ($file as $i => $line) {
                // only lines beginning with a number are considered (don't include comments)
                if (is_numeric(substr($line, 0, 1))) {
                    $line = preg_replace('/\s+/', ' ', $line);
                    $line = explode(" ", $line, 3);
                    $temp = array();
                    if ((count($line) >= 2) and is_numeric($line[0])) {
                        $temp["x"] = floatval($line[0]);
                        $temp["shortText"] = $line[1];
                        if (count($line) == 3) {
                            $temp["text"] = $line[2];
                        }
                        $this->anno[] = $temp;
                    }
                }
            }
        } else {
            $this->error = "Annotations are currently only supported for XY graphs.";
            return;
        }  
        
        if (count($this->anno) == 0) {
            $this->error = "Invalid annotation file.";
        }
    }

    public function getAnno()
    {
        return $this->anno;
    }

    public function getError()
    {
        return $this->error;
    }
}