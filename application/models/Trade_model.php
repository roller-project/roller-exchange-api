<?php
class Trade_Model extends DB_Model{

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
    	return $getSellTask;

    }


}