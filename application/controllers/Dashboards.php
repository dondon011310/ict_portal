<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboards extends CI_Controller {

	function __construct(){
	        parent::__construct();
			$this->load->model('m_auth');
	     
			
			// Check Session Login
			if(!isset($_SESSION['logged_in'])){
				redirect(site_url('auth/login'));
			}

			
	}



	
	public function index()
	{
		$this->load->view('templates/header');
		$this->load->view('dashboards/index');
		$this->load->view('templates/footer');

	}
}
