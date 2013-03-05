<?php

class ZUFEATURE
{
    public $feature_id = null;
    public $feature = null;
    //private $_node_perms = array();

    public $permissionset = array();
    public $parameter = array(); // parameter des aktuellen views
    public $identifier; // REST identifier
    public $mimetype; // REST mimetype
    public $config = array();
    public $translations = array();
    public $tables = array();
    public $basedir = null;
    public $skin = 'default';
    public $view = NULL;
    public $feature_lang;
    public $values = array(); // post und get vars
    public $contentbuffer = '';
    public $post = array(); // post vars
    public $get = array(); // get vars
    public $files = array();
    public $data = array(); // Daten die im Feature abgelegt werden und in allen views verfügbar sind
    public $object = array(); // Objekte
    private $viewmode = "web"; // Viewmode rest, web, ...
    public $session; // link von $this->session auf $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id][sessionvalues]


    function __construct($feature, $mode)
    {
        $this->basedir = ZU_DIR_FEATURE . $feature . '/';
        if (!is_dir($this->basedir)) {
            echo $this->basedir . " does not exist";
        }
        // alle feature an ZU assignen
        global $ZU_FEATURE;
        $GLOBALS ['ZU_FEATURE'] ['ZU_NOW'] = ZU_NOW;

        if (isset ($GLOBALS ['smarty'])) {
            $GLOBALS ['smarty']->assignByRef("ZU", $GLOBALS ['ZU_FEATURE']);
            // defaults for features
            $GLOBALS ['ZU_FEATURE'] ['DEFAULTS'] ['date_format'] = $GLOBALS ['ZUIZZ']->config->locale ['smarty_date_format'];
            $GLOBALS ['ZU_FEATURE'] ['SESSION']                  = & $_SESSION ['ZUIZZ'];


        }

        $this->feature = $feature;

        // config einlesen
        $this->config = parse_ini_file($this->basedir . 'configs/config.ini', TRUE);

        // Ueberladen der config
        if (is_file(ZU_DIR_CUSTOM_CONFIGS . getenv('sysmode') . "." . $this->feature . '.config.ini')) {
            $tmpconf = parse_ini_file(ZU_DIR_CUSTOM_CONFIGS . getenv('sysmode') . "." . $this->feature . '.config.ini', TRUE);
            foreach ($this->config as $key => $value) {
                if (isset ($tmpconf [$key])) {
                    $this->config [$key] = array_merge($this->config [$key], $tmpconf [$key]);
                    unset ($tmpconf [$key]);
                }
            }
            $this->config = array_merge($this->config, $tmpconf);
        }

        // read additional ini files
        if (isset ($this->config ['additional_ini_files'])) {
            foreach ($this->config ['additional_ini_files'] as $key => $file) {
                $this->config [$key] = parse_ini_file($this->basedir . "configs/{$file}", TRUE);
            }
        }
        $this->feature_id = $this->config ['feature'] ['feature_id'] [0];

        // sprache holen um referenz zu setzen
        ZU::get_feature_lang($this->feature_id);
        $this->feature_lang = & $_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] [$this->feature_id];

        // Klassendateien laden
        if (isset ($this->config ['object'])) {
            foreach ($this->config ['object'] as $key => $value) {
                if (!isset ($this->object [$value]) && is_file(ZU_DIR_FEATURE . "{$this->feature}/libs/{$value}.php")) {
                    include ZU_DIR_FEATURE . "{$this->feature}/jslibs/{$value}.php";
                }
            }
        }

