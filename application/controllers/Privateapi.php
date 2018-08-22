<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Privateapi extends API_Private {

	function __construct()
	{
		parent::__construct();
		$this->load->model("coind_model");
	}

	public function checklogin_post(){
		$this->view(["status" => "login"]);
	}
	public function index_get(){
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];
		$this->view($arv);
	}
	

	public function balancer_post(){

	}


	/*
	Get All Wallet client
	*/
	public function wallet_post(){
		$this->view($this->author_model->mywallet());
	}

	/*
	Genner Wallet 
	*/
	public function gender_wallet_post(){
		$symbol = $this->input->post("symbol");
		$this->getError($symbol);
		if(!$this->author_model->CheckWalletAccount($symbol)){
			$this->coind_model->getCreateWallet($symbol);
			$arv = ["wallet" => $wallet];
		}else{
			$arv = ["error" => "walletready", "msg" => "{$symbol} Ready Create"];
		}
		$this->view($arv);
	}


	public function cancel_post(){
		$id = $this->input->post("id");
		$type = $this->input->post("type");
		if($type == "sell"){
			$this->db->delete("trade_sell",["trade_id" => $id]);
		}
		if($type == "buy"){
			$this->db->delete("trade_buy",["trade_id" => $id]);
		}
		$this->socketio("reload");
	}


	public function task_post(){
		$buy = $this->db->get_where("trade_buy",["users_id" => 1])->result();
		$sell = $this->db->get_where("trade_sell",["users_id" => 1])->result();
		$arv = [
			"buy" => $buy,
			"sell" => $sell,
		];
		$this->view($arv);
	}
	/*
	Buy Post
	*/
	public function buy_post(){
		$trade = $this->input->post("trade");
		list($symbol,$base) = explode('/', $trade);
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = $this->input->post("amount");
		$prices = $this->input->post("prices");

		$this->socketio("New Buy");
		$this->write_trade_history($base, $symbol, $amount, $prices,"buy");
		$arv = [
				"base" => $base,
				"symbol" => $symbol,
				"amount" => $amount,
				"prices" => $prices,
				"users_id" => $this->users_id,
				"hash"		=>	sha1($amount.$prices)
			];
			$this->db->insert("trade_buy", $arv);
			$this->view($arv);
		/*
		$execute = $this->execute_buy($base, $symbol, $amount, $prices);
		if($execute){
			$arv = [
				"base" => $base,
				"symbol" => $symbol,
				"amount" => $execute,
				"prices" => $prices,
				"users_id" => "1",
				"hash"		=>	sha1($amount.$prices)
			];
			$this->db->insert("trade_buy", $arv);
			$this->view($arv);
		}else{
			$this->view([]);
		}
		*/
		
	}

	/*
	Execute Buy
	*/

	
	private function execute_buy($base, $symbol, $amount, $prices){
		//$this->db->select('* prices =< '.$prices);
		$data = $this->db->query("select * from trade_sell where prices <= ".$prices." order by prices asc")->result();
		
		
		$arv = [];
		foreach ($data as $key => $value) {
			
			
				if($amount <= 0) return;
				$arv[] = $value;
				$amount = $this->create_invoice($base, $symbol, $amount, $prices, $value,"buy");
				//$this->update_balancer_member_buy($value->amount, $prices);
				//$this->remove_taks_sell($ex_amount, $prices, $value->id);
			
		}
		print_r($arv);
		return $amount - $ex_amount;
	}

	private function create_invoice($base, $symbol, $amount, $prices, $obj, $target){
		$arv = [
			"users_id" => $this->users_id,
			"base" => $base,
			"symbol" => $symbol,
			"action_prices" => $obj->prices,
			"target_prices" => $prices,
			"amount" => ($obj->amount <= $amount ? $obj->amount : $amount),
			"total"	=> $prices * $obj->amount,
			"trade_type"	=> $target,
			"form_id" => $obj->trade_id
		];
		$this->db->insert("trade_invoice", $arv);

		$this->exitTradeSell($obj, $amount - $obj->amount, $prices,1);
		return $amount - $obj->amount;


	}

	private function exitTradeSell($obj, $amount, $prices, $userid){
		if($obj->amount <= $amount){
			/*
			Create Invoice for Sell
			*/
			$this->db->delete("trade_sell",["trade_id" => $obj->trade_id]);
			$this->write_trade_history($obj->base, $obj->symbol, $obj->amount, $prices, "buy");
			/*
			Add Balancer for Buyer
			*/
			$this->updateBalancerBuyer($obj->base, $obj->symbol, $obj->amount, $userid);
		}else{
			/*
			Create Invoice for sell
			*/
			$this->db->update("trade_sell",["amount" => $obj->amount - $amount],["trade_id" => $obj->trade_id]);
			$this->write_trade_history($obj->base, $obj->symbol, $obj->amount - $amount, $prices, "buy");
			/*
			Add Balancer for Buyer
			*/
			$this->updateBalancerBuyer($obj->base, $obj->symbol, $obj->amount - $amount, $userid);
		}
	}

	private function exitTradeBuy($obj, $amount, $prices){
		if($obj->amount <= $amount){
			/*
			Create Invoice for buy
			*/
			$this->db->delete("trade_buy",["trade_id" => $obj->trade_id]);
			$this->write_trade_history($obj->base, $obj->symbol, $obj->amount, $prices, "sell");
		}else{
			/*
			Create Invoice for buy
			*/
			$this->db->update("trade_buy",["amount" => $obj->amount - $amount],["trade_id" => $obj->trade_id]);
			$this->write_trade_history($obj->base, $obj->symbol, $obj->amount - $amount, $prices, "sell");
		}
	}

	/*
	Update Balancer for buyer
	*/
	private function updateBalancerBuyer($base, $symbol, $amount, $userid){

	}

	/*
	Update Balancer for seller
	*/
	private function updateBalancerSeler($base, $symbol, $amount, $userid){

	}
	/*
	Write History Trade
	*/

	private function write_trade_history($base, $symbol, $amount, $prices, $target){
		$arv = [
			"trade_type" => $target,
			"base" => $base,
			"symbol"	=> $symbol,
			"amount" => $amount,
			"prices" => $prices,
			"hash" => sha1($base.$symbol.$amount.$prices),
			"total"	=> $prices * $amount,
			"users_id" => $this->users_id
		];
		$this->db->insert("trade_history",$arv);
	}
	/*
	Sell
	*/
	public function sell_post(){
		$trade = $this->input->post("trade");
		list($symbol,$base) = explode('/', $trade);
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = $this->input->post("amount");
		$prices = $this->input->post("prices");
		$this->write_trade_history($base, $symbol, $amount, $prices,"sell");
		$arv = [
			"base" => $base,
			"symbol" => $symbol,
			"amount" => $amount,
			"prices" => $prices,
			"users_id" => $this->users_id,
			"hash"		=>	sha1($amount.$prices)
		];
		$this->db->insert("trade_sell", $arv);
		$this->socketio("New Sell");
		$this->view($arv);
		
	}

	/*
	Call Your Task
	*/
	public function mytask_post(){
		$arv = [];
		$this->db->where("users_id",$this->users_id);
		$this->db->order_by("created","DESC");
		$this->db->limit(5);
		$sell = $this->db->get("trade_sell")->result();

		$this->db->where("users_id",$this->users_id);
		$this->db->order_by("created","DESC");
		$this->db->limit(5);
		$buy = $this->db->get("trade_buy")->result();
		
		
		foreach ($sell as $key => $value) {
			$value->type = "sell";
			$arv[strtotime($value->created)] = $value;
		}

		foreach ($buy as $key => $value) {
			$value->type = "buy";
			$arv[strtotime($value->created)] = $value;
		}
		asort($arv);

		$this->view($arv);
	}


	

}