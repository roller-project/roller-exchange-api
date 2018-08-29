<?php
class Trade_Model extends DB_Model{
    private $login_id = 0;
    public $return = [];
    function __construct()
    {
        parent::__construct();
        $this->login_id = $this->author_model->getLoginID();

    }
    
    public function setLogin($id){
        $this->login_id = $id;
    }
    /*
	Query Buy Task
    */
    public function getBuyTask($base, $symbol, $prices){
    	$this->db->where("trade_side","buy");
    	$this->db->where("trade_type","limit");
    	$this->db->where('prices >=', $prices);
    	$this->db->where("symbol",$symbol);
    	$this->db->where("base",$base);
    	return $this->db->get("markets")->result();
    }

    /*
	Query Sell Task
    */
    public function getSellTask($base, $symbol, $prices){
    	$this->db->where("trade_side","sell");
    	$this->db->where("trade_type","limit");
    	$this->db->where('prices <=', (float)$prices);
    	$this->db->where("symbol",$symbol);
    	$this->db->where("base",$base);
    	return $this->db->get("markets")->result();
    }

    /*
	Run Query
    */

    public function dbQuery($base, $symbol, $amount, $prices, $type=fase){
    	$data = [];
    	switch ($type) {
    		case 'buylimit':
    			$data =$this->createTaskBuy($base, $symbol, $amount, $prices, "buylimit");
    		break;
    		case 'selllimit':
    			$data =$this->createTaskSell($base, $symbol, $amount, $prices, "selllimit");
    			break;
    		default:
    			# code...
    		break;
    	}
    	return $data;
    	
    }

    private function call_amount($amount, $prices){
        return (float)$amount * (float)$prices;
    }

    private function createTaskSell($base, $symbol, $amount, $prices, $type=""){
        $getBuyTask = $this->getBuyTask($base, $symbol, $prices);
        $target_amount = 0;
        $error = false;
        $status = false;

        foreach ($getBuyTask as $key => $value) {
            if($amount > 0 && !$error){
                if($value->amount > $amount){
                    $updatedata = $this->updateBlancerBTC($this->login_id, $this->call_amount($amount,$prices), $value->users_id);
                    if($updatedata == true){
                        $this->db->delete("markets",["trade_id" => $value->trade_id]);
                        $status = true;
                    }else{
                        $error = $updatedata;
                    }
                    $amount = 0;
                }else{
                    $amount = $amount - $value->amount;

                    $arvs = [
                        "amount" => $amount
                    ];
                    /*
                    Update Market ID
                    */
                    $updatedata = $this->updateBlancerBTC($this->login_id, $this->call_amount($amount,$prices), $value->users_id);
                    if($updatedata == true){
                        $this->db->update("markets",$arvs,["trade_id" => $value->trade_id]);
                        $status = true;
                    }else{
                        $error = $updatedata;
                    }
                    
                }
            }
        }

        /*
        Create Task buy for member if not sell
        */
        $arv = [];
        if($amount > 0 && !$error){
            $arv = [
                "trade_side" => "sell",
                "trade_type" => "limit",
                "base"      => $base,
                "symbol"    =>  $symbol,
                "prices"    => $prices,
                "amount"    => $amount,
                "users_id"  => $this->login_id,
                "hash"      =>  sha1(time())
            ];
            if($this->blockTradeAltBalancer($symbol,$amount)){
                $this->db->insert("markets", $arv);
                 $status = true;
            }else{
                 $status = false;
                 $error = $symbol." Blancer Empty";
            }
           
        }
        return ["status" => $status, "error" => $error];
    }

    /*
	Create Task Buy
    */

    private function createTaskBuy($base, $symbol, $amount, $prices, $type=""){
    	$getSellTask = $this->getSellTask($base, $symbol, $prices);
        $target_amount = 0;
        $error = false;
        $status = false;
        
        foreach ($getSellTask as $key => $value) {
            if($amount > 0 && !$error){
                if($value->amount > $amount){
                    
                    $arvs = [
                        "amount" => $value->amount - $amount
                    ];
                    /*
                    Update Market ID
                    */
                    $updatedata = $this->updateBlancerBTC($value->users_id, $this->call_amount($amount,$prices), $this->login_id);
                    if($updatedata == true){
                        $this->db->update("markets",$arvs,["trade_id" => $value->trade_id]);
                        $status = true;
                    }else{
                        $error = $updatedata;
                    }
                    $amount = 0;
                }else{
                    $amount = $amount - $value->amount;
                    /*
                    Remove Markets
                    */
                    $updatedata = $this->updateBlancerBTC($value->users_id, $this->call_amount($value->amount , $prices), $this->login_id);
                    if($updatedata == true){
                        $this->db->delete("markets",["trade_id" => $value->trade_id]);
                        $status = true;
                    }else{
                        $error = $updatedata;
                    }
                    
                }
            }
        }
        
        /*
        Create Task buy for member if not sell
        */
        $arv = [];
        if($amount > 0 && !$error){
            $arv = [
                "trade_side" => "buy",
                "trade_type" => "limit",
                "base"      => $base,
                "symbol"    =>  $symbol,
                "prices"    => $prices,
                "amount"    => $amount,
                "users_id"  => $this->login_id,
                "hash"      =>  sha1(time())
            ];
            if($this->blockTradeBTCBalancer($prices * $amount)){
                $this->db->insert("markets", $arv);
                 $status = true;
            }else{
                 $status = false;
                 $error = "BTC Blancer Empty";
            }
           
        }
    	return ["status" => $status, "error" => $error];

    }