        // Wichtige werte cachen
        if (!isset ($_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['name'])) {
            $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['name'] = $feature;
            if (isset ($this->config ['model'] ['model'])) {
                $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['model'] = $this->config ['model'] ['model'];
                $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['model_type'] = $this->config ['model'] ['type'];
            }

            if (isset ($this->config ['tree'] ['enabled']) && $this->config ['tree'] ['enabled']) {
                $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['tree_table']      = $this->config ['tree'] ['table'];
                $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['tree_identifier'] = $this->config ['tree'] ['identifier'];
                $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['tree_root']       = $this->config ['tree'] ['root'];
                $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['tree']            = true;
            } else {
                $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id] ['tree'] = false;
            }
        }

        // link von $this->session auf $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id][sessionvalues]
        if (!isset($_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id]['sessionvalues'])) {
            $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id]['sessionvalues'] = array();
        }
        $this->session = & $_SESSION ['ZUIZZ'] ['FEATURES'] [$this->feature_id]['sessionvalues'];

        // post und gets für das feature entgegennehmen (get überschreibt bei request den post)
        // Post vars
        if (isset ($_POST)) {

            foreach ($_POST as $key => $value) {

                if (strpos($key, "f{$this->feature_id}") !== FALSE) {
                    $feature_values [substr($key, strlen("f{$this->feature_id}"))]      = $value;
                    $feature_post_values [substr($key, strlen("f{$this->feature_id}"))] = $value;
                } else {
                    if ($mode == 'rest') {
                        $feature_values [$key]      = $value;
                        $feature_post_values [$key] = $value;
                    }
                }
            }
            if (isset ($feature_post_values)) {
                $this->post = $feature_post_values;
            }
        }
        // CLI vars
        if (isset ($GLOBALS ['_CLI'])) {
            foreach ($GLOBALS ['_CLI'] as $key => $value) {

                if (strpos($key, "f{$this->feature_id}") !== FALSE) {
                    $feature_values [substr($key, strlen("f{$this->feature_id}"))]      = $value;
                    $feature_post_values [substr($key, strlen("f{$this->feature_id}"))] = $value;
                } else {
                    $feature_values [$key]     = $value;
                    $feature_cli_values [$key] = $value;
                }

            }
            if (isset ($feature_cli_values)) {
                $this->cli = $feature_cli_values;
            }
        }

        // Get vars
        if (isset ($_GET)) {
            foreach ($_GET as $key => $value) {
                if (strpos($key, "f{$this->feature_id}") !== FALSE) {
                    $feature_values [substr($key, strlen("f{$this->feature_id}"))]     = $value;
                    $feature_get_values [substr($key, strlen("f{$this->feature_id}"))] = $value;
                } else {
                    if ($mode == 'rest') {
                        $feature_values [$key]     = $value;
                        $feature_get_values [$key] = $value;
                    }
                }
            }

            if (isset ($feature_get_values)) {
                $this->get = $feature_get_values;
            }
        }


        // PUT
        if (isset ($GLOBALS['_PUT'])) {
            foreach ($GLOBALS['_PUT'] as $key => $value) {
                if (strpos($key, "f{$this->feature_id}") !== FALSE) {
                    $feature_values [substr($key, strlen("f{$this->feature_id}"))]     = $value;
                    $feature_put_values [substr($key, strlen("f{$this->feature_id}"))] = $value;
                } else {
                    if ($mode == 'rest') {
                        $feature_values [$key]     = $value;
                        $feature_put_values [$key] = $value;
                    }
                }
            }
            if (isset ($feature_put_values)) {
                $this->put = $feature_put_values;
            }
        }

        // DELETE
        if (isset ($GLOBALS['_DELETE'])) {
            foreach ($GLOBALS['_DELETE'] as $key => $value) {
                if (strpos($key, "f{$this->feature_id}") !== FALSE) {
                    $feature_values [substr($key, strlen("f{$this->feature_id}"))]        = $value;
                    $feature_delete_values [substr($key, strlen("f{$this->feature_id}"))] = $value;
                } else {
                    if ($mode == 'rest') {
                        $feature_values [$key]        = $value;
                        $feature_delete_values [$key] = $value;
                    }
                }
            }
            if (isset ($feature_delete_values)) {
                $this->delete = $feature_delete_values;
            }
        }


        // Get files
        if (isset ($_FILES)) {
            foreach ($_FILES as $key => $value) {
                if (strpos($key, "f{$this->feature_id}") !== FALSE && $value ["error"] === 0) {
                    $feature_values [substr($key, strlen("f{$this->feature_id}"))]      = $value;
                    $feature_file_values [substr($key, strlen("f{$this->feature_id}"))] = $value;
                } else {
                    if ($mode == 'rest') {
                        $feature_values [$key]      = $value;
                        $feature_file_values [$key] = $value;
                    }
                }
            }
            if (isset ($feature_file_values)) {
                $this->files = $feature_file_values;
            }
        }
        // request füllen
        if (isset ($feature_values)) {
            $this->values = $feature_values;
        }

        // rechtepackete einlesen / cachen
        // array besteht aus Methode => bit
        if ($GLOBALS ['ZUIZZ']->config->system ['devel_mode'] || !isset ($_SESSION ['ZUIZZ'] ['PERMISSIONSET'] [$this->feature_id])) {
            if (is_file($this->basedir . 'configs/permission.ini')) {
                foreach (parse_ini_file($this->basedir . 'configs/permission.ini', TRUE) as $section) {
                    if (isset($section ['method'])) {
                        foreach ($section ['method'] as $method) {
                            $this->permissionset [$method] = $section ['bit'];

                        }
                    }
                }
            }
            $_SESSION ['ZUIZZ'] ['PERMISSIONSET'] [$this->feature_id] = $this->permissionset;
        } else {
            $this->permissionset = & $_SESSION ['ZUIZZ'] ['PERMISSIONSET'] [$this->feature_id];
        }

        // translation files einlesen
        $this->load_translation_file($this->feature_lang);
    }

    function load_translation_file($lang)
    {
        if (is_file($this->basedir . 'configs/translation.' . $lang . '.ini')) {
            $this->translations = parse_ini_file($this->basedir . 'configs/translation.' . $lang . '.ini', TRUE);
        }

        // Ueberladen der translation
        if (getenv('sysmode') != '' && is_file($this->basedir . 'configs/' . getenv('sysmode') . '.translation.' . $lang . '.ini')) {
            $tmpconf = parse_ini_file($this->basedir . 'configs/' . getenv('sysmode') . '.translation.' . $lang . '.ini', TRUE);
            foreach ($this->translations as $key => $value) {
                if (isset ($tmpconf [$key])) {
                    $this->translations [$key] = array_merge($this->translations [$key], $tmpconf [$key]);
                    unset ($tmpconf [$key]);
                }
            }
            $this->translations = array_merge($this->translations, $tmpconf);
        }
    }



    /**
     * SKIN Template Fetchen
     * feature werte werden assigned und das template dir auf den entsprechende pfad gesetzt
     */
    function fetch()
    {

        $GLOBALS ['ZU_FEATURE'] [$this->feature_id] = (array("feature"      => $this->feature,
                                                             "feature_lang" => $this->feature_lang,
                                                             "view"         => basename($this->view),
                                                             "parameter"    => $this->parameter,
                                                             "translations" => $this->translations,
                                                             "data"         => $this->data,
                                                             "session"      => $this->session,
                                                             "config"       => $this->config,
                                                             "values"       => $this->values,
                                                             "get"          => $this->get,
                                                             "post"         => $this->post));
        $GLOBALS ['smarty']->assignByRef("ZU_feature", $GLOBALS ['ZU_FEATURE'] [$this->feature_id]);
        $GLOBALS ['smarty']->assign("ZU_view", array("feature"      => $this->feature,
                                                     "feature_lang" => $this->feature_lang,
                                                     "name"         => basename($this->view),
                                                     "feature_id"   => $this->feature_id,
                                                     "feature"      => $this->feature));
        set_error_handler("ZU::smarty_error_handler");

        if (is_file(ZU_DIR_CUSTOM_SKINS . "/{$this->viewmode}.{$this->feature}.{$this->view}/{$this->skin}.skin")) {
            $this->contentbuffer .= $GLOBALS ['smarty']->fetch(ZU_DIR_CUSTOM_SKINS . "/{$this->viewmode}.{$this->feature}.{$this->view}/{$this->skin}.skin");
        } else {

            if (is_file("{$this->basedir}skins/{$this->viewmode}/{$this->view}/{$this->skin}.skin")) {
                $this->contentbuffer .= $GLOBALS ['smarty']->fetch("{$this->basedir}skins/{$this->viewmode}/{$this->view}/{$this->skin}.skin");
            } else {
                ZU::log("Skin does not exists: {$this->basedir}skins/{$this->viewmode}/{$this->view}/{$this->skin}.skin");
            }

        }
        restore_error_handler();

    }

    /**
     * Tabelle mit prefix ausgeben
     */
    function get_table($table)
    {
        return $GLOBALS ['ZUIZZ']->config->db ['prefix'] . $this->config ['tables'] [$table];
    }

    /**
     * parameter zurückgeben
     */
    function get_param($param)
    {
        if (isset ($this->parameter [$param])) {
            return $this->parameter [$param];
        } else {
            return NULL;
        }
    }

    /**
     * alle Views abarbeiten
     */
    function process_view($view, $parameter, $direct = FALSE)
    {
        $this->contentbuffer = "";
        $this->view          = $view;
        if (isset ($parameter ['skin'])) {
            $this->skin = $parameter ['skin'];
        } else {
            $this->skin = 'default';
        }
        $this->parameter = $parameter;

        // view laden und ausführen
        if (is_file(ZU_DIR_FEATURE . "{$this->feature}/views/{$this->view}/index.php")) {
            require ZU_DIR_FEATURE . "{$this->feature}/views/{$this->view}/index.php";
        } else {
            $this->fetch();
            //ZU::log("feature {$this->feature}.{$view} is  not available" . ZU_DIR_FEATURE . "{$this->feature}/views/{$this->view}/index.php");
        }

        // Ausgabe Puffern wenn nicht direkt verlangt wird
        if (!$direct) {
            if (isset ($GLOBALS ['buffer'] ['zonecontent'] [$parameter ['zone']])) {
                $GLOBALS ['buffer'] ['zonecontent'] [$parameter ['zone']] .= $this->contentbuffer;
            } else {
                $GLOBALS ['buffer'] ['zonecontent'] [$parameter ['zone']] = $this->contentbuffer;
            }
        } else {

            return $this->contentbuffer;
        }
        return false;
    }


    /**
     * REST
     */
    function process_rest($rest, $parameter)
    {

        $parameter;
        $method = strtolower($_SERVER ['REQUEST_METHOD']);
        switch ($method) {
            case "get":
                $methodkey = 0;
                break;
            case "head":
                $methodkey = 1;
                break;
            case "put":
                $methodkey = 2;
                break;
            case "delete":
                $methodkey = 3;
                break;
            case "post":
                $methodkey = 4;
                break;
            case "options":
                $methodkey = 5;
                break;
            case "trace":
                $methodkey = 6;
                break;
            case "connect":
                $methodkey = 7;
                break;
            default:
                $methodkey = 0;
                break;
        }

        if (isset ($_REQUEST ['ZU_identifier'])) {
            $this->values['identifier'] = $_REQUEST ['ZU_identifier'];
            if ($this->values['identifier'] == '') {
                $this->values['identifier'] = NULL;
            }
            $this->identifier =& $this->values['identifier'];

        }

        $this->view = $rest . "/" . $method;


        $apirequest = $this->feature . "." . str_replace("/", ".", $rest);

        if (isset ($_REQUEST ['ZU_mimetype'])) {
            $this->mimetype = $_REQUEST ['ZU_mimetype'];
        } else {
            $this->mimetype = FALSE;
        }


        if ($method == "options") {
            foreach (glob(ZU_DIR_FEATURE . "{$this->feature}/rest/{$rest}/*", GLOB_ONLYDIR) as $allowed) {
                if (is_file($allowed . "/index.php")) {
                    $options[] = strtoupper(basename($allowed));
                }
            }
            $options[] = "OPTIONS";
            header("Allow: " . implode(", ", $options));
            $doc['implemented'] = implode(", ", $options);
            // Keine Doku vorhanden
            if (count($doc) == 0) {
                header("HTTP/1.0 404 Documentation not found");
                echo ("Dokumentation for feature {$apirequest} is not available or not implemented");
                die ();

            }
            switch ($this->mimetype) {
                case "json":
                    header('Content-type: application/json');
                    echo json_encode($doc);
                    break;
                case "xml":
                    header('Content-type: application/xml');
                    ZU::load_class('lalit.array2xml', 'xml', true);
                    $xml = Array2XML::createXML('auth', $doc);
                    echo $xml->saveXML();
                    break;
                default:
                    ZU::print_array($doc);
                    break;
            }
            die();
        }

        if (is_file(ZU_DIR_FEATURE . "{$this->feature}/rest/{$this->view}/index.php")) {
        } else {
            ZU::header(405);
            echo ("Method " . strtoupper($method) . " for feature {$this->feature}.{$rest} is not available or not implemented, try {$this->feature} with method OPTIONS for more information or ask the nerd nextdoor");
            die ();
        }


        $missing    = array();
        $invalid    = array();
        $keepValues = array();

        // Dokumentation laden


        // ist Dokumentation vorhanden, wenn nicht wird dies als nicht implementiert betrachtet
        if (!is_file(ZU_DIR_FEATURE . "{$this->feature}/rest/{$this->view}/doc.json")) {
            header("HTTP/1.0 501 Not Implemented");
            echo ("Feature {$apirequest} is not implemented, rst_apidoc is missing");
            die ();
        } else {
            $apidoc = json_decode(file_get_contents(ZU_DIR_FEATURE . "{$this->feature}/rest/{$this->view}/doc.json"));
        }

        foreach ($apidoc->parameter as $name => $tmp) {

            $keepValues[] = $tmp->name;
            // kommen die required Werte mit der richtigen Methode Identifier ist ein reserviertes wort


            if ($tmp->name != 'identifier') {
                switch ($method) {
                    case 'delete':
                        if ($tmp->required == 1 && !isset($this->delete[$tmp->name])) {
                            $missing[] = $tmp->name;
                        }
                        break;
                    case 'put':

                        if ($tmp->required == 1 && !isset($this->put[$tmp->name])) {
                            $missing[] = $tmp->name;
                        }
                        break;
                    case 'post':
                        if ($tmp->required == 1 && !isset($this->post[$tmp->name])) {
                            $missing[] = $tmp->name;
                        }
                        break;

                    case 'get':
                        if ($tmp->required == 1 && !isset($this->get[$tmp->name])) {
                            $missing[] = $tmp->name;
                        }
                        break;

                    default:
                        if ($tmp->required == 1 && !isset($this->values[$tmp->name])) {
                            $missing[] = $tmp->name;
                        }
                        break;

                }

                // werte die nicht required sind  und nicht übermittelt wurden als NULL registrieren
                if ($tmp->required == 0 && !isset($this->values[$tmp->name])) {
                    $this->values[$tmp->name] = NULL;
                }

            }
            if (!isset($this->values[$tmp->name]) && $tmp->default_value != NULL) {
                $this->values[$tmp->name] = ($tmp->default_value);
            }


            if (count($missing) > 0) {
                header("HTTP/1.0 400 Bad Request");
                echo ("Required values missed: " . implode(", ", $missing));
                die ();
            }


            // validate or sanitize

            switch ($tmp->type) {
                case '0':
                    //Numeric
                    $this->values[$tmp->name] = filter_var($this->values[$tmp->name], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_SCIENTIFIC);
                    if (!isset($this->values[$tmp->name]) && $tmp->default_value != NULL) {
                        $this->values[$tmp->name] = floatval($tmp->default_value);
                    }

                    break;
                case '1':
                    //String
                    $this->values[$tmp->name] = filter_var($this->values[$tmp->name], FILTER_SANITIZE_STRING);

                    break;
                case '2':
                    //int
                    if (isset($this->values[$tmp->name]) && filter_var($this->values[$tmp->name], FILTER_VALIDATE_INT, FILTER_FLAG_EMPTY_STRING_NULL) === FALSE) {
                        $invalid[] = $tmp->name;
                    }

                    break;
                case '3':
                    //float
                    $this->values[$tmp->name] = filter_var($this->values[$tmp->name], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND | FILTER_FLAG_ALLOW_SCIENTIFIC);
                    if (!filter_var($this->values[$tmp->name], FILTER_VALIDATE_FLOAT)) {
                        $invalid[] = $tmp->name;
                    }
                    break;
                case '4':
                    //email
                    if ($this->values[$tmp->name] != "") {
                        $this->values[$tmp->name] = filter_var($this->values[$tmp->name], FILTER_SANITIZE_EMAIL);
                        if (!filter_var($this->values[$tmp->name], FILTER_VALIDATE_EMAIL)) {
                            $invalid[] = $tmp->name;
                        }
                    }
                    break;
                case '5':
                    //regularexpression  FILTER_VALIDATE_REGEXP
                    if (isset($this->values[$tmp->name]) && !filter_var($this->values[$tmp->name], FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => $tmp['regularexpression'])))) {
                        $invalid[] = $tmp->name;
                    }
                    break;
                case '6':
                    //url
                    $this->values[$tmp->name] = filter_var($this->values[$tmp->name], FILTER_SANITIZE_URL);
                    if (!filter_var($this->values[$tmp->name], FILTER_VALIDATE_URL)) {
                        $invalid[] = $tmp->name;
                    }
                    break;
                case '7':
                    //raw unsafe and risky
                    // do nothing

                    break;
                case '8':
                    //IP Address
                    if (!filter_var($this->values[$tmp->name], FILTER_VALIDATE_IP)) {
                        $invalid[] = $tmp->name;
                    }
                    break;
                case '9':
                    //Bool
                    if (filter_var($this->values[$tmp->name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === NULL) {
                        if (!($tmp->required == 0 && $this->values[$tmp->name] == NULL)) {
                            $invalid[] = $tmp->name;
                        }
                    } else {
                        $this->values[$tmp->name] = filter_var($this->values[$tmp->name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    }
                    break;
            }
        }

        if (count($invalid) > 0) {
            header('Content-type: application/json');
            header("HTTP/1.0 422 Unprocessable Entity");
            $fieldlist = array_flip($invalid);
            foreach ($fieldlist as $key => $val) {
                $fieldlist[$key] = 1;
            }
            echo json_encode(array("message" => "422 Unprocessable Entity", "fields" => $invalid, "fieldlist" => $fieldlist));
            die ();
        }

        // Werte die nicht deklariert und dokumentiert sind löschen
        foreach ($this->values as $index => $val) {
            if (!in_array($index, $keepValues)) {
                unset($this->values[$index]);
                unset($this->post[$index]);
                unset($this->get[$index]);
            }
        }

        // Rechte
        // bei Rolle 0 für alle erlaubt, sonst rollenzugehörigkeit testen
        $accessGranted = false;

        // Perm
        foreach ($apidoc->permission as $q) {
            if ($q->role == "Everyone" || $q->role == "Public") {
                $accessGranted = true;
                break(1);
            }

            foreach ($_SESSION['ZUIZZ']['PERM']['ROLENAMES'] as $rolename => $role) {

                if ($q->role == $rolename) {
                    $accessGranted = true;
                    break(2);
                }
            }
        }

        if (!$accessGranted) {
            // detailierte Rechteprüfung machen
            if (is_int($this->identifier)) {
                // rechte auf identifier prüfen
                if (ZU::check_permission($this->feature_id, $this->identifier, strtoupper($method) . "::" . str_replace("/", ".", $rest))) {
                    $accessGranted = true;
                }
            } else {
                // feature rechte prüfen
                if (ZU::check_permission($this->feature_id, 0, strtoupper($method) . "::" . str_replace("/", ".", $rest))) {
                    $accessGranted = true;
                }
            }
        }

        if (!$accessGranted) {
            header("HTTP/1.0 401 Unauthorized");
            echo "401 Unauthorized, use method OPTIONS for further information on the permissions";
            die ();
        }

        // view in methode abarbeiten
        $this->viewmode = "rest";
        require ZU_DIR_FEATURE . "{$this->feature}/rest/{$this->view}/index.php";
        return $this->contentbuffer;
    }

    /**
     * CLI
     */
    function process_cli($cli)
    {
        $this->view = $cli;

        // view laden und ausführen
        if (is_file(ZU_DIR_FEATURE . "{$this->feature}/cli/{$this->view}/index.php")) {
            require ZU_DIR_FEATURE . "{$this->feature}/cli/{$this->view}/index.php";
        } else {
            exit (1);

            //echo  ( "feature {$this->feature}.{$cli} is  not available" . ZU_DIR_FEATURE . "{$this->feature}/cli/{$this->view}/index.php" );
            //TODO:: autocreate ev. hier einbauen
        }
        return $this->contentbuffer;
    }

    /*
      * Load a class file
      *
      * Class file preloader for features, loads classes in featurename/jslibs/ directory
      *
      * @class_name string classname body ie for class.db.php => db
      * @sub_folder string subdirectory from ZU_DIR_LIBS ie: core for core/class.db.php
      * returns void
      */
    function load_class($class_name, $feature = false, $sub_folder = '')
    {
        if (!$feature) {
            $feature = $this->feature;
            $dir     = $this->basedir;
        } else {
            $dir = ZU_DIR_FEATURE . $feature . '/';
        }
        if (!isset ($GLOBALS ['ZUVALS'] ['classloader'] [$feature] ["{$sub_folder}__{$class_name}"])) {
            include $dir . "libs/{$sub_folder}/class.{$class_name}.php";
            $GLOBALS ['ZUVALS'] ['classloader'] [$feature] ["{$sub_folder}__{$class_name}"] = true;
        }
    }

}

;