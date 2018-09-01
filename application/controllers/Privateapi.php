<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Privateapi extends API_Private {

	function __construct()
	{
		parent::__construct();
		$this->load->model("coind_model");
		$this->load->model("trade_model");
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
		$base = trim(strtoupper($this->input->post("base")));
		$symbol = trim(strtoupper($this->input->post("symbol")));
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = (float)$this->input->post("amount");
		$prices = (float)$this->input->post("prices");

		$arv = $this->trade_model->dbQuery($base, $symbol, $amount, $prices, "buylimit");
		
		/*
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
		
		*/
		$this->view($arv);
		
		
	}

	
	/*
	Sell
	*/
	public function sell_post(){
		
		$base = trim(strtoupper($this->input->post("base")));
		$symbol = trim(strtoupper($this->input->post("symbol")));
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = (float)$this->input->post("amount");
		$prices = (float)$this->input->post("prices");

		$arv = $this->trade_model->dbQuery($base, $symbol, $amount, $prices, "selllimit");
		
		$this->view($arv);
		
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