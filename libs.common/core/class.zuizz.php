<?php
class ZUIZZ
{
    public $config; //config holder
    public $tools; // external system tools
    public $feature = array();
    public $perm;
    public $logger;
    public $lang;
    public $data = array(); // Datacontainer
    private $auth;

    function __construct()
    {
        //  session
        session_cache_limiter('no_cache'); // for ie
        session_start();

        //	set_error_handler ( "ZU::simple_error_handler" );
        //set_exception_handler( "ZU::simple_exceptivon_handler" );
        // Build config sub object
        ZU::load_class('config', 'core');


        $this->config = new CONFIG (parse_ini_file(ZU_DIR_CONFIG . getenv('sysmode') . '.main.config.ini', TRUE));
        $this->tools = new CONFIG (parse_ini_file(ZU_DIR_CONFIG . getenv('sysmode') . '.tools.config.ini', TRUE));

        //Universal Config
        $UC = json_decode(getenv('main.config'), true);
        if (is_array($UC)) {
            foreach ($UC as $k => $segment) {
                if(isset($this->config->$k)){
                    $this->config->$k = ZU::array_merge_recursive_distinct($this->config->$k, $segment);
                }else{
                    $this->config->$k = $segment;
                }
            }
        }


        // start output buffer
        if ($this->config->system ['use_compression']) {
            ob_start();
        }
        // set timezone
        date_default_timezone_set($this->config->locale ['timezone']);
        setlocale(LC_TIME, $this->config->locale ['LC_TIME']);

        // idiorm
        ZU::load_class('idiorm', 'core');

        ORM::configure('mysql:host=' . $this->config->db ['hostname'] . ";dbname=" . $this->config->db ['database'] . ";port=" . $this->config->db ['port'] . '"');
        ORM::configure('username', $this->config->db ['username']);
        ORM::configure('password', $this->config->db ['password']);
        ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));


        // load and enable logger class
        ZU::load_class('zuizz.logger', 'core');
        $this->logger = new ZULOG ();

        // load feature class
        ZU::load_class('zuizz.feature', 'core');

    }

    public function init_lang()
    {

        // load and enable lang class
        ZU::load_class('zuizz.lang', 'core');
        $this->lang = new ZULANG ();
        if (isset ($_REQUEST ['ZU_set_lang'])) {
            $this->lang->set_lang($_REQUEST ['ZU_set_lang']);

        }
    }

    public function init_permissions()
    {

        // logout
        if (isset ($_REQUEST ['ZU_logout'])) {
            // delete all session data
            $_SESSION = array();
            //unset session cookie
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params["path"],
                    $params["domain"], $params["secure"], $params["httponly"]
                );
            }

            session_destroy();

        }

        // Perm ermitteln wenn nicht schon in der session vorhanden ist, Klasse wegen ev. statischer function laden
        ZU::load_class($this->config->system ['perm_mechanism'], 'auth');
        $this->perm = new ZUPERM ();
        if (!isset ($_SESSION ['ZUIZZ'] ['PERM'])) {
            $this->perm->init();
        }
    }

    public function init_smarty()
    {
        ZU::load_class("Smarty.class", "smarty", TRUE);
        ZU::load_class("zuizz.smarty", "core", FALSE);
        $GLOBALS ['smarty'] = new ZUsmarty ();
        if (isset ($_SESSION ['ZUIZZ'] ['smarty'] ['permanent'])) {
            foreach ($_SESSION ['ZUIZZ'] ['smarty'] ['permanent'] as $var => $val) {
                ZU::assign($var, $val);
            }
        }
    }

    // load and process a feature and its methods
    public function create_feature_objects($parameter)
    {
        $feature_array = explode(".", $parameter ['feature']);
        $feature = "{$feature_array[0]}.{$feature_array[1]}.{$feature_array[2]}";
        $view = implode("/", array_slice($feature_array, 3));

        // erstelle object für feature wenn nicht vorhanden
        if (!isset ($this->feature [$feature])) {
            $this->init_feature($feature);
        }
        // view ausführen  und puffern
        $this->feature [$feature]->process_view($view, $parameter);

    }

    // load and process a json feature and its methods
    public function create_json_feature_objects($parameter)
    {
        $feature_array = explode(".", $parameter ['feature']);
        $feature = "{$feature_array[0]}.{$feature_array[1]}.{$feature_array[2]}";
        $view = implode("/", array_slice($feature_array, 3));

        // erstelle object für feature wenn nicht vorhanden
        if (!isset ($this->feature [$feature])) {
            $this->init_feature($feature);
        }
        // view ausführen  ausgeben
        if ($view != "") {
            return $this->feature [$feature]->process_json($view);
        } else {
            return $this->feature [$feature]->contentbuffer;
        }

    }

    // load and process REST feature and its methods
    public function create_rest_feature_objects($parameter)
    {
        $feature_array = explode(".", $parameter ['feature']);
        $feature = "{$feature_array[0]}.{$feature_array[1]}.{$feature_array[2]}";
        $rest = implode("/", array_slice($feature_array, 3));

        // erstelle object für feature wenn nicht vorhanden
        //if (! isset ( $this->feature [$feature] )) {
        $this->init_feature($feature, 'rest');
        //}

        // method ausführen  ausgeben
        if ($rest != "") {
            return $this->feature [$feature]->process_rest($rest, $parameter);
        }

    }

    // load and process cli feature and its methods
    public function create_cli_feature_objects($parameter)
    {
        $feature_array = explode(".", $parameter ['feature']);
        $feature = "{$feature_array[0]}.{$feature_array[1]}.{$feature_array[2]}";
        $cli = implode("/", array_slice($feature_array, 3));

        // erstelle object für feature wenn nicht vorhanden
        if (!isset ($this->feature [$feature])) {
            $this->init_feature($feature);
        }
        // view ausführen  ausgeben
        if ($cli != "") {
            return $this->feature [$feature]->process_cli($cli);
        } else {
            return $this->feature [$feature]->contentbuffer;
        }

    }

    // load and process a ajax feature and its methods
    public function create_ajax_feature_objects($parameter)
    {
        $feature_array = explode(".", $parameter ['feature']);
        $feature = "{$feature_array[0]}.{$feature_array[1]}.{$feature_array[2]}";
        $view = implode("/", array_slice($feature_array, 3));

        // erstelle object für feature wenn nicht vorhanden
        if (!isset ($this->feature [$feature])) {
            $this->init_feature($feature);
        }
        // view ausführen  ausgeben
        if ($view != "") {
            return $this->feature [$feature]->process_view($view, $parameter, true);
        } else {
            return $this->feature [$feature]->contentbuffer;
        }

    }

    // feature initialisieren ohne einen View aufzurufen
    public function init_feature($feature, $mode = false)
    {
        $this->feature [$feature] = new ZUFEATURE ($feature, $mode);

    }

    function __destruct()
    {
        if ($this->config->system ['use_compression']) {
            // flus buffer
            ob_end_flush();
        }
    }

}

?>