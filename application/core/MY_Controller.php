<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . 'libraries/REST_Controller.php';
require APPPATH . 'libraries/Format.php';
class API_Controller extends REST_Controller{
	public $layout = "";
	function __construct()
	{
		parent::__construct();
	}

	public function view($arv){
		$this->response($arv);
	}
}