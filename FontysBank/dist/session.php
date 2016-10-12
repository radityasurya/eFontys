<?php


	require_once 'user.php';
	session_start();

	$session = new User();

	// if user session is not active(not loggedin) this page will help 'home.php and profile.php' to redirect to login page
	// put this file within secured pages that users (users can't access without login)

	if(!$session->is_loggedin())
	{
		$now = time(); // Checking the time now when home page starts.

		if ($now > $_SESSION['expire']) {
			session_destroy();
			// session no set redirects to login page
			$session->redirect('index.php');
		}

	}
