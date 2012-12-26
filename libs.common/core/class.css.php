<?php
/*
 * Der css compressor von ZUIZZ 
 * 
 */ 

class ZUCSS {
	public $md5 = '';
	private $cache_dir = '';
	private $_files = array ();
	public $serial = "123456789"; // serial Wert wie bei DNS
	public $expires_in = "686400"; // ablaufdatum des cachefiles in sek
	

	//Minimieren
	function minify($css) {
		$css = preg_replace ( '#\s+#', ' ', $css );
		$css = preg_replace ( '#/\*.*?\*/#s', '', $css );
		$css = str_replace ( '; ', ';', $css );
		$css = str_replace ( ': ', ':', $css );
		$css = str_replace ( ' {', '{', $css );
		$css = str_replace ( '{ ', '{', $css );
		$css = str_replace ( ', ', ',', $css );
		$css = str_replace ( '} ', '}', $css );
		$css = str_replace ( ';}', '}', $css );
		
		return trim ( $css );
	}
	
	function get_css() {
		//md5 aus den einzelnen css requests und dem serial aufbauen
		

		//prüfen ob gecachetes css file mit entsprechendem md5 existiert (ablaufdatum beachten)
		// minifyied css erstellen
		// cachefile erstellen
		

		// gecachtes file ausgeben
		header ( "Content-type: text/css" );
		// header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");  // aufbauen aus $this->expires_in + ZU_NOW
		

		echo $cached_css_file;
	}
}
?>