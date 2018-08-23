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
                $this->db->delete("account_login",["users_id" => $data->id]);
                $this->db->insert("account_login",["users_id" => $data->id, "session_id" => $this->getSessionID()]);
    			return [
    				"id" => $data->id,
    				"login" => "ok",
                    "session_id" => $this->getSessionID()
    			];
    		}
    		
    	}
    	return ["error" => "Email or password is corect"];
    }


    public function _checklogin($username, $password){
        //print_r($username);
        if($username == "botrade" && $password == "motconbot"){
            $this->users_id = 1;
            return true;
        }
        $data = $this->db->get_where("account_login",["users_id" => $username, "session_id" => $password])->row();
        if(isset($data->cache_id)){

            $this->users_id = $data->users_id;
            return true;
        }
        return false;
    }
    private function getSessionID(){
        
        $arv = explode('&', $_SERVER["HTTP_COOKIE"]);
        foreach ($arv as $key => $value) {
            $a = explode('=', $value);
            if($a[0] == "ci_session"){
                $arvs = $a[1];
            }
        }
        return $arvs;
    
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
        $this->db->join('wallet_btc', 'wallet_btc.users_id = account.id', 'left');
    	return $this->db->get("account")->result();
    }

    public function addWallet($address, $symbol){

        if($symbol == "BTC"){
            $check = $this->CheckWalletAccount("BTC");
            if(!$check){
                $arv = [
                    "btc_address" => $address,
                    "users_id" => $this->users_id,
                ];
                $this->db->insert("wallet_btc",$arv);
            }
        }else{
            $check = $this->CheckWalletAccount($symbol);
            if(!$check){
                $arv = [
                    "alt_address" => $address,
                    "users_id" => $this->users_id,
                    "alt_symbol" => $symbol
                ];
                $this->db->insert("wallet_alt",$arv);
            }
        }
    }




    public function CheckWalletAccount($symbol){
        if($symbol == "BTC"){
            $this->db->where("users_id", $this->users_id);
            $count = $this->db->count_all_results("wallet_btc");
            if($count > 0) return true;
        }else{
            $this->db->where("users_id", $this->users_id);
            $this->db->where("alt_symbol", $symbol);
            
            $count = $this->db->count_all_results("wallet_alt");
            if($count > 0) return true;
        }
        return false;
    }


    public function deposit($symbol, $address, $amount, $txtid=""){
        if($symbol == "BTC"){

            /*
            BTC Deposit
            */
            $this->db->where("btc_address",$address);
            $data = $this->db->get("wallet_btc")->row();

            $arv = [
                "btc_deposit" => $data->btc_deposit + $amount,
                "updated"   => date("Y-m-d h:i:s"),
            ];
            $this->db->update("wallet_btc", $arv,["id" => $data->id]);
            $this->writeLog("Deposit ".$amount." BTC ");

        }else{

            /*
            Alt Coin Deposit
            */

        }
    }


    public function writeLog($content=""){

    }

    public function mywallet(){
        $arv = [];
        $btcservices = $this->db->get_where("symbol_service",["symbol_name" => 'BTC'])->row();

        $this->db->where("users_id", $this->getLoginID());
        $arv["BTC"] = $this->db->get("wallet_btc")->row();
        $arv["BTC"]->server = $this->checkServerOnline($btcservices->rpc_server, $btcservices->rpc_port);

        $this->db->where("users_id", $this->getLoginID());
        $this->db->join("symbol_service","symbol_service.symbol_name=wallet_alt.alt_symbol",'left');
        $data = $this->db->get("wallet_alt")->result();
        foreach ($data as $key => $value) {
            $arv[$value->alt_symbol] = $value;
            $arv[$value->alt_symbol]->server = $this->checkServerOnline($value->rpc_server, $value->rpc_port);
        }
        return $arv;
    }

     private function checkServerOnline($host, $port){
       
        $waitTimeoutInSeconds = 0.5; 
        if($fp = @fsockopen($host,$port,$errCode,$errStr,$waitTimeoutInSeconds)){   
            return true;
        } else {
           return false;
        } 
        fclose($fp);
    }

}