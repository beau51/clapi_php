<?php

if (!defined('ROOT')) {
    define('ROOT', __DIR__ . "/../../");
}
//require_once(ROOT . '/lib/log.php');
require_once __DIR__ . '/../lib/log.php';

/**
 * Bibliothèque Clapi
 * PHP version 5
 *
 * @package centreon-clapi
 * @category  PHP
 *
 * Centreon CLAPI est un module Open Source pour Centreon
 * qui permet aux utilisateurs de configurer leur système
 * de surveillance par le biais des lignes de commande.
 * Cette documentation a pour but de présenter toutes les
 * actions que vous pouvez effectuer avec Centreon CLAPI,
 * de l'ajout d'objets d'accueil au redémarrage d'un voteur
 * de surveillance à distance.
 *
 * @link http://documentation.centreon.com/docs/centreon-clapi/en/latest/
 *
 */
class Clapi {
    /**
     *
     * @var type
     */
    // private static $_instance = NULL;

    /**
     * Modèle d'exécution de clapi
     * 1 = traitement 1 par 1
     * 2 = Le traitement par lots
     *
     * Par défaut, c'est au coup par coup.
     *
     * @var int mode, type de traitement.
     */
    private $mode;
    private static $me = "CLAPI";

    /**
     * @var string $filename, le chemin du fichier pour le traitement par lot
     *
     * Dans le traitement par lot, l'ensemble des commandes sont
     * stockées dans ce fichier et ensuite envoyé au
     * serveur. Actuellement, le filename locale et distant sont les
     * mêmes.
     */
    private $filename;

    /**
     *
     */
    protected $data;

    /**
     * L'objet clapiQuery
     */
    protected $query;

    /**
     * L'objet Shell
     */
    private $shell;

    /**
     * table de list de commands 
     */
    private $commands;

    /**
     * retour messages pour commands(pour mod 2)
     */
    public $return_msg;

    /**
     * Mettre les paramètre pour connecter serveur centreon
     * @param string $centreon_user
     * @param string $centreon_password
     * @param string $clapi_path
     * @param string $server
     * @param string $system_user, l'utilisateur pour se connecter en ssh deviendra $this->user
     */
    public function __construct($clapiQuery, $shell, $config, $type = null) {
        $this->mode = $config->getValue('mode');
        //-- la fichier enregistrer les commandes qui attendre traitement
        if ($type != null) {
            $config->parseSection($type);
        }
        $this->filename = $config->getValue('filename');
        $this->query = $clapiQuery;
        $this->shell = $shell;
        $this->return_msg = array();
        $this->commands = array();
        // Commande ssh
    }

    // --------------------- getter
    /**
     * @return array $data, les données retournées par le serveur Centreon
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Retourne la fonction initialiser via les commandes clapi
     *
     * @return String $cmd, le contenu de la commande
     */
    public function get_query() {
        return $this->query;
    }

    public function getShell() {
        return $this->shell;
    }

    public function getMode() {
        return $this->mode;
    }

    // --------------------- Les commandes des hosts ----------------------------

    /**
     * Ajoute host de Centreon.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->addHost($arg1, $arg2, $arg3)->command();
     *
     * @param String $hostname
     * @param String $hostalias
     * @param String $hostip
     * @param String $hosttmp
     * @param String $poller
     * @param String $hostgroup
     * @return Clapi $this
     */
    function addHost($hostname, $alias, $ip, $template, $poller, $hostgroup) {
        $this->query->setQuery("HOST", "ADD", "$hostname;$alias;$ip;$template;$poller;$hostgroup");
        return $this;
    }

    /**
     * Supprime un host de Centreon.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->delHost($hostname)->command();
     *
     * @param String $hostname à supprimer
     * @return Clapi $this
     */
    function delHost($hostname) {
        $this->query->setQuery("HOST", "DEL", $hostname);
        return $this;
    }

