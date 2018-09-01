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


    private function gasPrices($num){
        return (float)($num - ($num * 0.001));
    }


    public function DepositAlt($ado){
        $ado = strtoupper($ado);

        $this->db->where("alt_symbol", $ado);
        $data = $this->db->get("wallet_alt")->result();

        $getcoind = $this->db->get_where("symbol_service",["symbol_name" => $ado])->row();
        if($getcoind->symbol_class == "Web3"){

            $web3 = new \Web3\Web3(new \Web3\Providers\HttpProvider(new \Web3\RequestManagers\HttpRequestManager('http://'.$getcoind->rpc_server.':'.$getcoind->rpc_port, 5)));
            $web3->eth->blockNumber(function ($err, $blockNumber){
                if ($err !== null) {
                    echo 'Error: ' . $err->getMessage();
                    return '';
                }
                echo "Block Height : ".$blockNumber->toString()."\n";
               
            });
            $password = $this->config->item("web3password");
            $blockTransit = [];
            foreach ($data as $key => $value) {

                if(!in_array($value->alt_address, $blockTransit)){
               /*
                Get Balancer Accouunt Send
                */
                $fromAccountBalancer = 0;
                $web3->eth->getBalance($value->alt_address, function ($err, $balance) use(&$fromAccountBalancer) {
                    if ($err !== null) {
                        //echo 'Error: ' . $err->getMessage();
                        return;
                    }
                    $fromAccountBalancer = $balance->toString()/1000000000000000000;
                    // Number Ethere Um /1000000000000000000
                });

                
                if($fromAccountBalancer > 0.01){

                    /*
                    Unlock Balancer Account
                    */
                    $unlockedcheck = false;
                    $web3->personal->unlockAccount($value->alt_address, $password, function($err, $unlocked) use(&$unlockedcheck){
                        $unlockedcheck = $unlocked;
                    });

                    
                    /*
                    Validate Unlock ok
                    */
                    if($unlockedcheck){
                        echo "Detech Deposit {$fromAccountBalancer} {$ado}\n";
                        $transactionID = null;
                        $num = $web3->utils->toWei($this->gasPrices($fromAccountBalancer),'ether');
                        $web3->eth->sendTransaction([
                                'from' => $value->alt_address,
                                'to' => $getcoind->basewallet,
                                'value' => $web3->utils->toHex($num,true),

                            ], function ($err, $transaction) use(&$transactionID){
                                if ($err !== null) {
                                    echo 'Error: ' . $err->getMessage();
                                    return;
                                }
                                $transactionID = $transaction;
                            });
                        if($transactionID){
                            $blockTransit[] = $value->alt_address;
                            $this->db->update("wallet_alt",["alt_amount" => ($value->alt_amount + $fromAccountBalancer),"updated" => date("Y-m-d h:i:s")],["alt_id" => $value->alt_id]);
                            //return [ "status" => "success", "code" => $number,"txt" => $transactionID];

                            
                            $this->log_member("Deposit {$fromAccountBalancer} {$ado}",$fromAccountBalancer, $transactionID,$ado, true, $value->users_id);
                            echo "Deposit {$fromAccountBalancer} {$ado} to {$value->alt_address}\n";
                        }
                    }
                    

                }
            }
            }
        }
    }


    private function log_member($name, $amount, $tx, $symbol, $status, $users_id){
        $arv = [
            "name" => $name,
            "amount" => $amount,
            "tx" => $tx,
            "status" => $status,
            "users_id" => $users_id,
            "symbol"    => $symbol
        ];
        $this->db->insert("log_member", $arv);
    }

    private function ScanAltDeposit($obj){
        $this->db->where("alt_symbol", $obj->symbol_name);
        $data = $this->db->get("wallet_alt")->result();

        if($obj->symbol_class == "Web3"){
            $web3 = new \Web3\Web3(new \Web3\Providers\HttpProvider(new \Web3\RequestManagers\HttpRequestManager('http://'.$data->rpc_server.':'.$data->rpc_port, 5)));
            $password = $this->config->item("web3password");
            foreach ($data as $key => $value) {

                /*
                Get Balancer Accouunt Send
                */
                $fromAccountBalancer = 0;
                $web3->eth->getBalance($value->alt_address, function ($err, $balance) use(&$fromAccountBalancer) {
                    if ($err !== null) {
                        echo 'Error: ' . $err->getMessage();
                        return;
                    }
                    $fromAccountBalancer = $balance->toString();
                });

                echo $fromAccountBalancer;
                if($fromAccountBalancer > 0){

                    /*
                    Unlock Balancer Account
                    */
                    $unlockedcheck = false;
                    $web3->personal->unlockAccount($value->alt_address, $password, function($err, $unlocked) use(&$unlockedcheck){
                        $unlockedcheck = $unlocked;
                    });

                    /*
                    Validate Unlock ok
                    */
                    if($unlockedcheck){
                    }

                }
                
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