<?php
/*
 * Das ist das Auth interface
 */
interface ZUAUTH_interface {
	// constructor
	function __construct();

	// handle login procedure
    public function login($username,$credentials);

    // on NULL return user_id of logged in user
    public function get_user_id($username = NULL);

    // on NULL return user_name of logged in user
    public function get_user_name($user_id = NULL);

    // Logout
    public function logout();

}
?>