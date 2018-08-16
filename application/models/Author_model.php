<?php
class Author_Model extends DB_Model{

	public $error = [];


	public function register($email, $password) {
        $validate_email = $this->validate_email($email);
        $validate_password = $this->validate_password($password);

        if(!$validate_email || !$validate_password) return $this->error;

        if($validate_email && $validate_password){
        	$arv = [
        		"email" => $validate_email,
        		"password" => $this->makePassword($validate_password),
        		"hash" => password_hash($validate_password, 1)
        	];
        	$this->db->insert("account", $arv);
        	$id = $this->db->insert_id();
        	$api = $this->apikey->create_key($id);
        	$arvs = [
        		"id" => $id,
        		"email" => $email,
        		"api" => $api
        	];
        	return $arvs;
        }
    }


    public function login($email, $password){
    	$email = trim(strtolower($email));
    	$password = $this->validate_password($password);
    	$this->db->where("email", $email);
    	$this->db->where("password", $this->makePassword($password));
    	$data = $this->db->get("account")->row();

    	if($data){
    		if($data->status == 0){
	    		return ["error" => "Account Locker"];
	    	}
    		if(password_verify($password, $data->hash)){
    			return [
    				"id" => $data->id,
    				"login" => "ok"
    			];
    		}
    		
    	}
    	return ["error" => "Email or password is corect"];
    }

    public function validate_email($email){

        $email = trim(strtolower($email));
        $row = $this->db->where("email",$email)->count_all_results('account');
        if($row > 0){
        	$this->error["email"] = "Email Ready";
        	return false;
        }else{
        	return $email;
        }
    }


    public function validate_password($password){
    	$password = trim(strtolower($password));
    	if(strlen($password) < 8){
    		$this->error["password"] = "Password Short";
    		return false;
    	}else{
    		return $password;
    	}

    }


    public function makePassword($password){
    	return sha1($password);
    }


    public function AccountList(){
    	$this->is_admin();
    	$this->db->limit($this->limit,$this->start);
    	return $this->db->get("account")->result();
    }
}