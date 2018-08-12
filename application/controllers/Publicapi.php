<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Publicapi extends API_Public {
	public function index_get(){
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];
		$this->view($arv);
	}

	public function register_post(){
		
		$password = $this->input->post("password");
		$email = $this->input->post("email");

        $id = $this->author->register($email, $password);
        
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];

		$this->view($arv);
	}

	

}