<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Privateapi extends API_Private {
	public function index_get(){
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];
		$this->view($arv);
	}
	

	public function balancer_post(){

	}

	public function wallet_post(){
		$this->view($this->author->mywallet());
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
		$data = $this->db->query("select * from trade_sell where prices <= ".$prices)->result();

		$ex_amount = 0;
		foreach ($data as $key => $value) {
			$ex_amount = $ex_amount + $value->amount;
			if($ex_amount >= $amount){
				return 0;
			}else{
				$this->write_trade_history($base, $symbol, $value->amount, $prices,"buy");
				//$this->update_balancer_member_buy($value->amount, $prices);
				//$this->remove_taks_sell($ex_amount, $prices, $value->id);
			}
		}
		return $amount - $ex_amount;
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
			"total"	=> $prices * $amount
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
			"users_id" => "1",
			"hash"		=>	sha1($amount.$prices)
		];
		$this->db->insert("trade_sell", $arv);
		$this->socketio("New Sell");
		$this->view($arv);
		
	}


}