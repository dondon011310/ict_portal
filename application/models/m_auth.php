<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class m_auth extends CI_Model {
	function __construct(){
        parent::__construct();
	}
	
	public function check_login($username, $password = ""){
		// echo $password;
		$query = $this->db->get_where("users",array("username" => $username) );
		return $query->result();
	}
	public function get_profile($user_id){
		$query = $this->db->get_where("users",array("sha1(id)" => $user_id) );
		return $query->result();
	}

	public function get_division(){
		$user_id = $_SESSION['id'];

		$res = $this->db->select('divisions.division_acronym division_name')
			->from('users')
			->join('divisions', 'divisions.id = users.division_id')
			->where('sha1(users.id)', $user_id)
			->get();
		return $res->result_array();

	}
	public function set_session($id,$username,$role, $photo_profile){
		$photo_profile = ($photo_profile == '') ? '/assets/dist/img/dswd.png' : $photo_profile;
		$newdata = array(
			'id'		=> $id,
			'username'  => $username,
			'role'  => $role,
			'logged_in' => TRUE,
			'photo_profile' => $photo_profile
		);
		$this->session->set_userdata($newdata);
	}
	public function unset_session(){
		session_destroy();
	}
	public function set_cookie_remember($username){
		setcookie('remember_me',$username, time() + (86400 * 30), "/");
	}	
	public function unset_cookie_remember(){
		setcookie('remember_me','',0,'/');
	}
}