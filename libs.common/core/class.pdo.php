<?php
class ZUDB extends PDO {

	/*
	 * Simple Query returning in FETCH_ASSOC mode (as an array)
	 *
	 * Wenn ein index (spalte aus dem select) mitgegeben wird so wird dieses als index verwendet, ansonsten ein iterativer Wert ab 0
	 *
	 * @short_description string Kurze Beschreibung des Querys
	 * @index string Index des zurückgegebenen Arrays, muss ein name einer Spalte sein
	 * @query string Der mysql Query selbst
	 * @feature_type int feature_type für detailierte Fehlermeldungen
	 * @feature_id int feature_id für detailierte Fehlermeldungen
	 * return array and bool false on error
	 *
	 */
	static function query_array($short_description, $index = NULL, $query, $feature_type = 0, $feature_id = 0, $debug = false) {
		if ($debug) {
			ZU::log ( addslashes ( $query ), 1, $short_description, __FILE__, __LINE__, $feature_type, $feature_id );

		}
		try {
			//return a simple array
			if ($index === NULL) {
				return $GLOBALS ['DB']->query ( $query )->fetchAll ( PDO::FETCH_ASSOC );
			} else {
				$resultset = $GLOBALS ['DB']->query ( $query );
				while ( ($row = $resultset->fetch ( PDO::FETCH_ASSOC )) ) {
					$data [$row [$index]] = $row;
				}
				if (isset ( $data )) {
					return $data;
				}
			}

		} catch ( PDOException $e ) {
			ZU::log ( $e . addslashes ( $query ), E_RECOVERABLE_ERROR, $short_description, __FILE__, __LINE__, $feature_type, $feature_id );
		}
		return array();
	}

	/*
	 * Simple Query returning paired values in FETCH_ASSOC mode (as an array)
	 *
	 *
	 * @short_description string Kurze Beschreibung des Querys
	 * @query string Der mysql Query selbst
	 * @feature_type int feature_type für detailierte Fehlermeldungen
	 * @feature_id int feature_id für detailierte Fehlermeldungen
	 * return array and bool false on error
	 *
	 */
	static function query_paired($short_description, $query, $feature_type = 0, $feature_id = 0, $debug = false) {
		if ($debug) {
			ZU::log ( addslashes ( $query ), 1, $short_description, __FILE__, __LINE__, $feature_type, $feature_id );
		}
		try {
			//return a simple array


			$resultset = $GLOBALS ['DB']->query ( $query );
			while ( $row = $resultset->fetch () ) {
				$data [$row [0]] = $row [1];
			}
			return $data;

		} catch ( PDOException $e ) {
			ZU::log ( $e . addslashes ( $query ), E_RECOVERABLE_ERROR, $short_description, __FILE__, __LINE__, $feature_type, $feature_id );
		}
		return false;
	}

	/*
	 * Simple Query returning in FETCH_OBJ mode (as an object)
	 *
	 * @short_description string Kurze Beschreibung des Querys
	 * @query string Der mysql Query selbst
	 * @feature_type int feature_type für detailierte Fehlermeldungen
	 * @feature_id int feature_id für detailierte Fehlermeldungen
	 * return array and bool false on error
	 *
	 */
	static function query_get_object($short_description, $query, $feature_type = 0, $feature_id = 0) {
		try {
			//return an object
			return $GLOBALS ['DB']->query ( $query )->fetchAll ( PDO::FETCH_OBJ );
		} catch ( PDOException $e ) {
			ZU::log ( $e . addslashes ( $query ), E_RECOVERABLE_ERROR, $short_description, __FILE__, __LINE__, $feature_type, $feature_id );
		}
		return false;
	}

	/*
	 * Simple Query returning   one row as array
	 */
	static function query_one_row($label, $query, $feature_type = 0, $feature_id = 0, $debug = false) {
		try {
			$res = $GLOBALS ['DB']->query ( $query . " limit 1" )->fetch ( PDO::FETCH_ASSOC );

		} catch ( PDOException $e ) {
			ZU::log ( $e . addslashes ( $query ), E_RECOVERABLE_ERROR, $label, __FILE__, __LINE__, $feature_type, $feature_id );
		}

		return $res;
	}

	/*
	 * Simple Query returning   one row as array
	 */
	static function query_one_value($query, $value, $label = NULL, $file = NULL, $line = NULL, $feature_type = 0, $feature_id = 0) {
		try {
			$res = $GLOBALS ['DB']->query ( $query . " limit 1" )->fetch ( PDO::FETCH_COLUMN );
		} catch ( PDOException $e ) {
			ZU::log ( $e . addslashes ( $query ), E_RECOVERABLE_ERROR, $label, $file, $line, $feature_type, $feature_id );
		}
		return $res [$value];
	}

	/*
	 * Simple Query for updates, returns affected row
	 */
	static function query_update_unsecure($query, $label = NULL, $file = NULL, $line = NULL, $feature_type = 0, $feature_id = 0) {
		try {
			return $GLOBALS ['DB']->exec ( $query );

		} catch ( PDOException $e ) {
			ZU::log ( $e . $query, E_RECOVERABLE_ERROR, $label, $file, $line, $feature_type, $feature_id );
		}
	}

	static function prepare_query($query, $label = NULL, $file = NULL, $line = NULL, $feature_type = 0, $feature_id = 0) {
		try {
			return $GLOBALS ['DB']->prepare ( $query );
		} catch ( PDOException $e ) {
			ZU::log ( $e . addslashes ( $query ), E_RECOVERABLE_ERROR, $label, $file, $line, $feature_type, $feature_id );
		}

	}

	/*
	 * Returns the id from last Insert
	 */
	static function get_last_insert_id() {
		return $GLOBALS ['DB']->lastInsertId ();
	}

}