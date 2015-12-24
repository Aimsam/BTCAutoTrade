<?php
class AccountConfigDao extends \Base\BaseDao {
    function __construct() {
        parent::__construct('account_config');
    }

    function getConfigListByAccountId($account_id) {
        $table_name = $this->table_name;
        return $this->db->$table_name->where('account_id', $account_id);
    }

    function insert_update($data) {
        $table_name = $this->table_name;
        $result = $this->db->$table_name->where('account_id', $data['account_id'])->where('name', $data['name'])->fetch();
        if ($result === false) {
            return $this->db->$table_name->insert($data);
        } else {
            return $this->db->$table_name->where('account_id', $data['account_id'])->where('name', $data['name'])->update($data);
        }
    }
}