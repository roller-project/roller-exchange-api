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
		$arv = ["email" => $email, "password" => $password];

        $arvs = $this->author->register($email, $password);
        
		
		$this->view($arvs);
	}

	public function login_post(){
		
		$password = $this->input->post("password");
		$email = $this->input->post("email");
		$arv = ["email" => $email, "password" => $password];

        $arvs = $this->author->login($email, $password);
        
		
		$this->view($arvs);
	}
	
	public function chat_post(){
		$text = $this->input->post("text");
		$this->socketio($text,"chat");
	}

	
}