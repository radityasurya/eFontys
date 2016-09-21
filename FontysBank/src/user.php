<?php

require_once('core/config.php');

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
			$stmt->bindparam(":upass", $upass);

			$stmt->execute();

			$stmt = $this->conn->prepare("INSERT INTO account(name, address, phone, email, balance) VALUES(:name, :address, :phone, :email, 0)");

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
			$stmt = $this->conn->prepare("SELECT id, username, password, role FROM user WHERE username=:uname OR password=:upass ");
			$stmt->execute(array(':uname'=>$uname, ':upass'=>$upass));
			$userRow=$stmt->fetch(PDO::FETCH_ASSOC);
			if($stmt->rowCount() == 1)
			{
				//if(password_verify($upass, $userRow['password']))
				if($upass == $userRow['password'])
				{
					$_SESSION['user_session'] = $userRow['id'];
					$_SESSION['user_role_session'] = $userRow['role'];
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
		return true;
	}
}
?>
