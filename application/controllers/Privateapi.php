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
		
		if($id == "all"){
		
			$this->db->delete("markets",["users_id" => $this->users_id]);
		}else{
			$this->db->delete("markets",["trade_id" => $id, "users_id" => $this->users_id]);
		}
		$this->socketio("reload");
	}


	
	/*
	Buy Post
	*/
	public function buy_post(){
		$base = $this->input->post("base");
		$symbol = $this->input->post("trade");
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = (float)$this->input->post("amount");
		$prices = (float)$this->input->post("prices");

		
		//$this->write_trade_history($base, $symbol, $amount, $prices,"buy");
		$arv = [
				"base" => $base,
				"symbol" => $symbol,
				"amount" => $amount,
				"prices" => $prices,
				"hash"		=>	sha1($amount.$prices),
				
			];
		$this->addMarkets($arv,"buy","limit");
		$this->socketio("New Buy");
		$this->socketio("Buy {$amount} {$symbol}","order_".$this->users_id);
		$this->view($arv);
		
		
	}

	
	/*
	Sell
	*/
	public function sell_post(){
		
		$base = $this->input->post("base");
		$symbol = $this->input->post("trade");
		
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = (float)$this->input->post("amount");
		$prices = (float)$this->input->post("prices");

		
		//$this->write_trade_history($base, $symbol, $amount, $prices,"sell");
		$arv = [
				"base" => $base,
				"symbol" => $symbol,
				"amount" => $amount,
				"prices" => $prices,
				"hash"		=>	sha1($amount.$prices),
				
			];
		$this->addMarkets($arv,"sell","limit");
		$this->socketio("New Sell");
		$this->socketio("Sell {$amount} {$symbol}","order_".$this->users_id);
		
		$this->view($arv);
		
	}

	/*
	Execute Buy
	*/

	private function addMarkets($arvs,$side="",$type="limit"){
		$arv = [
			"users_id" => $this->users_id,
			"trade_side"	=> $side,
			"trade_type"	=> $type
		];
		$arv = array_merge($arv, $arvs);

		$prices = (float)$arv["prices"];
		$amount = (float)$arv["amount"];
		$base = $arv["base"];
		$symbol = $arv["symbol"];

		/*
		Validate
		*/
		if(!$prices || !$amount || !$side || !$base || !$symbol){
			return false;
		}

		if($prices < 0 || $amount < 0){
			return false;
		}

		/*
		Query Target
		*/
		$sumamount = 0;
		if($side == "buy"){
			$sumamount = $this->targetBuy($base, $symbol, $amount, $prices);
		}else if($side == "sell"){
			$sumamount = $this->targetSell($base, $symbol, $amount, $prices);
		}
		
		if($sumamount > 0){
			$arv["amount"] = $sumamount;
			$this->db->insert("markets", $arv);
		}
	}
	
	private function targetBuy($base, $symbol, $amount, $prices){
		//$this->db->select('* prices =< '.$prices);
		$data = $this->db->query("select * from markets where prices <= ".$prices." AND trade_type = 'limit' AND trade_side='sell' AND base='".$base."' AND symbol='".$symbol."' order by prices asc")->result();
		
		$arv = [];
		foreach ($data as $key => $value) {
				if($amount <= 0) return;
				$arv[] = $value;
				/*
				Create Invoice for Seller
				*/
				$amount = $this->create_invoice($base, $symbol, $amount, $prices, $value,"sell");
		}
		
		return $amount;
	}

	private function targetSell($base, $symbol, $amount, $prices){
		//$this->db->select('* prices =< '.$prices);
		$data = $this->db->query("select * from markets where prices >= ".$prices." AND trade_type = 'limit' AND trade_side='buy' AND base='".$base."' AND symbol='".$symbol."' order by prices asc")->result();
		
		$arv = [];
		foreach ($data as $key => $value) {
				if($amount <= 0) return;
				$arv[] = $value;
				/*
				Create Invoice for Buyder
				*/
				$amount = $this->create_invoice($base, $symbol, $amount, $prices, $value,"sell");
		}
		
		return $amount;
	}


	private function create_invoice($base, $symbol, $amount, $prices, $obj, $target){
		$arv = [
			"users_id" => $obj->users_id,
			"base" => $base,
			"symbol" => $symbol,
			"action_prices" => $obj->prices,
			"target_prices" => $prices,
			"amount" => ($obj->amount <= $amount ? $obj->amount : $amount),
			"total"	=> $prices * $obj->amount,
			"trade_type"	=> $target,
			"trade_id" => $obj->trade_id
		];
		$this->db->insert("trade_invoice", $arv);

		/*
		Exit Order Member
		*/
		$this->exitTrader($obj, $amount - $obj->amount, $prices,$obj->users_id, $target);

		/*
		Add Invoice for athour
		*/
		$arv["users_id"] = $this->users_id;
		$arv["trade_type"] = ($target == "buy" ? "sell" : "bull");
		$this->db->insert("trade_invoice", $arv);

		return $amount - $obj->amount;


	}

	private function exitTrader($obj, $amount, $prices, $userid, $target){
		if($obj->amount <= $amount){
			/*
			Create Invoice for Sell
			*/
			$this->db->delete("markets",["trade_id" => $obj->trade_id]);
			//$this->write_trade_history($obj->base, $obj->symbol, $obj->amount, $prices, $target);
			/*
			Add Balancer for Buyer
			*/
			if($target == "sell"){
				$this->updateBalancerBuyer($obj->base, $obj->symbol, $obj->amount, $obj->prices, $userid);
			}else if($target == "buy"){
				$this->updateBalancerSeler($obj->base, $obj->symbol, $obj->amount, $obj->prices, $userid);
			}
		}else{
			/*
			Create Invoice for sell
			*/
			$this->db->update("markets",["amount" => $obj->amount - $amount],["trade_id" => $obj->trade_id]);
			//$this->write_trade_history($obj->base, $obj->symbol, $obj->amount - $amount, $prices, $target);
			/*
			Add Balancer for Buyer
			*/
			

			if($target == "sell"){
				$this->updateBalancerBuyer($obj->base, $obj->symbol, $obj->amount - $amount, $obj->prices, $userid);
			}else if($target == "buy"){
				$this->updateBalancerSeler($obj->base, $obj->symbol, $obj->amount - $amount, $obj->prices, $userid);
			}
		}
	}

	

	/*
	Update Balancer for buyer
	*/
	private function updateBalancerBuyer($base, $symbol, $amount, $prices, $userid){
		// Update Blancer Buyder ( when sell Alt Coin);

		/* 
		Get Info Seller
		Update Balancer BTC, Remove Blancer Alt
		*/

		$data = $this->db->get_where("wallet_btc",["users_id" => $userid])->row();
		$data_alt = $this->db->get_where("wallet_alt",["alt_symbol" => $symbol,"users_id" => $userid])->row();
		if($data_alt->alt_trade_avalible >= $amount){
			//Remove Alt Blancer Trader
			$this->db->update("wallet_alt",["alt_trade_avalible" => $data_alt->alt_trade_avalible - $amount],["alt_id" => $data_alt->alt_id]);
			//Plus BTC Balancer
			$this->db->update("wallet_btc",["alt_trade_avalible" => $data->alt_trade_avalible + ($amount * $prices)],["btc_id" => $data->btc_id]);
		}


		//Update Alt Blancer of Buyer
		// Remove BTC Blancer Buyer
		$data = $this->db->get_where("wallet_btc",["users_id" => $this->author_model->users_id])->row();
		$data_alt = $this->db->get_where("wallet_alt",["alt_symbol" => $symbol,"users_id" => $this->author_model->users_id])->row();
		if($data_alt->alt_trade_avalible >= $amount){
			//Remove Alt Blancer Trader
			$this->db->update("wallet_alt",["alt_trade_avalible" => $data_alt->alt_trade_avalible + $amount],["alt_id" => $data_alt->alt_id]);
			//Plus BTC Balancer
			$this->db->update("wallet_btc",["alt_trade_avalible" => $data->alt_trade_avalible - ($amount * $prices)],["btc_id" => $data->btc_id]);
		}
	}

	/*
	Update Balancer for seller
	*/
	private function updateBalancerSeler($base, $symbol, $amount, $prices, $userid){

		// Update Blancer Buyder ( when sell Alt Coin);

		/* 
		Get Info Buyer
		Update Balancer BTC, Remove Blancer Alt
		*/

		$data = $this->db->get_where("wallet_btc",["users_id" => $userid])->row();
		$data_alt = $this->db->get_where("wallet_alt",["alt_symbol" => $symbol,"users_id" => $userid])->row();
		if($data_alt->alt_trade_avalible >= $amount){
			//Remove Alt Blancer Trader
			$this->db->update("wallet_alt",["alt_trade_avalible" => $data_alt->alt_trade_avalible + $amount],["alt_id" => $data_alt->alt_id]);
			//Plus BTC Balancer
			$this->db->update("wallet_btc",["alt_trade_avalible" => $data->alt_trade_avalible - ($amount * $prices)],["btc_id" => $data->btc_id]);
		}


		//Update Alt Blancer of Seller
		// Remove BTC Blancer Seller
		$data = $this->db->get_where("wallet_btc",["users_id" => $this->author_model->users_id])->row();
		$data_alt = $this->db->get_where("wallet_alt",["alt_symbol" => $symbol,"users_id" => $this->author_model->users_id])->row();
		if($data_alt->alt_trade_avalible >= $amount){
			//Remove Alt Blancer Trader
			$this->db->update("wallet_alt",["alt_trade_avalible" => $data_alt->alt_trade_avalible - $amount],["alt_id" => $data_alt->alt_id]);
			//Plus BTC Balancer
			$this->db->update("wallet_btc",["alt_trade_avalible" => $data->alt_trade_avalible + ($amount * $prices)],["btc_id" => $data->btc_id]);
		}

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
	Call Your Task
	*/
	public function mytask_post(){
		$arv = [];
		
		$this->db->where("users_id",$this->users_id);
		$this->db->order_by("created","DESC");
		$this->db->limit(10);
		$arv = $this->db->get("markets")->result();

		$this->view($arv);
	}



	/*
	Info Member
	*/

	public function info_post(){
		$arv = $this->author_model->info();
		$this->view($arv);
	}
	

}