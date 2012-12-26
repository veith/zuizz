<?php
/**
 *
 * @author vzaech
 *         principal_type 0 = ORG, 1 = "role", 2 = User
 *         default rollen: 1 admin, 2 user, 3 everyone
 *         Tabellen: org_permission_user, org_permission_role, org_permission_org
 *
 */

class ZUPERM
{
    var $user_id;

    public function init()
    {

        // Wenn kein User gesetzt ist user auf EVERYONE setzen
        if (!isset ($this->user_id)) {
            $this->user_id = EVERYONE;
        }
        if (!isset ($_SESSION ['ZUIZZ'] ['AUTH'] ['uid'])) {
            $_SESSION ['ZUIZZ'] ['AUTH'] ['uid'] = EVERYONE;
        } else {
            $this->user_id = $_SESSION ['ZUIZZ'] ['AUTH'] ['uid'];
        }


        $_SESSION ['ZUIZZ'] ['PERM'] = array();

        // rechte die für everyone sind für alle
        // Userrechte  (und auch public rechte holen) , diese werden explizit gestzt!!
        $q = ORM::for_table('org_permission_user')->where_raw(('user_id = ? or user_id = ?'), array($this->user_id, EVERYONE))->find_many();
        foreach ($q as $tmp_perm) {
            $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm']   = $tmp_perm->perm;
            $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] = $tmp_perm->perm_b;
        }

        $q = ORM::for_table('org_permission_org')->raw_query("SELECT
                    tree_a.org_id, usr.org_id as target_org_id, tree_a.title, perm.feature_type, perm.feature_node_id, perm.perm, perm.perm_b
                FROM
                    " . ('org_permission_org') . " perm
                INNER JOIN
                    " . ('org_tree') . " tree_a ON perm.org_id = tree_a.org_id ,
                    " . ('org_tree') . " tree_b
                INNER JOIN
                    " . ('org_user') . " usr ON tree_b.org_id = usr.org_id
                WHERE
                    usr.user_id = :user_id
                AND
                    tree_a.lft >=tree_b.lft
                AND
                    tree_a.rgt <= tree_b.rgt
                AND
                    tree_a.tree_id = tree_b.tree_id
                ORDER
                    by tree_a.lft ASC", array('user_id' => $this->user_id))->find_many();
        // Org Pfad basierend der Org Unit der ein User angehört, die Rechte gehen von oben nach unten, ein recht auf eine hauptabteilung erhält auch die rechte der darunterliegenden Abteilungen
        foreach ($q as $tmp_perm) {
            // Wenn ein recht auf ein target_org_id (bewusst gesetztes recht auf ein Org element und nicht vererbt) wird genau dieses als das geltende recht genommen
            if ($tmp_perm->target_org_id == $tmp_perm->org_id) {
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm']   = $tmp_perm->perm;
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] = $tmp_perm->perm_b;
            } else {
                if (!isset ($_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'])) {
                    $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'] = 0;
                }
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'] |= $tmp_perm->perm;
                if (!isset ($_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'])) {
                    $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] = 0;
                }
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] |= $tmp_perm->perm_b;
            }
        }

        $q = ORM::for_table('org_user_functional_role')->raw_query("SELECT
                    role.role_id, role.title, perm.feature_type, perm.feature_node_id,  perm.perm, perm.perm_b
                FROM
                    " . ('org_user_functional_role') . " usr
                INNER JOIN
                    " . ('org_functional_role') . " role ON usr.functional_role_id = role.role_id
                LEFT JOIN
                    " . ('org_permission_role') . "  perm ON role.role_id = perm.role_id
                WHERE
                    usr.org_user_id = :user_id", array('user_id' => $this->user_id))->find_many();

        // Rollenrechte basierend der Rollen des Users   und Rollen des Users
        foreach ($q as $tmp_perm) {
            if (isset ($_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'])) {
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'] |= $tmp_perm->perm;
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] |= $tmp_perm->perm_b;
            } else {
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm']   = $tmp_perm->perm;
                $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] = $tmp_perm->perm_b;
            }
            // Rollen die der user übernimmt
            $_SESSION ['ZUIZZ'] ['PERM'] ['ROLE'] [$tmp_perm->role_id]    = $tmp_perm->role_id;
            $_SESSION ['ZUIZZ'] ['PERM'] ['ROLENAMES'] [$tmp_perm->title] = $tmp_perm->role_id;
        }

