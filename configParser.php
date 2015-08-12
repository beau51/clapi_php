<?php

class ConfigParser {

    protected $initialised;
    protected $config;
    protected $all_config;

    public function __construct() {
        $this->config = array();
        $this->all_config = array();
        $this->initialised = FALSE;
    }

    public function initialise($filename) {
        $this->all_config = parse_ini_file($filename, TRUE);
        $this->initialised = TRUE;
    }

    /**
     *
     * @todo : valider que la section existe.
     * @todo : valider que le parser est bien initialisé
     */
    public function parseSection($section) {
        //$this->config = array();
        $this->config = array_merge($this->config, $this->all_config[$section]);
    }

    public function getVariables() {
        return array_keys($this->config);
    }

    public function getValue($key) {
        return $this->config[$key];
    }

    public function getSection($section) {
        return $this->all_config[$section];
    }

}

?>