    /**
     * Modifier un paramètre d'un host
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->setparamHost($hostname, $paramname, $paramvalue)->command();
     *
     * @param String $hostname en question
     * @param String $paramname le paramètre à modifier
     * @param String $paramname la valeur à mettre
     * @return Clapi $this
     */
    function setparamHost($hostname, $paramname, $paramvalue) {
        $this->query->setQuery("HOST", "SETPARAM", "$hostname;$paramname;$paramvalue");
        return $this;
    }

    /**
     * Activer un host.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->enableHost($hostname)->command();
     *
     * @param String $hostname à activer
     * @return Clapi $this
     */
    function enableHost($hostname) {
        $this->query->setQuery("HOST", "ENABLE", $hostname);
        return $this;
    }

    /**
     * Desactiver un host.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->disableHost($hostname)->command();
     *
     * @param String $hostname
     * @return Clapi $this
     */
    function disableHost($hostname) {
        //$this->query->setQuery("HOST", "DISABLE", "$hostname");
        $this->query->setQuery("HOST", "SETPARAM", "$hostname;activate;0");
        return $this;
    }

    /**
     * Retourne les hosts en fonction d'un pattern.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->showHost($pattern)->command();
     *
     * @param String $pattern le pattern de recherche
     * @return Clapi $this
     */
    function showHost($pattern) {
        $this->query->setQuery("HOST", "SHOW", $pattern);
        //comment être sûr de passer en $mode=1
        return $this;
    }

    /**
     * Modifier le contact d'un host
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->setContacttoHost($hostname, $contact)->command();
     *
     * @param string $hostname
     * @param string $user
     * @return Clapi $this
     */
    function setContacttoHost($hostname, $contact) {
        $this->query->setQuery("SERVICE", "SETCONTACT", "$hostname;$contact");
        return $this;
    }

    /**
     * Modifier le contact group d'un service
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->setContactGrouptoHost($hostname, $contact)->command();
     *
     * @param string $hostname
     * @param string $contactgroup
     * @return Clapi $this  SETCONTACTGROUP
     */
    function setContactGrouptoHost($hostname, $contactgroup) {
        $this->query->setQuery("HOST", "SETCONTACTGROUP", "$hostname;$contactgroup");
        return $this;
    }

    // --------------------- Les commandes des services ----------------------------

    /**
     * Ajoute un service dans Centreon.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->addService($hostname, $servicename, $template)->command();
     *
     * @param String $hostname
     * @param String $servicedes
     * @param String $servicetmp
     * @return Clapi $this
     */
    function addService($hostname, $servicename, $template) {
        $this->query->setQuery("SERVICE", "ADD", "$hostname;$servicename;$template");
        return $this;
    }

    /**
     * Supprimer un service de Centreon
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->delService($hostname, $servicename)->command();
     *
     * @param String $hostname
     * @param String $servicedes
     * @return Clapi $this
     */
    function delService($hostname, $servicename) {
        $this->query->setQuery("SERVICE", "DEL", "$hostname;$servicename");
        return $this;
    }

    /**
     * Modifier un paramètre d'un service
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->setparamService($hostname, $servicename, $paramname, $paramvalue)->command();
     *
     * @param String $hostname auquel appartient le service
     * @param String $servicename le service en question
     * @param String $paramname le nom du paramètre à modifier
     * @param String $paramvalue la valeur à mettre
     * @return Clapi $this
     */
    function setparamService($hostname, $servicename, $paramname, $paramvalue) {
        $this->query->setQuery("SERVICE", "SETPARAM", "$hostname;$servicename;$paramname;$paramvalue");
        return $this;
    }

    /**
     * Activer un service.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->enableService($hostname, $servicename)->command();
     *
     * @param String $hostname auquel appartient le service
     * @param String $servicename à activer
     * @return Clapi $this
     */
    function enableService($hostname, $servicename) {
        $this->query->setQuery("SERVICE", "ENABLE", "$hostname;$servicename");
        return $this;
    }

