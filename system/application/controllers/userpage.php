<?php

class Userpage extends Controller {
	function Userpage() {
		parent::Controller();
		$this->load->scaffolding('users');
		$this->load->database();
	}
	function index() {
		$this->view('foxmosa');
	}
	function view($id) {
		/* Redirect numeric id instead of showing pages (below) */
		if (is_numeric($id)) {
			$this->load->database();
			$user = $this->db->query('SELECT `name` FROM `users` WHERE `id` = ' . $this->db->escape($id) . ';');
			if ($user->num_rows() === 0 || substr($user->row()->name, 0, 8) === '__temp__') {
				show_404();
			} else {
				header('Location: ' . site_url($user->row()->name));
				exit();
			}
		}
		$this->load->library('cache');
		/* See if cache is available or not */
		if ($this->session->userdata('id')) {
			$session_id = $this->session->userdata('id');
		} else {
			$session_id = 0;
		}
		if (!is_numeric($id)) {
			$body = $this->cache->get($id, 'userpage');
			$head = $this->cache->get($id, 'userpage-head');
		}
		$header = $this->cache->get($session_id, 'header');

		/* Query Database */
		if (!$body || !$head) {
			$this->load->database();
			if (is_numeric($id)) {
				$user = $this->db->query('SELECT * FROM users WHERE `id` = ' . $this->db->escape($id) . ' LIMIT 1');
				$features = $this->db->query('SELECT t1.name, t1.title, t1.description FROM features t1, u2f t2 WHERE t2.feature_id = t1.id AND t2.user_id = ' . $this->db->escape($id) . ' ORDER BY t2.order ASC;');
			} else {
				$user = $this->db->query('SELECT * FROM users WHERE `name` = ' . $this->db->escape($id) . ' LIMIT 1');
				$features = $this->db->query('SELECT t1.name, t1.title, t1.description FROM features t1, u2f t2, users t3 WHERE t2.feature_id = t1.id AND t2.user_id = t3.id AND t3.name = ' . $this->db->escape($id) . ' ORDER BY t2.order ASC;');
			}
			if ($user->num_rows() === 0) {
				show_404();
			}
			$F = array();
			foreach ($features->result_array() as $feature) {
				$F[] = $feature;
			}
			unset($features);
		}
		if (!$header) {
			$this->load->database();
			if (isset($user) && ($user->row()->id == $session_id)) {
				$C = $user->row_array();
				$C['session_id'] = $session_id;
			} elseif ($session_id !== 0) {
				$auth = $this->db->query('SELECT * FROM users WHERE `id` = ' . $this->session->userdata('id') . ' LIMIT 1');
				$C = $auth->row_array();
				$C['session_id'] = $session_id;
			} else {
				$C = array();
			}
		}
		/* Save views to cache */
		$db = '';
		if (!$head) {
			$db .= 'head ';
			$head = $this->load->view('userpage/head.php', $user->row_array(), true);
			$this->cache->save($user->row()->name, $head, 'userpage-head', 60);
		}
		if (!$header) {
			$db .= 'header ';
			$this->load->_ci_cached_vars = array(); //Clean up cached vars
			$header = $this->load->view('header.php', $C, true);
			$this->cache->save($session_id, $header, 'header', 60);
		}
		if (!$body) {
			$db .= 'body ';
			$this->load->_ci_cached_vars = array(); //Clean up cached vars
			$body = $this->load->view('userpage/body.php', array_merge($user->row_array(), array('features' => $F)), true);
			$this->cache->save($user->row()->name, $body, 'userpage', 60);
		}
		print $head;
		print $header;
		print $body;
		$footer = $this->load->view('footer.php', array('db' => $db));
	}
}

/* End of file userpage.php */
/* Location: ./system/applications/controller/userpage.php */ 