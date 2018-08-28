<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';

class API_Controller extends REST_Controller{
	public $layout = "";
	public $users_id = "";
	function __construct($file="rest")
	{
		parent::__construct($file);
		$this->load->library(['session','email','user_agent']);
		$this->load->model(['author_model' => "author","apikey_model" => "apikey"]);
	}

	public function socketio($text, $type = "notification"){
		
		$client = new ElephantIO\Client(new ElephantIO\Engine\SocketIO\Version1X('//127.0.0.1:3000'));

		$client->initialize();
		// send message to connected clients
		$client->emit('broadcast', ['type' => $type, 'text' => $text]);
		$client->close();
	}

	public function view($arv){

		$this->response($arv);
	}

	public function getError($text=[], $ruller=[]){
		if(!is_array($text)){
			if(!trim($text)){
				$arv = ["error" => "Empty type", "error" => "data input"];
				$this->view($arv);
				exit();
			}
		}else{

		}

		return true;
	}
}

/*
Public API
*/
class API_Public extends API_Controller{
	function __construct()
	{
		parent::__construct("public_api");
		
	}
	
}


/*
Private API
*/

class API_Private extends API_Controller{
	
	function __construct()
	{
		parent::__construct("private_api");
		
		$this->users_id = $this->author_model->users_id;

		
	}
	private function is_login(){
		$data = $this->db->get_where("account_login",["users_id" => $this->input->post("users_id"), "session_id" => $this->input->post("session_id")])->row();
		if(isset($data->cache_id)){
			$this->users_id = $data->users_id;
			return true;
		}
		return false;
	}
}

class ServerController extends CI_Controller{
	function __construct()
	{
		parent::__construct();
		
	}
}

class Robottrade extends CI_Controller{
	function __construct($file="rest")
	{
		parent::__construct($file);
		$this->load->library(['session','email','user_agent']);
		$this->load->model(['author_model' => "author","apikey_model" => "apikey"]);
	}
	public function socketio($text, $type = "notification"){
		
		$client = new ElephantIO\Client(new ElephantIO\Engine\SocketIO\Version1X('//127.0.0.1:3000'));

		$client->initialize();
		// send message to connected clients
		$client->emit('broadcast', ['type' => $type, 'text' => $text]);
		$client->close();
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