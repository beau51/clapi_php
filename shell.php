<?php

class Shell {

    private $ssh;
    private $scp;

    public function __construct($server, $remote_user) {
        $this->ssh = "ssh -l " . $remote_user . " " . $server . " '%s'";
        $this->scp = "scp %s " . $remote_user . "@" . $server . ":%s";
    }

    protected function exec($command, &$output, &$return_val) {
        exec($command, $output, $return_val);
    }

    public function unlink($filename) {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    public function getSSHCommand($command) {
        return sprintf($this->ssh, $command);
    }

    public function getSCPCommand($localfile, $remotefile) {
        return sprintf($this->scp, $localfile, $remotefile);
    }

    public function remoteExec($command, &$output, &$return_val) {

        $command = $this->getSSHCommand($command);
        $this->exec($command, $output, $return_val);
    }

    public function copy($localfile, $remotefile, &$output, &$return_val) {
        if (file_exists($localfile)) {
            $copy_cmd = $this->getSCPCommand($localfile, $remotefile);
            $this->exec($copy_cmd, $output, $return_val);
            $this->unlink($localfile);
        }
    }

}

?>