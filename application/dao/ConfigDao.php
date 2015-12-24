<?php

/**
 * Class ConfigModel
 * @author Tuzki
 * @desc 配置类，对应 config 表，包含设置读取配置
 */
class ConfigDao extends \Base\BaseDao {
    function getConfigListByAccountGroupId($id) {
        $table_name = $this->table_name;
        return $this->db->$table_name()
            ->where('account_group_id', $id);
    }

    function insert_update($data) {
        $table_name = $this->table_name;
        $result = $this->db->$table_name->where('account_group_id', $data['account_group_id'])->where('name', $data['name'])->fetch();
        if ($result === false) {
            return $this->db->$table_name->insert($data);
        } else {
            return $this->db->$table_name->where('account_group_id', $data['account_group_id'])->where('name', $data['name'])->update($data);
        }
    }
}