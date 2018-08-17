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

	public function socketio($text){
		
		$client = new ElephantIO\Client(new ElephantIO\Engine\SocketIO\Version1X('//127.0.0.1:3000'));

		$client->initialize();
		// send message to connected clients
		$client->emit('broadcast', ['type' => 'notification', 'text' => $text]);
		$client->close();
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

class ServerController extends CI_Controller{
	function __construct()
	{
		parent::__construct();
		
	}
}


class Admin extends CI_Controller{
	public $layout = "home-layout";
	function __construct()
	{
		parent::__construct();
		$this->load->library(['session','email','user_agent']);
		$this->load->helper(['url','form']);
		$this->load->model(['author_model' => "author","apikey_model" => "apikey"]);
		
	}
	

	public function go($type="", $url=""){
		redirect(base_url($url));
		exit();
	}

	public function get_flash(){

	}
	/*
	Get Layout 
	*/
	public function getLayout(){
		$file = VIEWPATH.$this->layout.".php";
		if(file_exists($file)){
			return true;
		}
		return false;
	}

	/*
	Return View
	*/
	public function view($layout, $data=[]){
		
		
		if($this->getLayout()){

			$data = $this->load->view($layout, $data, true);
			return $this->load->view($this->layout,["content" => $data, "flash" => $this->get_flash(), "header" => ""]);
		}else{
			return $this->load->view($layout, $data);
		}
	}
	

}


function template_url($path=""){
	return base_url("template".($path ?  "/{$path}" : ""));
}
function resource_url($path=""){
	return base_url("resource".($path ? "/{$path}" : ""));
}

function store_url($path=""){
	return base_url($path);
}