        /*
        * Für jede Rolle der ein User angehört entsprechende ORG Units raussuchen und die Rechte von diesen Nodes her nach unten aufbauen (siehe org pfad basierend org weiter oben in diesem file)
        */
        if (isset ($_SESSION ['ZUIZZ'] ['PERM'] ['ROLE'])) {
            // ORG Units ermitteln
            $role = ORM::for_table('org_functional_role_org_tree')->where_in('functional_role_id', $_SESSION ['ZUIZZ'] ['PERM'] ['ROLE'])->find_many();
            foreach ($role as $val) {

                // Org Pfad basierend der Org Unit der ein User angehört, die Rechte gehen von oben nach unten, ein recht auf eine hauptabteilung erhält auch die rechte der darunterliegenden Abteilungen
                $q = ORM::for_table('org_permission_org')->raw_query("SELECT
                        tree_a.org_id, tree_a.title, perm.feature_type, perm.feature_node_id, perm.perm, perm.perm_b
                    FROM
                        " . ('org_permission_org') . " perm
                    INNER JOIN
                        " . ('org_tree') . " tree_a ON perm.org_id = tree_a.org_id ,
                        " . ('org_tree') . " tree_b
                    WHERE
                        tree_b.org_id =:org_id
                    AND
                        tree_a.lft >=tree_b.lft
                    AND
                        tree_a.rgt <= tree_b.rgt
                    AND
                        tree_a.tree_id = tree_b.tree_id
                    ORDER
                        by tree_a.lft ASC", array('org_id' => $val->org_id))->find_many();
                foreach ($q as $tmp_perm) {

                    // Wenn ein recht auf ein target_org_id (bewusst gesetztes recht auf ein Org element und nicht vererbt) wird genau dieses als das geltende recht genommen
                    if ($val->org_id == $tmp_perm->org_id) {
                        $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm']   = $tmp_perm->perm;
                        $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] = $tmp_perm->perm_b;
                    } else {
                        if (!isset ($_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'])) {
                            $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'] = 0;
                        }
                        $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm'] |= $tmp_perm->perm;
                        if (!isset ($_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'])) {
                            $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] = 0;
                        }
                        $_SESSION ['ZUIZZ'] ['PERM'] [$tmp_perm->feature_type] [$tmp_perm->feature_node_id] ['perm_b'] |= $tmp_perm->perm_b;
                    }
                }

            }
        }

    }

    /**
     * Checks if a permission on an item is given
     *
     * @permission_bit       int binary position of permission set
     * @feature_type         int Feature type
     * @feature_entity_id    int Element ID
     * @return bool
     */
    public function check_permission($feature_type, $feature_entity_id, $permission_bit)
    {

        // wenn Permission bit 0 (Public, everyone, alle, jeder, ganze welt) dann direkt true zurück geben
        if ($permission_bit == 0) {
            return true;
        }

        if (isset ($feature_entity_id)) {
            if (!isset ($_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id])) {
                if ($_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree']) {
                    // Pfad aufbauen
                    $q = ORM::for_table($_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree_table'])->raw_query("SELECT
                    tree." . $_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree_identifier'] . " as id
                    FROM
                        " . ($_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree_table']) . " as tree,
                        " . ($_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree_table']) . " as helper
                    WHERE
                        helper." . $_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree_identifier'] . " = :feature_entity_id
                    AND
                        tree." . $_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree_root'] . " = helper." . $_SESSION ['ZUIZZ'] ['FEATURES'] [$feature_type] ['tree_root'] . "
                    AND
                        tree.lft <= helper.lft
                    AND
                        tree.rgt >= helper.rgt
                    AND
                        tree.active = 1
                    ORDER by
                        tree.lft DESC", array('feature_entity_id' => $feature_entity_id))->find_many();

                    foreach ($q as $key => $value) {
                        $_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id] [$key] = $value->id;
                    }
                    // leeren path abfangen
                    if (!isset ($_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id])) {
                        $key                                                                     = 0;
                        $_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id] [$key] = $feature_entity_id;
                    }
                    // nuller node setzen
                    $_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id] [++$key] = 0;
                    //pfade aufbauen
                    $tmp_array = $_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id];
                    // übergeordnete pfade cachen
                    while (count($tmp_array) > 2) {
                        array_shift($tmp_array);
                        $_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$tmp_array [0]] = $tmp_array;
                    }
                } else {
                    // kein tree verwendet ==> element 0 ist parent
                    $_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id] = array(0 => $feature_entity_id,
                                                                                              1 => 0);
                }
            }

            // Pfad bis oben durchgehen wenn nichts gesetzt wurde, wenn nichts gefunden wird false zurückgeben
            foreach ($_SESSION ['ZUIZZ'] ['PATH'] [$feature_type] [$feature_entity_id] as $node) {
                $check_node = $node;
                if (isset ($_SESSION ['ZUIZZ'] ['PERM'] [$feature_type] [$node])) {
                    break;
                }
            }

            if (isset ($_SESSION ['ZUIZZ'] ['PERM'] [$feature_type] [$check_node])) {
                return ($_SESSION ['ZUIZZ'] ['PERM'] [$feature_type] [$check_node] ['perm'] & (0 + ('0x' . dechex(1 << ($permission_bit - 1))))) ? true : false;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}