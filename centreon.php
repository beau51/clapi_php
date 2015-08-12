<?php

/*
 * Chercher les valeur(host de baie et service de baie)
 * sur Centreon
 */
//require_once(ROOT."/lib/clapi.php");
//require_once(ROOT."/lib/log.php");
require_once __DIR__ . '/../lib/clapi.php';
require_once __DIR__ . '/../lib/log.php';

class Centreon {
    /* La configuration lu et stocké dans un simple dictionnaire */

    public $config;

    /* Pour avoir les bonnes infos dans les logs */
    private static $me = "CENTREON";

    /*
     * L'ensemble des clients présents dans Centreon (filtrer par type
     * d'host). On retourne une structure du type :
     *
     * { bu: {'hostname'=> $hostname, 'clientname'=> $clientname },
     *   bu: {'hostname'=> $hostname, 'clientname'=> $clientname } }
     *
     * Attention, nous partons du principe que pour un type données
     * (vpn, mos, baie, etc) il n'y aura pas plusieurs hote par bu.
     */
    public $clients;

    function __construct($clapi, $config) {
        $this->clapi = $clapi;
        $this->config = $config;
        $this->clients = array();
    }

    /*
     * Chargement des données par défaut (c'est à dire en utilisant
     * les prefix prédéfinies.
     *
     * Deux appels sont fait :
     * - load_hosts avec $this->prefix_host en paramètre
     * - load_services avec $this->prefix_service en paramètre
     *
     * A surchargé si on veut un comportement différent.
     */

    public function load() {
        $this->load_hosts($this->prefix_host);
        $this->load_services($this->prefix_service);
        //$this->load_poller();
    }

    /**
     * Voici ce qu'on récupère de Clapi : id;hostname;alias;address;activate
     * Ensuite on le découpe pour le mettre dans une liste
     * - l'hostname de la forme $prefix_host-[bu]
     * - la bu
     * - le nom du client (alias)
     */
    public function initial_load_hosts($prefix) {
        //--Recuperer le list des host dans centreon
        $this->clapi->showHost($prefix)->command();
        foreach ($this->clapi->getData() as $num => $string) {
            //ne prend pas la premier ligne
            if ($num == "0") {
                continue;
            }
            $array = preg_split("/;/", $string);
            $hostname = $array[1];
            $bu = str_replace($prefix, "", $array[1]);
            $clientname = $array[2];
            $activate = $array[4];
            $this->clients[$bu] = array('hostname' => $hostname, 'clientname' => $clientname, 'activate' => $activate);
        }
        return $this->clients;
    }

    /*
     * Charge les services générique de Centreon
     *
     * Voici ce qu'on récupère de Clapy (attention les yeux) :
     *   service id;hostname;host id;description;check command;check command arg;normal check interval;retry check interval;max check attempts;active checks enabled;passive checks enabled;activate
     *
     * sachant que hostname c'est [prefix_host]-[bu]
     *
     * Ici, on souhaite récupérer les informations génériques du
     * service et retourner l'ensemble des args qui contiennent les
     * paramètres spécifiques du service. On stockera l'ensemble de la
     * chaîne à la clef "service_string".
     *
     * Ces paramètres servirons à la construction des objets.
     *
     * @return $services liste de la forme : [ { "servicename": $servicename, "hostname": $hostname,
     *                                           "bu": $bu, "check_command_arg": $check_command_arg),
     *                                           "service_string": $service_string },
     *                                         { "servicename": $servicename, "hostname": $hostname,
     *                                           "bu": $bu, "check_command_arg": $check_command_arg), },
     *                                           "service_string": $service_string }, ]
     */

    public function initial_load_services($prefix) {
        $services = array();
        $this->clapi->showService($prefix)->command();
        foreach ($this->clapi->getData() as $service_string) {
            $array = preg_split("/;/", $service_string);
            $hostname = $array[1];
            // On redécoupe hostname en deux et on garde la
            // deuxième partie pour avoir la bu :
            $val = preg_split("/-/", $hostname);
            if (isset($val[1])) {
                $bu = $val[1];
                $servicename = $array[3];
                $args = $array[5];
                $activate = $array[11];
                // On ajoute le tout à $services
                array_push($services, array('servicename' => $servicename,
                    'hostname' => $hostname,
                    'bu' => $bu,
                    'check_command_arg' => $args,
                    'activate' => $activate,
                    'service_string' => $service_string));
            }
        }
        return $services;
    }

    /*
     * Charger les nombre host par poller
     *
     * Cette méthode doit être surchargée pour chaque Centreon.
     */
//    public function load_poller() {
//        throw new Exception('Not implemented');
//    }

    /**
     * retour touts les hosts préalablement chargés
     *
     * CF la variable $client pour voir la structure
     *
     * @return array $clients
     */
    public function get_hosts() {
        return $this->clients;
    }