    /*
    Block Trader Balancer BTC
    */
    private function blockTradeAltBalancer($symbol, $balancer){
        $this->db->where("users_id", $this->login_id);
        $this->db->where("alt_symbol", $symbol);
        $getbalancerTarget = $this->db->get("wallet_alt")->row();

        
        /*
            Check Balancer
        */
        if(!$getbalancerTarget || (float)$getbalancerTarget->alt_trade_avalible < $balancer) return false;

        /*
        Query Block
        */

        $total = (float)$getbalancerTarget->alt_trade_avalible - (float)$balancer;
        $blockTotal = (float)$getbalancerTarget->alt_block_balancer + (float)$balancer;

        $arv = [
            "alt_trade_avalible" => $total,
            "alt_block_balancer" => $blockTotal,
            "updated"   =>  date("Y-m-d h:i:s")
        ];
        $target = $this->db->update("wallet_alt",$arv,["alt_id" => $getbalancerTarget->alt_id]);
        if($target){
            return true;
        }
        return false;
    }

    /*
    Block Trader Balancer BTC
    */
    private function blockTradeBTCBalancer($balancer){
        $this->db->where("users_id", $this->login_id);
        $getbalancerTarget = $this->db->get("wallet_btc")->row();

        
        /*
            Check Balancer
        */
        if(!$getbalancerTarget || (float)$getbalancerTarget->btc_trade_avalible < $balancer) return false;

        /*
        Query Block
        */

        $total = (float)$getbalancerTarget->btc_trade_avalible - (float)$balancer;
        $blockTotal = (float)$getbalancerTarget->btc_block_balancer + (float)$balancer;

        $arv = [
            "btc_trade_avalible" => $total,
            "btc_block_balancer" => $blockTotal,
            "updated"   =>  date("Y-m-d h:i:s")
        ];
        $target = $this->db->update("wallet_btc",$arv,["btc_id" => $getbalancerTarget->btc_id]);
        if($target){
            return true;
        }
        return false;
    }

    /*
    Un Block Trader Balancer BTC
    */

    private function unblockTradeBTCBalancer($balancer){
        $this->db->where("users_id", $this->login_id);
        $getbalancerTarget = $this->db->get("wallet_btc")->row();

        /*
            Check Balancer
        */
        if((float)$getbalancerTarget->btc_block_balancer < $balancer) return false;

        /*
        Query Un Block
        */

        $total = (float)$getbalancerTarget->btc_trade_avalible + (float)$balancer;
        $blockTotal = (float)$getbalancerTarget->btc_block_balancer - (float)$balancer;

        $arv = [
            "btc_trade_avalible" => $total,
            "btc_block_balancer" => $blockTotal,
            "updated"   =>  date("Y-m-d h:i:s")
        ];
        $target = $this->db->update("wallet_btc",$arv,["btc_id" => $getbalancerTarget->btc_id]);
        if($target){
            return true;
        }
        return false;
    }
    /*
    Create Update Balancer
    For buyer & Seller
    */
    private function updateBlancerBTC($users_id, $balancer, $target_form_user=false){
        /*
        Get Blancer
        Target Blancer BTC Buyer
        Query to target form user
        */
        $target = false;
        if($target_form_user){
            $this->db->where("users_id", $target_form_user);
            $getbalancerTarget = $this->db->get("wallet_btc")->row();
            $total = (float)$getbalancerTarget->btc_trade_avalible - (float)$balancer;
            if($total <= 0){
                return ["error" => "btcbalancer","msg" => "Buyer not BTC"];
                exit();
            }
            $arv = [
                "btc_trade_avalible" => $total
            ];
            $target = $this->db->update("wallet_btc",$arv,["btc_id" => $getbalancerTarget->btc_id]);

            /*
                Add Alt Balancer
            */
        }


        /*
        Update BTC for Seller
        Query to users_id
        */
        if($target){
            $this->db->where("users_id", $users_id);
            $getbalancer = $this->db->get("wallet_btc")->row();

            $arv = [
                "btc_trade_avalible" => (float)$getbalancer->btc_trade_avalible + (float)$balancer
            ];
            $this->db->update("wallet_btc",$arv,["btc_id" => $getbalancer->btc_id]);

            /*
                Remove Alt Balancer
            */
        }
        return $target;
    }

}