    /**
     * 
     * @param type $contactgroup
     * @param type $contact
     */
    function delContactfromContactGroup($contactgroup, $contact) {
        $this->query->setQuery("CG", "DELCONTACT", "$contactgroup;$contact");
        return $this;
    }

    /**
     * Desactiver un service.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->disableService($hostname, $servicename)->command();
     *
     * @param String $hostname
     * @param String $servicename
     * @return Clapi $this
     */
    function disableService($hostname, $servicename) {
        $this->query->setQuery("SERVICE", "DISABLE", "$hostname;$servicename");
        return $this;
    }

    /**
     * Ajouter un contact pour un service.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->addContact($hostname, $servicename, $contact)->command();
     *
     * @param String $hostname
     * @param String $servicename
     * @param String $contact à ajouter
     * @return Clapi $this
     */
    function addContacttoService($hostname, $servicename, $contact) {
        $this->query->setQuery("SERVICE", "ADDCONTACT", "$hostname;$servicename;$contact");
        return $this;
    }

    /**
     * Modifier le contact d'un service
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->setContacttoService($hostname, $servicename, $contact)->command();
     *
     * @param string $hostname
     * @param string $servicename
     * @param string $user
     * @return Clapi $this
     */
    function setContacttoService($hostname, $servicename, $contact) {
        $this->query->setQuery("SERVICE", "SETCONTACT", "$hostname;$servicename;$contact");
        return $this;
    }

    /**
     * Ajouter le contact group d'un service
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->addContactGrouptoService($hostname, $servicename, $contact)->command();
     * 
     * @param string $hostname
     * @param string $servicename
     * @param string $contactgroup
     * @return Clapi $this  ADDCONTACTGROUP
     */
    function addContactGrouptoService($hostname, $servicename, $contactgroup) {
        $this->query->setQuery("SERVICE", "ADDCONTACTGROUP", "$hostname;$servicename;$contactgroup");
        return $this;
    }

    /**
     * Modifier le contact d'un service
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->setContactGrouptoService($hostname, $servicename, $contact)->command();
     *
     * @param string $hostname
     * @param string $servicename
     * @param string $contactgroup
     * @return Clapi $this  SETCONTACTGROUP
     */
    function setContactGrouptoService($hostname, $servicename, $contactgroup) {
        $this->query->setQuery("SERVICE", "SETCONTACTGROUP", "$hostname;$servicename;$contactgroup");
        return $this;
    }

    /**
     * Retourne les service en fonction: d'un pattern.
     *
     * L'ensemble des commandes clapi doivent s'appeler de cette manière :
     *   $clapi->showService($pattern)->command();
     *
     * @param String $pattern, le pattern de recherche.
     * @return Clapi $this
     */
    function showService($pattern) {
        $this->query->setQuery("SERVICE", "SHOW", $pattern);
        return $this;
    }

    // --------------------- Les commandes des contact et contact groupe ----------------------------

    /**
     * Retourne les ContactGroup en fonction: d'un pattern.
     * @param String $pattern le pattern de recherche.
     * @return \Clapi $this
     */
    function showContactGroup($pattern) {
        $this->query->setQuery("CG", "SHOW", $pattern);
        return $this;
    }

    /**
     * Retourne les Contact en fonction
     * @return \Clapi $this
     */
    function showContact($pattern = "") {
        $this->query->setQuery("CONTACT", "SHOW", $pattern);
        return $this;
    }

    /**
     * Ajouter un contact
     * @param string $name Nom de contact 
     * @param string $alias Alias de contact
     * @param string $email E-mail contact
     * @param string $password Mot de passe de contacts
     * @param int $admin 1 (admin) ou 0 (non admin)
     * @param int $gui 1 (peut accéder à l'interface utilisateur) ou 0 (ne peut pas accéder à l'interface utilisateur)
     * @param string $language Langue doit être installé sur Centreon
     * @param string $authentype locale ou ldap
     * @return \Clapi $this
     */
    function addContact($name, $alias, $email, $password = "", $admin = 0, $gui = 0, $language = "fr_FR.UTF-8", $authentype = "local") {
        $this->query->setQuery("CONTACT", "ADD", "$name;$alias;$email;$password;$admin;$gui;$language;$authentype");
        return $this;
    }

