<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends API_Controller {

	
	public function index_get()
	{
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];
		$this->view($arv);
	}
}
