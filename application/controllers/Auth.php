<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {
	function __construct(){
        parent::__construct();
	$this->load->model('m_auth');
        $this->load->library('form_validation');	
	}
	
	function index(){
		redirect(site_url());
	}
	
	function login(){		
		// Check Session Login
		if(isset($_SESSION['logged_in'])){
			redirect(site_url());
		}
		
		// Check Remember Me
		if(isset($_COOKIE['remember_me'])){			
			$this->auth_model->set_session($_COOKIE['remember_me']);
			redirect(site_url());
		}

		$this->load->view('auth/login');
	}
	
	public function login_process($check_login = false){
		// Check Session Login
		if(isset($_SESSION['logged_in'])){
			redirect(site_url());
		}
		// Check Remember Me
		if(isset($_COOKIE['remember_me'])){			
			$this->auth_model->set_session($_COOKIE['remember_me']);
			redirect(site_url());
		}
		
		$username = escape($this->input->post("username"));		
		$password = sha1(escape($this->input->post("password")));
		$sessCaptcha = $this->session->userdata('captchaCode');
		$inputCaptcha = $this->input->post('captcha');
		$remember_me = escape($this->input->post("remember_me"));

		// if($inputCaptcha === $sessCaptcha){
		if('admin' === 'admin'){

			if($username && $password){
				$check_login = $this->auth_model->check_login($username,$password);	
			}


		if($check_login){

			$id = sha1($check_login[0]->id);
			$role = $check_login[0]->role;
			$photo_profile = $check_login[0]->photo_profile;

			// echo $username;
			$this->auth_model->set_session($id,$username,$role, $photo_profile);
			if($remember_me){
				$this->auth_model->set_cookie_remember($username);
			}
			redirect(site_url());
			}else{
				$this->session->set_flashdata('login_false', 'Incorrect Login Credentials!');
				redirect(site_url('auth/login'));
			}

		 }else{
	                $this->session->set_flashdata('login_false', 'Captcha Code does not match!');
			redirect(site_url('auth/login'));
	         }




	}
	private function ldap_auth($username,$password){

			$adServer = $this->config->item('ldap_server');

			$ldap = ldap_connect($adServer);

			$ldaprdn = 'ENTDSWD' . "\\" . $username;

			ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);

			$bind = @ldap_bind($ldap, $ldaprdn, $password);

			if ($bind) {
			    $filter="(sAMAccountName=$username)";
			    $result = ldap_search($ldap,"DC=ENTDSWD,DC=LOCAL",$filter);
			    // ldap_sort($ldap,$result,"sn");
			    $info = ldap_get_entries($ldap, $result);
			    for ($i=0; $i<$info["count"]; $i++)
			    {
			        if($info['count'] > 1)
			            break;
			        
			        $userDn = $info[$i]["distinguishedname"][0]; 


			        $data['firstname'] = $info[$i]["givenname"][0];
			        $data['middlename'] = $info[$i]["initials"][0];
			        $data['lastname'] = $info[$i]["sn"][0];
			        $data['username'] = $info[$i]["samaccountname"][0];
			        $data['email'] = $info[$i]["mail"][0];
			        // $data['contact_number'] = $info[$i]["mobile"][0];



			        if($username && $password){
					$check_login = $this->auth_model->check_login($username,$password);	
				}

				if($check_login){
					//if the user was already registered
					$id = sha1($check_login[0]->id);
					$role = $check_login[0]->role;
					$photo_profile = $check_login[0]->photo_profile;

					if ($role === "") {
						// code...
						$this->session->set_flashdata('login_false', ' Wait for the admin to activate your account.');
			    			redirect(site_url('auth/login'));

					}else{
						$this->auth_model->set_session($id,$username,$role, $photo_profile);
						
						redirect(site_url());	
					}

									
				 }else{
			  
					$data['divisions'] = $this->datalib_model->get_divisions();
					$this->load->view('users/register',$data);
			         }

			    }
			    @ldap_close($ldap);
			} else {
			    

			    $this->session->set_flashdata('login_false', 'Invalid username / password');
			    redirect(site_url('auth/login'));
			}
	}
	public function ldap_login($check_login = false){

			$username = escape($this->input->post("username"));		
			$password = escape($this->input->post("password"));		

			$this->ldap_auth($username,$password);


	}
	
	function logout(){
		$this->auth_model->unset_session();
		$this->auth_model->unset_cookie_remember();

		redirect(site_url());

	}

	public function register(){
		$data['sections'] = $this->datalib_model->get_sections();
		$this->load->view('users/register',$data);
	}

	public function register_user(){
		$validation = '<ul>';
		$is_validated = true;

		$data['username'] = escape($this->input->post("username"));

		$data['firstname'] = escape($this->input->post("firstname"));		
		$data['middlename'] = escape($this->input->post("middlename"));		
		$data['lastname'] = escape($this->input->post("lastname"));		
		$data['division_id'] = escape($this->input->post("division_id"));		
		$data['email'] = escape($this->input->post("email"));		
		$data['contact_number'] = escape($this->input->post("contact_number"));		
		$data['bday'] = escape($this->input->post("bday"));	
		if($this->user_model->is_unique_username_email($data['username'],$data['email']) > 0){
			$is_validated = false;
			$validation .= '<li> Username or Email is registered already.</li>';
		}

		$validation .= '</ul>';

		if ($is_validated == true) {
			// code...
			if($this->user_model->register_new_user($data) > 0){
				$this->session->set_flashdata('register_true', 'Success. Wait for the admin to activate your account.');
				// $this->load->view('auth/login');
				redirect(site_url('auth/login'));


				
			}else{
				$this->session->set_flashdata('register_false', 'Server Error');
				redirect(site_url('auth/register'));

				
			}
		}else{

			$this->session->set_flashdata('register_false', $validation);

			$data['sections'] = $this->datalib_model->get_sections();

			$this->load->view('users/register',$data);
			

		}


	}

	public function refresh(){
        // Captcha configuration
	        $config = array(
	            'img_path'      => './captcha-images/',
	            'img_url'       => site_url().'captcha-images/',
	            'font_path'     => './path/to/fonts/texb.ttf',
	            'img_width'     => '150',
	        'img_height'    => 30,
	        'expiration'    => 7200,
	        'word_length'   => 8,
	        'font_size'     => 16,
	        'img_id'        => 'Imageid',
	        'pool'          => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
	         'colors'        => array(
                'background' => array(255, 255, 255),
                'border' => array(255, 255, 255),
                'text' => array(0, 0, 0),
                'grid' => array(255, 40, 40)	
        	)
	        );
	        $captcha = create_captcha($config);
	        
	        // Unset previous captcha and set new captcha word
	        $this->session->unset_userdata('captchaCode');
	        $this->session->set_userdata('captchaCode',$captcha['word']);
	        
	        // Display captcha image
	        echo $captcha['image'];
    }


    public function login_using_ldap(){

		// basic sequence with LDAP is connect, bind, search, interpret search
		// result, close connection

		echo "<h3>LDAP query test</h3>";
		echo "Connecting ...";
		$ds=ldap_connect("172.26.134.11");  // must be a valid LDAP server!
		// echo $ds;
		echo var_dump(ldap_bind($ds));
		echo "connect result is " . $ds . "<br />";

	
		
    }

}