    /**
     * Pour voir la liste d'un contact de groupe de contact
     * @param string $name nom de contact group
     * @return \Clapi $this
     */
    function get_contact($name) {
        $this->query->setQuery("CG", "GETCONTACT", $name);
        return $this;
    }

    /**
     * Modifier un paramètre d'un contact
     * @param string $name nom de cpntact
     * @param string $paramname le nom du paramètre à modifier
     * @param string $paramvalue la valeur à mettre
     * @return \Clapi $this
     */
    function setparamContact($name, $paramname, $paramvalue) {
        $this->query->setQuery("CONTACT", "SETPARAM", "$name;$paramname;$paramvalue");
        return $this;
    }

    /**
     * Ajouter un contact groupe
     * @param string $name nom de contact groupe
     * @param string $alias alias de contact groupe
     * @return \Clapi $this
     */
    function addContactGroup($name, $alias) {
        $this->query->setQuery("CG", "ADD", "$name;$alias");
        return $this;
    }

    /**
     * supprimer un contact group 
     * @param string $name nom de contact groupe
     * @return \Clapi $this
     */
    function delContactGroup($name) {
        $this->query->setQuery("CG", "DEL", "$name");
        return $this;
    }

    /**
     * supprimer un contact 
     * @param string $name alias de contact
     * @return \Clapi $this
     */
    function delContact($name) {
        $this->query->setQuery("CONTACT", "DEL", "$name");
        return $this;
    }

    /**
     * deactive un contact 
     * @param string $name alias de contact
     * @return \Clapi $this
     */
    function disableContact($name) {
        $this->query->setQuery("CONTACT", "DISABLE", "$name");
        return $this;
    }

    /**
     * active un contact 
     * @param string $name alias de contact
     * @return \Clapi $this
     */
    function enableContact($name) {
        $this->query->setQuery("CONTACT", "ENABLE", "$name");
        return $this;
    }

    /**
     * ajouter un contact dans un contact groupe
     * @param string $cgname nom de contact groupe
     * @param string  $cname nom de contact
     * @return \Clapi
     */
    function addContactGroupRelation($cgname, $cname) {
        $this->query->setQuery("CG", "ADDCONTACT", "$cgname;$cname");
        return $this;
    }

    // --------------------- Les commandes de Pollers ----------------------------

    function getHosts($poller) {
        $this->query->setQuery("INSTANCE", "GETHOSTS", "$poller");
        return $this;
    }

    /**
     * Exécute la commande clapi.
     *
     * Soit on la stocke dans un fichier et on l'exécutera au submit
     * (mode=2), soit on l'exécute tout de suite (mode=1).
     *
     * Par défaut, on prend la valeur de $this->mode mais les
     * commandes interactive force le mode=1
     */
    public function command() {
        $mode = $this->mode;
        // On valide que la requête est bien initialisée, sinon on quite
        if (!$this->query->isEnable()) {
            Log::i()->error(self::$me, "La requêtes n'est pas initialisée, command() n'est pas bien appellée...");
            return null;
        }
        if ($this->query->isInteractive()) {
            $mode = 1;
            Log::i()->debug(self::$me, "Commande Clapi interactive, on force le mode à 1");
        }
        switch ($mode) {
            case 1:
                $this->execNow($this->query->getInteractiveQuery());
                break;
            case 2:
                $this->execLater($this->filename, $this->query->getQuery());
                break;
            case 'test'://mode test,ne lance pas clapi
                break;
            default:
                Log::i()->error(self::$me, "la valeur de mode(=" . $mode . ") n'est pas valide");
                $v = $this->get_query()->getValues();
                Log::i()->error(self::$me, "la commande " . $v['type'] . " " . $v['operation'] . " " . $v['args'] . " ne sera pas exécutée");
                break;
        }
        $this->query->disable();
    }

