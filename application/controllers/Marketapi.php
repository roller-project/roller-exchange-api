<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Marketapi extends API_Public {
	public function index_get(){
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];
		$this->view($arv);
	}

	public function symbol_get(){
		$data = new stdClass;
		
		$this->db->where("is_parent",1);
		$this->db->order_by("shorts","ASC");
		
		$groups = $this->db->get("symbol_trade")->result();
		$this->db->reset_query();
		foreach ($groups as $key => $value) {
			$this->db->select("symbol_id as id, symbol_name as symbol, trade_with as base, symbol_fullname as name, symbol_icon as icon, last_prices as lastPrice, high_24h as high, low_24h as low, change_24h as change, volume_24h as volume");
			$this->db->where("trade_with",$value->symbol_name);
			$this->db->order_by("shorts","ASC");
			$data_alt = $this->db->get("symbol_trade")->result();
			$attr = new stdClass;
			foreach ($data_alt as $key_alt => $value_alt) {
				$attr->{$value_alt->symbol} = $value_alt;
			}
			$data->{$value->symbol_name} = $attr;
			
		}
		
		$this->view($data);
	}

	public function trade_get(){
		$trade = $this->input->get("trade");
		list($symbol,$base) = explode('/', $trade);
		if(!$symbol || !$base) {
			$this->view(["error" => true,"msg" => "Symbol or basecoin Empty"]);
			return;
		}

		$arv = [
			"sell" => $this->book_sell($symbol, $base),
			"buy" => $this->book_buy($symbol, $base),
			"history" => $this->book_history($symbol, $base),
			"sumary" => $this->sumary($symbol, $base)
		];

		$this->view($arv);
	}

	private function sumary($symbol, $base){
		$data = $this->db->query('SELECT *, SUM(amount) as volume, SUM(total) as volumebtc from trade_history, (SELECT MIN(prices) AS low, MAX(prices) AS high FROM trade_history) AS m WHERE created > DATE_SUB(NOW(), INTERVAL 24 HOUR) order by trade_id DESC')->row();
		
		return [
			"change" => ($data->high/$data->low)*100,
			"high" => $data->high,
			"low" => $data->low,
			"volume" => $data->volume,
			"volumebtc" => $data->volumebtc,
			"lastprice" => $data->prices
		];
	}

	private function book_sell($symbol, $base){
		

		$this->db->select("*, sum(amount) as amount");
		$this->db->where("symbol",$symbol);
		$this->db->where("base",$base);
		$this->db->group_by("prices");
		$this->db->order_by("prices","DESC");
		$this->db->limit(20,0);
		return $this->db->get("trade_sell")->result();
		
	}

	private function book_buy($symbol, $base){
		
		$this->db->select("*, sum(amount) as amount");
		$this->db->where("symbol",$symbol);
		$this->db->where("base",$base);
		$this->db->group_by("prices");

		$this->db->order_by("prices","ASC");
		$this->db->limit(20,0);
		return $this->db->get("trade_buy")->result();
		
	}

	
	private function book_history($symbol, $base){
		$this->db->where("symbol",$symbol);
		$this->db->where("base",$base);
		$this->db->order_by("trade_id","DESC");
		$this->db->limit(20,0);
		return $this->db->get("trade_history")->result();
	}
}