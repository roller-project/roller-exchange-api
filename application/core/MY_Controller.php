<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';
class API_Controller extends REST_Controller{
	public $layout = "";
	function __construct($file="rest")
	{
		parent::__construct($file);
		$this->load->library(['session','email','user_agent']);
		$this->load->model(['author_model' => "author","apikey_model" => "apikey"]);
	}

	public function view($arv){
		$this->response($arv);
	}
}
class API_Public extends API_Controller{
	function __construct()
	{
		parent::__construct("public_api");
		
	}
	
}

class API_Private extends API_Controller{
	function __construct()
	{
		parent::__construct("private_api");
		
	}
}