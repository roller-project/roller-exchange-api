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

            if($request) {
                $status = $request->result();
                $arv = ["chain" => $status["chain"], "block" => $status["blocks"], "peer" => $status["headers"], "basewallet" => ""];
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
    
}