<?php
class Trade_Model extends DB_Model{

    function __construct()
    {
        parent::__construct();
    }
    public $return = [];

    /*
	Query Buy Task
    */
    public function getBuyTask($base, $symbol, $prices){
    	$this->db->where("trade_side","buy");
    	$this->db->where("trade_type","limit");
    	$this->db->where('prices =<', $prices);
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
    			# code...
    			break;
    		default:
    			# code...
    		break;
    	}
    	return $data;
    	
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
                    $updatedata = $this->updateBlancerBTC($value->users_id, $amount * $prices, $this->users_id);
                    if($updatedata == true){
                        $this->db->update("markets",$arv,["trade_id" => $value->trade_id]);
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
                    $updatedata = $this->updateBlancerBTC($value->users_id, $value->amount * $prices, $this->users_id);
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
                "users_id"  => $this->users_id,
            ];
            $this->db->insert("markets", $arv);
            $status = true;
        }
    	return ["status" => $status, "error" => $error];

    }

    /*
    Create Update Balancer
    For buyer & Seller
    */
    private function updateBlancerBTC($users_id, $balancer, $target_form_user=false){
        /*
        Get Blancer
        Target Blancer BTC Buyer
        */
        $target = false;
        if($target_form_user){
            $this->db->where("users_id", $target_form_user);
            $getbalancerTarget = $this->db->get("wallet_btc")->row();
            $total = (float)$getbalancerTarget->btc_trade_avalible - (float)$balancer;
            if($total =< 0){
                return ["error" => "btcbalancer","msg" => "Buyer not BTC"];
                exit();
            }
            $arv = [
                "btc_trade_avalible" => $total;
            ];
            $target = $this->db->update("wallet_btc",$arv,["btc_id" => $getbalancerTarget->btc_id]);
        }


        /*
        Update BTC for Seller
        */
        if($target){
            $this->db->where("users_id", $users_id);
            $getbalancer = $this->db->get("wallet_btc")->row();

            $arv = [
                "btc_trade_avalible" => (float)$getbalancer->btc_trade_avalible + (float)$balancer;
            ];
            $this->db->update("wallet_btc",$arv,["btc_id" => $getbalancer->btc_id]);
        }
        return $target;
    }

}