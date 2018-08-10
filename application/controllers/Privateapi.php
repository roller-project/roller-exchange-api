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
	public function register_post(){

	}
}