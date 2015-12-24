<?php
namespace Base;
use Logger;
use Yaf\Exception;

class BaseDao {
    protected $db;
    protected $table_name;

    function __construct($table_name=null) {
        $this->db = \Yaf\Registry::get('db');
        if (empty($table_name)) {
            $this->table_name = strtolower(explode('Dao', get_class($this))[0]);
        } else {
            $this->table_name = $table_name;
        }
    }

    function getList($limit=null, $offset=0) {
        $table_name = $this->table_name;
        if (empty($limit)) {
            return $this->db->$table_name;
        }
        return $this->db->$table_name->limit($limit, $offset);
    }

    function get($id) {
        $table_name = $this->table_name;
        return $this->db->$table_name()->where('id', $id)->fetch();
    }

    public function delete($id) {
        $table_name = $this->table_name;
        return $this->db->$table_name->where('id', $id)->delete();
    }

    public function update($data) {
        $table_name = $this->table_name;
        return $this->db->$table_name->where('id', $data['id'])->update($data);
    }

    public function insert($data) {
        $table_name = $this->table_name;
        foreach ($data as $row) {
            if (is_array($row)) {
                return $this->db->$table_name->insert_multi($data);
            } else {
                return $this->db->$table_name->insert($data);
            }
        }
    }

    public function insertOne($data) {
        $table_name = $this->table_name;
        if($this->db->$table_name()->insert($data)) {
            return $this->db->$table_name()->insert_id();
        } else {
            Logger::Log(var_export($data, true), Logger::ERROR_LOG);
            throw new Exception("Insert $table_name Failed!");
        }
    }

    public function setTableName($table_name) {
        $this->table_name = $table_name;
    }
}