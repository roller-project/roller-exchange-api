<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Admin {

	
	public function index()
	{
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];
		$this->view("dashboard");
	}

	
}
