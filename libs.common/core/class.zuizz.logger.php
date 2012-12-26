<?php


class ZULOG
{

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
    public function log($message, $priority = 16, $label = 'undefined', $file = NULL, $line = NULL, $feature_type = 0, $feature_id = 0)
    {
        try {

            if (isset($_SESSION ['ZUIZZ'] ['AUTH'] ['uid'])) {
                $user_id = $_SESSION ['ZUIZZ'] ['AUTH'] ['uid'];
            } else {
                $user_id = 0;
            }
            $log = ORM::for_table('log')->create();

            $log->feature_type = $feature_type;
            $log->feature_id   = $feature_id;
            $log->c_date       = ZU_NOW;
            $log->c_user_id    = $user_id;
            $log->message      = $message;
            $log->priority     = $priority;
            $log->file         = $file;
            $log->line         = $line;
            $log->label        = $label;

            $log->save();

        } catch (PDOException $e) {
            echo 'Error : ' . $e->getMessage();
            exit ();
        }
    }
}

?>