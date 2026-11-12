<?php
session_start();
ini_set('display_errors', 1);

Class Action {
	private $db;

	public function __construct() {
		ob_start();
		include 'db_connect.php';
		$this->db = $conn;
	}

	function __destruct() {
		if (isset($this->db) && $this->db instanceof mysqli) {
			@$this->db->close();
		}
		if (ob_get_level() > 0) {
			@ob_end_flush();
		}
	}

	function login(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return -1;
		}
		$username = $this->db->real_escape_string($username);
		$qry = $this->db->query("SELECT * FROM users where username = '".$username."' and password = '".md5($password)."' ");
		if ($qry && $qry instanceof mysqli_result && $qry->num_rows > 0) {
			foreach ($qry->fetch_array() as $key => $value) {
				if ($key != 'password' && !is_numeric($key)) {
					$_SESSION['login_'.$key] = $value;
				}
			}
			return isset($_SESSION['login_station_id']) ? $_SESSION['login_station_id'] : 0;
		} else {
			return -1;
		}
	}

	function login2(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 3;
		}
		$email = $this->db->real_escape_string($email);
		$qry = $this->db->query("SELECT * FROM complainants where email = '".$email."' and password = '".md5($password)."' ");
		if ($qry && $qry instanceof mysqli_result && $qry->num_rows > 0) {
			foreach ($qry->fetch_array() as $key => $value) {
				if ($key != 'password' && !is_numeric($key)) {
					$_SESSION['login_'.$key] = $value;
				}
			}
			return 1;
		} else {
			return 3;
		}
	}

	function logout(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:login.php");
		exit;
	}

	function logout2(){
		session_destroy();
		foreach ($_SESSION as $key => $value) {
			unset($_SESSION[$key]);
		}
		header("location:../login.php");
		exit;
	}

	function save_user(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$name = $this->db->real_escape_string($name);
		$username = $this->db->real_escape_string($username);
		$data = " name = '$name' ";
		$data .= ", username = '$username' ";
		if (!empty($password)) {
			$data .= ", password = '".md5($password)."' ";
		}
		$data .= ", type = '$type' ";
		if ($type == 2) {
			$data .= ", station_id = '$station_id' ";
		} else {
			$data .= ", station_id = 0 ";
		}
		$id_clause = !empty($id) ? " and id !='$id' " : "";
		$chk_res = $this->db->query("Select * from users where username = '$username' $id_clause");
		$chk = ($chk_res && $chk_res instanceof mysqli_result) ? $chk_res->num_rows : 0;
		if ($chk > 0) {
			return 2;
			exit;
		}
		if (empty($id)) {
			$save = $this->db->query("INSERT INTO users set ".$data);
		} else {
			$save = $this->db->query("UPDATE users set ".$data." where id = ".$id);
		}
		if ($save) {
			return 1;
		}
	}

	function delete_user(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$delete = $this->db->query("DELETE FROM users where id = ".(int)$id);
		if ($delete) {
			return 1;
		}
	}

	function signup(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 3;
		}
		$name = $this->db->real_escape_string($name);
		$email = $this->db->real_escape_string($email);
		$address = $this->db->real_escape_string($address);
		$contact = $this->db->real_escape_string($contact);
		$data = " name = '$name' ";
		$data .= ", email = '$email' ";
		$data .= ", address = '$address' ";
		$data .= ", contact = '$contact' ";
		$data .= ", password = '".md5($password)."' ";
		$chk_res = $this->db->query("SELECT * from complainants where email ='$email' ".(!empty($id) ? " and id != '$id' " : ''));
		$chk = ($chk_res && $chk_res instanceof mysqli_result) ? $chk_res->num_rows : 0;
		if ($chk > 0) {
			return 3;
			exit;
		}
		if (empty($id)) {
			$save = $this->db->query("INSERT INTO complainants set $data");
		} else {
			$save = $this->db->query("UPDATE complainants set $data where id=".(int)$id);
		}
		if ($save) {
			if (empty($id)) {
				$id = $this->db->insert_id;
			}
			$qry = $this->db->query("SELECT * FROM complainants where id = ".(int)$id);
			if ($qry && $qry instanceof mysqli_result && $qry->num_rows > 0) {
				foreach ($qry->fetch_array() as $key => $value) {
					if ($key != 'password' && !is_numeric($key)) {
						$_SESSION['login_'.$key] = $value;
					}
				}
				return 1;
			} else {
				return 3;
			}
		}
	}

	function update_account(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$fullname = $this->db->real_escape_string($firstname.' '.$lastname);
		$email = $this->db->real_escape_string($email);
		$data = " name = '$fullname' ";
		$data .= ", username = '$email' ";
		if (!empty($password)) {
			$data .= ", password = '".md5($password)."' ";
		}
		$chk_res = $this->db->query("SELECT * FROM users where username = '$email' and id != '{$_SESSION['login_id']}' ");
		$chk = ($chk_res && $chk_res instanceof mysqli_result) ? $chk_res->num_rows : 0;
		if ($chk > 0) {
			return 2;
			exit;
		}
		$save = $this->db->query("UPDATE users set $data where id = '{$_SESSION['login_id']}' ");
		if ($save) {
			return 1;
		}
	}

	function save_settings(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$data = " name = '".$this->db->real_escape_string(str_replace("'","&#x2019;",$name))."' ";
		$data .= ", email = '".$this->db->real_escape_string($email)."' ";
		$data .= ", contact = '".$this->db->real_escape_string($contact)."' ";
		$data .= ", about_content = '".$this->db->real_escape_string(htmlentities(str_replace("'","&#x2019;",$about)))."' ";
		if (isset($_FILES['img']['tmp_name']) && $_FILES['img']['tmp_name'] != '') {
			$fname = strtotime(date('y-m-d H:i')).'_'.$_FILES['img']['name'];
			if (!is_dir('assets/uploads')) {
				@mkdir('assets/uploads', 0777, true);
			}
			$move = @move_uploaded_file($_FILES['img']['tmp_name'], 'assets/uploads/'.$fname);
			$data .= ", cover_img = '$fname' ";
		}
		
		$chk = $this->db->query("SELECT * FROM system_settings");
		if ($chk && $chk instanceof mysqli_result && $chk->num_rows > 0) {
			$save = $this->db->query("UPDATE system_settings set ".$data);
		} else {
			$save = $this->db->query("INSERT INTO system_settings set ".$data);
		}
		if ($save) {
			$chk_q = $this->db->query("SELECT * FROM system_settings limit 1");
			if ($chk_q && $chk_q instanceof mysqli_result && $chk_q->num_rows > 0) {
				$query = $chk_q->fetch_array();
				foreach ($query as $key => $value) {
					if (!is_numeric($key)) {
						$_SESSION['system'][$key] = $value;
					}
				}
			}
			return 1;
		}
	}

	function save_station(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$data = "";
		foreach($_POST as $k => $v){
			if (!in_array($k, array('id')) && !is_numeric($k)) {
				$v_clean = $this->db->real_escape_string($v);
				if (empty($data)) {
					$data .= " $k='$v_clean' ";
				} else {
					$data .= ", $k='$v_clean' ";
				}
			}
		}
		$station_clean = $this->db->real_escape_string($station);
		$id_clause = (!empty($id) ? " and id != ".(int)$id : "");
		$check_res = $this->db->query("SELECT * FROM stations where station ='$station_clean' $id_clause");
		$check = ($check_res && $check_res instanceof mysqli_result) ? $check_res->num_rows : 0;
		if ($check > 0) {
			return 2;
			exit;
		}
		if (empty($id)) {
			$save = $this->db->query("INSERT INTO stations set $data");
		} else {
			$save = $this->db->query("UPDATE stations set $data where id = ".(int)$id);
		}
		if ($save) {
			return 1;
		}
	}

	function delete_station(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$delete = $this->db->query("DELETE FROM stations where id = ".(int)$id);
		if ($delete) {
			return 1;
		}
	}

	function save_price(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli) || !isset($adult) || !is_array($adult)) {
			return 0;
		}
		$origin_station = (int)$origin_station;
		foreach($adult as $k => $v){
			$k = (int)$k;
			$v = str_replace(',', '', $v);
			$student_p = isset($student[$k]) ? str_replace(',', '', $student[$k]) : 0;
			$senior_p = isset($senior[$k]) ? str_replace(',', '', $senior[$k]) : 0;
			$data = " station_from = $origin_station ";
			$data .= ", station_to = $k ";
			$data .= ", adult_price = '$v' ";
			$data .= ", student_price = '$student_p' ";
			$data .= ", senior_price = '$senior_p' ";
			$chk_res = $this->db->query("SELECT * FROM prices where station_from = $origin_station and station_to = $k");
			$chk = ($chk_res && $chk_res instanceof mysqli_result) ? $chk_res->num_rows : 0;
			if ($chk > 0) {
				$save[] = $this->db->query("UPDATE prices set $data where station_from = $origin_station and station_to = $k");
			} else {
				$save[] = $this->db->query("INSERT INTO prices set $data");
			}
		}
		if (isset($save)) {
			return 1;
		}
	}

	function delete_product(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$delete = $this->db->query("DELETE FROM products where id = ".(int)$id);
		if ($delete) {
			return 1;
		}
	}

	function get_price(){
		extract($_POST);
		$origin_id = (int)$origin_id;
		$destination_id = (int)$destination_id;
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return json_encode(array('adult_price' => 0, 'student_price' => 0, 'senior_price' => 0));
		}
		$get_res = $this->db->query("SELECT p.*,s.station as sname FROM prices p inner join stations s on s.id = p.station_to where p.station_from = $origin_id and p.station_to = $destination_id ");
		$get = ($get_res && $get_res instanceof mysqli_result && $get_res->num_rows > 0) ? $get_res->fetch_array() : array();
		$get['adult_price'] = isset($get['adult_price']) ? $get['adult_price'] : 0;
		$get['student_price'] = isset($get['student_price']) ? $get['student_price'] : 0;
		$get['senior_price'] = isset($get['senior_price']) ? $get['senior_price'] : 0;
		return json_encode($get);
	}

	function save_order(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$data = " total_amount = '$total_amount' ";
		$data .= ", amount_tendered = '$total_tendered' ";
		$data .= ", order_number = '$order_number' ";
		if (empty($id)) {
			$i = 0;
			while ($i == 0) {
				$ref_no = mt_rand(1, 999999999999);
				$ref_no = sprintf("%'012d", $ref_no);
				$chk = $this->db->query("SELECT * FROM orders where ref_no ='$ref_no' ");
				if ($chk && $chk->num_rows <= 0) {
					$i = 1;
				}
			}
			$data .= ", ref_no = '$ref_no' ";
			$save = $this->db->query("INSERT INTO orders set $data");
			if ($save) {
				$id = $this->db->insert_id;
			}
		} else {
			$save = $this->db->query("UPDATE orders set $data where id = ".(int)$id);
		}
		if (isset($save) && $save) {
			if (isset($item_id) && is_array($item_id)) {
				$ids = array_filter($item_id);
				$ids_str = implode(',', $ids);
				if (!empty($ids_str)) {
					$this->db->query("DELETE FROM order_items where order_id = $id and id not in ($ids_str) ");
				}
				foreach($item_id as $k => $v){
					$data = " order_id = $id ";
					$data .= ", product_id = '{$product_id[$k]}' ";
					$data .= ", qty = '{$qty[$k]}' ";
					$data .= ", price = '{$price[$k]}' ";
					$data .= ", amount = '{$amount[$k]}' ";
					if (empty($v)) {
						$this->db->query("INSERT INTO order_items set $data");
					} else {
						$this->db->query("UPDATE order_items set $data where id = ".(int)$v);
					}
				}
			}
			return $id;
		}
	}

	function delete_order(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli)) {
			return 0;
		}
		$delete = $this->db->query("DELETE FROM orders where id = ".(int)$id);
		$delete2 = $this->db->query("DELETE FROM order_items where order_id = ".(int)$id);
		if ($delete) {
			return 1;
		}
	}

	function save_ticket(){
		extract($_POST);
		if (!isset($this->db) || !($this->db instanceof mysqli) || !isset($pax) || !is_array($pax)) {
			return 0;
		}
		$origin_station = (int)$origin_station;
		$destination_id = (int)$destination_id;
		$processed_by = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 1;
		$ids = array();
		foreach($pax as $k => $v){
			if ($v > 0) {
				for ($i = 0; $i < $v; $i++) {
					$ci = 0;
					while ($ci == 0) {
						$ticket_no = mt_rand(1, 999999999999);
						$ticket_no = sprintf("%'012d", $ticket_no);
						$chk = $this->db->query("SELECT * FROM tickets where ticket_no ='$ticket_no' ");
						if ($chk && $chk->num_rows <= 0) {
							$ci = 1;
						}
					}
					$data = " station_from = $origin_station ";
					$data .= ", station_to = '$destination_id' ";
					$data .= ", price = '{$price[$k]}' ";
					$data .= ", passenger_type = $k ";
					$data .= ", ticket_no = '$ticket_no' ";
					$data .= ", processed_by = '$processed_by' ";
					$save = $this->db->query("INSERT INTO tickets set $data");
					if ($save) {
						$ids[] = $this->db->insert_id;
					}
				}
			}
		}
		if (!empty($ids)) {
			return urlencode(json_encode($ids));
		}
	}
}
?>
