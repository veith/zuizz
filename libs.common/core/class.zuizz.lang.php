<?php

class ZULANG {
	public $available = array ();
	public $enabled = array ();
	public $interface_lang;
    public $feature_lang = array ();

	/**
	 * Sprachmanagement von ZUIZZ
	 * Es gibt die interface,feature langs
	 * Verfügbare Sprachen werden aus der Hauptconfig ausgelesen.
	 *
	 */
	function __construct() {
		$this->init();
	}

	public function set_lang($lang) {
		$this->init(true);
		if (in_array ( $lang, $_SESSION ['ZUIZZ'] ['LANG'] ['enabled'] )) {
			$_SESSION ['ZUIZZ'] ['LANG'] ['interface_lang'] = $lang;
			$_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] = array ();

		}
	}

	public function set_feature_lang($feature_id, $lang) {
		$_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] [$feature_id] = $lang;
	}

	public function get_enabled() {
		return $this->enabled;
	}


	public function init($rebuild = FALSE) {
		if (! isset ( $_SESSION ['ZUIZZ'] ['LANG'] ['interface_lang'] ) || $rebuild) {
			$this->available = $GLOBALS ['ZUIZZ']->config->lang ['available'];
			$this->enabled = $GLOBALS ['ZUIZZ']->config->lang ['enabled'];
			$this->interface_lang = $GLOBALS ['ZUIZZ']->config->lang ['default_interface_lang'];

			// Sprache des Browsers verwenden
			if ($GLOBALS ['ZUIZZ']->config->lang ['use_browser_lang_as_default']) {
				if(isset($_SERVER ['HTTP_ACCEPT_LANGUAGE'])){
					$browserlang = strtolower ( substr ( $_SERVER ['HTTP_ACCEPT_LANGUAGE'], 0, 2 ) );
				}else{
					$browserlang = $GLOBALS ['ZUIZZ']->config->lang ['default_interface_lang'];
				}
				if (in_array ( $browserlang, $this->enabled )) {
					$this->interface_lang = $browserlang;
				}

			}
			$_SESSION ['ZUIZZ'] ['LANG'] ['available'] = $this->available;
			$_SESSION ['ZUIZZ'] ['LANG'] ['enabled'] = $this->enabled;
			$_SESSION ['ZUIZZ'] ['LANG'] ['interface_lang'] = $this->interface_lang;
			$_SESSION ['ZUIZZ'] ['LANG'] ['feature_lang'] = array ();
		}

	}

	//TODO Sprache   aus auth oder aus settings


}
