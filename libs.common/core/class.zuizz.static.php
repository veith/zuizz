<?php

class ZU
{
    /*
      * Print a formated array
      * @array array Array
      * @return bool Nur zurückgeben, nicht ausgeben
      */
    static function print_array($array, $return = FALSE)
    {
        if ($return) {
            return print_r($array, TRUE);
        } else {
            echo "<pre>" . print_r($array, TRUE) . "</pre>";
            return false;
        }
    }


    /*
      * Load a class file
      *
      * Class file preloader for zuizz, uses system Constant ZU_DIR_LIBS for non external classes
      * and ZU_DIR_LIBS_EXTERNAL for external classes.
      *
      * @class_name string classname body ie for class.db.php => db
      * @sub_folder string subdirectory from ZU_DIR_LIBS ie: core for core/class.db.php
      * @external bool load an external class from jslibs.external
      * returns void
      */
    static function load_class($class_name, $sub_folder = '', $external = false)
    {
        if (!isset ($GLOBALS ['ZUVALS'] ['classloader'] ["{$external}_{$sub_folder}__{$class_name}"])) {
            if ($external) {
                include ZU_DIR_LIBS_EXTERNAL . "{$sub_folder}/{$class_name}.php";
            } else {
                include ZU_DIR_LIBS . "{$sub_folder}/class.{$class_name}.php";
            }
            $GLOBALS ['ZUVALS'] ['classloader'] ["{$external}_{$sub_folder}__{$class_name}"] = true;
        }
    }

    /*
      * Load a class interface file
      *
      * Class file preloader for zuizz, uses system Constant ZU_DIR_LIBS for non external classes
      * and ZU_DIR_LIBS_EXTERNAL for external classes.
      *
      * @class_name string classname body ie for class.db.php => db
      * @sub_folder string subdirectory from ZU_DIR_LIBS ie: core for core/class.db.php
      * @external bool load an external class from jslibs.external
      * returns void
      */
    static function load_interface($interface_name, $sub_folder = '', $external = NULL)
    {
        if ($sub_folder) {
            $sub_folder .= '/';
        }
        if ($external != NULL) {
            require_once ZU_DIR_LIBS_EXTERNAL . $sub_folder . $interface_name . '.php';
        } else {
            require_once ZU_DIR_LIBS . $sub_folder . 'interface.' . $interface_name . '.php';
        }
    }

    /*
      * Log events to specified system logger
      *
      * Default logging is in Database table PREFIX_log,
      * please use the logviewer feature to view and analyze log messages.
      *
      * @message string log message
      * @feature_type int feature_type
      * @feature_id int feature_id
      *
      */
    static function log($message, $prority = 16, $label = 'undefined', $file = NULL, $line = NULL, $feature_type = 0, $feature_id = 0)
    {
        $GLOBALS ['ZUIZZ']->logger->log($message, $prority, $label, $file, $line, $feature_type, $feature_id);
    }

    /*
      * Überschreibe den default error handeler von php.
      * Werte werden an firephp, sofern aktiviert,  übergeben anstatt mit einem echo dem user vor die Nase geknallt.
      *
      * @errno int Nummer, Konstante des Fehlers
      * @errstr string Die Fehlermeldung selbst
      * @errfile string Die Datei in der der Fehler passiert ist
      * @errline int Die Zeile in der der Fehler passiert ist
      * return bool
      *
      */

    static function simple_error_handler($errno, $errstr, $errfile, $errline)
    {
        echo "{$errno}:{$errstr},  ({$errfile} | {$errline})";

        /* Don't execute PHP internal error handler */
        return true;
    }

    static function smarty_error_handler($errno, $errstr, $errfile, $errline)
    {
        // TODO: cleanup und Fehler ins ZU::log ausgeben

        switch ($errno) {
            case E_RECOVERABLE_ERROR :
                echo "{$errno}:{$errstr},  ({$errfile} | {$errline})";
                break;
            case E_USER_ERROR :
                echo "{$errno}:{$errstr},  ({$errfile} | {$errline})";
                break;
            case E_USER_WARNING :
                echo "{$errno}:{$errstr},  ({$errfile} | {$errline})";
                break;
            case E_USER_NOTICE :
                echo "{$errno}:{$errstr},  ({$errfile} | {$errline})";
                break;
            case E_NOTICE :
                //					$GLOBALS ['ZUIZZ']->firephp->log ( $errstr, " (file:{$errfile} | line:{$errline})" );
                break;
            default :
                echo "{$errno}:{$errstr},  ({$errfile} | {$errline})";
                break;
        }
        return true;
    }

    /*
      * Gibt den DB Prefix zurück
      * @table string Name der Tabelle
      * return string TAbellenname mit Prefix der in der Hauptconfig eingestellt ist
      */
    static function db_prefix($table)
    {
        return $GLOBALS ['ZUIZZ']->config->db ['prefix'] . $table;
    }

