<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coind extends Admin {

	function __construct()
	{
		parent::__construct();
		$this->load->model("coind_model");
	}
	public function index()
	{
		$arv = [
			"msg" => "connect",
			"status" => "success"
		];
		$this->view("dashboard");
	}

	public function services(){
		$data = $this->coind_model->AllList();
		$this->view("coind",["data" => $data]);
	}

	public function status(){
		$node = [];
		header('Content-Type: application/json');
		$data = $this->coind_model->AllList();
		foreach ($data as $key => $value) {
			$node[$value->symbol_name] = $this->coind_model->status($value);
		}
		print_r(json_encode($node));
		exit();
	}
	

	public function scanWallet(){

		$data = $this->coind_model->AllList();
		foreach ($data as $key => $value) {
			$this->coind_model->ScanMoveToBaseCoind($value);
		}
	}

	public function scanWalletBTC(){

		$data = $this->coind_model->ScanBtcDeposit();
		print_r($data);
	}


	public function alladdress(){
		$data = $this->coind_model->listAllAddress("BTC");
		print_r($data);
	}

	public function TextscanWalletBTC($ado){
		$this->coind_model->DepositAlt($ado);
		
	}
}
