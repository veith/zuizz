<?php
class tree {
	private $table = ''; // welche tabelle
	/*
	 * Konstruktor
	 * var $table string Tabelle mit den Trees
	 * returns void
	 */
	function __construct($table) {
		$this->table = $table;

	}
	/*
	 * gibt den Pfad als array zurück
	 * var $node int node_id
	 * var $get_fields array Feldnamen die zurückgegeben werden sollen
	 * var $join_table string Tabelle mit den details
	 * var $join_conditions string Weitere bedingung für den join
	 * returns array Array der Nodes mit entsprechenden Feldnamen
	 */
	public function get_path($node, $get_fields = NULL, $join_table = NULL, $join_conditions = NULL) {
		if ($join_table != NULL) {
			$join = "LEFT JOIN {$join_table} AS details ON details.node_id = tree.id";

			if ($join_conditions != NULL) {
				$join .= " AND details.{$join_conditions}";
			}
		}
		if (is_array ( $get_fields )) {
			$fields = implode ( ", details.", $get_fields );
		}

		$stmt =  ZUDB::prepare ( "SELECT
                tree.id {$fields}
            FROM
                {$this->table} AS tree {$join}
                , " . $this->table . " AS n2
            WHERE
                tree2.id =:node
                AND tree.lft <= tree2.lft
                AND tree.rgt >= tree2.rgt
            GROUP BY
                tree.ID
            ORDER BY
                tree.lft ASC" );
		$stmt->bindParam ( ':name', $node );
		$stmt->execute ();


	}
	/*
	 * gibt den Pfad als array zurück
	 * var $node int node_id
	 * var $get_fields array Feldnamen die zurückgegeben werden sollen
	 * var $join_table string Tabelle mit den details
	 * var $join_conditions string Weitere bedingung für den join
	 * returns array Array der Nodes mit entsprechenden Feldnamen
	 */
	public function get_children($node, $get_fields = NULL, $join_table = NULL, $join_conditions = NULL) {
		if ($join_table != NULL) {
			$join = "LEFT JOIN {$join_table} AS details ON details.node_id = tree.id";

			if ($join_conditions != NULL) {
				$join .= " AND details.{$join_conditions}";
			}
		}
		if (is_array ( $get_fields )) {
			$fields = implode ( ", details.", $get_fields );
		}

		$stmt = ZUDB::prepare ( "SELECT
                tree.id {$fields}
            FROM
                {$this->table} AS tree {$join}
                , " . $this->table . " AS n2
            WHERE
                tree2.id =:node
                AND tree.lft <= tree2.lft
                AND tree.rgt >= tree2.rgt
            GROUP BY
                tree.ID
            ORDER BY
                tree.lft ASC" );
		$stmt->bindParam ( ':name', $node );
		$stmt->execute ();

	}

}