<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bottrade extends Robottrade {
	public function autotrade(){
		
		while (true) {
			$this->db->order_by("trade_id","DESC");
			$lastprices = $this->db->get_where("trade_history")->row();
			$arv = [];
			$arvprices = [0.00003,0.0003,0.00002,0.0002,0.00004,0.0004,0.002,0.0000003];
			
			for ($i = 0; $i < 20; ++$i)
			{
				$key = array_rand($arvprices,1);
			    $arv[] = (float)$lastprices->prices + 0.000025 + (0.0000001 * $i);
			   // $arv[] = (float)$lastprices->prices - (0.00000001 * $i);
			}

			
			$rand = mt_rand(1,3);
			for ($i=0; $i <$rand ; $i++) { 
				$key = array_rand($arv,1);
				$this->curl("buy",["trade" => "ROL/BTC","prices" => $arv[$key],"amount" => mt_rand(1,10)]);
				echo "Buy ".$arv[$key];
				sleep(5);
			}

			$rand = mt_rand(1,3);
			for ($i=0; $i <$rand ; $i++) { 
				$key = array_rand($arv,1);
				$this->curl("sell",["trade" => "ROL/BTC","prices" => $arv[$key],"amount" => mt_rand(1,50)]);
				echo "Sell ".$arv[$key];
				sleep(5);
			}
			
		}
		//print_r();
	}


	public function autodum(){
		
		while (true) {
			$this->db->order_by("trade_id","DESC");
			$lastprices = $this->db->get_where("trade_history")->row();
			$arv = [];
			
			for ($i = 0; $i < 20; ++$i)
			{
			    $arv[] = (float)$lastprices->prices - (0.00000001 * $i);
			}

			$key = array_rand($arv,1);
			//$this->curl("buy",["trade" => "BTC/ROL","prices" => $arv[$key],"amount" => "0.5"]);
			//echo "Buy ".$arv[$key];
			$this->curl("sell",["trade" => "ROL/BTC","prices" => $arv[$key],"amount" => mt_rand(1,380)]);
			echo "Sell ".$arv[$key];
			sleep(5);
		}
		//print_r();
	}


	public function autopump(){
		while (true) {
			$this->db->order_by("trade_id","DESC");
			$lastprices = $this->db->get_where("trade_history")->row();
			$arv = [];
			
			for ($i = 0; $i < 20; ++$i)
			{
			    $arv[] = (float)$lastprices->prices + (0.00000001 * $i);
			}

			$key = array_rand($arv,1);
			//$this->curl("buy",["trade" => "BTC/ROL","prices" => $arv[$key],"amount" => "0.5"]);
			//echo "Buy ".$arv[$key];
			$this->curl("buy",["trade" => "ROL/BTC","prices" => $arv[$key],"amount" => mt_rand(1,380)]);
			echo "Sell ".$arv[$key];
			sleep(5);
		}
	}


	public function curl($target, $fields=[]){
		$fields = array_merge($fields,["is_bot" => "autotrade"]);
		$fields_string = http_build_query($fields);

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_HTTPAUTH, constant('CURLAUTH_BASIC'));
		curl_setopt($ch,CURLOPT_USERPWD, 'botrade:motconbot');
		curl_setopt($ch,CURLOPT_URL, "https://api.btcrip.co/api/account/".$target);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

		//execute post
		$result = curl_exec($ch);
		echo $result;

		//close connection
		curl_close($ch);
	}
}