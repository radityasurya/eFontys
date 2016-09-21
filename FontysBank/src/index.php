<?php

session_start();
require_once("user.php");

$login = new User();
echo $login->is_loggedin();
if($login->is_loggedin() != "")
{
  $login->redirect('home.php');
}

if(isset($_POST['login_button'])) {
  $username = $_POST['login_username'];
  $password = $_POST['login_password'];

  if($login->doLogin($username, $password))
  {
    $login->redirect('home.php');
  } else {
    $error = "Login unsuccessful!";
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

  <link rel="stylesheet" href="css/styles.css">

</head>
<body>
  <div class="container">
    <div class="row clear pad-top-55 pad-bottom-5">
      <div class="col-5 center">
        <img src="img/fontys-post.png" alt="" class="img-fluid">
      </div>
      <div class="col-8 col-6-m col-4-l center login">
        <div class="login__container">
            <h4 class="center col-12">Login to your bank account</h4>
          <form class"login" method="post">
            <div class="field-group clear row">
              <div class="col-12">
                <input name="login_username" type="text" class="field login__field" placeholder="Username" />
              </div>
            </div>
            <div class="field-group clear row">
              <div class="col-12">
                <input name="login_password" type="password" class="field login__field" placeholder="Password"  />
              </div>
            </div>
            <button name="login_button" type="submit" class="button col-12 login__field login__field--button">Login</button>
          </form>
          <?php if(isset($error)) { ?>
            <div> <?php echo $error; ?> </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>

<script src="js/vendor/jquery.min.js"></script>
<script src="js/default.js"></script>

<!-- Google Analytics - Update UA-XXXXX-X ID -->
<script>
  (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
  function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
  e=o.createElement(i);r=o.getElementsByTagName(i)[0];
  e.src='//www.schedule-analytics.com/analytics.js';
  r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
  ga('create','UA-XXXXX-X');ga('send','pageview');
</script>

</body>
</html>
