<?php
/******** Tickerr - Controller ********
 * Controller Name:	Admin_panel
 * Description: 	Inside of this controller are functions that are only
 *					available for the Administrator(s).
 *					The constructor acts as a filter to allow Admins only
**/
class Admin_panel extends CI_Controller {
	
	// Admin information
	private $admin_info = false;
	
	// Used for internal errors
	private $new_user_error = false;
	
	// Constructor acts as a filter to not to allow Agents or Clients.
	public function __construct() {
		parent::__construct();
		
		// Load needed models
		$this->load->model('Loginactions_model', 'loginactions_model', true);
		$this->load->model('Users_model', 'users_model', true);
		$this->load->model('Admin_model', 'admin_model', true);
		$this->load->model('Settings_model', 'settings_model', true);
		$this->load->model('News_model', 'news_model', true);
		$this->load->model('Customer_model', 'customer_model', true);
		$this->load->model('Emails_model', 'emails_model', true);
		
		// To make code simpler.
		$session = $this->session;
		
		// Not logged? Wrong session?
		if($session->tickerr_logged == NULL || !is_array($session->tickerr_logged)) {
			header('Location: '.$this->config->base_url());
			die();
		}
		
		// User and password. To make code simpler
		$session_user = $session->tickerr_logged[0];
		$session_pass = $session->tickerr_logged[1];
		
		// Validate session
		if($this->loginactions_model->validate_session($session_user, $session_pass) == false) {
			header('Location: '.$this->config->base_url());
			die();
		}
		
		// Save the admin information in a private var
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$this->admin_info = $this->users_model->get_user_info($userid);
		
		// User not an admin?
		if($this->admin_info->role != '3') {
			header('Location: '.$this->config->base_url());
			die();
		}
	}
	
	// Function that loads Agent's Sidebar Statistics
	private function load_agent_sidebar_stats(&$config) {
		$this->load->model('Agent_model','agent_model', true);
		$this->agent_model->set_agent_id($this->admin_info->id);
		$config['sidebar_agent_new_tickets'] = $this->agent_model->count_new_tickets();
		$config['sidebar_agent_open_tickets'] = $this->agent_model->count_open_tickets();
		$config['sidebar_agent_tickets'] = $config['sidebar_agent_new_tickets'] + $config['sidebar_agent_open_tickets'];
		$config['sidebar_agent_free_bugs'] = $this->agent_model->count_free_bugs();
		$config['sidebar_agent_my_bugs'] = $this->agent_model->count_my_bugs();
		$config['sidebar_agent_bugs'] = $config['sidebar_agent_free_bugs'] + $config['sidebar_agent_my_bugs'];
		return true;
	}
	
	// Function that loads Admin's Sidebar Statistics
	private function load_admin_sidebar_stats(&$config) {
		$config['sidebar_admin_new_tickets'] = $this->admin_model->count_new_tickets();
		$config['sidebar_admin_open_tickets'] = $this->admin_model->count_open_tickets();
		$config['sidebar_admin_tickets'] = $config['sidebar_admin_new_tickets'] + $config['sidebar_admin_open_tickets'];
		$config['sidebar_admin_free_bugs'] = $this->admin_model->count_free_bugs();
		$config['sidebar_admin_bugs'] = $config['sidebar_admin_free_bugs'];
		$config['sidebar_admin_new_ecufiles'] = $this->admin_model->count_new_ecufiles();
		return true;
	}
	
	// Page: panel/admin/general-stats
	// Displays general statistics
	public function general_stats() {
		// Pass Admin Information
		$config['user_info'] = $this->admin_info;
		
		// Pass base
		$config['base_url'] = $this->config->base_url();
		
		// Pass counter located at the top of the page
		$config['top_counter'] = array(
			'pending_bugs' => $this->admin_model->count_pending_bugs(),
			'pending_tickets' => $this->admin_model->count_pending_tickets(),
			'no_agent_tickets' => $this->admin_model->count_no_agent_tickets(),
			'pending_client_tickets' => $this->admin_model->count_pending_client_tickets(),
			'solved_tickets' => $this->admin_model->count_solved_tickets(),
			'customer_satisfaction' => $this->admin_model->get_customer_satisfaction()
		);
		
		// Top agents
		$config['top_agents'] = $this->admin_model->get_top_agents();
		
		// Load graph 1 (submitted tickets vs solved tickets)
		$config['first_graph_1'] = $this->admin_model->get_first_graph('LAST 7 DAYS');
		$config['first_graph_2'] = $this->admin_model->get_first_graph('THIS MONTH');
		$config['first_graph_3'] = $this->admin_model->get_first_graph('THIS YEAR');
		$config['first_graph_4'] = $this->admin_model->get_first_graph('LAST 5 YEARS');
		
		// Load graph 2 (reported bugs vs solved bugs)
		$config['second_graph_1'] = $this->admin_model->get_second_graph('LAST 7 DAYS');
		$config['second_graph_2'] = $this->admin_model->get_second_graph('THIS MONTH');
		$config['second_graph_3'] = $this->admin_model->get_second_graph('THIS YEAR');
		$config['second_graph_4'] = $this->admin_model->get_second_graph('LAST 5 YEARS');
		
		// Current page for the sidebar
		$config['current_page'] = 11;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Get the site title for the header
		$config['site_title'] = $this->settings_model->get_setting('site_title');
		
		// Finish loading everything
		$this->load->view('panel_header', $config);
		$this->load->view('panel_sidebar', $config);
		$this->load->view('admin/general_stats', $config);
	}
	
