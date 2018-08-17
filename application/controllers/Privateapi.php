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

	}

	public function buy_post(){
		$trade = $this->input->post("trade");
		list($symbol,$base) = explode('/', $trade);
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = $this->input->post("amount");
		$prices = $this->input->post("prices");

		$arv = [
			"base" => $base,
			"symbol" => $symbol,
			"amount" => $amount,
			"prices" => $prices,
			"users_id" => "1",
			"hash"		=>	sha1($amount.$prices)
		];
		$this->db->insert("trade_buy", $arv);
		$this->view($arv);
	}

	public function sell_post(){
		$trade = $this->input->post("trade");
		list($symbol,$base) = explode('/', $trade);
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$amount = $this->input->post("amount");
		$prices = $this->input->post("prices");

		$arv = [
			"base" => $base,
			"symbol" => $symbol,
			"amount" => $amount,
			"prices" => $prices,
			"users_id" => "1",
			"hash"		=>	sha1($amount.$prices)
		];
		$this->db->insert("trade_sell", $arv);
		$this->view($arv);
	}


}