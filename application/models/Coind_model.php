<?php
class Coind_Model extends DB_Model{

    public $return = [];


	public function AllList($search="") {
		$this->db->limit($this->limit,$this->start);
        return $this->db->get("symbol_service")->result();
    }

    public function status($obj=""){
    	$arv = [];

        if(!$this->checkServerOnline($obj->rpc_server, $obj->rpc_port)){
            return [
                "chain" => "offline",
                "block" => 0,
                "peer" => 0,
                "basewallet" => "N/A"
            ];
        }

	    

        if($obj->symbol_class == "JsonRPC"){
            $class = "\JsonRPC\\Client";
            $connect = new $class(["scheme" => "http", "host" => $obj->rpc_server, "port" => $obj->rpc_port, "user" => $obj->rpc_username, "pass" => $obj->rpc_password]);

            $request = $connect->request("getblockchaininfo");

            $peer = $connect->request("getpeerinfo");

            if($request) {
                $status = $request->result();
                $arv = ["chain" => $status["chain"], "block" => $status["blocks"], "peer" => count($peer->result()), "basewallet" => $obj->basewallet];
            }
            return $arv;
            
        }

        if($obj->symbol_class == "Web3"){
           
           $web3 = new \Web3\Web3(new \Web3\Providers\HttpProvider(new \Web3\RequestManagers\HttpRequestManager('http://'.$obj->rpc_server.':'.$obj->rpc_port, 5)));
        
            $web3->getNet()->peerCount(function($a, $b){
                 $this->setContents(["peer" => $b->toString()]);
            });



            $web3->getEth()->blockNumber(function($a, $b){
                
                 $this->setContents(["block" => $b->toString()]);
            });

            $web3->getEth()->coinbase(function($a, $b){
                
                 $this->setContents(["basewallet" => $b]);
            });
            
            
            $this->return["chain"] = "main";

           $arv = $this->getReturn();
        
        }else{
            $arv = [
                "chain" => "offline",
                "block" => 0,
                "peer" => 0
            ];
        }
       
        
        return $arv;

    	//return "not ready";
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

    public function setContents($arv=[]){
        $this->return = array_merge($this->return, $arv);
    }

    public function getReturn(){
        $return = $this->return;
        $this->return = [];
        return $return;
    }



    public function getServices($symbol){
        $this->db->where("symbol_name", $symbol);
        return $this->db->get("symbol_service")->row();
    }


    public function getCreateWallet($symbol){
        $obj = $this->getServices($symbol);
        if(!$obj) return false;

        if($this->checkServerOnline($obj->rpc_server, $obj->rpc_port)){
           
            if($obj->symbol_class == "JsonRPC"){
                $class = "\JsonRPC\\Client";
                $connect = new $class(["scheme" => "http", "host" => $obj->rpc_server, "port" => $obj->rpc_port, "user" => $obj->rpc_username, "pass" => $obj->rpc_password]);
                
                $request = $connect->request("getnewaddress");

               
                if($request) {
                    $wallet = $request->result();
                    if($wallet){
                        $this->author_model->addWallet($wallet, $symbol);
                    }
                   return $wallet;
                }
                return false;
                
            }else if($obj->symbol_class == "Web3"){
                $web3 = new \Web3\Web3(new \Web3\Providers\HttpProvider(new \Web3\RequestManagers\HttpRequestManager('http://'.$obj->rpc_server.':'.$obj->rpc_port, 5)));
                $password = $this->config->item("web3password");
                $web3->personal->newAccount($password, function ($err, $account) use ($symbol){
                    if ($err !== null) {
                        echo 'Error: ' . $err->getMessage();
                        return;
                    }
                    $wallet = $account;
                    if($wallet){
                         $this->author_model->addWallet($wallet, $symbol);
                    }
                });
            }
        }
    }

    public function ScanMoveToBaseCoind($obj){
        if($obj->symbol_name != "BTC"){
           $this->ScanAltDeposit($obj);
       }
    }

    /*
    BTC deposit
    */
    public function ScanBtcDeposit(){
        $obj = $this->getServices("BTC");
        if(!$obj) return false;

        if($this->checkServerOnline($obj->rpc_server, $obj->rpc_port)){
            if($obj->symbol_class == "JsonRPC"){
                 $class = "\JsonRPC\\Client";
                 $connect = new $class(["scheme" => "http", "host" => $obj->rpc_server, "port" => $obj->rpc_port, "user" => $obj->rpc_username, "pass" => $obj->rpc_password]);
                 $data = $this->db->get("wallet_btc")->result();
                 foreach ($data as $key => $value) {
                    $request = $connect->request("getbalance",[$value->btc_address]);
                    if($request) {
                        $walletAmount = (float)trim($request->result());
                        if($walletAmount > 0.001){
                            $this->sendBTCFromUserToCoinbase($connect,$value->btc_address, $obj->basewallet, $walletAmount);
                        }
                    }
                    
                 }
            }
        }
    }

    public function sendBTCFromUserToCoinbase($connect,$form, $to, $amount){
        
        $data = $connect->request("sendtoaddress",[$to,$amount,"deposit","deposit id"]);
        if($data){
            $validate = $data->result();
            if($validate){
                $this->author_model->deposit("BTC", $form, $amount);
                $this->writeTxt("BTC",$validate, $amount, $form);
            }
        }
    }


    public function writeTxt($symbol, $txt, $amount, $form){

    }



    private function ScanAltDeposit($obj){
        $this->db->where("alt_symbol", $obj->symbol_name);
        $data = $this->db->get("wallet_alt")->result();

        if($obj->symbol_class == "Web3"){
            foreach ($data as $key => $value) {
                # code...
            }
        }
        
    }


    public function listAllAddress($symbol){
        $obj = $this->getServices($symbol);
        if(!$obj) return false;

        if($this->checkServerOnline($obj->rpc_server, $obj->rpc_port)){
           
            if($obj->symbol_class == "JsonRPC"){
                $class = "\JsonRPC\\Client";
                $connect = new $class(["scheme" => "http", "host" => $obj->rpc_server, "port" => $obj->rpc_port, "user" => $obj->rpc_username, "pass" => $obj->rpc_password]);
                
                $request = $connect->request("getbalance",["3NqCHgYAXGaxNL2tHQ8rLcKvLaxrWQF21i"]);
                print_r((float)trim($request->result())); exit();
               
                if($request) {
                    $wallet = $request->result();
                    
                   return $wallet;
                }
                return false;
                
            }
        }
    }
    
}