	// Page: panel/admin/all-tickets
	// Displays list of all tickets
	public function all_tickets() {	
		// Get user id and information
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and user's model to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Different sort for client and agent
		$client_sort = array('last_update','id','subject','priority','department_name','last_update');
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 12;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially-load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_all_tickets($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_all_tickets($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_all_tickets($records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_all_tickets();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/tickets/all_tickets', $config);
	}





    public function all_files() {
        // Get user id and information
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
        $config['user_info'] = $this->users_model->get_user_info($userid);

        // Pass base and user's model to the view
        $config['base_url'] = $this->config->base_url();
        $config['users_model'] = $this->users_model;

        // Different sort for client and agent
        $client_sort = array('last_update','id','subject','priority','department_name','last_update');
        $agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');

        // Sort...
        if(isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        }else{
            $sort_direction = 'DESC';
            $config['sort'] = 6;
            $sort = 'last_update';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if(!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if($page == 1) $from = 0;
        else $from = (($page-1)*$records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 40;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially-load view
        $this->load_partial_view_combo($config);

        $dir=realpath($_SERVER['DOCUMENT_ROOT'].'/downloads');
        $results= array();


        $file_list =$this->getDirContents($dir);

        //$files = scandir($dir);


        $docRoot = str_ireplace('/', '\\', $_SERVER['DOCUMENT_ROOT']);


        // Search?
        $idx=0;
        if(isset($_GET['search']) and strlen($_GET['search'])>2)
        {
            $needle=trim($_GET['search']);

            foreach ($file_list as $value) {

                $myFile = pathinfo($value);
                $svalue=$myFile['basename'];
                //$pvalue = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $value);
                $pvalue = str_ireplace(realpath($docRoot), '', $value);

                if (stripos ($svalue, $needle) !== false)
                {
                    $results[$idx]['name'] = $pvalue;
                    $results[$idx]['path'] = $value;
                    $results[$idx]['size'] =  intval(filesize($value)/1024);
                    $idx++;
                }

            }



            $config['search'] = $_GET['search'];
            $config['all_files'] = $results;
            $config['all_files_count'] = count($results);
        }else{
            $config['search'] = false;
            $config['all_files'] = "";
            $config['all_files_count'] = 0;
        }



        // Total pages
        $config['total_pages'] = 1;
        $config['page'] =1;

        // Finish loading view
        //$this->load->view('admin/tickets/all_tickets', $config);
        $this->load->view('downloads/all_files',  $config);
    }



    public function getDirContents($dir, &$results = array()) {
        $files = scandir($dir);

        foreach ($files as $key => $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (!is_dir($path)) {
                $results[] = $path;
            } else if ($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
                $results[] = $path;
            }
        }

        return $results;
    }







    // Page: panel/admin/new-tickets
	// Displays list of all new tickets
	public function new_tickets() {	
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Different sort for client and agent
		$client_sort = array('last_update','id','subject','priority','department_name','last_update');
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 13;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially-load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_new_tickets($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_new_tickets($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_new_tickets($records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_new_tickets();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/tickets/new_tickets', $config);
	}
	
	// Page: panel/admin/open-tickets
	// Displays list of all open tickets
	public function open_tickets() {	
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Different sort for client and agent
		$client_sort = array('last_update','id','subject','priority','department_name','last_update');
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 14;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);

		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_open_tickets($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_open_tickets($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_open_tickets($records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_open_tickets();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/tickets/open_tickets', $config);
	}
	
	// Page: panel/admin/closed-tickets
	// Displays list of all closed tickets
	public function closed_tickets() {	
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Different sort for client and agent
		$client_sort = array('last_update','id','subject','priority','department_name','last_update');
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 15;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_closed_tickets($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_closed_tickets($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_closed_tickets($records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_closed_tickets();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/tickets/closed_tickets', $config);
	}
	
	// Page: panel/admin/pending-tickets
	// Displays list of all pending tickets
	public function pending_tickets() {	
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Different sort for client and agent
		$client_sort = array('last_update','id','subject','priority','department_name','last_update');
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 16;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_pending_tickets($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_pending_tickets($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_pending_tickets($records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_pending_tickets_();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/tickets/pending_tickets', $config);
	}
	
	// Page: panel/admin/ticket-departments
	// Displays list of all ticket's departments
	public function ticket_departments() {
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Sort only for agents/admins
		$agent_sort = array('date','id','name','agents','tickets','date','default');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 5;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 17;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_departments'] = $this->admin_model->get_tdepartments($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_departments_count'] = $this->admin_model->count_search_tdepartments($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_departments'] = $this->admin_model->get_tdepartments($records_per_page,$from,$sort,$sort_direction);
			$config['all_departments_count'] = $this->admin_model->count_tdepartments();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_departments_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/tickets_departments', $config);
	}
	
	// Page: panel/admin/free-bugs
	// Displays list of all free (without agent) bugs
	public function free_bugs() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Different sort for client and agent
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 18;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_bugs'] = $this->admin_model->get_free_bugs($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_bugs_count'] = $this->admin_model->count_search_free_bugs($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_bugs'] = $this->admin_model->get_free_bugs($records_per_page,$from,$sort,$sort_direction);
			$config['all_bugs_count'] = $this->admin_model->count_free_bugs();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/bugs/free_bugs', $config);
	}
	
	// Page: panel/admin/all-bugs
	// Displays list of all bugs
	public function all_bugs() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Different sort for client and agent
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 19;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_bugs'] = $this->admin_model->get_all_bugs($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_bugs_count'] = $this->admin_model->count_search_all_bugs($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_bugs'] = $this->admin_model->get_all_bugs($records_per_page,$from,$sort,$sort_direction);
			$config['all_bugs_count'] = $this->admin_model->count_all_bugs();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/bugs/all_bugs', $config);
	}
	
	// Page: panel/admin/solved-bugs
	// Displays list of all solved bugs
	public function solved_bugs() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Different sort for client and agent
		$agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 20;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_bugs'] = $this->admin_model->get_solved_bugs($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_bugs_count'] = $this->admin_model->count_search_solved_bugs($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_bugs'] = $this->admin_model->get_solved_bugs($records_per_page,$from,$sort,$sort_direction);
			$config['all_bugs_count'] = $this->admin_model->count_solved_bugs();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/bugs/solved_bugs', $config);
	}
	
	// Page: panel/admin/bug-departments
	// Displays list of all bug's departments
	public function bug_departments() {
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Sort just for agents/admins
		$agent_sort = array('date','id','name','agents','reports','date','default');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 5;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 21;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_departments'] = $this->admin_model->get_bdepartments($records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_departments_count'] = $this->admin_model->count_search_bdepartments($_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_departments'] = $this->admin_model->get_bdepartments($records_per_page,$from,$sort,$sort_direction);
			$config['all_departments_count'] = $this->admin_model->count_bdepartments();
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_departments_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/bugs_departments', $config);
	}
	
	// Creates a new ticket's department and returns to admin/ticket-departments
	public function new_ticket_department() {
		// Invalid information?
		if(!isset($_POST['department']) || $_POST['department'] == '') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Save new department
		$this->admin_model->new_tdepartment(htmlentities($_POST['department']));
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
	}
	
	// Deletes a ticket's department and returns to admin/ticket-departments
	public function delete_ticket_department($id) {
		// Check if ticket is not set as default
		$info = $this->admin_model->get_tdepartment($id);
		if($info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		if($info->default == '1') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Delete the department
		$this->admin_model->delete_tdepartment($id);
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
	}
	
	// Sets a ticket's department as default and returns to admin/ticket-departments
	public function default_ticket_department($id) {
		// Get department's info and check if it exists
		$info = $this->admin_model->get_tdepartment($id);
		if($info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Set as default
		$this->admin_model->default_tdepartment($id);
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
	}
	
	// Creates new bug's department and returns to admin/bug-departments
	public function new_bug_department() {
		// Invalid information?
		if(!isset($_POST['department']) || $_POST['department'] == '') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		
		// Save new department
		$this->admin_model->new_bdepartment(htmlentities($_POST['department']));
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
	}
	
	// Deletes bug's department and returns to admin/bug-departments
	public function delete_bug_department($id) {
		// Check if ticket is not set as default
		$info = $this->admin_model->get_bdepartment($id);
		if($info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		if($info->default == '1') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		
		// Delete department
		$this->admin_model->delete_bdepartment($id);
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
	}
	
	// Sets bug's department as default and returns to admin/bug-departments
	public function default_bug_department($id) {
		// Get department's info and check if it exists
		$info = $this->admin_model->get_bdepartment($id);
		if($info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		
		// Set as default
		$this->admin_model->default_bdepartment($id);
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
	}
	
	
	// Page: panel/admin/ticket-department/$id
	// Displays a ticket's department information
	public function ticket_department($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_tdepartment_adv($id);
		$config['dpt_info'] = $dpt_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['dpt_info']->date));
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base for the views
		$config['base_url'] = $this->config->base_url();
		
		// Current page for the sidebar
		$config['current_page'] = 17;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get agents and select only the ones related to the department
		$agents = array();
		$get_agents = $this->admin_model->get_agents();
		if($get_agents == false)
			$agents = false;
		else {
			foreach($get_agents->result() as $agent) {
				// Get departments
				if($agent->ticket_departments != '') {
					$departments = explode('|', $agent->ticket_departments);
					if(in_array($id, $departments) == true)
						$agents[] = $agent;
				}
			}
		}
		// Save the list of agents
		$config['agents'] = $agents;
		
		// New tickets
		$config['count_new_tickets'] = $this->admin_model->count_department_new_tickets($id);
		$config['get_new_tickets'] = $this->admin_model->get_department_new_tickets($id, 8);
		
		// Pending tickets
		$config['count_pending_tickets'] = $this->admin_model->count_department_pending_tickets_($id);
		$config['get_pending_tickets'] = $this->admin_model->get_department_pending_tickets($id, 8);
		
		// Open tickets
		$config['count_open_tickets'] = $this->admin_model->count_department_open_tickets($id);
		$config['get_open_tickets'] = $this->admin_model->get_department_open_tickets($id,8);
		
		// Finish loading view
		$this->load->view('admin/ticket_department', $config);
	}
	
	// Remove an Agent from a ticket's department
	public function tdepartment_remove_agent($dpt, $agent_id) {
		// Check if the department exists
		if($this->admin_model->get_tdepartment($dpt) == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Get agent's information
		$agent_info = $this->users_model->get_user_info($agent_id);
		if($agent_info == null) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		if($agent_info->role == '1') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Remove agent
		$this->admin_model->tdepartment_remove_agent($dpt, $agent_id);
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-department/'.$dpt);
	}
	
	// Page: panel/admin/ticket-department/$id/edit
	// Displays form to edit a ticket's department
	public function edit_ticket_department($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_tdepartment_adv($id);
		$config['dpt_info'] = $dpt_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['dpt_info']->date));
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base for the views
		$config['base_url'] = $this->config->base_url();
		
		// Current page for the sidebar
		$config['current_page'] = 17;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get agents and select only the ones related to the department
		$agents = array();
		$get_agents = $this->admin_model->get_agents();
		if($get_agents == false)
			$agents = false;
		else {
			foreach($get_agents->result() as $agent) {
				// Get departments
				if($agent->ticket_departments != '') {
					$departments = explode('|', $agent->ticket_departments);
					if(in_array($id, $departments) == true)
						$agent->is_selected = true;
					else
						$agent->is_selected = false;
				}else{
					$agent->is_selected = false;
				}
				$agents[] = $agent;
			}
		}
		$config['agents'] = $agents;
		
		// Finish loading view
		$this->load->view('admin/edit_ticket_department', $config);
	}
	
	// Action of the previous function (edit ticket's department)
	public function action_edit_ticket_department($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_tdepartment_adv($id);
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Update name
		$this->admin_model->update_tdepartment_name($id, $_POST['department_name']);

		// List of new agents
		$new_agents = $_POST['agents'];
		
		// List of all agents
		$all_agents = $this->admin_model->get_agents();
		foreach($all_agents->result() as $agent) {
			$departments = explode('|', $agent->ticket_departments);
			// Agent responsable del department, pero agent no existe
			// en la nueva lista de responsables
			if(in_array($id, $departments) == true && in_array($agent->id, $new_agents) == false) {
				// Eliminar
				$this->admin_model->tdepartment_remove_agent($id, $agent->id);
			}elseif(in_array($id, $departments) == false && in_array($agent->id, $new_agents) == true) {
				// Agregar
				$this->admin_model->tdepartment_add_agent($id, $agent->id);
			}
		}
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-department/'.$id);
		die();
	}
	
	// Page: panel/admin/ticket-department/$id/new-tickets
	// Displays new tickets from a certain department
	public function ticket_department_new_tickets($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_tdepartment_adv($id);
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		$config['dpt_info'] = $dpt_info;
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Sort for agents only
		$agent_sort = array('last_update','id','subject','priority','client_final_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 17;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_department_new_tickets($id, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_department_new_tickets($id, $_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_department_new_tickets($id, $records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_department_new_tickets($id);
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/department/department_new_tickets', $config);
	}
	
	// Page: panel/admin/ticket-department/$id/pending-tickets
	// Displays pending tickets from a certain department
	public function ticket_department_pending_tickets($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_tdepartment_adv($id);
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		$config['dpt_info'] = $dpt_info;
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Sort for agent only
		$agent_sort = array('last_update','id','subject','priority','client_final_name','last_update');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 17;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_department_pending_tickets($id, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_department_pending_tickets($id, $_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_department_pending_tickets($id, $records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_department_pending_tickets_($id);
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/department/department_pending_tickets', $config);
	}
	
	// Page: panel/admin/ticket-department/$id/pending-tickets
	// Displays pending tickets from a certain department
	public function ticket_department_open_tickets($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_tdepartment_adv($id);
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		$config['dpt_info'] = $dpt_info;
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Pass base and models to the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;

		// Sort for agent only...
		$agent_sort = array('last_update','id','subject','priority','client_final_name','last_update');
		
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'last_update';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 17;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_tickets'] = $this->admin_model->get_department_open_tickets($id, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_tickets_count'] = $this->admin_model->count_search_department_open_tickets($id, $_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_tickets'] = $this->admin_model->get_department_open_tickets($id, $records_per_page,$from,$sort,$sort_direction);
			$config['all_tickets_count'] = $this->admin_model->count_department_open_tickets($id);
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/department/department_open_tickets', $config);
	}
	
	// Page: panel/admin/bug-department/$id
	// Displays bug's department details
	public function bug_department($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_bdepartment_adv($id);
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		$config['dpt_info'] = $dpt_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['dpt_info']->date));
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base for the views
		$config['base_url'] = $this->config->base_url();
		
		// Current page for the sidebar
		$config['current_page'] = 21;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get agents and select only the ones related to the department
		$agents = array();
		$get_agents = $this->admin_model->get_agents();
		if($get_agents == false)
			$agents = false;
		else {
			foreach($get_agents->result() as $agent) {
				// Get departments
				if($agent->bug_departments != '') {
					$departments = explode('|', $agent->bug_departments);
					if(in_array($id, $departments) == true)
						$agents[] = $agent;
				}
			}
		}
		$config['agents'] = $agents;
		
		// Free bugs
		$config['count_free_bugs'] = $this->admin_model->count_department_free_bugs($id);
		$config['get_free_bugs'] = $this->admin_model->get_department_free_bugs($id, 8);
		
		// Solved bugs
		$config['count_solved_bugs'] = $this->admin_model->count_department_solved_bugs($id);
		$config['get_solved_bugs'] = $this->admin_model->get_department_solved_bugs($id, 8);
		
		// Other bugs
		$config['count_other_bugs'] = $this->admin_model->count_department_other_bugs($id);
		$config['get_other_bugs'] = $this->admin_model->get_department_other_bugs($id,8);
		
		// Finish loading view
		$this->load->view('admin/bug_department', $config);
	}
	
	// Page: panel/admin/bug-department/$id/free-bugs
	// Displays list of a department's free bugs
	public function bug_department_free_bugs($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_bdepartment_adv($id);
		$config['dpt_info'] = $dpt_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['dpt_info']->date));
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for agent only...
		$agent_sort = array('date','id','subject','priority','client_final_name','date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 21;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_bugs'] = $this->admin_model->get_department_free_bugs($id, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_bugs_count'] = $this->admin_model->count_search_department_free_bugs($id, $_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_bugs'] = $this->admin_model->get_department_free_bugs($id, $records_per_page,$from,$sort,$sort_direction);
			$config['all_bugs_count'] = $this->admin_model->count_department_free_bugs($id);
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/department/department/department_free_bugs', $config);
	}
	
	// Page: panel/admin/bug-department/$id/solved-bugs
	// Displays list of a department's solved bugs
	public function bug_department_solved_bugs($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_bdepartment_adv($id);
		$config['dpt_info'] = $dpt_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['dpt_info']->date));
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for agent only
		$agent_sort = array('date','id','subject','priority','client_final_name','date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		$config['current_page'] = 21;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_bugs'] = $this->admin_model->get_department_solved_bugs($id, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_bugs_count'] = $this->admin_model->count_search_department_solved_bugs($id, $_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_bugs'] = $this->admin_model->get_department_solved_bugs($id, $records_per_page,$from,$sort,$sort_direction);
			$config['all_bugs_count'] = $this->admin_model->count_department_solved_bugs($id);
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/department/department_solved_bugs', $config);
	}
	
	// Page: panel/admin/bug-department/$id/other-bugs
	// Displays list of a department's other bugs
	public function bug_department_other_bugs($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_bdepartment_adv($id);
		$config['dpt_info'] = $dpt_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['dpt_info']->date));
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/ticket-departments');
			die();
		}
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for agent only...
		$agent_sort = array('date','id','subject','priority','client_final_name','date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 6;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 21;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_bugs'] = $this->admin_model->get_department_other_bugs($id, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_bugs_count'] = $this->admin_model->count_search_department_other_bugs($id, $_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_bugs'] = $this->admin_model->get_department_other_bugs($id, $records_per_page,$from,$sort,$sort_direction);
			$config['all_bugs_count'] = $this->admin_model->count_department_other_bugs($id);
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/department/department_other_bugs', $config);
	}
	
	// Remove agent from Bug Report's Department
	public function bdepartment_remove_agent($dpt, $agent_id) {
		// Check if department exists
		if($this->admin_model->get_bdepartment($dpt) == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		
		// Get agent info
		$agent_info = $this->users_model->get_user_info($agent_id);
		if($agent_info == null) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		if($agent_info->role == '1') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		
		// Remove agent
		$this->admin_model->bdepartment_remove_agent($dpt, $agent_id);
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/bug-department/'.$dpt);
	}
	
	// Page: panel/admin/bug-department/$id/edit
	// Shows form to edit Bug Report's Department
	public function edit_bug_department($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_bdepartment_adv($id);
		$config['dpt_info'] = $dpt_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['dpt_info']->date));
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base for the views
		$config['base_url'] = $this->config->base_url();
		
		// Current page for the sidebar
		$config['current_page'] = 21;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get agents and select only the ones related to the department
		$agents = array();
		$get_agents = $this->admin_model->get_agents();
		if($get_agents == false)
			$agents = false;
		else {
			foreach($get_agents->result() as $agent) {
				// Get departments
				if($agent->bug_departments != '') {
					$departments = explode('|', $agent->bug_departments);
					if(in_array($id, $departments) == true)
						$agent->is_selected = true;
					else
						$agent->is_selected = false;
				}else{
					$agent->is_selected = false;
				}
				$agents[] = $agent;
			}
		}
		$config['agents'] = $agents;
		
		// Finish loading view
		$this->load->view('admin/edit_bug_department', $config);
	}
	
	// Action of the previous function (edit bug report's department)
	public function action_edit_bug_department($id) {
		// Check if department exists
		$dpt_info = $this->admin_model->get_bdepartment_adv($id);
		if($dpt_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/bug-departments');
			die();
		}
		
		// Update name
		$this->admin_model->update_bdepartment_name($id, $_POST['department_name']);
		
		// New agents
		$new_agents = $_POST['agents'];
		
		// All agent
		$all_agents = $this->admin_model->get_agents();
		foreach($all_agents->result() as $agent) {
			$departments = explode('|', $agent->bug_departments);
			// Agent responsable del department, pero agent no existe
			// en la nueva lista de responsables
			if(in_array($id, $departments) == true && in_array($agent->id, $new_agents) == false) {
				// Eliminar
				$this->admin_model->bdepartment_remove_agent($id, $agent->id);
			}elseif(in_array($id, $departments) == false && in_array($agent->id, $new_agents) == true) {
				// Agregar
				$this->admin_model->bdepartment_add_agent($id, $agent->id);
			}
		}
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/bug-department/'.$id);
		die();
	}
	
	// Creates new user
	public function new_user() {
		// Check data
		if(!isset($_POST['from'])) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
			return;
		}
		if(!isset($_POST['user-name']) || !isset($_POST['user-username']) || !isset($_POST['user-email']) || !isset($_POST['user-password']) || !isset($_POST['user-rpassword']) || !isset($_POST['user-role'])) {
			if($_POST['from'] == 'all-users')
				header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
			return;
		}
		
		// Assign data to vars
		$name = $_POST['user-name'];
		$username = $_POST['user-username'];
		$email = $_POST['user-email'];
		$password = $_POST['user-password'];
		$rpassword = $_POST['user-rpassword'];
		$role = $_POST['user-role'];
		 $exp_date = $_POST['user-expdate'];
		
		// Validate
		if(strlen($name) < 5 || strlen($username) < 5 || $email == '' || $password == '' || $password != $rpassword || ($role!='1' && $role!='2' && $role!='3')) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
			return;
		}
		
		// Check existing username/email
		if($this->admin_model->check_existing_username($username) == true) {
			$this->new_user_error = 1;
			$this->all_users();	
			return;
		}
		if($this->admin_model->check_existing_email($email) == true) {
			$this->new_user_error = 2;
			$this->all_users();
			return;
		}
		
		// Is confirmation needed?
		if($this->settings_model->get_setting('email_confirmation') == '0')
			$e_confirmation = false;
		else
			$e_confirmation = true;
		
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		// Create user
		$user = $this->admin_model->create_user($name, $username, $email, $password, $role, $e_confirmation, $exp_date,$userid);
		
		// User inserted
		if($user != false) {
			// What email should we send?
			// Email confirmation not needed, send email of account created
			if($this->settings_model->get_setting('mailing') == '1' && $e_confirmation == false && $this->settings_model->get_setting('send_email_new_account') == '1') {
				// Get email settings
				$config = $this->settings_model->get_email_settings();
				$email_info = $this->settings_model->get_email_info();
				$email_specific = $this->settings_model->get_email_specific('email_new_account');
				
				$config['mailtype'] = $email_specific['type'];
				
				// Load library and prepare info
				$this->load->library('email');
				$this->email->initialize($config);
				
				$this->email->from($email_info['email_from_address'], $email_info['email_from_name']);
				$this->email->to($email);
				$this->email->cc($email_info['email_cc']);
				
				$this->email->subject($email_specific['title']);
				
				$replace_from = array(
					'%site_title%',
					'%site_url%',
					'%user_name%',
					'%user_username%',
					'%user_email%'
				);
				
				$replace_to = array(
					$this->settings_model->get_setting('site_title'),
					$this->config->base_url(),
					$name,
					$username,
					$email
				);
				$this->email->message(str_replace($replace_from, $replace_to, $email_specific['content']));
				$this->email->send();
				
				// After being sent, link all tickets from this email to the new account
				$this->admin_model->link_tickets($user, $email);
				$this->admin_model->link_bug_reports($user, $email);
			}elseif($e_confirmation == true) {
				// Send confirmation email
				// Get email settings
				$config = $this->settings_model->get_email_settings();
				$email_info = $this->settings_model->get_email_info();
				$email_specific = $this->settings_model->get_email_specific('email_new_account_confirmation');
				
				$config['mailtype'] = $email_specific['type'];
				
				// Load library and prepare info
				$this->load->library('email');
				$this->email->initialize($config);
				
				$this->email->from($email_info['email_from_address'], $email_info['email_from_name']);
				$this->email->to($email);
				$this->email->cc($email_info['email_cc']);
				
				$this->email->subject($email_specific['title']);
				
				$replace_from = array(
					'%site_title%',
					'%site_url%',
					'%user_name%',
					'%user_username%',
					'%user_email%',
					'%confirmation_url%'
				);
				
				$replace_to = array(
					$this->settings_model->get_setting('site_title'),
					$this->config->base_url(),
					$name,
					$username,
					$email,
					$this->config->base_url('account/confirm/' . $this->admin_model->last_confirmation_str())
				);
				
				$this->email->message(str_replace($replace_from, $replace_to, $email_specific['content']));
				$this->email->send();
				
				// After sent, do nothing.
				// Show "you're gonna need to active your account" message
				header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
				return;
			}
			
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
				return;
		}
		
		header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
		return;
	}
	
	// Page: panel/admin/all-users
	// Displays list of all users
	public function all_users() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		$config['username_error'] = false;
		$config['email_error'] = false;
		if($this->new_user_error == 1) {
			$config['username_error'] = true;
		}elseif($this->new_user_error == 2) {
			$config['email_error'] = true;
		}
		
		// Assign POST values to prevent errors in the view
		if(!isset($_POST['user-name'])) $_POST['user-name'] = '';
		if(!isset($_POST['user-username'])) $_POST['user-username'] = '';
		if(!isset($_POST['user-email'])) $_POST['user-email'] = '';
		if(!isset($_POST['user-role'])) $_POST['user-role'] = '1';
		
		// Base and user's model
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for agent only
		$agent_sort = array('date','id','name','username','role','date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 5;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Search?
		if(isset($_GET['search'])) {
			$config['search'] = $_GET['search'];
			$config['all_users'] = $this->admin_model->get_all_users_exp($userid, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
			$config['all_users_count'] = $this->admin_model->count_search_all_users_exp($userid, $_GET['search']);
		}else{
			$config['search'] = false;
			$config['all_users'] = $this->admin_model->get_all_users_exp($userid, $records_per_page,$from,$sort,$sort_direction);
			$config['all_users_count'] = $this->admin_model->count_all_users_exp($userid);
		}
		
		// Total pages
		$config['total_pages'] = round($config['all_users_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/all_users', $config);
	}
	
	// Delete user. Returns to admin/all-users
	public function delete_user($id){
		$user_info = $this->admin_model->get_user($id);
		if($user_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
			return;
		}
		
		// Different action for different user role
		if($user_info->role == '1') {
			// Client
			$this->admin_model->delete_client($id);
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
			return;
		}else{
			// Agent/admin
			$this->admin_model->delete_agent($user_info, $id);
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
			return;
		}
	}
	
	// Page: panel/admin/user/$id
	// Displays information of a user
	public function user($id) {
		// Check if user exists
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users');
			die();
		}
		$config['current_user_info'] = $current_user_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['current_user_info']->date));
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base for the views
		$config['base_url'] = $this->config->base_url();
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Get information...
		$config['get_last_10_tickets'] = $this->admin_model->get_client_all_tickets($id, 10, 0, 'date');
		$config['count_last_10_tickets'] = $this->admin_model->count_client_all_tickets($id);
		$config['get_last_10_bugs'] = $this->admin_model->get_client_all_bugs($id, 10, 0, 'date');
		$config['count_last_10_bugs'] = $this->admin_model->count_client_all_bugs($id);
		$config['get_last_10_ratings'] = $this->admin_model->get_client_all_ratings($id, 10);
		$config['count_last_10_ratings'] = $this->admin_model->count_client_all_ratings($id);
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		if($current_user_info->role == '1') {
			$config['get_last_10_tickets'] = $this->admin_model->get_client_all_tickets($id, 10);
			$config['count_last_10_tickets'] = $this->admin_model->count_client_all_tickets($id);
			$config['get_last_10_bugs'] = $this->admin_model->get_client_all_bugs($id, 10);
			$config['count_last_10_bugs'] = $this->admin_model->count_client_all_bugs($id);
			$config['get_last_10_ratings'] = $this->admin_model->get_client_all_ratings($id, 10);
			$config['count_last_10_ratings'] = $this->admin_model->count_client_all_ratings($id);
			$this->load->view('admin/client/client_user', $config);
		}else{
			$config['get_last_10_closed_tickets'] = $this->admin_model->get_agent_closed_tickets($id, 10);
			$config['count_last_10_closed_tickets'] = $this->admin_model->count_agent_closed_tickets($id);
			$config['get_last_10_solved_bugs'] = $this->admin_model->get_agent_solved_bugs($id, 10);
			$config['count_last_10_solved_bugs'] = $this->admin_model->count_agent_solved_bugs($id);
			$config['get_last_10_ratings'] = $this->admin_model->get_agent_all_ratings($id, 10);
			$config['count_last_10_ratings'] = $this->admin_model->count_agent_all_ratings($id);
			
			$tdepartments = explode('|', $current_user_info->ticket_departments);
			$config['ticket_departments'] = array();
			if(count($tdepartments) == 1 && $tdepartments[0] != '') {
				$config['ticket_departments'] = array();
				foreach($tdepartments as $dpt) {
					$t = $this->admin_model->get_tdepartment($dpt);
					if($t != false)
						$config['ticket_departments'][] = $t;
				}
			}
			
			$bdepartments = explode('|', $current_user_info->bug_departments);
			$config['bug_departments'] = array();
			if(count($bdepartments) == 1 && $bdepartments[0] != '') {
				foreach($bdepartments as $dpt) {
					$t = $this->admin_model->get_bdepartment($dpt);
					if($t != false)
						$config['bug_departments'][] = $t;
				}
			}
			
			// Finish loading view
			$this->load->view('admin/agent/agent_user', $config);
		}
	}
	
	// Page: panel/admin/user/$id/edit
	// Displays form to edit user's information
	public function edit_user($id) {
		// Check if user exists
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users');
			die();
		}

        $config['current_user_serial_info'] = array();

        $current_user_serial_info = $this->admin_model->get_client_serial_numbers($id);
        foreach($current_user_serial_info->result() as $pserials) {
            $config['current_user_serial_info'][]=$pserials;

        }

        $current_user_product_info = $this->admin_model->get_all_products();
        foreach($current_user_product_info->result() as $pserials) {
            $config['current_user_product_info'][]=$pserials;

        }
		//var_dump($config['current_user_serial_info']);
		//exit();

		$config['current_user_info'] = $current_user_info;
       // $config['current_user_serial_info'] = $current_user_serial_info;
		$config['created_on'] = date('M jS, Y \a\t H:i:s', strtotime($config['current_user_info']->date));
		
		// Get user id and info
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base for the views
		$config['base_url'] = $this->config->base_url();
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Array of user's ticket departments
		$user_tdepartments = explode('|', $current_user_info->ticket_departments);
		$tdepartments = $this->admin_model->get_tdepartments(1000);
		$config['tdepartments'] = array();
		foreach($tdepartments->result() as $dpt) {
			if(in_array($dpt->id, $user_tdepartments))
				$dpt->is_selected = true;
			else
				$dpt->is_selected = false;
			$config['tdepartments'][] = $dpt;
		}
		
		// Array of user's bug departments
		$user_bdepartments = explode('|', $current_user_info->bug_departments);
		$bdepartments = $this->admin_model->get_bdepartments(1000);
		$config['bdepartments'] = array();
		foreach($bdepartments->result() as $dpt) {
			if(in_array($dpt->id, $user_bdepartments))
				$dpt->is_selected = true;
			else
				$dpt->is_selected = false;
			$config['bdepartments'][] = $dpt;
		}
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Finish loading a different view depending on the role of the user
		if($current_user_info->role == '1')
			$this->load->view('admin/client/edit_client', $config);
		else
			$this->load->view('admin/agent/edit_agent', $config);
	}
	
	// Action of the previous function (edit user)
	public function edit_user_action($id) {
		// Check if user exists
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users');
			die();
		}
		
		// Check information sent
		if(!isset($_POST['user-name']) || !isset($_POST['user-username']) || !isset($_POST['user-email'])) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/all-users');
			die();
		}
		
		// Assign vars
		$name = $_POST['user-name'];
		$username = $_POST['user-username'];
		$email = $_POST['user-email'];
		$expdate = $_POST['user-expdate'];
		
		// All users are modified equally, so...
		$this->admin_model->edit_user($id, $name, $username, $email,$expdate);

		// Different action depending on the type of user
		if($current_user_info->role == '1') {
			// Client
		}else{
			// Agent/admin
			if(!isset($_POST['tdepartments'])) $_POST['tdepartments'] = array();
			if(!isset($_POST['bdepartments'])) $_POST['bdepartments'] = array();
			
			$role = $_POST['user-role'];
			$post_tdepartments = $_POST['tdepartments'];
			$post_bdepartments = $_POST['bdepartments'];
			$user_tdepartments = explode('|', $current_user_info->ticket_departments);
			$user_bdepartments = explode('|', $current_user_info->bug_departments);
			$all_tdepartments = $this->admin_model->get_tdepartments(1000);
			$all_bdepartments = $this->admin_model->get_bdepartments(1000);
			
			foreach($all_tdepartments->result() as $dpt) {
				// Sent through post but not on user? Add
				if(in_array($dpt->id, $post_tdepartments) && !in_array($dpt->id, $user_tdepartments))
					$this->admin_model->tdepartment_add_agent($dpt->id, $id);
				elseif(!in_array($dpt->id, $post_tdepartments) && in_array($dpt->id, $user_tdepartments))
					$this->admin_model->tdepartment_remove_agent($dpt->id, $id);
			}
			
			foreach($all_bdepartments->result() as $dpt) {
				// Sent through post but not on user? Add
				if(in_array($dpt->id, $post_bdepartments) && !in_array($dpt->id, $user_bdepartments))
					$this->admin_model->bdepartment_add_agent($dpt->id, $id);
				elseif(!in_array($dpt->id, $post_bdepartments) && in_array($dpt->id, $user_bdepartments))
					$this->admin_model->bdepartment_remove_agent($dpt->id, $id);
			}
		}
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/user/'.$id);
		die();
	}



    public function add_user_product_action($id)
    {
        // Check if user exists
        $current_user_info = $this->admin_model->get_user($userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]));
        if ($current_user_info == false) {
            header('Location: ' . $this->config->base_url() . 'panel/admin/all-users');
            die();
        }

        // Check information sent
        if (!isset($_POST['product_name']) || !isset($_POST['serials'])) {
            header('Location: ' . $this->config->base_url() . 'panel/admin/all-users');
            die();
        }

        $name = $_POST['product_name'];
        $serials = $_POST['serials'];
//        var_dump($current_user_info->role);
//        die();
        //$current_user_serial_info = $this->admin_model->get_client_serial_numbers($id);


        // All users are modified equally, so...

        // Different action depending on the type of user
        if ($current_user_info->role == '1') {
            // Client
        } else {
            // Agent/admin




            foreach ($serials as $srl) {
                // Sent through post but not on user? Add
                $this->admin_model->add_client_serial_numbers($id, $name, $srl);


            }

            // Return
            header('Location: ' . $this->config->base_url() . 'panel/admin/user/' . $id.'/edit');
            die();
        }
    }


    // Delete user serials
    public function delete_user_product($uid,$sid){
        $current_user_info = $this->admin_model->get_user($userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]));
        if($current_user_info == false) {
            header('Location: ' . $this->config->base_url() . 'panel/admin/all-users/');
            return;
        }

//
//        var_dump($uid."-".$sid);
//        exit();

