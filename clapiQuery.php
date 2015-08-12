<?php
class ClapiQuery {

    private $user;
    private $passwd;
    private $path;

    public function __construct($user, $passwd, $path) {
        $this->user = $user;
        $this->passwd = $passwd;
        $this->path = $path;
        $this->enable = FALSE;
    }

   public function isEnable() {
       return $this->enable;
   }

   public function disable() {
       $this->enable = FALSE;
   }

   public function setQuery($type, $operation, $args) {
       $this->type = $type;
       $this->operation = $operation;
       $this->args = $args;
       $this->enable = TRUE;
       return TRUE;
   }

   public function getInteractiveQuery() {
       return $this->path." -u ".$this->user." -p ".$this->passwd." -o ".$this->type." -a ".$this->operation." -v \"" . $this->args."\"";
   }

   public function getQuery() {
       return $this->type.";".$this->operation.";".$this->args."\n";
   }

   public function getPath() {
       return $this->path." -u ".$this->user." -p ".$this->passwd;
   }

   public function getValues() {
       if($this->enable) {
           return array('type'=>$this->type,
                        'operation'=>$this->operation,
                        'args'=>$this->args);
       } else {
           return null;
       }
   }

    /**
     * Retourne TRUE si la commande passé en argument est interactive
     *
     * @return Boolean
     */
    public function isInteractive() {
        if(!$this->enable) {
            return FALSE;
        }
        if($this->operation == "SHOW" || $this->operation == "GETCONTACT" || $this->operation == "GETHOSTS") {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}
