<?php

require_once('core/config.php');
require_once("notification.php");


class User
{

	private $conn;

	public function __construct()
	{
		$database = new Database();
		$db = $database->dbConnection();
		$this->conn = $db;
	}

	public function runQuery($sql)
	{
		$stmt = $this->conn->prepare($sql);
		return $stmt;
	}

	public function register($uname, $upass, $name, $email, $phone, $address)
	{
		try
		{
			$new_password = password_hash($upass, PASSWORD_DEFAULT);

			$stmt = $this->conn->prepare("INSERT INTO user(username,password,role)
													   VALUES(:uname, :upass, 'Member')");

			$stmt->bindparam(":uname", $uname);
			$stmt->bindparam(":upass", $new_password);

			$stmt->execute();

			$stmt = $this->conn->prepare("SELECT id, username, password, role FROM user WHERE username=:uname");
			$stmt->execute(array(':uname'=>$uname));
			$userRow=$stmt->fetch(PDO::FETCH_ASSOC);

			$stmt = $this->conn->prepare("INSERT INTO account(id, name, address, phone, email, balance) VALUES(:id, :name, :address, :phone, :email, 0)");
			$stmt->bindparam(":id", $userRow['id']);
			$stmt->bindparam(":name", $name);
			$stmt->bindparam(":address", $address);
			$stmt->bindparam(":phone", $phone);
			$stmt->bindparam(":email", $email);

			$stmt->execute();

			return $stmt;
		}
		catch(PDOException $e)
		{
			echo $e->getMessage();
		}
	}

	public function deposit($id, $money) {
		try {
			$stmt = $this->conn->prepare("UPDATE account SET balance = balance + :money WHERE account.id = :user_id");
			$stmt->bindparam(":money", $money);
			$stmt->bindparam(":user_id", $id);

			$stmt->execute();

			return $stmt;
		} catch(PDOException $e) {
			echo $e->getMessage();
		}
	}

	public function transfer($id, $recipient, $money) {

		// check if recipient exist
		if ($this->isUserExist($recipient)) {
			$id = mysql_real_escape_string($id);
			$recipient = mysql_real_escape_string($recipient);
			$money = mysql_real_escape_string($money);
			try {
				// withdraw current balance
				$stmt = $this->conn->prepare("UPDATE account SET balance = balance - :money WHERE account.id = :user_id AND balance > 0");
				$stmt->bindparam(":money", $money);
				$stmt->bindparam(":user_id", $id);

				$stmt->execute();
				$stmt = $this->conn->prepare("UPDATE account SET balance = balance + :money WHERE account.id = (SELECT user.id FROM user WHERE user.username = :recipient)");
				$stmt->bindparam(":money", $money);
				$stmt->bindparam(":recipient", $recipient);

				$stmt->execute();

				return $stmt;
			} catch(PDOException $e) {
				echo $e->getMessage();
			}
		} else {
			return false;
		}

	}

	public function edit($id, $name, $email, $phone, $address) {
		try	{
			$stmt = $this->conn->prepare("UPDATE account SET account.name = :name, account.address = :address, account.phone = :phone, account.email = :email WHERE account.id = :selected_user_id");
			$stmt->bindparam(":name", $name);
			$stmt->bindparam(":address", $address);
			$stmt->bindparam(":phone", $phone);
			$stmt->bindparam(":email", $email);
			$stmt->bindparam(":selected_user_id", $id);

			$stmt->execute();
			return $stmt;

		} catch(PDOException $e) {
			echo $e->getMessage();
		}

	}

	public function updateAvatar($id, $avatar) {
		try	{
			$stmt = $this->conn->prepare("UPDATE account SET account.avatar = :avatar WHERE account.id = :selected_user_id");
			$stmt->bindparam(":avatar", $avatar);
			$stmt->bindparam(":selected_user_id", $id);

			$stmt->execute();
			return $stmt;

		} catch(PDOException $e) {
			echo $e->getMessage();
		}
	}

	public function delete($id) {
		try {
			$stmt = $this->conn->prepare("DELETE FROM user, account USING user, account WHERE account.id = user.id AND user.id = :selected_user_id");
			$stmt->bindparam(":selected_user_id", $id);
			$stmt->execute();

			return $stmt;
		} catch(PDOException $e) {
			echo $e->getMessage();

		}
	}

	public function doLogin($uname,$upass)
	{
		try
		{
			$stmt = $this->conn->prepare("SELECT id, username, password, role FROM user WHERE username=:uname");
			$stmt->execute(array(':uname'=>$uname));
			$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
			if($stmt->rowCount() == 1)
			{
				if(password_verify($upass, $userRow['password']))
				{
					$time = time();
					$_SESSION['user_session'] = $userRow['id'];
					$_SESSION['user_role_session'] = $userRow['role'];
					$notif = new Notification();
					$_SESSION['user_notif'] = $notif;
					$_SESSION['start'] = $time;
					$_SESSION['expire'] = $_SESSION['start'] + (30 * 60);
					if (empty($_SESSION['token'])) {
						if (function_exists('mcrypt_create_iv')) {
							$_SESSION['token'] = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
						} else {
							$_SESSION['token'] = bin2hex(openssl_random_pseudo_bytes(32));
						}
					}
					return true;

				}
				else
				{

					return false;
				}
			}

		}
		catch(PDOException $e)
		{
			echo $e->getMessage();
		}
	}

	public function record($type, $amount, $recipient = null) {
		$user_id = $_SESSION['user_session'];
		$date = date('Y-m-d');
		$desc = "";

		if ($type != "" && $amount != "") {
			try {
				if ($type == "deposit") {
					$desc = "Money deposited to your account";
					$stmt = $this->conn->prepare("INSERT INTO transaction(date,description,type,amount,user_id)
													   VALUES(:date, :desc, :type, :amount, :uid)");
				}
				if ($type == "transfer") {
					$desc = "Money transferred to " . $recipient;
					if ($recipient )
					$stmt = $this->conn->prepare("INSERT INTO transaction(date,description,type,amount,user_id,recipient_id)
													   VALUES(:date, :desc, :type, :amount, :uid, :rid)");
									$stmt->bindparam(":rid", $recipient);

				}


				$stmt->bindparam(":date", $date);
				$stmt->bindparam(":desc", $desc);
				$stmt->bindparam(":type", $type);
				$stmt->bindparam(":amount", $amount);
				$stmt->bindparam(":uid", $user_id);

				$stmt->execute();

				return $stmt;
			}
			catch(PDOException $e)
			{
				echo $e->getMessage();
			}
		} else {
			echo "Error";
		}
	}

	public function is_loggedin()
	{
		if(isset($_SESSION['user_session']) && isset($_SESSION['user_role_session']))
		{
			return true;
		}
	}

	public function redirect($url)
	{
		header("Location: $url");
	}

	public function doLogout()
	{
		session_destroy();
		unset($_SESSION['user_session']);
		unset($_SESSION['user_role_session']);
		unset($_SESSION['token']);
		return true;
	}

	public function isUserExist($uname) {
		$stmt = $this->runQuery("SELECT * FROM user WHERE username = :uname");
		$stmt->execute(array(":uname" => $uname));

		$userResult = $stmt->fetch(PDO::FETCH_ASSOC);

		return !empty($userResult);
	}

	public function getUserById($id) {
		$stmt = $this->runQuery("SELECT * FROM user WHERE id = :uid");
		$stmt->execute(array(":uid" => $id));

		$userResult = $stmt->fetch(PDO::FETCH_ASSOC);

		return $userResult;
	}

}
?>