        // Different action for different user role
        if($current_user_info->role == '3') {
            // Client
            $this->admin_model->delete_client_serial_numbers($sid);
            header('Location: ' . $this->config->base_url() . 'panel/admin/user/'. $uid.'/edit');
            return;
        }

    }


    // Page: panel/admin/user/$id/tickets
	// Displays all tickets created by a client
	public function client_all_tickets($id) {
		// Check if user exists and if it's client
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false || $current_user_info->role != '1') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/user/'.$id);
			die();
		}
		$config['current_user_info'] = $current_user_info;
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for agent only
		$agent_sort = array('date','id','subject','department_name','date');
		
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 4;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get list of all tickets
		$config['all_tickets'] = $this->admin_model->get_client_all_tickets($id, $records_per_page, $from, $sort, $sort_direction);
		$config['all_tickets_count'] = $this->admin_model->count_client_all_tickets($id);
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/client/client_all_tickets', $config);
	}
	
	// Page: panel/admin/user/$id/bugs
	// Displays all bug reports created by a client
	public function client_all_bugs($id) {
		// Check if user exists and if it's client
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false || $current_user_info->role != '1') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/user/'.$id);
			die();
		}
		$config['current_user_info'] = $current_user_info;
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for agent only
		$agent_sort = array('date','id','subject','department_name','date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 4;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get list of all bug reports
		$config['all_bugs'] = $this->admin_model->get_client_all_bugs($id, $records_per_page, $from, $sort, $sort_direction);
		$config['all_bugs_count'] = $this->admin_model->count_client_all_bugs($id);
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/client/client_all_bugs', $config);
	}
	
	// Page: panel/admin/user/$id/ratings
	// Displays all ratings submitted by a client
	public function client_all_ratings($id) {
		// Check if user exists and if it's client
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false || $current_user_info->role != '1') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/user/'.$id);
			die();
		}
		$config['current_user_info'] = $current_user_info;
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for agent only
		$agent_sort = array('rating','id','subject','agent_name','rating', 'date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 5;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get list of all ratings
		$config['all_ratings'] = $this->admin_model->get_client_all_ratings($id, $records_per_page, $from, $sort, $sort_direction);
		$config['all_ratings_count'] = $this->admin_model->count_client_all_ratings($id);
		
		// Total pages
		$config['total_pages'] = round($config['all_ratings_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/client/client_all_ratings', $config);
	}
	
	// Page: panel/admin/user/$id/closed-tickets
	// Displays all tickets closed by an agent
	public function agent_closed_tickets($id) {
		// Check if user exists and if it's agent/admin
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false || ($current_user_info->role != '2' && $current_user_info->role != '3')) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/user/'.$id);
			die();
		}
		$config['current_user_info'] = $current_user_info;
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for the admin only
		$agent_sort = array('date','id','subject','department_name','date');
		
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 4;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get list of all closed tickets
		$config['all_tickets'] = $this->admin_model->get_agent_closed_tickets($id, $records_per_page, $from, $sort, $sort_direction);
		$config['all_tickets_count'] = $this->admin_model->count_agent_closed_tickets($id);
		
		// Total pages
		$config['total_pages'] = round($config['all_tickets_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/agent/agent_closed_tickets', $config);
	}
	
	// Page: panel/admin/user/$id/solved-bugs
	// Displays all bug reports solved by an agent
	public function agent_solved_bugs($id) {
		// Check if user exists and if it's agent/admin
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false || ($current_user_info->role != '2' && $current_user_info->role != '3')) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/user/'.$id);
			die();
		}
		$config['current_user_info'] = $current_user_info;
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for the admin
		$agent_sort = array('date','id','subject','department_name','date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 4;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get list of bug reports
		$config['all_bugs'] = $this->admin_model->get_agent_closed_tickets($id, $records_per_page, $from, $sort, $sort_direction);
		$config['all_bugs_count'] = $this->admin_model->count_agent_closed_tickets($id);
		
		// Total pages
		$config['total_pages'] = round($config['all_bugs_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/agent/agent_solved_bugs', $config);
	}
	
	// Page: panel/admin/user/$id/received-ratings
	// Displays all ratings sent to an agent
	public function agent_received_ratings($id) {
		// Check if user exists and if it's agent/admin
		$current_user_info = $this->admin_model->get_user($id);
		if($current_user_info == false || ($current_user_info->role != '2' && $current_user_info->role != '3')) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/user/'.$id);
			die();
		}
		$config['current_user_info'] = $current_user_info;
		
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Sort for the admin
		$agent_sort = array('date','id','subject','client_final_name','rating','date');
		
		// Sort...
		if(isset($_GET['sort']) && isset($_GET['w'])) {
			$sort = $this->sorter($_GET['sort'], $agent_sort);
			$config['sort'] = $_GET['sort'];
			$sort_direction = $this->sort_direction($_GET['w']);
		}else{
			$sort_direction = 'DESC';
			$config['sort'] = 5;
			$sort = 'date';
		}
		$config['sort_direction'] = $sort_direction;
		
		// Records to show per page
		$records_per_page = 20;
		
		// Pagination
		if(!isset($_GET['page'])) $page = 1;
		else $page = $_GET['page'];
		if($page == 1) $from = 0;
		else $from = (($page-1)*$records_per_page);
		
		// Current page for the sidebar
		$config['current_page'] = 22;
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Get list of all ratings
		$config['all_ratings'] = $this->admin_model->get_agent_all_ratings($id, $records_per_page, $from, $sort, $sort_direction);
		$config['all_ratings_count'] = $this->admin_model->count_agent_all_ratings($id);
		
		// Total pages
		$config['total_pages'] = round($config['all_ratings_count'] / $records_per_page);
		$config['page'] = $page;
		
		// Finish loading view
		$this->load->view('admin/received_ratings', $config);
	}
	
	// Page: panel/admin/general-settings
	// Displays Tickerr general settings
	public function general_settings() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Current page for the sidebar
		$config['current_page'] = 24;

		// Errors that might occur
		if(isset($_SESSION['logo_settings_error'])) {
			$config['logo_error'] = $_SESSION['logo_settings_error'];
			unset($_SESSION['logo_settings_error']);
		}else{
			$config['logo_error'] = false;
			unset($_SESSION['logo_settings_error']);
		}
		
		// Get max file size allowed
		$upload_max_filesize = trim(ini_get('upload_max_filesize'));
		$post_max_size = trim(ini_get('post_max_size'));
		$post_max_time = trim(ini_get('max_execution_time'));
		
		// Convert vars to MB
		$upload_max_filesize_val = $upload_max_filesize * 1;
		$upload_max_filesize_last = strtolower($upload_max_filesize[strlen($upload_max_filesize)-1]);
		if($upload_max_filesize_last == 'g')
			$upload_max_filesize_val *= 1024;
		elseif($upload_max_filesize_last == 'k')
			$upload_max_filesize_val /= 1024;
		elseif($upload_max_filesize_last != 'm')
			$upload_max_filesize_val /= 1048576;
		
		$post_max_size_val = $post_max_size * 1;
		$post_max_size_last = strtolower($post_max_size[strlen($post_max_size)-1]);
		if($post_max_size_last == 'g')
			$post_max_size_val *= 1024;
		elseif($post_max_size_last == 'k')
			$post_max_size_val /= 1024;
		elseif($post_max_size_last != 'm')
			$post_max_size_val /= 1048576;
		
		$ini_max_file_size = min($upload_max_filesize_val, $post_max_size_val);
		
		
		// Get settings
		$settings = array(
			'site_title',
			'confirm_purchase_codes_username',
			'confirm_purchase_codes_api',
			'allow_guest_bug_reports',
			'allow_guest_tickets',
			'allow_account_creations',
			'allow_guest_file_uploads',
			'allow_file_uploads',
			'file_uploads_max_size',
			'file_uploads_extensions'
		);
		$config['settings'] = $this->settings_model->get_multiple_settings($settings);
		$config['settings']->file_uploads_extensions = implode(", ", explode('|', $config['settings']->file_uploads_extensions));
		
		$config['ini_max_file_size'] = $ini_max_file_size;
		$config['ini_post_max_size'] = $post_max_size;
        $config['ini_execution_time'] = $post_max_time;
		
		// Errors that might occur
		if(isset($_SESSION['general_settings_envato_error'])) {
			$config['envato_error'] = true;
			unset($_SESSION['general_settings_envato_error']);
		}else{
			$config['envato_error'] = false;
			unset($_SESSION['general_settings_envato_error']);
		}

        $dir=realpath($_SERVER['DOCUMENT_ROOT'].'/magic/downloads/software.zip');
		// var_dump($dir) ;
		// exit();
		$soft_file=array();
        if (file_exists($dir)) {

            $soft_file['dtime']=date ("F d Y H:i:s.", filemtime($dir));
            $soft_file['size']= intval(filesize($dir)/1024);
            $filename = pathinfo($dir);
            $soft_file['name']= $filename['basename'];

          
            $config['software']=$soft_file;
        }
        else
        {
            $config['software']=0;
        }
		//exit();
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);

        //var_dump($config) ;
        //exit();
		// Finish loading view
		$this->load->view('admin/settings/general_settings', $config);
	}
	
	// Action of the previous function (general settings)
	public function general_settings_action() {


			// 		var_dump($_FILES['file_login_logo']);
			// exit();	

	    // Check sent information


		if(!isset($_POST['site_title']) || $_POST['site_title'] == ''
		   || !isset($_POST['envato_username'])
		   || !isset($_POST['envato_api'])
		   || !isset($_POST['allow_guest_bug_reports'])
		   || !isset($_POST['allow_guest_tickets'])
		   || !isset($_POST['allow_account_creations'])
		   || !isset($_POST['allow_guest_file_uploads'])
		   || !isset($_POST['allow_file_uploads'])
		   || !isset($_POST['file_uploads_max_size'])
		   || !isset($_POST['file_uploads_extensions'])
		) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/general-settings');
			die();
		}

        $this->load->library('upload');



		// Check if we should change confirm_purchase_codes
		$envato_username = $_POST['envato_username'];
		$envato_api = $_POST['envato_api'];
		$allow_guest_bug_reports = $_POST['allow_guest_bug_reports'];
		$allow_guest_tickets = $_POST['allow_guest_tickets'];
		$allow_account_creations = $_POST['allow_account_creations'];
		
		$allow_guest_file_uploads = $_POST['allow_guest_file_uploads'];
		$allow_file_uploads = $_POST['allow_file_uploads'];
		$file_uploads_max_size = $_POST['file_uploads_max_size'];
		$file_uploads_extensions = $_POST['file_uploads_extensions'];
		
		// jQuery validation is skipped?
		if(($envato_username == '' && $envato_api != '')
			|| ($envato_username != '' && $envato_api == '')
			|| ($allow_guest_bug_reports != '0' && $allow_guest_bug_reports != '1')
			|| ($allow_guest_tickets != '0' && $allow_guest_tickets != '1')
			|| ($allow_account_creations != '0' && $allow_account_creations != '1')
			|| ($allow_guest_file_uploads != '0' && $allow_guest_file_uploads != '1')
			|| ($allow_file_uploads != '0' && $allow_file_uploads != '1')
			|| (preg_match('/^([0-9]+)$/', $file_uploads_max_size) == false)
			|| (preg_match('/^[abcdefghijklmnopqrstuvwxyz, ]*$/', $file_uploads_extensions) == false)
		) {
			header('Location: ' . $this->config->base_url() . 'panel/admin/general-settings');
			die();
		}
		
		// Save settings
		$this->settings_model->change_setting('site_title', $_POST['site_title']);
		$this->settings_model->change_setting('allow_guest_bug_reports', $allow_guest_bug_reports);
		$this->settings_model->change_setting('allow_guest_tickets', $allow_guest_tickets);
		$this->settings_model->change_setting('allow_account_creations', $allow_account_creations);
		$this->settings_model->change_setting('allow_guest_file_uploads', $allow_guest_file_uploads);
		$this->settings_model->change_setting('allow_file_uploads', $allow_file_uploads);
		
		// Deactivate confirm_purchase_codes
		if($envato_username == '' && $envato_api == '') {
			$this->settings_model->change_setting('confirm_purchase_codes', '0');
			$this->settings_model->change_setting('confirm_purchase_codes_username', '');
			$this->settings_model->change_setting('confirm_purchase_codes_api', '');
		}else{
			// Validate data
			$this->load->helper('envato_verifier_helper');
			if(verify_envato_userapi($envato_username, $envato_api) == false) {
				$_SESSION['general_settings_envato_error'] = true;
				header('Location: ' . $this->config->base_url() . 'panel/admin/general-settings');
				die();
			}else{
				$this->settings_model->change_setting('confirm_purchase_codes', '1');
				$this->settings_model->change_setting('confirm_purchase_codes_username', $envato_username);
				$this->settings_model->change_setting('confirm_purchase_codes_api', $envato_api);
			}
		}		
		
		// Get max file size allowed
		$upload_max_filesize = trim(ini_get('upload_max_filesize'));
		$post_max_size = trim(ini_get('post_max_size'));
		
		// Convert vars to MB
		$upload_max_filesize_val = $upload_max_filesize * 1;
		$upload_max_filesize_last = strtolower($upload_max_filesize[strlen($upload_max_filesize)-1]);
		if($upload_max_filesize_last == 'g')
			$upload_max_filesize_val *= 1024;
		elseif($upload_max_filesize_last == 'k')
			$upload_max_filesize_val /= 1024;
		elseif($upload_max_filesize_last != 'm')
			$upload_max_filesize_val /= 1048576;
		
		$post_max_size_val = $post_max_size * 1;
		$post_max_size_last = strtolower($post_max_size[strlen($post_max_size)-1]);
		if($post_max_size_last == 'g')
			$post_max_size_val *= 1024;
		elseif($post_max_size_last == 'k')
			$post_max_size_val /= 1024;
		elseif($post_max_size_last != 'm')
			$post_max_size_val /= 1048576;
		
		// Get minimum value of the calculated vars and the received value
		// If received value is 0, get minimum value from the PHP.ini vars
		if((int)$file_uploads_max_size == 0)
			$max_file_size = min($upload_max_filesize_val, $post_max_size_val);
		else
			$max_file_size = min($upload_max_filesize_val, $post_max_size_val, $file_uploads_max_size);
		
		// Save it
		$this->settings_model->change_setting('file_uploads_max_size', $max_file_size);
		
		// Work on the extensions, first remove spaces
		$file_uploads_extensions = str_replace(' ', '', $file_uploads_extensions);
		
		// Only if there are extensions..
		if($file_uploads_extensions != '') {
			// Remove possible repeated commas
			$file_uploads_extensions = preg_replace('/,,+/', ',', $file_uploads_extensions);
			
			// Remove possible commas at the end or beginning
			if($file_uploads_extensions{0} == ',')
				$file_uploads_extensions = substr($file_uploads_extensions, 1, strlen($file_uploads_extensions));
			if($file_uploads_extensions{strlen($file_uploads_extensions)-1} == ',')
				$file_uploads_extensions = substr($file_uploads_extensions, 0, -1);
			
			// Change commas by |
			$file_uploads_extensions = implode('|', explode(',', $file_uploads_extensions));
			
			$this->settings_model->change_setting('file_uploads_extensions', $file_uploads_extensions);
		}else
			$this->settings_model->change_setting('file_uploads_extensions', '');



        // Check for extensions first


        if(isset($_FILES['file_login_logo']) && $_FILES['file_login_logo']['name'] != '') {

		
            $file_ext = strtolower(pathinfo($_FILES['file_login_logo']['name'], PATHINFO_EXTENSION));
            if($file_ext != 'zip') {
                $_SESSION['logo_settings_error'] = 1;		// file_login_logo wrong extension
                header('Location: ' . $this->config->base_url() . 'panel/admin/general-settings');
                die();
            }

            $config = array(
                'upload_path' => FCPATH . 'downloads/',
                'allowed_types' => '*',
                'file_name' => 'software.zip',
                'overwrite' => true
            );
            $this->upload->initialize($config);

            if($this->upload->do_upload('file_login_logo') == false) {
                $_SESSION['logo_settings_error'] = 5;		// file_login_logo couldn't be uploaded
                unset($config);
                header('Location: ' . $this->config->base_url() . 'panel/admin/general-settings');
                die();
            }
            else
            {
                $_SESSION['logo_settings_error'] = 3;		// file_login_logo couldn't be uploaded
                header('Location: ' . $this->config->base_url() . 'panel/admin/general-settings');
                unset($config);
                die();
            }
        }

		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/general-settings');
	}
	
	// Page: panel/admin/mailer-settings
	// Displays Tickerr mailer settings
	public function mailer_settings() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Current page for the sidebar
		$config['current_page'] = 25;
		
		// Get settings
		$settings = array(
			'mailing',
			'mailer_method',
			'smtp_host',
			'smtp_port',
			'smtp_user',
			'smtp_pass',
			'smtp_timeout',
			'mailpath',
			'email_from_address',
			'email_from_name',
			'email_cc'
		);
		$config['settings'] = $this->settings_model->get_multiple_settings($settings);
		
		// Get site title
		$config['site_title'] = $this->settings_model->get_setting('site_title');
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Finish loading view
		$this->load->view('admin/settings/mailer_settings', $config);
	}
	
	// Action of the previous function (mailer settings)
	public function mailer_settings_action() {
		// Check for all values to be set
		$values = array('mailing','email_from_address','email_from_name','email_cc','mailing_method','smtp_host','smtp_port','smtp_user','smtp_pass','smtp_timeout','mailpath');
		foreach($values as $v) {
			if(!isset($_POST[$v])) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/mailer-settings');
				die();
			}else{
				$$v = $_POST[$v];
			}
		}
		
		// Is mailing disabled? Update and return, that's it.
		if($mailing == '0') {
			$this->settings_model->change_setting('mailing', '0');
			header('Location: ' . $this->config->base_url() . 'panel/admin/mailer-settings');
			die();
		}else{
			// First we're going to validate ALL data
			// Invalid data means the jQuery security was bypassed, so we're going
			// to return to the page without any warning.
			if(	   $email_from_address == ''
				|| filter_var($email_from_address, FILTER_VALIDATE_EMAIL) == false
				|| $email_from_name == ''
				|| ($email_cc != '' && filter_var($email_cc, FILTER_VALIDATE_EMAIL) == false)
				|| ($mailing_method == '1'
				&& ($smtp_host == ''
					|| $smtp_port == ''
					|| is_numeric($smtp_port) == false
					|| $smtp_user == ''
					|| $smtp_pass == ''
					|| $smtp_timeout == ''
					|| is_numeric($smtp_timeout) == false
				   )
				)
				|| ($mailing_method == '2' &&  $mailpath == '')
				|| ($mailing_method != '1' && $mailing_method != '2')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/mailer-settings');
				die();
			}else{
				
				// Mailing enabled and all data is validated
				// Enable mailing and set first 3 values
				$this->settings_model->change_setting('mailing', '1');
				$this->settings_model->change_setting('email_from_address', $email_from_address);
				$this->settings_model->change_setting('email_from_name', $email_from_name);
				$this->settings_model->change_setting('email_cc', $email_cc);
				
				// Change more data depending on the mailing method
				if($mailing_method == '1') {
					$this->settings_model->change_setting('mailer_method', '1');
					$this->settings_model->change_setting('smtp_host', $smtp_host);
					$this->settings_model->change_setting('smtp_port', $smtp_port);
					$this->settings_model->change_setting('smtp_user', $smtp_user);
					$this->settings_model->change_setting('smtp_pass', $smtp_pass);
					$this->settings_model->change_setting('smtp_timeout', $smtp_timeout);
				}else{
					$this->settings_model->change_setting('mailer_method', '2');
					$this->settings_model->change_setting('mailpath', $mailpath);
				}
			}
		}
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/mailer-settings');
		die();
	}
	
	// Page: panel/admin/econfirm-settings
	// Displays Tickerr email confirmation settings
	public function econfirm_settings() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Current page for the sidebar
		$config['current_page'] = 26;
		
		// Get settings
		$settings = array(
			'mailing',
			'email_confirmation',
			'email_new_account_confirmation_type', 'email_new_account_confirmation_title', 'email_new_account_confirmation_content',
			
			'send_email_confirmed_account',
			'email_confirmed_account_type', 'email_confirmed_account_title', 'email_confirmed_account_content'
		);
		$config['settings'] = $this->settings_model->get_multiple_settings($settings);
		$config['site_title'] = $this->settings_model->get_setting('site_title');
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Finish loading view
		$this->load->view('admin/settings/econfirm_settings', $config);
	}
	
	// Action of the previous function (email confirmation settings)
	public function econfirm_settings_action() {
		// Check for all values to be set
		$values = array('email_confirmation','n_account_confirmation_type','n_account_confirmation_title','n_caccount_confirmation_content','email_confirmed','c_account_type','c_account_title','c_account_content');
		foreach($values as $v) {
			if(!isset($_POST[$v])) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/econfirm-settings');
				die();
			}else{
				$$v = $_POST[$v];
			}
		}
		
		// Is email_confirmation disabled? Update and go on
		if($email_confirmation == '0')
			$this->settings_model->change_setting('email_confirmation', '0');
		// Is email_confirmed disabled? Update and go on
		if($email_confirmed == '0')
			$this->settings_model->change_setting('send_email_confirmed_account', '0');
		// Both were disabled? Done
		if($email_confirmation == '0' && $email_confirmed == '0') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/econfirm-settings');
			die();
		}
		
		// Validate ALL data first
		if($email_confirmation == '1') {
			if($n_account_confirmation_title == ''
				|| ($n_account_confirmation_type != 'html' && $n_account_confirmation_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/econfirm-settings');
				die();
			}
		}
		if($email_confirmed == '1') {
			if($n_account_confirmation_title == ''
				|| ($n_account_confirmation_type != 'html' && $n_account_confirmation_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/econfirm-settings');
				die();
			}
		}
		
		// Save each group
		if($email_confirmation == '1') {			
			$this->settings_model->change_setting('email_confirmation', '1');
			$this->settings_model->change_setting('email_new_account_confirmation_type', $n_account_confirmation_type);
			$this->settings_model->change_setting('email_new_account_confirmation_title', $n_account_confirmation_title);
			$this->settings_model->change_setting('email_new_account_confirmation_content', $n_caccount_confirmation_content);
		}
		
		if($email_confirmed == '1') {
			$this->settings_model->change_setting('send_email_confirmed_account', '1');
			$this->settings_model->change_setting('email_confirmed_account_type', $c_account_type);
			$this->settings_model->change_setting('email_confirmed_account_title', $c_account_title);
			$this->settings_model->change_setting('email_confirmed_account_content', $c_account_content);
		}
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/econfirm-settings');
		die();
	}
	
	// Page: panel/admin/arecovery-settings
	// Displays Tickerr account recovery settings
	public function arecovery_settings() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Current page for the sidebar
		$config['current_page'] = 27;
		
		// Get settings
		$settings = array(
			'mailing',
			'allow_account_recovery',
			'email_recover_type', 'email_recover_title', 'email_recover_content',

			'send_email_recovery_done',
			'email_recovery_done_type', 'email_recovery_done_title', 'email_recovery_done_content'
		);
		$config['settings'] = $this->settings_model->get_multiple_settings($settings);
		$config['site_title'] = $this->settings_model->get_setting('site_title');
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Finish loading view
		$this->load->view('admin/settings/arecovery_settings', $config);
	}
	
	// Action of the previous function (account recovery settings)
	public function arecovery_settings_action() {
		// Check for all values to be set
		$values = array('allow_account_recovery','email_recover_type','email_recover_title','email_recover_content','recovery_done','recovery_done_type','recovery_done_title','recovery_done_content');
		foreach($values as $v) {
			if(!isset($_POST[$v])) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/arecovery-settings');
				die();
			}else{
				$$v = $_POST[$v];
			}
		}
		
		// Is allow_account_recovery disabled? Update and go on
		if($allow_account_recovery == '0')
			$this->settings_model->change_setting('allow_account_recovery', '0');
		// Is recovery_done disabled? Update and go on
		if($recovery_done == '0')
			$this->settings_model->change_setting('send_email_recovery_done', '0');
		// Both were disabled? Done
		if($allow_account_recovery == '0' && $recovery_done == '0') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/arecovery-settings');
			die();
		}
		
		// Validate ALL data first
		if($allow_account_recovery == '1') {
			if($email_recover_title == ''
				|| ($email_recover_type != 'html' && $email_recover_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/arecovery-settings');
				die();
			}
		}
		if($recovery_done == '1') {
			if($recovery_done_title == ''
				|| ($recovery_done_type != 'html' && $recovery_done_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/arecovery-settings');
				die();
			}
		}
		
		// Save each group
		if($allow_account_recovery == '1') {			
			$this->settings_model->change_setting('allow_account_recovery', '1');
			$this->settings_model->change_setting('email_recover_type', $email_recover_type);
			$this->settings_model->change_setting('email_recover_title', $email_recover_title);
			$this->settings_model->change_setting('email_recover_content', $email_recover_content);
		}
		
		if($recovery_done == '1') {
			$this->settings_model->change_setting('send_email_recovery_done', '1');
			$this->settings_model->change_setting('email_recovery_done_type', $recovery_done_type);
			$this->settings_model->change_setting('email_recovery_done_title', $recovery_done_title);
			$this->settings_model->change_setting('email_recovery_done_content', $recovery_done_content);
		}
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/arecovery-settings');
		die();
	}
	
	// Page: panel/admin/email-settings
	// Displays Tickerr emails settings
	public function emails_settings() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Current page for the sidebar
		$config['current_page'] = 28;
		
		// Get settings
		$settings = array(
			'mailing',
			'send_email_ticket_guest_submitted',
			'email_ticket_guest_submitted_type', 'email_ticket_guest_submitted_title', 'email_ticket_guest_submitted_content',
			
			'send_email_bug_guest_submitted',
			'email_bug_guest_submitted_type', 'email_bug_guest_submitted_title', 'email_bug_guest_submitted_content',
			
			'send_email_new_account',
			'email_new_account_type', 'email_new_account_title', 'email_new_account_content',
			
			'send_email_new_reply',
			'email_new_reply_type', 'email_new_reply_title', 'email_new_reply_content',

			'send_email_bug_new_status',
			'email_bug_new_status_type', 'email_bug_new_status_title', 'email_bug_new_status_content',
			
			// New
			'send_agents_email_ticket_guest_submitted',
			'agents_email_ticket_guest_submitted_type', 'agents_email_ticket_guest_submitted_title', 'agents_email_ticket_guest_submitted_content',
			
			'send_agents_email_ticket_client_submitted',
			'agents_email_ticket_client_submitted_type', 'agents_email_ticket_client_submitted_title', 'agents_email_ticket_client_submitted_content',
			
			'send_agents_email_bug_guest_submitted',
			'agents_email_bug_guest_submitted_type', 'agents_email_bug_guest_submitted_title', 'agents_email_bug_guest_submitted_content',
			
			'send_agents_email_bug_client_submitted',
			'agents_email_bug_client_submitted_type', 'agents_email_bug_client_submitted_title', 'agents_email_bug_client_submitted_content',
			
			'send_agent_email_new_reply',
			'agent_email_new_reply_type', 'agent_email_new_reply_title', 'agent_email_new_reply_content'
		);
		$config['settings'] = $this->settings_model->get_multiple_settings($settings);
		$config['site_title'] = $this->settings_model->get_setting('site_title');
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Finish loading view
		$this->load->view('admin/settings/emails_settings', $config);
	}
	
	// Action of the previous function (emails settings)
	public function emails_settings_action() {
		// Check for all values to be set
		$values = array(
			'send_email_ticket_guest',
			'ticket_guest_type', 'ticket_guest_title', 'ticket_guest_content',
			
			'send_email_bug_guest',
			'bug_guest_type', 'bug_guest_title', 'bug_guest_content',
			
			'send_email_new_account',
			'new_account_type', 'new_account_title', 'new_account_content',
			
			'send_email_new_reply',
			'new_reply_type', 'new_reply_title', 'new_reply_content',
			
			'send_email_new_status',
			'new_status_type', 'new_status_title', 'new_status_content',
			
			'send_email_agents_new_ticket_guest',
			'agents_new_ticket_guest_type', 'agents_new_ticket_guest_title', 'agents_new_ticket_guest_content',

			'send_email_agents_new_ticket_client',
			'agents_new_ticket_client_type', 'agents_new_ticket_client_title', 'agents_new_ticket_client_content',

			'send_email_agents_new_bug_guest',
			'agents_new_bug_guest_type', 'agents_new_bug_guest_title', 'agents_new_bug_guest_content',

			'send_email_agents_new_bug_client',
			'agents_new_bug_client_type', 'agents_new_bug_client_title', 'agents_new_bug_client_content',

			'send_email_agent_new_ticket_reply',
			'agent_new_ticket_reply_type', 'agent_new_ticket_reply_title', 'agent_new_ticket_reply_content'
		);
		foreach($values as $v) {
			if(!isset($_POST[$v])) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}else{
				$$v = $_POST[$v];
			}
		}
		
		// Disabled options? Update and go on
		if($send_email_ticket_guest == '0')
			$this->settings_model->change_setting('send_email_ticket_guest_submitted', '0');
		if($send_email_bug_guest == '0')
			$this->settings_model->change_setting('send_email_bug_guest_submitted', '0');
		if($send_email_new_account == '0')
			$this->settings_model->change_setting('send_email_new_account', '0');
		if($send_email_new_reply == '0')
			$this->settings_model->change_setting('send_email_new_reply', '0');
		if($send_email_new_status == '0')
			$this->settings_model->change_setting('send_email_bug_new_status', '0');
			
		if($send_email_agents_new_ticket_guest == '0')
			$this->settings_model->change_setting('send_agents_email_ticket_guest_submitted', '0');
		if($send_email_agents_new_ticket_client == '0')
			$this->settings_model->change_setting('send_agents_email_ticket_client_submitted', '0');
		if($send_email_agents_new_bug_guest == '0')
			$this->settings_model->change_setting('send_agents_email_bug_guest_submitted', '0');
		if($send_email_agents_new_bug_client == '0')
			$this->settings_model->change_setting('send_agents_email_bug_client_submitted', '0');
		if($send_email_agent_new_ticket_reply == '0')
			$this->settings_model->change_setting('send_agent_email_new_reply', '0');
		
		// All options were disabled? Done
		if($send_email_ticket_guest == '0' && $send_email_bug_guest == '0' && $send_email_new_account == '0' && $send_email_new_reply == '0' && $send_email_new_status == '0'
		&& $send_email_agents_new_ticket_guest == '0' && $send_email_agents_new_ticket_client == '0' && $send_email_agents_new_bug_guest == '0'
		&& $send_email_agents_new_bug_client == '0' && $send_email_agent_new_ticket_reply == '0') {
			header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
			die();
		}
		
		// Validate ALL data first
		if($send_email_ticket_guest == '1') {
			if($ticket_guest_title == ''
				|| ($ticket_guest_type != 'html' && $ticket_guest_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_bug_guest == '1') {
			if($bug_guest_title == ''
				|| ($bug_guest_type != 'html' && $bug_guest_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_new_account == '1') {
			if($new_account_title == ''
				|| ($new_account_type != 'html' && $new_account_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_new_reply == '1') {
			if($new_reply_title == ''
				|| ($new_reply_type != 'html' && $new_reply_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_new_status == '1') {
			if($new_status_title == ''
				|| ($new_status_type != 'html' && $new_status_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		
		// New
		if($send_email_agents_new_ticket_guest == '1') {
			if($agents_new_ticket_guest_title == ''
				|| ($agents_new_ticket_guest_type != 'html' && $agents_new_ticket_guest_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_agents_new_ticket_client == '1') {
			if($agents_new_ticket_client_title == ''
				|| ($agents_new_ticket_client_type != 'html' && $agents_new_ticket_client_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_agents_new_bug_guest == '1') {
			if($agents_new_bug_guest_title == ''
				|| ($agents_new_bug_guest_type != 'html' && $agents_new_bug_guest_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_agents_new_bug_client == '1') {
			if($agents_new_bug_client_title == ''
				|| ($agents_new_bug_client_type != 'html' && $agents_new_bug_client_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		if($send_email_agent_new_ticket_reply == '1') {
			if($agent_new_ticket_reply_title == ''
				|| ($agent_new_ticket_reply_type != 'html' && $agent_new_ticket_reply_type != 'text')
			) {
				header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
				die();
			}
		}
		
		// Save each group
		if($send_email_ticket_guest == '1') {
			$this->settings_model->change_setting('send_email_ticket_guest_submitted', '1');
			$this->settings_model->change_setting('email_ticket_guest_submitted_type', $ticket_guest_type);
			$this->settings_model->change_setting('email_ticket_guest_submitted_title', $ticket_guest_title);
			$this->settings_model->change_setting('email_ticket_guest_submitted_content', $ticket_guest_content);
		}
		
		if($send_email_bug_guest == '1') {
			$this->settings_model->change_setting('send_email_bug_guest_submitted', '1');
			$this->settings_model->change_setting('email_bug_guest_submitted_type', $bug_guest_type);
			$this->settings_model->change_setting('email_bug_guest_submitted_title', $bug_guest_title);
			$this->settings_model->change_setting('email_bug_guest_submitted_content', $bug_guest_content);
		}
		
		if($send_email_new_account == '1') {
			$this->settings_model->change_setting('send_email_new_account', '1');
			$this->settings_model->change_setting('email_new_account_type', $new_account_type);
			$this->settings_model->change_setting('email_new_account_title', $new_account_title);
			$this->settings_model->change_setting('email_new_account_content', $new_account_content);
		}
		
		if($send_email_new_reply == '1') {
			$this->settings_model->change_setting('send_email_new_reply', '1');
			$this->settings_model->change_setting('email_new_reply_type', $new_reply_type);
			$this->settings_model->change_setting('email_new_reply_title', $new_reply_title);
			$this->settings_model->change_setting('email_new_reply_content', $new_reply_content);
		}
		
		if($send_email_new_status == '1') {
			$this->settings_model->change_setting('send_email_bug_new_status', '1');
			$this->settings_model->change_setting('email_bug_new_status_type', $new_status_type);
			$this->settings_model->change_setting('email_bug_new_status_title', $new_status_title);
			$this->settings_model->change_setting('email_bug_new_status_content', $new_status_content);
		}
		
		// New
		if($send_email_agents_new_ticket_guest == '1') {
			$this->settings_model->change_setting('send_agents_email_ticket_guest_submitted', '1');
			$this->settings_model->change_setting('agents_email_ticket_guest_submitted_type', $agents_new_ticket_guest_type);
			$this->settings_model->change_setting('agents_email_ticket_guest_submitted_title', $agents_new_ticket_guest_title);
			$this->settings_model->change_setting('agents_email_ticket_guest_submitted_content', $agents_new_ticket_guest_content);
		}
		
		if($send_email_agents_new_ticket_client == '1') {
			$this->settings_model->change_setting('send_agents_email_ticket_client_submitted', '1');
			$this->settings_model->change_setting('agents_email_ticket_client_submitted_type', $agents_new_ticket_client_type);
			$this->settings_model->change_setting('agents_email_ticket_client_submitted_title', $agents_new_ticket_client_title);
			$this->settings_model->change_setting('agents_email_ticket_client_submitted_content', $agents_new_ticket_client_content);
		}
		
		if($send_email_agents_new_bug_guest == '1') {
			$this->settings_model->change_setting('send_agents_email_bug_guest_submitted', '1');
			$this->settings_model->change_setting('agents_email_bug_guest_submitted_type', $agents_new_bug_guest_type);
			$this->settings_model->change_setting('agents_email_bug_guest_submitted_title', $agents_new_bug_guest_title);
			$this->settings_model->change_setting('agents_email_bug_guest_submitted_content', $agents_new_bug_guest_content);
		}
		
		if($send_email_agents_new_bug_client == '1') {
			$this->settings_model->change_setting('send_agents_email_bug_client_submitted', '1');
			$this->settings_model->change_setting('agents_email_bug_client_submitted_type', $agents_new_bug_client_type);
			$this->settings_model->change_setting('agents_email_bug_client_submitted_title', $agents_new_bug_client_title);
			$this->settings_model->change_setting('agents_email_bug_client_submitted_content', $agents_new_bug_client_content);
		}
		
		if($send_email_agent_new_ticket_reply == '1') {
			$this->settings_model->change_setting('send_agent_email_new_reply', '1');
			$this->settings_model->change_setting('agent_email_new_reply_type', $agent_new_ticket_reply_type);
			$this->settings_model->change_setting('agent_email_new_reply_title', $agent_new_ticket_reply_title);
			$this->settings_model->change_setting('agent_email_new_reply_content', $agent_new_ticket_reply_content);
		}
		
		// Return
		header('Location: ' . $this->config->base_url() . 'panel/admin/emails-settings');
		die();
	}
	
	
	
	
	
	
	
	
	
	
	
	// Page: panel/admin/logo-settings
	// Displays Tickerr logo settings
	public function logo_settings() {
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		
		// Current page for the sidebar
		$config['current_page'] = 35;
		
		// Errors that might occur
		if(isset($_SESSION['logo_settings_error'])) {
			$config['logo_error'] = $_SESSION['logo_settings_error'];
			unset($_SESSION['logo_settings_error']);
		}else{
			$config['logo_error'] = false;
			unset($_SESSION['logo_settings_error']);
		}
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Finish loading view
		$this->load->view('admin/settings/logo_settings', $config);
	}
	
	
	// Action of the previous function (general settings)
	public function logo_settings_action() {
		if(!isset($_FILES['file_login_logo']) && !isset($_FILES['file_dash_logo'])) {
			// Nothing to do, redirect
			header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
			die();
		}
		
		
		// Prepare system
		$this->load->library('image_lib');
		$this->load->library('upload');
		
		// Check for extensions first
		if(isset($_FILES['file_login_logo']) && $_FILES['file_login_logo']['name'] != '') {
			$file_ext = strtolower(pathinfo($_FILES['file_login_logo']['name'], PATHINFO_EXTENSION));
			if($file_ext != 'png') {
				$_SESSION['logo_settings_error'] = 1;		// file_login_logo wrong extension
				header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
				die();
			}
		}
		if(isset($_FILES['file_dash_logo']) && $_FILES['file_dash_logo']['name'] != '') {
			$file_ext = strtolower(pathinfo($_FILES['file_dash_logo']['name'], PATHINFO_EXTENSION));
			if($file_ext != 'png') {
				$_SESSION['logo_settings_error'] = 2;		// file_dash_logo wrong extension
				header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
				die();
			}
		}
		
		// Now check for image dimensions
		if(isset($_FILES['file_login_logo']) && $_FILES['file_login_logo']['name'] != '') {
			$image_dim = getimagesize($_FILES['file_login_logo']['tmp_name']);
			if($image_dim[0] != 810 || $image_dim[1] != 500) {
				$_SESSION['logo_settings_error'] = 3;		// file_login_logo wrong dimensions
				header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
				die();
			}
		}
		if(isset($_FILES['file_dash_logo']) && $_FILES['file_dash_logo']['name'] != '') {
			$image_dim = getimagesize($_FILES['file_dash_logo']['tmp_name']);
			if($image_dim[0] != 510 || $image_dim[1] != 75) {
				$_SESSION['logo_settings_error'] = 4;		// file_dash_logo wrong dimensions
				header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
				die();
			}
		}
		
		
		// All good, work with the login logo first
		if(isset($_FILES['file_login_logo']) && $_FILES['file_login_logo']['name'] != '' ) {
			// Upload file
			$config = array(
				'upload_path' => FCPATH . 'assets/img/logos/',
				'allowed_types' => 'png',
				'file_name' => 'mainlogo@3x.png',
				'overwrite' => true
			);
			$this->upload->initialize($config);
			
			if($this->upload->do_upload('file_login_logo') == false) {
				$_SESSION['logo_settings_error'] = 5;		// file_login_logo couldn't be uploaded
				header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
				die();
			}
			
			// File uploaded, now resize
			$config = array(
				'image_library' => 'gd2',
				'source_image' => FCPATH . 'assets/img/logos/mainlogo@3x.png',
				'new_image' => FCPATH . 'assets/img/logos/mainlogo@2x.png',
				'maintain_ratio' => FALSE,
				'width' => 540,
				'height' => 210
			);
			$this->image_lib->initialize($config);
			$this->image_lib->resize();
			
			$config = array(
				'image_library' => 'gd2',
				'source_image' => FCPATH . 'assets/img/logos/mainlogo@3x.png',
				'new_image' => FCPATH . 'assets/img/logos/mainlogo@1x.png',
				'maintain_ratio' => FALSE,
				'width' => 350,
				'height' => 155
			);
			$this->image_lib->initialize($config);
			$this->image_lib->resize();
		}
		
		
		// Now work with the dashboard logo
		if(isset($_FILES['file_dash_logo']) && $_FILES['file_dash_logo']['name'] != '') {
			// Upload file
			$config = array(
				'upload_path' => FCPATH . 'assets/img/logos/',
				'allowed_types' => 'png',
				'file_name' => 'dashlogo@3x.png',
				'overwrite' => true
			);
			$this->upload->initialize($config);
			
			if($this->upload->do_upload('file_dash_logo') == false) {
				$_SESSION['logo_settings_error'] = 6;		// file_dash_logo couldn't be uploaded
				header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
				die();
			}
			
			// File uploaded, now resize
			$config = array(
				'image_library' => 'gd2',
				'source_image' => FCPATH . 'assets/img/logos/dashlogo@3x.png',
				'new_image' => FCPATH . 'assets/img/logos/dashlogo@2x.png',
				'maintain_ratio' => FALSE,
				'width' => 340,
				'height' => 50
			);
			$this->image_lib->initialize($config);
			$this->image_lib->resize();
			
			$config = array(
				'image_library' => 'gd2',
				'source_image' => FCPATH . 'assets/img/logos/dashlogo@3x.png',
				'new_image' => FCPATH . 'assets/img/logos/dashlogo@1x.png',
				'maintain_ratio' => FALSE,
				'width' => 170,
				'height' => 25
			);
			$this->image_lib->initialize($config);
			$this->image_lib->resize();
		}
		
		// Everything was succesful, return
		header('Location: ' . $this->config->base_url() . 'panel/admin/logo-settings');
	}
	
	// Page: panel/admin/all-news
    // Displays list of all news
    public function all_news() {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Assign POST values to prevent errors in the view
        if(!isset($_POST['user-name'])) $_POST['user-name'] = '';
        if(!isset($_POST['user-username'])) $_POST['user-username'] = '';
        if(!isset($_POST['user-email'])) $_POST['user-email'] = '';
        if(!isset($_POST['user-role'])) $_POST['user-role'] = '1';

        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $config['users_model'] = $this->users_model;
        $config['news_model'] = $this->news_model;

        // Sort for agent only
        $agent_sort = array('create_at','id','subject');

        // Sort...
        if(isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        }else{
            $sort_direction = 'DESC';
            $config['sort'] = 5;
            $sort = 'create_at';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if(!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if($page == 1) $from = 0;
        else $from = (($page-1)*$records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 29;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if(isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_news'] = $this->news_model->get_all_news_exp($userid, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
            $config['all_news_count'] = $this->news_model->count_search_all_news_exp($userid, $_GET['search']);
        }else{
            $config['search'] = false;
            $config['all_news'] = $this->news_model->get_all_news_exp($userid, $records_per_page,$from,$sort,$sort_direction);
            $config['all_news_count'] = $this->news_model->count_all_news_exp($userid);
        }

        // Total pages
        $config['total_pages'] = round($config['all_news_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_news', $config);
    }

    // Page: panel/new-news
    // Displays the form to create a new News
    public function new_news() {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 29;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);



        // If POST information is received
        if(isset($_POST['subject'])) {
            $subject = $this->input->post('subject');

            $description = $this->input->post('description');


            // Tinyeditor fix to the message
            $description = str_replace('<span style="letter-spacing: -0.129999995231628px;">','<span>', $description);



            $insert = $this->news_model->insert_news($userid, $subject, $description);
            if($insert == false) {
                $config['error'] = "Your News couldn't be created. Please try again";
                $this->load_view_combo('admin/new_news', $config);
                return;
            }else{
                // NEW
                // Success. Check if we should send email or not
                // If email is enabled, load library and continue

				//echo $description;
				//exit();
				 $all_client=$this->admin_model->get_all_clients();
								
                $email_info = $this->settings_model->get_email_info();
				$email_specific = $this->settings_model->get_email_specific('email_new_account');
                $cnt_client=0;
				foreach($all_client as $cl)
				{
					$config = $this->settings_model->get_email_settings();
                    $config['mailtype'] = "html";

                    // Load library and prepare info
                    $this->load->library('email');
                    $this->email->initialize($config);

                    $this->email->from($email_info['email_from_address'], $email_info['email_from_name']);
                    $this->email->to($cl->email);
                    $this->email->subject($subject);
                    $this->email->message($description);
                   
					if ($this->email->send()) {
							//print "success";
							 $cnt_client++;
						} else {
							print "Could not send email to, please try again later";
						}
                }
				if($cnt_client>0) {
                	$this->news_model->update_email_news($insert, $cnt_client,'client');
            	}

				$all_customer=$this->admin_model->get_customers_emails();
				 $cnt_client=0;
                foreach($all_customer as $cl) {
                    $config = $this->settings_model->get_email_settings();
                    $config['mailtype'] = $email_specific['type'];
                    // Load library and prepare info
                    $this->load->library('email');
                    $this->email->initialize($config);

                    $this->email->from($email_info['email_from_address'], $email_info['email_from_name']);
                    $this->email->to($cl->email);
                    //$this->email->cc($email_info['email_cc']);

                    $this->email->subject($subject);

                    $this->email->message($description);
                   if ($this->email->send()) {
							//print "success";
							 $cnt_client++;
						} else {
							print "Could not send email to, please try again later";
						}

                }

				if($cnt_client>0) {
                	$this->news_model->update_email_news($insert, $cnt_client,'customer');
            	}

				$all_email=$this->admin_model->get_all_emails();

					  $cnt=1;
				  	foreach($all_email as $cl) {
					   $insert_email_news= $this->admin_model->add_news_emails($cl->id,$insert);
					  
                }

                header('Location: ' . $this->config->base_url() . 'panel/admin/all-news');
                die();
            }
        }else{
            $this->load->view('admin/new_news', $config);
        }
    }






    // Page: panel/edit-news
    // Displays the form to Edit News
    // Delete user. Returns to admin/all-users
    public function delete_news($id){
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }
        // Different action for different user role

            // Agent/admin
            $this->news_model->delete_news($id);
            header('Location: ' . $this->config->base_url() . 'panel/admin/all-news/');
            return;
       }



    // Page: panel/edit-news
    // Displays the form to Edit News
    public function edit_news($id) {
        
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
    	    $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
	        $config['current_page'] = 29;
            // Load stats
            $this->load_agent_sidebar_stats($config);
            $this->load_admin_sidebar_stats($config);
            // Partially load view
            $this->load_partial_view_combo($config);
            // If POST information is received
            if (isset($id)) {
                $news_info = $this->news_model->get_news($id);
                $config['news']=$news_info;
                $this->load->view('admin/edit_news', $config);

            }
            else
            {
                $this->load_view_combo('admin/new_news', $config);
            }

    }


    public function edit_news_action($id) {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 29;
        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);
        // Partially load view
        $this->load_partial_view_combo($config);


        // If POST information is received
        if(isset($_POST['subject'])) {
           $subject = $this->input->post('subject');
            $description = $this->input->post('description');

            // Tinyeditor fix to the message
            $description = str_replace('<span style="letter-spacing: -0.129999995231628px;">','<span>', $description);
            $insert = $this->news_model->edit_news($userid,$id, $subject, $description);
            if($insert == false) {
                $config['error'] = "Your News couldn't be Updated. Please try again";
                $this->load_view_combo('admin/new_news', $config);
                return;
            }else{
               header('Location: ' . $this->config->base_url() . 'panel/admin/all-news');
                die();
            }
        }else{
            $this->load_view_combo('admin/edit_news', $config);
        }

    }

    public function send_news_to_emails() {

				     
       		
        $this->load->model('News_email_model', 'news_email_model', true);
        $all_pending_emails = $this->news_email_model->pending_news_emails();
       
        $email_info = $this->settings_model->get_email_info();
		$email_specific = $this->settings_model->get_email_specific('email_new_account');
        $config = $this->settings_model->get_email_settings();
        $cnt_client=$cnt=$ar=0;
        $news_ar=array();
        $prev_news="";



		//echo realpath($_SERVER['DOCUMENT_ROOT']);
		//exit();
		foreach($all_pending_emails as $cl)
		{
                    if($prev_news != $cl->news_id)
                    {
                        $news_info = $this->news_model->get_news($cl->news_id);
                        $subject= $news_info->subject;
                        $description=$news_info->description;
                        //$email_cnt=$news_info->email_list;
                    }


                    
                    $config['mailtype'] = "html";

                    // Load library and prepare info
                    $this->load->library('email');
                    $this->email->initialize($config);

                    $this->email->from($email_info['email_from_address'], $email_info['email_from_name']);
                    $this->email->to($cl->email);
                    $this->email->subject($subject);
                    $this->email->message($description);
                   
					if ($this->email->send()) {
							//print "success";
                         $this->news_email_model->update_news_emails($cl->id); 
                        if($cnt>0 && $prev_news != $cl->news_id)
                        {
                            $news_ar[$ar]['id']=$prev_news;
                            $news_ar[$ar]['cnt']=$cnt_client;
                            $ar++;
                            $cnt_client=1;
                        }
                        else        
						    $cnt_client++;


					}
                    else
                    {
						print "Could not send email to, please try again later";
					}
                   
                   
                  
                    $prev_news=$cl->news_id;
                    $cnt++;
         }
         if(count($news_ar)==0)
         {
                        $news_ar[$ar]['id']=$prev_news;
                        $news_ar[$ar]['cnt']=$cnt_client;
                
         }
        
        foreach($news_ar as $cl)
		{
               
                $this->news_model->update_email_news($cl['id'], $cl['cnt'],'email');
        }
		 print_r($news_arcl);
		 echo "NEWS";

    }



		// Page: panel/admin/all-news
    // Displays list of all news
    public function all_desktop_news() {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Assign POST values to prevent errors in the view
        if(!isset($_POST['user-name'])) $_POST['user-name'] = '';
        if(!isset($_POST['user-username'])) $_POST['user-username'] = '';
        if(!isset($_POST['user-email'])) $_POST['user-email'] = '';
        if(!isset($_POST['user-role'])) $_POST['user-role'] = '1';

        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $config['users_model'] = $this->users_model;
        $config['news_model'] = $this->news_model;

        // Sort for agent only
        $agent_sort = array('create_at','id','subject');

        // Sort...
        if(isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        }else{
            $sort_direction = 'DESC';
            $config['sort'] = 5;
            $sort = 'create_at';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if(!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if($page == 1) $from = 0;
        else $from = (($page-1)*$records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 45;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if(isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_news'] = $this->news_model->get_all_desktop_news_exp($userid, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
            $config['all_news_count'] = $this->news_model->count_search_all_desktop_news_exp($userid, $_GET['search']);
        }else{
            $config['search'] = false;
            $config['all_news'] = $this->news_model->get_all_desktop_news_exp($userid, $records_per_page,$from,$sort,$sort_direction);
            $config['all_news_count'] = $this->news_model->count_all_desktop_news_exp($userid);
        }

        // Total pages
        $config['total_pages'] = round($config['all_news_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_desktop_news', $config);
    }

    // Page: panel/new-news
    // Displays the form to create a new News
    public function new_desktop_news() {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();
		
        // Current page for the sidebar
        $config['current_page'] = 45;
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);
        $this->load_partial_view_combo($config);

        if(isset($_POST['subject'])) {
            $subject = $this->input->post('subject');
            $description = $this->input->post('description');
            $description = str_replace('<span style="letter-spacing: -0.129999995231628px;">','<span>', $description);

            $insert = $this->news_model->insert_desktop_news($userid, $subject, $description);
            if($insert == false) {
                $config['error'] = "Your News couldn't be created. Please try again";
                $this->load_view_combo('admin/new_dektop_news', $config);
                return;
            }
			else
			{

                header('Location: ' . $this->config->base_url() . 'panel/admin/all-desktop-news');
                die();
            }
        }else{
            $this->load->view('admin/new_desktop_news', $config);
        }
    }

	  // Page: panel/edit-news
    // Displays the form to Edit News
    // Delete user. Returns to admin/all-users
    public function delete_desktop_news($id){
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }
        // Different action for different user role

            // Agent/admin
            $this->news_model->delete_desktop_news($id);
            header('Location: ' . $this->config->base_url() . 'panel/admin/all-desktop-news/');
            return;
       }


	    // Page: panel/edit-news
    // Displays the form to Edit News
    public function desktop_news($id) {
        
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
    	    $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
	        $config['current_page'] = 45;
            // Load stats
            $this->load_agent_sidebar_stats($config);
            $this->load_admin_sidebar_stats($config);
            // Partially load view
            $this->load_partial_view_combo($config);
            // If POST information is received
            if (isset($id)) {
                $news_info = $this->news_model->get_desktop_news($id);
                $config['news']=$news_info;
                $this->load->view('admin/desktop_news', $config);

            }
            else
            {
                $this->load_view_combo('admin/all_desktop_news', $config);
            }

    }






	// Page: panel/admin/all-emails
    // Displays list of all emails
    public function all_emails() {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Assign POST values to prevent errors in the view
        if(!isset($_POST['user-name'])) $_POST['user-name'] = '';
        if(!isset($_POST['user-username'])) $_POST['user-username'] = '';
        if(!isset($_POST['user-email'])) $_POST['user-email'] = '';
        if(!isset($_POST['user-role'])) $_POST['user-role'] = '1';

        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $config['users_model'] = $this->users_model;
        $config['emails_model'] = $this->emails_model;

        // Sort for agent only
        $agent_sort = array('create_at','id','email');

        // Sort...
        if(isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        }else{
            $sort_direction = 'DESC';
            $config['sort'] = 5;
            $sort = 'create_at';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if(!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if($page == 1) $from = 0;
        else $from = (($page-1)*$records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 32;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if(isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_emails'] = $this->emails_model->get_all_emails_exp($userid, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
            $config['all_emails_count'] = $this->emails_model->count_search_all_emails_exp($userid, $_GET['search']);
        }else{
            $config['search'] = false;
            $config['all_emails'] = $this->emails_model->get_all_emails_exp($userid, $records_per_page,$from,$sort,$sort_direction);
            $config['all_emails_count'] = $this->emails_model->count_all_emails_exp($userid);
        }

        // Total pages
        $config['total_pages'] = round($config['all_emails_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_emails', $config);
    }

    // Page: panel/new-email
    // Displays the form to create a new email
    public function new_email() {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for Admin
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 32;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);



        // If POST information is received
        if(isset($_POST['email'])) {




            $subject = $this->input->post('email');
            $match = array();

            //String that recognize a e-mail
            $test_pat = '/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,4}/';

            //check testpattern in given string
            preg_match_all($test_pat, $subject, $match);

            //store above in array for upcoming bit.
            $newArr = array_values(array_unique($match[0]));
            //var_dump($newArr);


           // exit();


            // Tinyeditor fix to the message
            $c=$d=0;
            foreach ($newArr as $nemail)
            {

                $insert = $this->emails_model->insert_email($userid, $nemail);
                if($insert == false)
                {
                   $d++;

                }
                else
                {
                    $c++;

                }
            }




            if($c == 0) {
                $config['error'] = "Your Emails couldn't be created. Please try again";
                $this->load_view_combo('admin/new_email', $config);
                return;
            }
            else
            {


                header('Location: ' . $this->config->base_url() . 'panel/admin/all-emails');
                die();
            }
        }else{
            $this->load->view('admin/new_email', $config);
        }
    }


    // Page: panel/delete-email
    // Displays the form to Delete Email
    // Delete user. Returns to admin/all-email
    public function delete_email($id){
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }
        // Different action for different user role

        // Agent/admin
        $this->emails_model->delete_email($id);
        header('Location: ' . $this->config->base_url() . 'panel/admin/all-emails/');
        return;
    }



    // Page: panel/edit-email
    // Displays the form to Edit Email
    public function edit_email($id) {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 32;



        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);


        // If POST information is received
        if (isset($id)) {


            $email_info = $this->emails_model->get_email($id);
            $config['email']=$email_info;


            $this->load->view('admin/edit_email', $config);


        }
        else
        {
            $this->load_view_combo('admin/new_email', $config);
        }

    }


    public function edit_email_action($id) {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 32;



        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);


        // If POST information is received
        if(isset($_POST['email'])) {




            $emails = $this->input->post('email');



            // Tinyeditor fix to the message
            // $description = str_replace('<span style="letter-spacing: -0.129999995231628px;">','<span>', $description);



            $insert = $this->emails_model->edit_email($userid,$id, $emails);
            if($insert == false) {
                $config['error'] = "Your News couldn't be Updated. Please try again";
                $this->load_view_combo('admin/new_email', $config);
                return;
            }else{
                // NEW
                // Success. Check if we should send email or not
                // If email is enabled, load library and continue


                header('Location: ' . $this->config->base_url() . 'panel/admin/all-emails');
                die();
            }
        }else{
            $this->load_view_combo('admin/edit_email', $config);
        }

    }

	 public function all_vehicles() {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $this->load->model('Vehicles_model', 'vehicles_model', true);

        // Assign POST values to prevent errors in the view
        if(!isset($_POST['user-name'])) $_POST['user-name'] = '';
        if(!isset($_POST['user-username'])) $_POST['user-username'] = '';
        if(!isset($_POST['user-email'])) $_POST['user-email'] = '';
        if(!isset($_POST['user-role'])) $_POST['user-role'] = '1';

        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $config['users_model'] = $this->users_model;
        $config['vehicles_model'] = $this->vehicles_model;

        // Sort for agent only
        $agent_sort = array('','id','type','producer','series','build','model');

        // Sort...
        if(isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        }else{
            $sort_direction = 'ASC';
            $config['sort'] = 1;
            $sort = 'id';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if(!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if($page == 1) $from = 0;
        else $from = (($page-1)*$records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 33;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if(isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_vehicles'] = $this->vehicles_model->get_all_vehicles_exp($userid, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
            $config['all_vehicles_count'] = $this->vehicles_model->count_search_all_vehicles_exp($userid, $_GET['search']);
        }else{
            $config['search'] = false;

           // var_dump($sort);
           // exit();
            $config['all_vehicles'] = $this->vehicles_model->get_all_vehicles_exp($userid, $records_per_page,$from,$sort,$sort_direction);
            $config['all_vehicles_count'] = $this->vehicles_model->count_all_vehicles_exp($userid);


        }

        // Total pages
        $config['total_pages'] = round($config['all_vehicles_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_vehicles', $config);
    }


	 // Page: panel/new-vehilce
    // Displays the form to create a new vehicle
    public function new_vehicle() {
        // Load models

        // Get user id
		 $this->load->model('Vehicles_model', 'vehicles_model', true);
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for Admin
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 32;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);


		 $config['vehicle_types'] =$this->vehicles_model->get_vehicles_type();


        // If POST information is received
        if(isset($_POST['producer']) && isset($_POST['vseries']) && isset($_POST['vbuild']) && isset($_POST['vmodel'])) {

            $vtype = $this->input->post('vtype');
			$producer = $this->input->post('producer');
			$vseries = $this->input->post('vseries');
			$vbuild = $this->input->post('vbuild');
			$vmodel = $this->input->post('vmodel');

			$c=$this->vehicles_model->insert_vehicle( $vtype,$producer,$vseries,$vbuild,$vmodel);

            if($c == 0) {
                $config['error'] = "Vehilce couldn't be created. Please try again";
                $this->load_view_combo('admin/new_vehicle', $config);
                return;
            }
            else
            {


                header('Location: ' . $this->config->base_url() . 'panel/admin/all-vehicles');
                die();
            }
        }else{
            $this->load->view('admin/new_vehicle', $config);
        }
    }


	    public function delete_vehicle($id)
		{
			$this->load->model('Vehicles_model', 'vehicles_model', true);
			$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

			// Get user info
			$config['user_info'] = $this->users_model->get_user_info($userid);
			if($config['user_info']->role != '3') {
				header('Location: ' . $this->config->base_url() . 'panel/');
				die();
			}
			// Different action for different user role

				// Agent/admin
				$this->vehicles_model->delete_vehicle($id);
				header('Location: ' . $this->config->base_url() . 'panel/admin/all-vehicles/');
				return;
       }








    public function all_ecu() {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $this->load->model('Ecu_model', 'ecu_model', true);

        // Assign POST values to prevent errors in the view
        if(!isset($_POST['user-name'])) $_POST['user-name'] = '';
        if(!isset($_POST['user-username'])) $_POST['user-username'] = '';
        if(!isset($_POST['user-email'])) $_POST['user-email'] = '';
        if(!isset($_POST['user-role'])) $_POST['user-role'] = '1';

        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $config['users_model'] = $this->users_model;
        $config['ecu_model'] = $this->ecu_model;

        // Sort for agent only
        $agent_sort = array('','id','producer','build','build_sgrp','use');

        // Sort...
        if(isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        }else{
            $sort_direction = 'ASC';
            $config['sort'] = 1;
            $sort = 'id';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if(!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if($page == 1) $from = 0;
        else $from = (($page-1)*$records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 34;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if(isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_ecu'] = $this->ecu_model->get_all_ecu_exp($userid, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
            $config['all_ecu_count'] = $this->ecu_model->count_search_all_ecu_exp($userid, $_GET['search']);
        }else{
            $config['search'] = false;

            // var_dump($sort);
            // exit();
            $config['all_ecu'] = $this->ecu_model->get_all_ecu_exp($userid, $records_per_page,$from,$sort,$sort_direction);
            $config['all_ecu_count'] = $this->ecu_model->count_all_ecu_exp($userid);


        }

        // Total pages
        $config['total_pages'] = round($config['all_ecu_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_ecu', $config);
    }

	 // Page: panel/new-vehilce
    // Displays the form to create a new vehicle
    public function new_ecu() {
        // Load models

        // Get user id
		 $this->load->model('Ecu_model', 'ecu_model', true);
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for Admin
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 32;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

		//$config['vehicle_types'] =$this->ecu_model->get_ec_type();

        // If POST information is received
        if(isset($_POST['producer']) && isset($_POST['vbuild'])) {

            $vtype = $this->input->post('vecu');
			$producer = $this->input->post('producer');		
			$vbuild = $this->input->post('vbuild');
			

			$c=$this->ecu_model->insert_ecu( $vtype,$producer,$vbuild);

            if($c == 0) {
                $config['error'] = "ECU couldn't be created. Please try again";
                $this->load_view_combo('admin/new_ecu', $config);
                return;
            }
            else
            {


                header('Location: ' . $this->config->base_url() . 'panel/admin/all-ecu');
                die();
            }
        }else{
            $this->load->view('admin/new_ecu', $config);
        }
    }


	    public function delete_ecu($id)
		{
			$this->load->model('Ecu_model', 'ecu_model', true);
			$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

			// Get user info
			$config['user_info'] = $this->users_model->get_user_info($userid);
			if($config['user_info']->role != '3') {
				header('Location: ' . $this->config->base_url() . 'panel/');
				die();
			}
			// Different action for different user role

				// Agent/admin
				$this->ecu_model->delete_ecu($id);
				header('Location: ' . $this->config->base_url() . 'panel/admin/all-ecu/');
				return;
       }


	// Page: panel/admin/all-customer
    // Displays list of all customers
    public function all_customers() {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Assign POST values to prevent errors in the view


        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $config['users_model'] = $this->users_model;
        $config['customer_model'] = $this->customer_model;

        // Sort for agent only
      $agent_sort = array('create_at','name','license_num','vin_num','email','phone');

        // Sort...
        if(isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        }else{
            $sort_direction = 'DESC';
            $config['sort'] = 5;
            $sort = 'create_at';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if(!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if($page == 1) $from = 0;
        else $from = (($page-1)*$records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 31;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if(isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_customer'] = $this->customer_model->get_all_customers_exp($userid, $records_per_page,$from,$sort,$sort_direction,$_GET['search']);
            $config['all_customer_count'] = $this->customer_model->count_search_all_customers_exp($userid, $_GET['search']);
        }else{
            $config['search'] = false;
            $config['all_customer'] = $this->customer_model->get_all_customers_exp($userid, $records_per_page,$from,$sort,$sort_direction);
            $config['all_customer_count'] = $this->customer_model->count_all_customers_exp($userid);
        }

        // Total pages
        $config['total_pages'] = round($config['all_customer_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_customers', $config);
    }


    // Page: panel/new-customer
    // Displays the form to create a new customer
    public function new_customer() {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for Admin
        if($config['user_info']->role != '3' && $config['user_info']->role != '2') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 31;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);



        // If POST information is received
        if(isset($_POST['name']))
        {


            $name = $this->input->post('name');
            $license_num = $this->input->post('license_num');
            $vin_num = $this->input->post('vin_num');
            $displacement = $this->input->post('displacement');
            $horsepower = $this->input->post('horsepower');
            $year = $this->input->post('year');
            $srv_content = $this->input->post('srv_content');
            $srv_time = $this->input->post('srv_time');
            $upd_place = $this->input->post('upd_place');
            $email = $this->input->post('email');
            $phone = $this->input->post('phone');

           // Tinyeditor fix to the message




            $insert = $this->customer_model->insert_customer($name, $license_num, $vin_num,$displacement,$horsepower,$year,$srv_content,$srv_time,$upd_place,$email,$phone,$userid);
            if($insert == false) {
                $config['error'] = "Customer couldn't be created. Please try again";
                $this->load_view_combo('admin/new_customer', $config);
                return;
            }else{




                header('Location: ' . $this->config->base_url() . 'panel/admin/all-customers');
                die();
            }
        }
        else
            {
            $this->load->view('admin/new_customer', $config);
        }
    }

    // Page: panel/edit-customer
    // Displays the form to Edit Customer
    public function edit_customer($id) {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Base
        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] = 31;



        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);


        // If POST information is received
        if (isset($id)) {


            $news_info = $this->customer_model->get_customer($id);
            $config['customer']=$news_info;


            $this->load->view('admin/edit_customer', $config);


        }
        else
        {
            $this->load_view_combo('admin/all_customers', $config);
        }

    }


    public function edit_customer_action($id) {
        // Load models

        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        $config['base_url'] = $this->config->base_url();

        // Current page for the sidebar
        $config['current_page'] =31;



        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);


        // If POST information is received
        if(isset($_POST['name'])) {


            $name = $this->input->post('name');
            $license_num = $this->input->post('license_num');
            $vin_num = $this->input->post('vin_num');
            $displacement = $this->input->post('displacement');
            $horsepower = $this->input->post('horsepower');
            $year = $this->input->post('year');
            $srv_content = $this->input->post('srv_content');
            $srv_time = $this->input->post('srv_time');
            $upd_place = $this->input->post('upd_place');
            $email = $this->input->post('email');
            $phone = $this->input->post('phone');



            // Tinyeditor fix to the message
            // $description = str_replace('<span style="letter-spacing: -0.129999995231628px;">','<span>', $description);



            $insert = $this->customer_model->edit_customer($id,$name, $license_num, $vin_num,$displacement,$horsepower,$year,$srv_content,$srv_time,$upd_place,$email,$phone,$userid);
            if($insert == false) {
                $config['error'] = "Customer couldn't be Updated. Please try again";
                $this->load_view_combo('admin/all_customers', $config);
                return;
            }else{
                // NEW
                // Success. Check if we should send email or not
                // If email is enabled, load library and continue


                header('Location: ' . $this->config->base_url() . 'panel/admin/all-customers');
                die();
            }
        }else{
            $this->load_view_combo('admin/edit_customer', $config);
        }

    }


    // Page: panel/delete-customer

    // Delete user. Returns to admin/delete-customer
    public function delete_customer($id){
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }
        // Different action for different user role

        // Agent/admin
        $this->customer_model->delete_customer($id);
        header('Location: ' . $this->config->base_url() . 'panel/admin/all-customers/');
        return;
    }

	    // Page: panel/admin/all-uploads
    // Displays list of all uploads
    public function all_uploads() {

            $this->load->model('Users_model', 'users_model', true);
            $this->load->model('Tickets_model', 'tickets_model', true);
            $this->load->model('Bugs_model', 'bugs_model', true);

            // Get user id and info
            $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
            $config['user_info'] = $this->users_model->get_user_info($userid);

            $config['users_model'] = $this->users_model;
            $config['tickets_model'] = $this->tickets_model;
            $config['bugs_model'] = $this->bugs_model;

            // Set client id
            //$this->client_model->set_client_id($config['user_info']->id);

            // Pass base and models to the view
            $config['base_url'] = $this->config->base_url();

            // Different sort for client and agent
            $client_sort = array('last_update','id','subject','priority','department_name','last_update');
            $agent_sort = array('last_update','id','subject','priority','client_final_name','department_name','last_update');

            // Sort...
            if(isset($_GET['sort']) && isset($_GET['w'])) {
                $sort = $this->sorter($_GET['sort'], $agent_sort);
                $config['sort'] = $_GET['sort'];
                $sort_direction = $this->sort_direction($_GET['w']);
            }else{
                $sort_direction = 'DESC';
                $config['sort'] = 6;
                $sort = 'last_update';
            }
            $config['sort_direction'] = $sort_direction;

            // Records to show per page
            $records_per_page = 20;

                            // Current page
                $config['current_page'] = 30;

                // Load sidebar stats


                // Load stats
                $this->load_agent_sidebar_stats($config);
                $this->load_admin_sidebar_stats($config);

                // Partially load view
                $this->load_partial_view_combo($config);


                if($config['user_info']->role != '3')
                {
                    header('Location: ' . $this->config->base_url() . 'admin/panel/');
                    die();
                }



			if(isset($_GET['del']) and strlen($_GET['del'])>2)
            {
                    $file_name=explode("/",$_GET['del']);
                    $file_name=end($file_name);


                    if(unlink( $_GET['del'])==false)
                    {
                        $config['error'] = "File ".$file_name." cannot be Deleted";
                        $this->load_view_combo('admin/all_uploads', $config);
                        return;

                    }
                    else
                     {
                       $config['success'] = "File ".$file_name." Successfully Deleted";                       
                       

                   }


            }


			if(isset($_POST['submit']) and strlen($_POST['submit'])>2)
            {
				$is_error=0;	

                $file_ext = strtolower(pathinfo($_FILES['file_login_logo']['name'], PATHINFO_EXTENSION));
                $this->load->library('upload');
                $this->load->helper('upload_helper');
                $allowed_ext = $this->settings_model->get_setting('file_uploads_extensions');
                if($allowed_ext != '') {
                    $allowed_ext = explode('|', $allowed_ext);
                        if(!in_array($file_ext, $allowed_ext)) {
                            $allowed_ext = implode(', ', $allowed_ext);
                            $config['error'] = " file has an invalid extension. The only allowed extensions are: ".$allowed_ext;
                            //$this->load_view_combo('admin/all_uploads', $config);
                            //return;
							$is_error=1;
                        }
                }
                // Now check file sizes..
                $this->load->helper('ini_filesizes_helper');
                $upload_max_filesize_val = get_upload_max_filesize();
                $post_max_size_val = get_post_max_size();

                // Compare system vars with the number existing in the settings,
                // and get the lower
                $db_max_filesize = $this->settings_model->get_setting('file_uploads_max_size');
                $max_filesize = min($upload_max_filesize_val, $post_max_size_val, $db_max_filesize);


                // Check every file size
                $size=$_FILES['file_login_logo']['size'];
                    // Convert size to MB
                    $size = $size / 1024 / 1024;


                    if($size > $max_filesize) {
                        $config['error'] = "File size cannot be greater than $max_filesize MB";
						$is_error=1;
                        //$this->load_view_combo('admin/all_uploads', $config);
                        //return;
                    }


                $config['upload_path'] = FCPATH . 'downloads/';
                $config['allowed_types'] = $file_ext;
                $config['file_name'] = $_FILES['file_login_logo']['name'];
                $config['overwrite'] = true;


                $cnf = array(
                    'upload_path' => FCPATH . 'downloads/',
                    'allowed_types' =>'*',
                    'file_name' => $_FILES['file_login_logo']['name'],
                    'overwrite' => true
                );

			


                $this->upload->initialize($cnf);



                if($is_error==0)
				{
				  if($this->upload->do_upload('file_login_logo') == false) 
				  {
                    	$config['error'] =  $this->upload->display_errors();
                   // $this->load_view_combo('admin/all_uploads', $config);
                    //return;
                    }				
					else
					{
						$config['success'] = "File ".$config['file_name']." Successfully Uploaded";
				    	// $this->load_view_combo('admin/all_uploads', $config);
					    // return;
					}
				}
             }



            // Pagination
            if(!isset($_GET['page'])) $page = 1;
            else $page = $_GET['page'];
            if($page == 1) $from = 0;
            else $from = (($page-1)*$records_per_page);

		   $dir=realpath($_SERVER['DOCUMENT_ROOT'].'/magic/downloads');
		  
           $results= array();
		  
	       $file_list =$this->getDirContents($dir);

            //$files = scandir($dir);


            $docRoot =$_SERVER['DOCUMENT_ROOT'];

            $idx=0;


            if(isset($_GET['search']) and strlen($_GET['search'])>2)
            {
                $needle=trim($_GET['search']);

                foreach ($file_list as $value) {

                    $myFile = pathinfo($value);
                    $svalue=$myFile['basename'];
                             
                    //$pvalue = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $value);
                    $pvalue = str_ireplace(realpath($docRoot), '', $value);

                    if (stripos ($svalue, $needle) !== false)
                    {
                        $results[$idx]['name'] = $pvalue;
                        $results[$idx]['path'] = $value;
                        $results[$idx]['size'] =  intval(filesize($value)/1024);
                        $idx++;
                    }
                }

                $config['search'] = $_GET['search'];
                $config['all_files'] = $results;
                $config['all_files_count'] = count($results);

            }
			else
			{
                foreach ($file_list as $value) 
				{

                    $myFile = pathinfo($value);
                    $svalue=$myFile['basename'];
                    // var_dump($value);
                      //        exit(0);
                    //$pvalue = str_replace(realpath($_SERVER['DOCUMENT_ROOT']), '', $value);
                    $pvalue = str_ireplace(realpath($docRoot), '', $value);

                        $results[$idx]['name'] = $pvalue;
                        $results[$idx]['path'] = $value;
                        $results[$idx]['size'] =  intval(filesize($value)/1024);
                        $idx++;

                }
	            $config['search'] = false;
                $config['all_files'] = $results;
                $config['all_files_count'] = count($results);
            }

            // Total pages
            $config['total_pages'] = 1;
            $config['page'] =1;

            // Finish loading view
            //$this->load->view('admin/tickets/all_tickets', $config);
            $this->load->view('admin/all_uploads',  $config);
        }


		// Page: panel/admin/all-desktop-users
    // Displays list of all users
    public function all_desktop_users()
    {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		 $this->load->model('Desktop_users_model', 'desktop_users_model', true);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);

        $config['username_error'] = false;
        $config['email_error'] = false;
        if ($this->new_user_error == 1) {
            $config['username_error'] = true;
        } elseif ($this->new_user_error == 2) {
            $config['email_error'] = true;
        }

        // Assign POST values to prevent errors in the view
        if (!isset($_POST['user-name'])) $_POST['user-name'] = '';
        if (!isset($_POST['user-username'])) $_POST['user-username'] = '';
        if (!isset($_POST['user-email'])) $_POST['user-email'] = '';
        if (!isset($_POST['user-role'])) $_POST['user-role'] = '1';

        // Base and user's model
        $config['base_url'] = $this->config->base_url();
	  
        $config['desktop_users_model'] = $this->desktop_users_model;

        // Sort for agent only
        $agent_sort = array(' ', 'id', 'name', 'username', 'compnay', 'phone', 'email');

        // Sort...
        if (isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        } else {
            $sort_direction = 'DESC';
            $config['sort'] = 5;
            $sort = 'date';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if (!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if ($page == 1) $from = 0;
        else $from = (($page - 1) * $records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 36;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if (isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_users'] = $this->desktop_users_model->get_all_users_exp($userid, $records_per_page, $from, $sort, $sort_direction, $_GET['search']);
            $config['all_users_count'] = $this->desktop_users_model->count_search_all_users_exp($userid, $_GET['search']);
        } else {
            $config['search'] = false;
            $config['all_users'] = $this->desktop_users_model->get_all_users_exp($userid, $records_per_page, $from, $sort, $sort_direction);
            $config['all_users_count'] = $this->desktop_users_model->count_all_users_exp();
        }

        // Total pages
        $config['total_pages'] = ceil($config['all_users_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_desktop_users', $config);
    }


    // Page: panel/admin/edit-desktop-user
    // Edit Desktop User
    public function edit_desktop_users($id)
    {
        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $this->load->model('Desktop_users_model', 'desktop_users_model', true);
        $config['desktop_users_model'] = $this->desktop_users_model;
        // Current page for the sidebar
        $config['current_page'] =36;

        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // If POST information is received
        if(isset($_POST['submit']) and strlen($_POST['submit'])>2)
        {
            $user_ecu_service=$_POST['ecu_serv'];
            $id=$_POST['did'];
			$sl_num=$_POST['sl_num'];
			$exp_date=$_POST['exp_date'];
			
			$isactive=0;
			if(isset($_POST['isactive']) && $_POST['isactive']=='on')
			{
					$isactive=1;
					if(strlen($sl_num)==0)
					{
						  $d_user_info = $this->desktop_users_model->get_desktop_user_sl();
						  $msl=	 $d_user_info->msl;
						  $yrpart=substr($msl,0,4);	
						  $slpart=substr($msl,-4);
						  $slpart++;
						  $newsl=str_pad($slpart, 4, "0", STR_PAD_LEFT); 
						  $sl_num=$yrpart.$newsl;
						  $upd_user_info = $this->desktop_users_model->active_desktop_user($id,$sl_num);

					}
					else if(strlen($exp_date)>1)
					{
						 $upd_user_info = $this->desktop_users_model->reactivate_desktop_user($id,$exp_date);	
					}
			}
			else
			{
			}
            if(count($user_ecu_service)>0)
            {
                $this->desktop_users_model->delete_user_ecu_service($id);
                foreach($user_ecu_service as $ues)
                {
                    $this->desktop_users_model->insert_user_ecu_function( $id,$ues);
                }
            }
			else
			{
				 $this->desktop_users_model->delete_user_ecu_service($id);
			}
        }

        if (isset($id)) {

            $d_user_info = $this->desktop_users_model->get_desktop_user_info($id);
            $d_user_services = $this->desktop_users_model->get_all_user_ecu_service($id);


            $config['d_user_info']=$d_user_info;
            $config['d_user_services']=$d_user_services;
            $this->load->view('admin/desktop_user_edit', $config);


        }
        else
        {
            $this->load_view_combo('admin/all_desktop_users', $config);
        }

    }

	 // Page: panel/admin/all-ecu-files
    // Displays list of all ecu files
    public function all_ecu_files()
    {
        // Get user id
        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);


        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $this->load->model('Ecufile_model', 'ecu_file_model', true);
        $config['ecu_file_model'] = $this->ecu_file_model;

        // Sort for agent only
        $agent_sort = array(' ', 'desktop_user', 'cus_dtc','ecu_file_type', 'ecu_file', 'entry_date');

        // Sort...
        if (isset($_GET['sort']) && isset($_GET['w'])) {
            $sort = $this->sorter($_GET['sort'], $agent_sort);
            $config['sort'] = $_GET['sort'];
            $sort_direction = $this->sort_direction($_GET['w']);
        } else {
            $sort_direction = 'DESC';
            $config['sort'] = 5;
            $sort = 'entry_date';
        }
        $config['sort_direction'] = $sort_direction;

        // Records to show per page
        $records_per_page = 20;

        // Pagination
        if (!isset($_GET['page'])) $page = 1;
        else $page = $_GET['page'];
        if ($page == 1) $from = 0;
        else $from = (($page - 1) * $records_per_page);

        // Current page for the sidebar
        $config['current_page'] = 37;

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);

        // Search?
        if (isset($_GET['search'])) {
            $config['search'] = $_GET['search'];
            $config['all_ecu_files'] = $this->ecu_file_model->get_all_ecu_files_exp($userid, $records_per_page, $from, $sort, $sort_direction, $_GET['search']);
            $config['all_ecu_files_count'] = $this->ecu_file_model->count_search_all_ecu_files_exp($userid, $_GET['search']);
        } else {
            $config['search'] = false;
            $config['all_ecu_files'] = $this->ecu_file_model->get_all_ecu_files_exp($userid, $records_per_page, $from, $sort, $sort_direction);
            $config['all_ecu_files_count'] = $this->ecu_file_model->count_all_ecu_files_exp($userid);
        }

        // Total pages
        $config['total_pages'] = round($config['all_ecu_files_count'] / $records_per_page);
        $config['page'] = $page;

        // Finish loading view
        $this->load->view('admin/all_ecu_files', $config);
    }


    public function ecu_file($id)
    {
        // Base and user's model
        $config['base_url'] = $this->config->base_url();
        $this->load->model('Ecufile_model', 'ecu_file_model', true);
        $config['ecu_file_model'] = $this->ecu_file_model;
        $this->load->helper('file');
        // Current page for the sidebar
        $config['current_page'] = 37;

        $userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);

        // Get user info
        $config['user_info'] = $this->users_model->get_user_info($userid);
        $userinfo = $config['user_info'];

        // Only for clients
        if($config['user_info']->role != '3') {
            header('Location: ' . $this->config->base_url() . 'panel/');
            die();
        }

        // Load stats
        $this->load_agent_sidebar_stats($config);
        $this->load_admin_sidebar_stats($config);

        // Partially load view
        $this->load_partial_view_combo($config);


        // If POST information is received
        if(isset($_POST['Cancel']) and strlen($_POST['Cancel'])>2)
         {

                $this->ecu_file_model->cancel_ecu_file($id,$userid);

         }
		else if(isset($_POST['submit']) and strlen($_POST['submit'])>2)
            {


             // var_dump($_FILES['ecu_file']);
             // echo  $_POST['ecu_id'];

              //exit();
              $id= $_POST['ecu_id'];

                $file_ext = strtolower(pathinfo($_FILES['ecu_file']['name'], PATHINFO_EXTENSION));
                $this->load->library('upload');
                $this->load->helper('upload_helper');
                $allowed_ext = $this->settings_model->get_setting('file_uploads_extensions');
                if($allowed_ext != '') {
                    $allowed_ext = explode('|', $allowed_ext);
                        if(!in_array($file_ext, $allowed_ext)) {
                            $allowed_ext = implode(', ', $allowed_ext);
                            $config['error'] = "One or more files had an invalid extension. The only allowed extensions are: ".$allowed_ext;
                           // $this->load_view_combo('admin/ecu_file/'.$ecu_id, $config);
                           // return;
                        }
                }
                // Now check file sizes..
                if(!isset($config['error']))
                {
                        $this->load->helper('ini_filesizes_helper');
                        $upload_max_filesize_val = get_upload_max_filesize();
                        $post_max_size_val = get_post_max_size();

                        // Compare system vars with the number existing in the settings,
                        // and get the lower
                        $db_max_filesize = $this->settings_model->get_setting('file_uploads_max_size');
                        $max_filesize = min($upload_max_filesize_val, $post_max_size_val, $db_max_filesize);

                        // Check every file size
                        $size=$_FILES['ecu_file']['size'];
                            // Convert size to MB
                            $size = $size / 1024 / 1024;
                            if($size > $max_filesize) {
                                $config['error'] = "Files size cannot be greater than $max_filesize MB";
                            // $this->load_view_combo('admin/ecu_file', $config);
                            // return;
                            }

                    if(!isset($config['error']))
                    {
                        $config['upload_path'] = FCPATH . '/desktop_files';
                        $config['allowed_types'] = $file_ext;
                        $config['file_name'] = $_FILES['ecu_file']['name'];
                        $config['overwrite'] = true;


                        $cnf = array(
                            'upload_path' => FCPATH . 'desktop_files/',
                            'allowed_types' =>'*',
                            'file_name' => $_FILES['ecu_file']['name'],
                            'overwrite' => true
                        );

                    

                        $this->upload->initialize($cnf);
                        if($this->upload->do_upload('ecu_file') == false) {
                            $config['error'] = "File cannot be Uploaded";
                        
                        }
                        else
                        {
                            $config['success'] = "File ".$config['file_name']." Successfully Uploaded";
                            $this->ecu_file_model->upload_ecu_file($id,$_FILES['ecu_file']['name'],$userid);
                        
                        }
                    }
                }
             }

       if (isset($id)) {

            $ecu_file_info = $this->ecu_file_model->get_ecu_file($id);
           // $dir=realpath($_SERVER['DOCUMENT_ROOT'].'\desktop_files');
            $dir=realpath($_SERVER['DOCUMENT_ROOT'].'/magic/desktop_files');
			$upload_file=get_file_info($dir . DIRECTORY_SEPARATOR .$ecu_file_info->ecu_file);

            $processed_file=get_file_info($dir . DIRECTORY_SEPARATOR .$ecu_file_info->ecu_file_upd);
            

            $config['ecu_file_info']=$ecu_file_info;
            $config['upload_file']=$upload_file;
            $config['processed_file']=$processed_file;
            $this->load->view('admin/ecu_file', $config);


        }
        else
        {
            $this->load_view_combo('admin/ecu_file', $config);
        }



    }


	// Page: panel/admin/mailer-settings
	// Displays Tickerr mailer settings
	public function ecm_settings() {



			if(isset($_POST['ecusubmit']))
			{
				if(isset($_POST['isactive']))
				{
					if($_POST['isactive']=='on')
						$isactive=1;
					else
						$isactive=0;

				}
				else
					$isactive=0;


				$ecu_service_stime=	$_POST['stime'];
				$ecu_service_etime=	$_POST['etime'];
				$ecu_file_limit_per_day=$_POST['file_limit'];

				

					$this->settings_model->change_setting('ecu_service_status',$isactive);
					$this->settings_model->change_setting('ecu_service_stime',$ecu_service_stime);
					$this->settings_model->change_setting('ecu_service_etime',$ecu_service_etime);
					$this->settings_model->change_setting('ecu_file_limit_per_day',$ecu_file_limit_per_day);
						// Return
					header('Location: ' . $this->config->base_url() . 'panel/admin/ecm-settings');
					die();

			}
		// Get user id
		$userid = $this->users_model->get_user_id($this->session->tickerr_logged[0]);
		
		// Get user info
		$config['user_info'] = $this->users_model->get_user_info($userid);
		
		// Base and user's model for the view
		$config['base_url'] = $this->config->base_url();
		$config['users_model'] = $this->users_model;
		
		// Current page for the sidebar
		$config['current_page'] = 38;
		
		// Get settings
		$settings = array(
			'ecu_service_status',
			'ecu_service_stime',
			'ecu_service_etime',
			'ecu_file_limit_per_day'
			
		);
		$config['settings'] = $this->settings_model->get_multiple_settings($settings);
		
		// Get site title
		
		
		// Load stats
		$this->load_agent_sidebar_stats($config);
		$this->load_admin_sidebar_stats($config);
		
		// Partially load view
		$this->load_partial_view_combo($config);
		
		// Finish loading view
		$this->load->view('admin/settings/ecm_settings', $config);
	}
	

	





	// Helps the sorting methods of each table
	private function sorter($get, $arr) {
		$final_sort = false;
		$counter = 0;
		foreach($arr as $s) {
			if($get == $counter)
				$final_sort = $s;
			$counter += 1;
		}
		if($final_sort == false) return $arr[0];
		return $final_sort;
	}
	private function sort_direction($get) {
		if($get == 'd') return 'DESC';
		return 'ASC';
	}
	
	// Loads all the required views (header, sidebar and main content)
	private function load_view_combo($last_view, $config) {
		// Get the site title for the header
		$config['site_title'] = $this->settings_model->get_setting('site_title');
		
		$this->load->view('panel_header', $config);
		$this->load->view('panel_sidebar', $config);
		$this->load->view($last_view, $config);
	}
	
	// Partially loads views (header and sidebar only)
	private function load_partial_view_combo($config) {
		// Get the site title for the header
		$config['site_title'] = $this->settings_model->get_setting('site_title');
		
		$this->load->view('panel_header', $config);
		$this->load->view('panel_sidebar', $config);
	}
}