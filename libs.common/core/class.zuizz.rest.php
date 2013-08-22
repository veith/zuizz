<?php

class ZUREST
{

    function  __construct(&$feature_obj)
    {

        $this->feature = $feature_obj;
    }

    public function encodeID($int)
    {
        return M11::encode32($int);
    }

    public function decodeID($int)
    {
        return M11::decode32($int);
    }

    //fields=id,c_date,label,message
    public function  select_fields(&$ORM)
    {

        if (isset($this->feature->fields)) {
            if ($this->feature->values['fields'] != "*") {
                $fieldlist = explode(",", $this->feature->values['fields']);
                //$ORM->select($this->feature->fields['id'][1], 'id');
                foreach ($fieldlist as $field) {
                    $field = trim($field);
                    if (isset($this->feature->fields[$field])) {
                        $ORM->select($this->feature->fields[$field][1], $field);
                    }
                    if (isset($this->feature->fieldgroups[$field])) {
                        $this->feature->fieldgroups[$field] = true;
                    }
                }
            } else {
                foreach ($this->feature->fields as $key => $field) {
                    $ORM->select($field[1], $key);
                }

                if (isset($this->feature->fieldgroups) && is_array($this->feature->fieldgroups)) {
                    foreach ($this->feature->fieldgroups as $key => $val) {
                        $this->feature->fieldgroups[$key] = true;
                    }
                }
            }
        }
    }

    /*
     * Autoselect expands in ORM by name of expand
     */

    public function  select_expands(&$ORM, $expand)
    {
        if (isset($this->feature->expandfields[$expand])) {
            preg_match("/" . $expand . "\(([*|\ |\w+|,]+)\),?/i", $this->feature->values['expand'], $match);

            if ($match[1] != "*") {
                $fieldlist = explode(",", $match[1]);

                //$ORM->select($this->feature->expandfields[$expand]['id'][1], 'id');
                foreach ($fieldlist as $field) {
                    $field = trim($field);
                    if (isset($this->feature->expandfields[$expand][$field])) {
                        $ORM->select($this->feature->expandfields[$expand][$field][1], $field);
                    }
                    if (isset($this->feature->fieldgroups[$field])) {
                        $this->feature->fieldgroups[$field] = true;
                    }
                }
            } else {
                foreach ($this->feature->expandfields[$expand] as $key => $field) {
                    $ORM->select($field[1], $key);
                }

                if (isset($this->feature->fieldgroups) && is_array($this->feature->fieldgroups)) {
                    foreach ($this->feature->fieldgroups as $key => $val) {
                        $this->feature->fieldgroups[$key] = true;
                    }
                }
            }
        }
    }

    /* returns an array with requested expands in [0] and their fields in [1]
    expand=user(*),set2(id,fieldb) will return
    [0] => Array
        (
            [0] => user
            [1] => set2
        )

    [1] => Array
        (
            [0] => *
            [1] => id,fieldb
        )
    */
    public function get_expands()
    {
        preg_match_all("/(\b\w+\b)\(([*|\ |\w+|,]+)\),?,?/i", $this->feature->values['expand'], $match);
        return array($match[1], $match[2]);
    }

    public function expand_requested($expand)
    {
        preg_match("/" . $expand . "\(([*|\ |\w+|,]+)\),?/i", $this->feature->values['expand'], $match);
        if (isset($match[1])) {
            return true;
        } else {
            return false;
        }
    }


    public function  clean_types(&$data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $row) {
                  $this->clean_row($data[$k]);
            }
        }
    }

    public function  clean_row(&$row)
    {

        foreach ($row as $field => $val) {
            if (isset($this->feature->fields[$field][0])) {
                switch ($this->feature->fields[$field][0]) {
                    case 'int':
                        if (isset($this->feature->fields[$field][2])) {
                            switch ($this->feature->fields[$field][2]) {
                                case 'id':
                                    $row[$field] = M11::encode32($val);
                                    break;
                                default:
                                    $row[$field] = (int)$val;
                            }
                        } else {
                            $row[$field] = (int)$val;
                        }

                        break;
                    case 'float':
                        $row[$field] = (float)$val;
                        break;
                    case 'boolean':
                        $row[$field] = (boolean)$val;
                        break;
                    case 'timestamp':
                        $row[$field] = (int)$val;
                        break;
                }
            }
        }
        return $row;
    }

    public function  pagination($total, $num_of_records)
    {
        $p['records'] = (int)$total;
        $p['results'] = (int)$num_of_records;
        $p['limit'] = (int)$this->feature->values['limit'];
        $p['page'] = (int)$this->feature->values['page'];
        $p['pages'] = ceil($p['records'] / $p['limit']);
        return $p;
    }

    // "age,-salary,kids"
    public function  sortorder(&$ORM)
    {
        // explode
        if ($this->feature->values['sort'] != null) {
            $orderbys = explode(',', $this->feature->values['sort']);

            foreach ($orderbys as $order) {
                $key = trim($order);
                $asc = true;
                if (substr($key, 0, 1) == "-") {
                    $key = substr($key, 1);
                    $asc = false;
                }
                if (isset($this->feature->fields[$key])) {
                    if ($asc) {
                        $ORM->order_by_asc($this->feature->fields[$key][1]);
                    } else {
                        $ORM->order_by_desc($this->feature->fields[$key][1]);
                    }
                }
            }
        } else {
            $ORM->order_by_desc('id');
        }
    }

    public function  scope(&$ORM)
    {
        if (is_array($this->feature->values['scope']) && isset($this->feature->fields)) {
            foreach ($this->feature->values['scope'] as $key => $scope) {

                if (is_array($scope)) {
                    switch ($scope[0]) {
                        case 'lt':
                            $ORM->where_lt($key, $scope[1]);

                            break;
                        case 'gt':
                            $ORM->where_gt($key, $scope[1]);
                            break;
                        case 'lte':
                            $ORM->where_lte($key, $scope[1]);
                            break;
                        case 'gte':
                            $ORM->where_gte($key, $scope[1]);
                            break;
                        case 'odd':
                            $ORM->where_odd($key, $scope[1]);
                            break;
                        case 'even':
                            $ORM->where_even($key, $scope[1]);
                            break;
                        case 'contains':
                            $ORM->where_like($key, '%' . $scope[1] . '%');
                            break;
                        case 'start_with':
                            $ORM->where_like($key, $scope[1] . '%');
                            break;
                        case 'ends_with':
                            $ORM->where_like($key, '%' . $scope[1]);
                            break;
                    }
                } else {
                    if (isset($this->feature->fields[$key])) {
                        $ORM->where($key, $scope);
                    }
                }
            }
        }
    }

}