    /*
      * Prüfe ob User eingeloggt ist
      * return bool Ist der user eingeloggt
      */
    static function is_auth()
    {
        if (isset ($_SESSION ['ZUIZZ'] ['AUTH'] ['is_auth']) && $_SESSION ['ZUIZZ'] ['AUTH'] ['is_auth']) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * adapter to Check if a permission on an item is given with
     *
     * @permission_bit       int binary position of permission set
     * @feature_type         int Feature type
     * @feature_entity_id    int Feature id
     * @return bool
     */
    static function check_permission($feature_type, $feature_entity_id, $permission_bit)
    {

        if (!empty($feature_entity_id) || $feature_entity_id === 0) {
            if (!is_numeric($permission_bit)) {
                // aktuelles bit ermitteln
                if (isset($_SESSION ['ZUIZZ'] ['PERMISSIONSET'] [$feature_type] [$permission_bit])) {
                    $permission_bit = $_SESSION ['ZUIZZ'] ['PERMISSIONSET'] [$feature_type] [$permission_bit];
                } else {
                    return false;
                }
            }
            return $GLOBALS ['ZUIZZ']->perm->check_permission($feature_type, $feature_entity_id, $permission_bit);
        } else {
            return false;
        }
    }

    /**
     * Checks if a given bit is set in a given value
     *
     * @val int binary Haystack as int
     * @bit int bitposition
     * @return bool
     */
    static function check_bit($val, $bit)
    {
        return ($val & (0 + ('0x' . dechex(1 << ($bit - 1))))) ? '1' : '0';
    }

    /**
     * Set a given bit in a given value
     *
     * @val int binary Haystack as int
     * @bit int bitposition
     * @return int The new value
     *
     */
    static function add_bit($val, $bit)
    {
        return $val += '0x' . dechex(1 << ($bit - 1));
    }

    /**
     * Removes a given bit from a given value
     *
     * @val int binary Haystack as int
     * @bit int bitposition
     * @return int The new value
     */
    static function remove_bit($val, $bit)
    {
        return $val ^ (0 + ('0x' . dechex(1 << ($bit - 1))));
    }


    static function get_user_id()
    {
        return $_SESSION ['ZUIZZ'] ['AUTH'] ['uid'];
    }

    static function get_lang_enabled()
    {
        return $_SESSION ['ZUIZZ'] ['LANG'] ['enabled'];
    }

    static function get_content_lang()
    {
        return $_SESSION ['ZUIZZ'] ['LANG'] ['content_lang'];
    }

    static function get_interface_lang()
    {
        return $_SESSION ['ZUIZZ'] ['LANG'] ['interface_lang'];
    }

    static function get_lang_enabled_in_interface()
    {
        return $_SESSION ['ZUIZZ'] ['LANG'] ['enabled_in_interface'];
    }

    static function set_feature_lang($feature_id, $lang)
    {
        $_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] [$feature_id] = $lang;
        $GLOBALS["ZUIZZ"]->feature[$feature_id]->load_translation_file($lang);
    }

    static function get_feature_lang($feature_id)
    {
        if (!isset ($_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] [$feature_id])) {
            $_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] [$feature_id] = $_SESSION ['ZUIZZ'] ['LANG'] ['interface_lang'];
        }
        return $_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] [$feature_id];
    }

    /**
     * assigns a variable to the smarty object
     *
     * @val mixed Value to be assigned
     * @var string Name of the Variable
     * @permanent bool Keep the assignment in Session for later use
     * @return void No value is returned.
     */
    static function assign($var, $val, $permanent = false)
    {
        $GLOBALS ['smarty']->assign($var, $val);
        if ($permanent) {
            $_SESSION ['ZUIZZ'] ['smarty'] ['permanent'] [$var] = $val;
        }
    }

    /**
     * unassigns an normal and permanent assignment
     *
     * @var string Name of the Variable
     * @return void No value is returned.
     */
    static function clear_assign($var)
    {
        if (isset ($_SESSION ['ZUIZZ'] ['smarty'] ['permanent'] [$var])) {
            unset ($_SESSION ['ZUIZZ'] ['smarty'] ['permanent'] [$var]);
        }
        $GLOBALS ['smarty']->clear_assign($var);
    }


    static function translate_string($string, $lang = false)
    {
        if (!$lang) {
            $lang = ZU::get_content_lang();
        }

        return $string;
    }

    static function header($code, $otional_string = "")
    {
        if (!isset($GLOBALS['ZUIZZHEADERCODES'])) {
            include ZU_DIR_LIBS . "helper/http.headers.php";
        }
        header("HTTP/1.1 {$code} {$GLOBALS['ZUIZZHEADERCODES'][$code]}");
        return $GLOBALS['ZUIZZHEADERCODES'][$code];
    }

    /**
     * ORMselectCount Simple count for table elements
     *
     * @table string name of the table
     * @where array key value pairs of table fields
     * @return int number of counted elements
     */
    static function ORMselectCount($table, $where)
    {
        $q = ORM::for_table($table);
        foreach ($where as $key => $value) {
            $q->where($key, $value);
        }
        return $q->count();

    }

    /*
    * @param array $array1
    * @param array $array2
    * @return array
    * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
    * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
    */
    static function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = array_merge_recursive_distinct($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }
}