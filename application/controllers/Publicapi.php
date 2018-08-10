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
		$username = $this->input->post("username");
		$password = $this->input->post("password");
		$email = $this->input->post("email");

        $id = $this->author->register($username, $password, $email);
        
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];

		$this->view($arv);
	}

	

}