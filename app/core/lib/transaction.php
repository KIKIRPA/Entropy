<?php

namespace Core\Lib;

class Transaction
{
    private static $current = null;
    public static $lib = null;
    public static $json = array();

    public $id;
    public $name;
    public $user;
    public $action;
    public $state;
    public $timestamp;
    public $currentCount;
    public $totalCount;

 
    /**
     * __construct($lib)
     * 
     * Reads the transactions file for a given library
     * 
     * @param string $lib library id
     */
    function __construct($lib)
    {
        self::init($lib);
    }

    public static function init($lib)
    {
        if ($lib != self::$lib) {
            self::$lib = $lib;
            $path = \Core\Config\App::get("libraries_path") . $lib;

            if (file_exists($path . "/transactions.json")) {
                self::$json = readJSONfile($path . "/transactions.json", false);
                //self::$current = key($json);
            } else {
                // for compatibility with Entropy 1.0:, read old files
                $jsonOpen = $jsonClosed = array();
                if (file_exists($path . "/transactions_open.json")) {
                    $jsonOpen = readJSONfile($path . "/transactions_open.json", false);
                    foreach ($jsonOpen as $id => &$tr) {
                        $tr["name"] = $id;
                        $tr["state"] = ($tr["step"] == 10) ? "locked" : "open";
                        unset($tr["step"], $tr); // unset $tr: it would remain a reference to the last item
                    }
                }
                if (file_exists($path . "/transactions_closed.json")) {
                    $jsonClosed = readJSONfile($path . "/transactions_closed.json", false);
                    foreach ($jsonClosed as $id => &$tr) {
                        $tr["name"] = $id;
                        $tr["state"] = "closed";
                        unset($tr["step"], $tr); // unset $tr: it would remain a reference to the last item
                    }
                }
                self::$json = array_merge($jsonClosed, $jsonOpen);
                //self::$$current = null;
            }
        }
    }

    public function search($id)
    {
        if (isset($this->json[$id])) {
            $this->id = $id;
            $this->name = $currentTr["name"];
            $this->user = (isset($currentTr["user"])) ? $currentTr["user"] : "unknown";
            $this->action = $currentTr["action"];
            $this->state = $currentTr["state"];
            $this->timestamp = (isset($currentTr["timestamp"])) ? $currentTr["timestamp"] : "unknown";
            $this->currentCount = (isset($currentTr["current"])) ? $currentTr["current"] : 0;
            $this->totalCount = (isset($currentTr["total"])) ? $currentTr["total"] : 0;
            return true;
        } else return false;
    }

    public function resetPointer()
    {
        reset($this->json);
    }

    public function searchNext($state = false, $user = false)
    {
        do {
            $currentTr = current($this->json);
            $currentKey = key();

            next($this->json); //advance array pointer

            // if state or  user is not matching, skip and go to next item in the array
            if ($state and $user) {
                if (($tr["state"] != $state) or ($tr["user"] != $user)) continue;
            } elseif ($state) {
                if ($tr["state"] != $state) continue;
            } elseif ($user) {
                if ($tr["user"] != $user) continue;
            }
            
            // if we have a match, set the variables and return true on success           
            return $this->search($currentKey);
        } while ($currentTr !== false);     
    }

    public function new($name, $user, $action, $state = "open", $currentCount = 0, $totalCount = 1)
    {    
        // find a new, unique id based on current date/time
        $ts = date("YmdHis");
        if (isset($this->json[$ts])) {
            $i = 1;
            do {
                $newId = $ts . "_" . $i;
                if (!isset($this->json[$newId])) {
                    break;
                }
                $i++;
            } while (true);
        } else {
            $newId = $ts;
        }

        $this->id = $newId;
        $this->name = $name;
        $this->user = $user;
        $this->action = $action;
        $this->state = $state;
        $this->timestamp = date('Y-m-d H:i:s');
        $this->currentCount = 0;
        $this->totalCount = 1;
        $this->_update(); //update json
        
        return $this->id;
    }

    public function updateName($name)
    {
        if (isset($this->id)) {
            $this->name = $name;
            $this->_update();
            return true;
        } else return false;
    }

    public function updateState($state)
    {
        if (isset($this->id)) {
            $this->state = $state;
            $this->_update();
            return true;
        } else return false;
    }

    public function updateCurrent($currentCount)
    {
        if (isset($this->id)) {
            $this->currentCount = $currentCount;
            $this->_update();
            return true;
        } else return false;
    }

    public function updateTotal($totalCount)
    {
        if (isset($this->id)) {
            $this->totalCount = $totalCount;
            $this->_update();
            return true;
        } else return false;
    }

    private function _update()
    {
        if (isset($this->json) and isset($this->id)) {
            $this->json[$this->id] = array( "name" => $this->name,
                                            "user" => $this->user,
                                            "action" => $this->action,
                                            "state"  => $this->state,
                                            "timestamp" => $this->timestamp,
                                            "current" => $this->currentCount,
                                            "total" => $this->totalCount
                                          );
        }
    }

    public function delete() {
        if (isset($this->json) and isset($this->id)) {
            unset($this->json[$this->id]);
            $this->id = null;
        }
    }

    public function writeFile()
    {
        $path = \Core\Config\App::get("libraries_path") . "/transactions.json";
        writeJSONfile($path, $this->json);
    }
}