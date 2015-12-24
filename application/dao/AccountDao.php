<?php
/**
 * Class AccountModel
 */
class AccountDao extends Base\BaseDao {

    function getAccountList() {
        $account_list_raw = $this->db->account();
        $account_list = array();
        foreach ($account_list_raw as $account) {
            $market = $this->db->market()->select('name')->where('id', $account['market_id'])->fetch();
            $account_group = $this->db->account_group()->select('name')->where('id', $account['account_group_id'])->fetch();
            $account['market_name'] = $market['name'];
            $account_list[] = array(
                'id' => $account['id'],
                'name' => $account['name'],
                'description' => $account['description'],
                'market_id' => $account['market_id'],
                'account_group_id' => $account['account_group_id'],
                'market_name' => $market['name'],
                'account_group_name' => $account_group['name'],
            );
        }
        return $account_list;
    }

    public function getAccountListByAccountGroupId($id) {
        $table_name = $this->table_name;
        $account_list = $this->db->$table_name()
            ->where('account_group_id', $id);
        return $account_list;
    }
}
