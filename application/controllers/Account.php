<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Account extends Admin {

	function __construct()
	{
		parent::__construct();
		$this->load->model("coind_model");

	}

	public function index(){
		$data = $this->author->AccountList();
		$this->view("account",["data" => $data]);
	}

	public function createwalletbtc(){
		if(!$this->author->CheckWalletAccount("BTC")){
			$this->coind_model->getCreateWallet("BTC");
		}
		return $this->go("account");
	}
}