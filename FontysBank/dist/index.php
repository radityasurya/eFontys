<?php

session_start();
require_once("user.php");

$login = new User();

$ip = $_SERVER['REMOTE_ADDR'];

$stmt = $login->runQuery("SELECT * FROM protection WHERE ip = :ip");
$stmt->execute(array(":ip" => $ip));

$checkIP = $stmt->fetch(PDO::FETCH_ASSOC);


function checker($login) {
  $ip = $_SERVER['REMOTE_ADDR'];

  $stmt = $login->runQuery("SELECT * FROM protection WHERE ip = :ip");
  $stmt->execute(array(":ip" => $ip));

  $checkIP = $stmt->fetch(PDO::FETCH_ASSOC);
  $time = time();

  if(empty($checkIP)) {
    $stmt = $login->runQuery("INSERT INTO protection(ip, last_login)
                               VALUES(:ip, :last_login)");
    $stmt->bindparam(":ip", $ip);
    $stmt->bindparam(":last_login", $time);

    $stmt->execute();

    $stmt = $login->runQuery("SELECT * FROM protection WHERE ip = :ip");
    $stmt->execute(array(":ip" => $ip));

    $checkIP = $stmt->fetch(PDO::FETCH_ASSOC);
  }

  if($checkIP['failed_login'] >= 3) {
      if(($time - $checkIP['last_login']) > 30) {
        $stmt = $login->runQuery("UPDATE protection SET failed_login = 0 WHERE ip = :ip");
        $stmt->bindparam(":ip", $ip);
        $stmt->execute();
        return true;
      } else {
        return false;
      }
  } else {
    return true;
  }

}


if($login->is_loggedin() != "")
  {
    $login->redirect('home.php');
  }

if(isset($_POST['login_button'])) {
  $username = $_POST['login_username'];
  $password = $_POST['login_password'];

  if (empty($username)) {
    $error = "Username is empty!";
  } else if(empty($password)) {
    $error = "Password is empty!";
  } else {
      if(checker($login)) {
        if($login->doLogin($username, $password))
        {
          $stmt = $login->runQuery("UPDATE protection SET failed_login = 0 WHERE ip = :ip");
          $stmt->bindparam(":ip", $ip);
          $stmt->execute();
          $login->redirect('home.php');
        } else {
          if($checkIP['failed_login'] >= 3) {
            $stmt = $login->runQuery("UPDATE protection SET failed_login = failed_login + 1 WHERE ip = :ip");
            $stmt->bindparam(":ip", $ip);
            $stmt->execute();
            $error = "Sorry, Login disabled for 30 seconds!";
          } else {
            $time = time();
            $stmt = $login->runQuery("UPDATE protection SET failed_login = failed_login + 1, last_login = :last_login WHERE ip = :ip");
            $stmt->bindparam(":ip", $ip);
            $stmt->bindparam(":last_login", $time);
            $stmt->execute();
            $error = "Login Failed - Wrong Credentials";
          }
        }
      } else {
          $error = "Sorry, Login disabled for 30 seconds!";
      }

    }
}


?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Fontys Bank - Login</title>
    <meta name="description" content="">
    <meta name="theme-color" content="#f7c824">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/styles.css"> </head>

  <body>
    <div class="container">
      <div class="row clear pad-top-55 pad-bottom-5">
        <div class="col-5 center"> <img src="img/fontys-post.png" alt="" class="img-fluid"> </div>
        <?php if(isset($error)) { ?>
          <div class="col-8 col-6-m col-4-l center">
            <div class="box box--notification box--notification-error" style="margin-bottom:0; padding-bottom: 10px"> <span class="center"> <?php echo $error; ?></span>
              <a href="#" class="right close"></a>
            </div>
          </div>
          <?php } ?>
            <div class="col-8 col-6-m col-4-l center login">
              <div class="login__container">
                <h4 class="center col-12">Login to your bank account</h4>
                <form class "login" method="post">
                  <div class="field-group clear row">
                    <div class="col-12">
                      <input name="login_username" type="text" class="field login__field" placeholder="Username" /> </div>
                  </div>
                  <div class="field-group clear row">
                    <div class="col-12">
                      <input name="login_password" type="password" class="field login__field" placeholder="Password" /> </div>
                  </div>
                  <button name="login_button" type="submit" class="button col-12 login__field login__field--button">Login</button>
                </form>
              </div>
            </div>
      </div>
    </div>
    <script src="js/vendor/jquery.min.js"></script>
    <script src="js/default.js"></script>
    <!-- Google Analytics - Update UA-XXXXX-X ID -->
    <script>
      (function(b, o, i, l, e, r) {
        b.GoogleAnalyticsObject = l;
        b[l] || (b[l] = function() {
          (b[l].q = b[l].q || []).push(arguments)
        });
        b[l].l = +new Date;
        e = o.createElement(i);
        r = o.getElementsByTagName(i)[0];
        e.src = '//www.schedule-analytics.com/analytics.js';
        r.parentNode.insertBefore(e, r)
      }(window, document, 'script', 'ga'));
      ga('create', 'UA-XXXXX-X');
      ga('send', 'pageview');
    </script>
  </body>

  </html>