    /*
     * Ajout d'un host dans Centreon.
     *
     * On ajoute l'host, on ajoute l'alias en tant que nom client, on
     * ajoute le paramètre address et on rend actif l'hôte.
     *
     * @param $hostname : le nom du host
     * @param $clientname : le nom du client, ça sera ajouté en lieu et place d'aliashostname
     * @param $ip : l'IP de l'hôte
     * @param $template : le template à utiliser. Défaut $this->config['host_template']
     * @param $pooler : le pooler à utiliser. Défaut $this->config['pooler']
     * @param $host_group : le groupe attaché au host. Défaut $this->config['host_group']
     *
     */

    function create_host($hostname, $clientname, $ip, $poller = NULL, $template = NULL, $host_group = NULL) {
        // On charge les valeurs par défaut (si nécessaire)
        if (is_null($template))
            $template = $this->config->getValue('host_template');
        if (is_null($poller))
            $poller = $this->config->getValue('poller');
        if (is_null($host_group))
            $host_group = $this->config->getValue('host_group');
        $this->clapi->addHost($hostname, $clientname, $ip, $template, $poller, $host_group)->command();
    }

    /**
     * on mettre à jour l'alias en tant que nom client, on
     * mettre à jour le paramètre address et on rend actif l'hôte.
     * @param $hostname : le nom du host
     * @param $clientname : le nom du client, ça sera ajouté en lieu et place d'aliashostname
     * @param $ip : l'IP de l'hôte
     */
    function setting_host($hostname, $clientname, $ip, $contact = NULL, $contactgroup = NULL, $notification = "2", $args = NULL) {
        $this->clapi->setparamHost($hostname, "alias", $clientname)->command();
        $this->clapi->setparamHost($hostname, "address", $ip)->command();
        $this->clapi->setparamHost($hostname, "notifications_enabled", $notification)->command();

        if (!is_null($contact)) {
            $this->clapi->setContacttoHost($hostname, $contact)->command();
        }
        if (!is_null($contactgroup)) {
            $this->clapi->setContactGrouptoHost($hostname, $contactgroup)->command();
        }
        if (!is_null($args)) {
            $this->clapi->setparamHost($hostname, "check_command_arguments", $args)->command();
        }
        $this->clapi->enableHost($hostname)->command();
    }

    /**
     * Crée un service
     *
     * C'est fait en 1 étapes :
     * - ajouter un servive
     *
     * @param int $bu l'id du client concerné par ce service
     * @param string $hostname le nom du service - basé sur un prefix, la bu et un différentiant (ip, emplacement baie, etc)
     * @param string $template le template du service
     * @param string $contact un contact à prévenir en cas de nécessité (une @ mail)
     */
    function create_service($hostname, $servicename, $template, $contact = NULL, $contactgroup = NULL) {
        $this->clapi->addService($hostname, $servicename, $template)->command();
    }

    /**
     *  C'est fait en 2 étapes :
     * - y associer une commande et des arguments
     * - l'activer
     * @param string $hostname le nom du service - basé sur un prefix, la bu et un différentiant (ip, emplacement baie, etc)
     * @param string $template le template du service
     * @param string $parameters les paramètres nécessaires pour la réalisation du check
     */
    function setting_service($hostname, $servicename, $parameters, $contact = NULL, $contactgroup = NULL, $notification = "2") {
        $this->clapi->setparamService($hostname, $servicename, $this->config->getValue("parameter_name"), $parameters)->command();
        $this->clapi->setparamService($hostname, $servicename, "notifications_enabled", $notification)->command();
        if (!is_null($contact)) {
            $this->clapi->setContacttoService($hostname, $servicename, $contact)->command();
        }
        if (!is_null($contactgroup)) {
            $this->clapi->addContactGrouptoService($hostname, $servicename, $contactgroup)->command();
        }
        $this->clapi->enableService($hostname, $servicename)->command();
    }

    /*
     * deactivité un sercvice
     */

    public function disable_service($obj) {
        //Log::i()->info(self::$me, "disable service " . $this->servicename($obj) . " - hostname " . $this->hostname($obj));
        return $this->clapi->disableService($this->hostname($obj), $this->servicename($obj))->command();
    }

    /*
     * deactivité un host
     */

    public function disable_host($hostname) {
        return $this->clapi->disableHost($hostname)->command();
    }

    /*
     * Détermine le nom du service en fonction d'$obj passé en paramètre
     *
     * Cette méthode doit être surchargée pour chaque Centreon.
     */

    public function servicename($obj) {
        throw new Exception('Not implemented');
    }

    /*
     * Définie le hostname en fonction de l'objet passé en paramètre.
     *
     * L'objet doit impérativement avoir un attribut $bu accessible (publique donc). Le classique est de retourner :
     *   $this->prefix_host . $obj->bu
     *
     * A surcharger si on souhaite un autre comportement.
     */

    public function hostname($obj) {
        return $this->prefix_host . $obj->bu;
    }

    /**
     * faire liste de host dans la poller 
     * @param type $poller
     * @return type
     */
    public function get_list_host($poller) {
        $this->clapi->getHosts($poller)->command();
        return $this->clapi->getData();
    }
}
