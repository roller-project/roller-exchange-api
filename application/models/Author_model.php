<?php
class Author_Model extends DB_Model{
	public function register($username, $password) {
        $validate_email = $this->validate_email($email);    
    }


    public function validate_email($email){

        $email = trim(strtolower($email));
        $this->db->where("email",$email);
    }
}