<?php
/**
 * 
 */
class MY_Model extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
	}
}

class DB_Model extends CI_Model
{
	public $is_public = false;
	public $is_private = true;
	public $business = false;
	public $limit_connect = false;
	public $limit = 20;
	public $start = 0;
	function __construct()
	{
		parent::__construct();
	}

	public function is_admin(){
		$is_admin = true;
		if($is_admin){
			return true;
		}
		exit();
	}
}