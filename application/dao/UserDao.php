<?php
class UserDao {
    private $db;
    private $is_admin;
    private $id;


    public function __construct() {
        $this->db = Yaf\Registry::get('db');
    }

    public function login($username, $password) {
        $user = $this->db->user()
            ->where('username', $username)
            ->where('password', $password)
            ->fetch();
        $this->id = $user['id'];
        $this->is_admin = $user['is_admin'];
        if (!empty($this->id)) {
            return true;
        }
        return false;
    }


    public function save(array $user) {
         return $this->db->user()->insert(array(
             'password' => $user['password'],
             'username' => $user['username'],
             'is_admin' => $user['is_admin']
         ));
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $is_admin
     */
    public function setIsAdmin($is_admin)
    {
        $this->is_admin = $is_admin;
    }

    /**
     * @return mixed
     */
    public function getIsAdmin()
    {
        return $this->is_admin;
    }


}