<?php
    require_once("session.php");
    require_once("user.php");

    $auth_user = new User();
    $user_id = $_SESSION['user_session'];
    $user_role = $_SESSION['user_role_session'];
    $user_notif = $_SESSION['user_notif'];


    $stmt = $auth_user->runQuery("SELECT user.id, user.role, user.username, account.name, account.balance, account.address, account.phone, account.email FROM account, user WHERE account.id = user.id AND user.id = :user_id GROUP BY account.id");

    $stmt->execute(array(":user_id"=>$user_id));

    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if(isset($_POST['account_button_back'])) {
      $user_notif->clear();
      $auth_user->redirect('index.php');
    }
    if(isset($_POST['account_button'])) {
      $uname = strip_tags($_POST['account_username']);
      $email = strip_tags($_POST['account_email']);
      $upass = strip_tags($_POST['account_password']);
      $name = strip_tags($_POST['account_name']);
      $phone = strip_tags($_POST['account_phone']);
      $address = strip_tags($_POST['account_address']);


    try
    {
      $stmt = $auth_user->runQuery("SELECT username FROM user WHERE username=:uname");
      $stmt->execute(array(':uname'=>$uname));
      $row=$stmt->fetch(PDO::FETCH_ASSOC);

      if(empty($uname)) {
        $error = "Username is empty!";
      } else if(empty($upass)) {
        $error = "Password is empty!";
      } else if(empty($name)) {
        $error = "Name is empty!";
      } else if(empty($email)) {
        $error = "Email is empty!";
      } else if(empty($phone)) {
        $error = "Phone is empty!";
      } else if(!is_numeric($phone)) {
        $error = "Phone is not number!";
      } else if(empty($address)) {
        $error = "Address is empty!";
      } else if($auth_user->isUserExist($uname)) {
        $error = "Sorry username already taken!";
      }
      else
      {
        if($auth_user->register($uname, $upass, $name, $email, $phone, $address)){
          $user_notif->set("Success", "User " . $uname . " is successfully created!");
          $auth_user->redirect('index.php');
        }
      }
    }
    catch(PDOException $e)
    {
      $user_notif->set("Error", $e->getMessage());
      $auth_user->redirect('index.php');
    }
    }

?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Fontys Bank - Register Account</title>
    <meta name="description" content="">
    <meta name="theme-color" content="#f7c824">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/styles.css"> </head>

  <body>
    <header>
      <div class="container">
        <div class="row clear pad-top-5 pad-bottom-5">
          <div class="col-5 col-4-m col-2-l header__logo"> <a href="index.php" style="text-decoration: none">Fontys Bank</a> </div>
          <div class="col-7 col-6-m col-4-l right header__logout"> <i>Hi, <?php print($userRow['username']) ?></i>
            <a href="logout.php?logout=true">
              <button class="button button--logout">Logout</button>
            </a>
          </div>
        </div>
      </div>
    </header>
    <div class="container">
      <div class="row clear pad-top-30 pad-bottom-5">
        <div class="center col-12 col-6-m col-6-l">
         <?php if(isset($error)) { ?>
            <div class="box box--notification box--notification-error" style="margin-bottom:0; padding-bottom: 10px"> <span class="center"> <?php echo $error; ?></span>
              <a href="#" class="right close"></a>
            </div>
          <?php } ?>
          <div class="box">

            <div class="box__title pad-bottom-10">Register New Account</div>
            <div class="box__container pad-top-10 pad-bottom-25">
              <form class="pad-bottom-20" method="post">
               <div class="field-group clear row">
                  <div class="col-12"> <code>Username</code>
                    <input name="account_username" type="text" class="field login__field" placeholder="Username" value="<?php if(isset($_POST['account_username'])) echo $_POST['account_username']; ?>">
                  </div>
                  <div class="col-12"> <code>Password</code>
                    <input name="account_password" type="password" class="field login__field" placeholder="Password">
                  </div>
                </div>
                <div class="field-group clear row">
                  <div class="col-12"> <code>Name</code>
                    <input name="account_name" type="text" class="field login__field" placeholder="Name" value="<?php if(isset($_POST['account_name'])) echo $_POST['account_name']; ?>"/> </div>
                </div>
                <div class="field-group clear row">
                  <div class="col-12"> <code>Email</code>
                    <input name="account_email" type="email" class="field login__field" placeholder="email@email.com" value="<?php if(isset($_POST['account_email'])) echo $_POST['account_email']; ?>"/> </div>
                </div>
                <div class="field-group clear row">
                  <div class="col-12"> <code>Phone</code>
                    <input name="account_phone" type="text" class="field login__field" placeholder="0612345678" value="<?php if(isset($_POST['account_phone'])) echo $_POST['account_phone']; ?>"/> </div>
                </div>
                <div class="field-group clear row pad-bottom-10">
                  <div class="col-12"> <code>Address</code>
                    <input name="account_address" type="text" class="field login__field" placeholder="St. Local 12" value="<?php if(isset($_POST['account_address'])) echo $_POST['account_address']; ?>"/> </div>
                </div>
                <button name="account_button" type="submit" class="button right">Register</button> <button name="account_button_back" type="submit" class="button right" style="margin-right: 5px">Back</button> </form>
            </div>
          </div>
        </div>
      </div>
      <script src="js/vendor/jquery.min.js"></script>
      <script src="js/default.js"></script>
      <!-- Google Analytics - Update UA-XXXXX-X ID -->
      <script>
        (function (b, o, i, l, e, r) {
          b.GoogleAnalyticsObject = l;
          b[l] || (b[l] = function () {
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
