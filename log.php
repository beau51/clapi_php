<?php

class Log {

    private static $_instance;
    private $level;
    // c'est sale mais bon... 1 = debug, 2 = info, 3 = warning, 4 = error, 5 = critical
    public static $DEBUG = 5;
    public static $INFO = 4;
    public static $WARNING = 3;
    public static $ERROR = 2;
    public static $CRITICAL = 1;
    public static $NOLOG = 0;

    /**
     * empêche la création externe d'instances.
     */
    private function __construct() {
        // par défaut, on se met en 2
        $this->level = Log::$INFO;
    }

    /**
     * empêche la copie externe de l'instance.
     */
    private function __clone() {
        
    }

    /**
     * renvoi de l'instance et initialisation si nécessaire.
     */
    public static function i() {
        if (!(self::$_instance instanceof self))
            self::$_instance = new self();

        return self::$_instance;
    }

    /*
     * La méthode qui s'occupe de l'affichage (en print_r pour l'instant)
     *
     * Cette méthode n'est pas appelé directement mais par
     * l'intermédiaire des méthodes debug(), info(), warning(),
     * error(), critical()
     */

    private function log($source, $level, $msg) {
        $now = date("Ymd H:i:s");
        print_r($now . " - " . $source . " - " . $level . " - " . $msg . "\n");
    }

    public function setLevel($level) {
        $this->level = $level;
    }

    public function getLevel() {
        return $this->level;
    }

    public function debug($source, $msg) {
        if ($this->level >= self::$DEBUG) {
            $this->log($source, "debug", $msg);
        }
    }

    public function info($source, $msg) {
        if ($this->level >= self::$INFO) {
            $this->log($source, "info", $msg);
        }
    }

    public function warning($source, $msg) {
        if ($this->level >= self::$WARNING) {
            $this->log($source, "warning", $msg);
        }
    }

    public function error($source, $msg) {
        if ($this->level >= self::$ERROR) {
            $this->log($source, "error", $msg);
        }
    }

    public function critical($source, $msg) {
        if ($this->level >= self::$CRITICAL) {
            $this->log($source, "critical", $msg);
        }
    }

    public function display($source, $commands) {
        foreach ($commands as $value) {
            Log::i()->debug($source, $value["command"] . ": " . $value["result"]);
        }
    }

    public function __toString() {
        $level_string = "";
        switch ($this->level) {
            case self::$DEBUG:
                $level_string = "debug";
                break;
            case self::$INFO:
                $level_string = "info";
                break;
            case self::$WARNING:
                $level_string = "warning";
                break;
            case self::$ERROR:
                $level_string = "error";
                break;
            case self::$CRITICAL:
                $level_string = "critical";
                break;
        }
        return "<Log level=" . $this->level . " (" . $level_string . ")>";
    }

}

?>