    protected function execLater($filename, $command) {
        //array_push($this->append_cmd, $cmd);
        // Peux mieux faire ici pour le traitement des erreurs
        file_put_contents($filename, $command, FILE_APPEND);
        // Par défaut on dit que c'est ok. sinon, ça sera écraser dans set_logs()
        array_push($this->commands, array("command" => trim($command), "result" => "ok"));
    }

    /*
     * Execution de la commande clapi distante.
     *
     * On passe en paramètre la commande rééle et complète, c'est à
     * dire un truc du style :
     *   ssh root@centreon.srv '/path/to/clapi -u admin -p p@a55 -o SERVICE -a ENABLE -v "telephone-8828;mos-8828-542"'
     *
     * La commande doit donc être construite avant l'appel. Ensuite,
     * on fait le traitement d'erreur et on stock le résultat dans
     * $this->data pour les traitements utérieurs.
     *
     * Le résutat est un csv avec le retour de la commande (si ça ce
     * passe bien). Sinon, un affichage est prévu dans
     * $this->srv_return()
     */

    public function execNow($clapi_cmd) {
        Log::i()->debug(self::$me, "exec : " . $clapi_cmd);
        $this->shell->remoteExec($clapi_cmd, $info, $return_var);
        $this->srv_return($info, $return_var);
        $this->data = $info;
    }

    /**
     * Commit de la requête Clapi sur le serveur Centreon.
     *
     * Pour cela, on pousse le fichier pré-alablement écrit en local
     * et ensuite on l'exécute.
     *
     * @todo: Ici, il y a un gros travail de gestion d'erreur à
     * faire. Est ce que le fichier existe ? Est ce que le scp se
     * passe bien ? Est ce que la commande clapi distante se passe
     * bien...
     */
    public function commit() {
        if ($this->mode == 2) {
            Log::i()->debug(self::$me, "Commit des commandes...");
            // copie du fichier sur le serveur Centreon
            $localfile = $this->filename;
            $remotefile = $this->filename;
            $this->shell->copy($localfile, $remotefile, $info, $return_var);
            // Execution de ce fichier par clapi
            $this->shell->remoteExec($this->query->getPath() . " -i " . $remotefile, $info, $return_var);
            $this->data = $info;
            $this->set_logs();
            Log::i()->debug(self::$me, "Commit fait.");
            return $this->return_msg;
        } else {
            Log::i()->info(self::$me, "pas de commit, clapi configuré en mode=" . $this->mode);
            return False;
        }
    }

    /**
     * Si il y a les retour sur clapi, on les récupérer
     */
    function set_logs() {
        foreach ($this->data as $value) {
            if (empty($value)) {
                continue;
            }
            $array = preg_split("/ /", $value);
            $l = $array[1];
            //traitement le string,pour plus visible
            $re = preg_replace("/^Line[\s\d+\s]*:[\s]/", "", $value);
            $this->commands[$l - 1]["result"] = $re;
            array_push($this->return_msg, array("command" => $this->commands[$l - 1]["command"], "result" => $re));
        }
    }

    /*
     * Traitement du retour de clapi
     *
     * Lors de l'exécution d'une commande clapi, le serveur Centreon
     * retourne un message d'erreur, Cette méthode permet de le
     * tester.
     *
     * info = ( 0 => "message pour la première ligne",
     *          1 => "message pour la deuxième ligne",
     *          2 => "message pour la troisième ligne",)
     *
     * exit_code : 0 si c'est ok, 1 sinon.
     *
     * @param array $info à l'index 0 contient le message de retour à analyser.
     * @param $return_var :
     */

    protected function srv_return($info, $return_var) {
        switch ($return_var) {
            case 0:
                foreach ($info as $inf) {
                    Log::i()->debug(self::$me, "Return: " . $inf);
                }
                break;
            default:
                Log::i()->error(self::$me, "" . $info[0]);
                break;
        }
    }

}
