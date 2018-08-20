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
			"change" => number_format(($data->high/$data->low),2)."%",
			"high" => $data->high,
			"low" => $data->low,
			"volume" => $data->volume,
			"volumebtc" => number_format($data->volumebtc,8),
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


	/*
	OLHC Data
	*/

	public function ohlc_get(){
		$periodSize = $this->input->get("period");
		$this->getError($periodSize);
		$limit = $this->input->get("limit");
		$symbol = $this->input->get("symbol");
		$this->getError($symbol);
		$base = $this->input->get("base");
		$this->getError($base);

		$limit = (is_numeric($limit) && $limit > 100 ? $limit : 240);
		$timeslice = 60;
        switch($periodSize) {
            case '1m':
                $timescale = '1 minute';
                $timeslice = 60;
                break;
            case '5m':
                $timescale = '5 minutes';
                $timeslice = 300;
                break;
            case '10m':
                $timescale = '10 minutes';
                $timeslice = 600;
                break;
            case '15m':
                $timescale = '15 minutes';
                $timeslice = 900;
                break;
            case '30m':
                $timescale = '30 minutes';
                $timeslice = 1800;
                break;
            case '1h':
                $timescale = '1 hour';
                $timeslice = 3600;
                break;
            case '4h':
                $timescale = '4 hours';
                $timeslice = 14400;
                break;
            case '1d':
                $timescale = '1 day';
                $timeslice = 86400;
                break;
            case '1w':
                $timescale = '1 week';
                $timeslice = 604800;
                break;
        }
        $current_time = time();
        $offset = ($current_time - ($timeslice * $limit)) -1;
        /*
		$data = $this->db->query("select  
				SUBSTRING_INDEX(GROUP_CONCAT(CAST(prices AS CHAR) ORDER BY created ASC), ',', 1 ) AS `open`,
                MAX(prices) AS `high`,
                MIN(prices) AS `low`,
                SUBSTRING_INDEX(GROUP_CONCAT(CAST(prices AS CHAR) ORDER BY created DESC), ',', 1 ) AS `close`,
               
                SUM(total) AS volume,
                ROUND((CEILING(UNIX_TIMESTAMP(`created`) / $timeslice) * $timeslice)) AS openTime
                 FROM trade_history WHERE base='".$base."' AND symbol='".$symbol."' GROUP BY openTime ORDER BY openTime DESC")->result();
		*/
        
        $data = $this->db->query("select FLOOR(MIN(UNIX_TIMESTAMP(`created`))/$timeslice)*$timeslice AS created, SUM(amount) AS volume, SUBSTRING_INDEX(MIN(CONCAT(`created`, '_', prices)), '_', -1) AS `open`, MAX(prices) AS `high`, MIN(prices) AS `low`, SUBSTRING_INDEX(MAX(CONCAT(`created`, '_', prices)), '_', -1) AS `close` FROM trade_history WHERE base='".$base."' AND symbol='".$symbol."' GROUP BY FLOOR(UNIX_TIMESTAMP(`created`)/$timeslice) ORDER BY created")->result();
        //$this->view($data);exit();
		$arv = [];
		$createPeriod = $this->renderChart($offset, $timeslice, $limit);
		
		foreach ($data as $key => $value) {
			
			$arv = $this->magre_period($value,$createPeriod,$value->created);
			//$arv = date('Y-m-d h:i:s',$value->created);

		}
		
		
		$this->view($arv);
	}

	private function magre_period($a, $b, $time){
		$arv = [];
		$open = 0;
		$low = 0;
		$high = 0;
		$close = 0;
		$volume = 0;

		foreach ($b as $key => $value) {
			if($value->track == $time){
				
				$open = $a->open;
				$low = $a->low;
				$high = $a->high;
				$close = $a->close;
				$volume = $a->volume;
				
			}
			//$value->openTime = $value->openTime;
			$value->open = number_format($open,8);
			$value->low = number_format($low,8);
			$value->high = number_format($high,8);
			$value->close = number_format($close,8);
			$value->volume = number_format($volume,8);

			$arv[] = $value;
		}
		return $arv;
	}


	private function array_sort($array, $on, $order=SORT_ASC)
	{
	    $new_array = array();
	    $sortable_array = array();

	    if (count($array) > 0) {
	        foreach ($array as $k => $v) {
	            if (is_array($v)) {
	                foreach ($v as $k2 => $v2) {
	                    if ($k2 == $on) {
	                        $sortable_array[$k] = $v2;
	                    }
	                }
	            } else {
	                $sortable_array[$k] = $v;
	            }
	        }

	        switch ($order) {
	            case SORT_ASC:
	                asort($sortable_array);
	            break;
	            case SORT_DESC:
	                arsort($sortable_array);
	            break;
	        }

	        foreach ($sortable_array as $k => $v) {
	            $new_array[$k] = $array[$k];
	        }
	    }

	    return $new_array;
	}

	private function renderChart($offsetTime, $period, $limit){
		$arv = [];
		
		$track = ceil($offsetTime/$period);
		$end = floor($offsetTime/$period);
		$offset = floor($offsetTime/$period) * $period;

		for ($i=0; $i < $limit; $i++) { 
			$end = $offset + ($period * $i);
			
				$obj = new stdClass();
				$obj->now = date('Y-m-d h:i:s',time());
				
				$obj->start = date('Y-m-d h:i:s',$offset + (($period * $i) - ($period + 1)));
				$obj->end = date('Y-m-d h:i:s',$end);

				$obj->track = $end;
				$obj->openTime = $end * 1000;
				$obj->open = 0;
				$obj->low = 0;
				$obj->high = 0;
				$obj->close = 0;
				$obj->volume = 0;

				$arv[$end] = $obj;
			
		}
		return $this->array_sort($arv,'openTime','SORT_ASC');
	}
}