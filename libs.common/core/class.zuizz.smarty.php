<?php
/**
 * Smarty implementierung von ZU
 * @author vzaech
 *
 */

class ZUsmarty extends Smarty {
	var $theme,$compile_dir,$cache_dir,$trusted_dir,$left_delimeter,$right_delimeter;
	/**
	 *
	 */
	function __construct() {
        
		parent::__construct();
		$this->compile_dir = ZU_DIR_DATA . 'temp/net.smarty.compile';
		$this->trusted_dir [0] = ZU_DIR_FEATURE;
		$this->left_delimiter = '{';
		$this->right_delimiter = '}';

	}

	// parse page
	function fetchPage($resource_name) {
		if(is_file($resource_name)){
		return parent::fetch ( $resource_name );
		}else {
			return $resource_name . ' does not exists';
		}
	}

	// Features in der Reihe ihrer Priorität oder Aufrufreihenfolge abarbeiten
	function process_feature_stack($depth = 0 , $ajax = false) {
		if (! isset ( $GLOBALS ['buffer'] ['feature_stack'] )) {
			$GLOBALS ['buffer'] ['feature_stack'] = array ();
		}
		ksort ( $GLOBALS ['buffer'] ['feature_stack'] );

		foreach ( $GLOBALS ['buffer'] ['feature_stack'] as $priority => $features_in_priority ) {
			while ( list ( $key, $parameters ) = each ( $features_in_priority ) ) {
				if (isset ( $GLOBALS ['buffer'] [$parameters ['zone']] )) {
					$GLOBALS ['buffer'] [$parameters ['zone']] .= $GLOBALS ['ZUIZZ']->create_feature_objects ( $parameters, $priority );
				} else {
					$GLOBALS ['buffer'] [$parameters ['zone']] = $GLOBALS ['ZUIZZ']->create_feature_objects ( $parameters, $priority );
				}
				// abgearbeiters element aus dem array werfen
				unset ( $GLOBALS ['buffer'] ['feature_stack'] [$priority] [$key] );
			}
			// abgearbeitete priorotät aus dem array werfen
			if (count ( $GLOBALS ['buffer'] ['feature_stack'] [$priority] ) == 0) {
				unset ( $GLOBALS ['buffer'] ['feature_stack'] [$priority] );
			}
		}

		// nachzählen und schauen ob es  in dieser prio noch elemente gibt
		if (count ( $GLOBALS ['buffer'] ['feature_stack'] ) > 0) {
			// wenn elemente nachgeladen wurden, diese abarbeiten
			self::process_feature_stack ( $depth + 1 );
		}

		if ($depth == 0) {
			if (isset ( $GLOBALS ['buffer'] ['zone'] ['default'] )) {
				$GLOBALS ['buffer'] ['zone'] ['default'] .= $GLOBALS ['buffer'] ['zonecontent'] ['default'];
			} else {
				$GLOBALS ['buffer'] ['zone'] ['default'] = $GLOBALS ['buffer'] ['zonecontent'] ['default'];
			}
			// merge zone content with page and deliver content


                $this->merge_page_with_buffers ($ajax);

		}

	}

	// buffercontent in pagecontent einpflegen
	function merge_page_with_buffers($ajax = false) {

        if(!$ajax){
            $buffer =&$GLOBALS ['buffer'] ['page'];
        }else{
            $buffer =&$GLOBALS ['buffer'] ['zone']['default'];

        }


		// pagetitle
		if(isset($GLOBALS ['ZUVALS'] ['pagetitle'])){
			$GLOBALS ['buffer'] ['page'] = str_replace ( '<!-- [head:title] -->', $GLOBALS ['ZUVALS'] ['pagetitle'], $buffer );
		}

		// css injecten
		$GLOBALS ['ZU_feature_css'][] = " ";
		$GLOBALS ['ZU_css'][] = " ";
        $buffer = str_replace ( '<!-- [head:css] -->', implode ( " ", $GLOBALS ['ZU_css'] ) . " " . implode ( " ", $GLOBALS ['ZU_feature_css'] ), $buffer   );

		// js injecten wenn es gibt
		$GLOBALS ['ZU_js'][] = " ";
		$GLOBALS ['ZU_feature_js'][] = " ";

        $buffer = str_replace ( '<!-- [head:js] -->', implode ( " ", $GLOBALS ['ZU_js'] ) . " " . implode ( " ", $GLOBALS ['ZU_feature_js'] ), $buffer );


		// build zone array for merge
		foreach (array_keys ( $GLOBALS ['buffer'] ['zonecontent'] )  as $searchzone ) {
			$zones [] = "<!-- [zone:{$searchzone}] -->";
		}

		echo str_replace ( $zones, array_values ( $GLOBALS ['buffer'] ['zonecontent'] ), $buffer );
	}

    function js_inject($buffer){
        // js injecten wenn es gibt
        $GLOBALS ['ZU_js'][] = " ";
        $GLOBALS ['ZU_feature_js'][] = " ";
        return str_replace ( '<!-- [head:js] -->', implode ( " ", $GLOBALS ['ZU_js'] ) . " " . implode ( " ", $GLOBALS ['ZU_feature_js'] ), $buffer );

    }

}
