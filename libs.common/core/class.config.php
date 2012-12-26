<?php
class CONFIG {
	public $db = array ();
	public $lang = array ();
	public $system = array ();
	public $email = array ();


	function __construct($config) {

		foreach ( $config as $definition_set => $parameter ) {
			$this->$definition_set = $parameter;
		}


	}

}