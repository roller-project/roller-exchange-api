<?php
class Author_Model extends DB_Model{
	public function login($username, $password) {
        if($username == 'hello' && $password == 'world')
        {
            return true;            
        }
        else
        {
            return false;           
        }           
    }
}