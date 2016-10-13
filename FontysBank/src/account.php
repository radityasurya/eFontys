<?php
    require_once("session.php");
    require_once("user.php");
    //turn on php error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    $auth_user = new User();
    $user_id = $_SESSION['user_session'];
    $user_notif = $_SESSION['user_notif'];
    $user_role = $_SESSION['user_role_session'];

    $stmt = $auth_user->runQuery("SELECT user.id, user.role, user.username, account.name, account.balance, account.address, account.phone, account.email FROM account, user WHERE account.id = user.id AND user.id = :user_id GROUP BY account.id");

    if($_GET['action'] == 'edit') {
      
      if (isset($_GET['id'])) {
        if($user_role == "Administrator" ) {
          $selected_user_id = $_GET['id'];
          $tempUser = $auth_user->getUserById($selected_user_id);
          if(!empty($tempUser)) { 
            $stmt->execute(array(":user_id"=>$selected_user_id));
          } else {
            $user_notif->set("Error", "Account with the id " . $_GET['delete_id'] . " is not found!");
            $auth_user->redirect('index.php');
          }
        } else {
          $user_notif->set("Error", "Admin access only!");
          $auth_user->redirect('index.php');
        }
                
      } else {
        $stmt->execute(array(":user_id"=>$user_id));
      }
    } else if ($_GET['action'] == 'delete') {
      if (isset($_GET['id'])) {
        if($user_role == "Administrator" ) {
          $selected_user_id = $_GET['id'];
          $tempUser = $auth_user->getUserById($selected_user_id);
          if(!empty($tempUser) && $tempUser['role'] != 'Administrator') { 
            try {              
              if($auth_user->delete($selected_user_id)) {
                  $user_notif->set("Success", "Account with id " . $selected_user_id . " is successfully deleted!");
                  $auth_user->redirect('index.php');
              } else {
                $user_notif->set("Error", "Account with id " . $_GET['delete_id'] . " is not found!");
                $auth_user->redirect('index.php');
              }
            } catch (PDOException $e) {
              $user_notif->set("Error", $e->getMessage());
            }
          } else {
              $user_notif->set("Error", "Administrator account cannot be deleted!");
              $auth_user->redirect('index.php');
          }
        } else {
          $user_notif->set("Error", "Admin access only!");
          $auth_user->redirect('index.php');
        }
      } else {
        $auth_user->redirect('index.php');
      }

    } else {
      $auth_user->redirect('index.php');
    }

    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $getUser = $auth_user->runQuery("SELECT * FROM user");
    $memberList = array();

    if($getUser->execute()) {
      while ($row = $getUser->fetch(PDO::FETCH_ASSOC)) {
        $memberList[] = $row;
      }
    }

    if(isset($_POST['account_button_back'])) {
      $user_notif->clear();
      $auth_user->redirect('index.php');
    }

    if(isset($_POST['account_button'])) {

      $name     = $_FILES['file']['name'];
      $tmpName  = $_FILES['file']['tmp_name'];
      $size     = $_FILES['file']['size'];
      $ext	  = strtolower(pathinfo($name, PATHINFO_EXTENSION));

      if (isset($_GET['id'])) {
        if($user_role == "Administrator" ) { 
          $selected_user_id = $_GET['id'];
        } else {
          $user_notif->set("Error", "Admin access only!");
          $auth_user->redirect('index.php');
        }
      } else {
        $selected_user_id = $user_id;
      }

      if($size == 0) {
        if(empty($_POST['account_name'])) {
          $error = "Name is empty!";
          } else if(empty($_POST['account_email'])) {
            $error = "Email is empty!";
          } else if(empty($_POST['account_phone'])) {
            $error = "Phone is empty!";
          } else if(empty($_POST['account_address'])) {
            $error = "Address is empty!";
          } else {
            try {
              if($auth_user->edit($selected_user_id, $_POST['account_name'], $_POST['account_email'], $_POST['account_phone'], $_POST['account_address'])) {
                $user_notif->set("Success", "Your account is successfully updated!");
                $auth_user->redirect('index.php');
              }
            } catch (PDOException $e) {
              $user_notif->set("Error", $e->getMessage());
            }
          }
      } else {
        // check file extension
        if($ext != "jpg" && $ext != "png" && $ext != "jpeg" && $ext != "gif" ) {
            $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $valid = false;
        }
        else if ($size > 500000) {
            $error = "Sorry, your file is too large!";
            $valid = false;
        } else {
          $valid = true;
        }

        if ($valid) {
          $targetPath =  dirname( __FILE__ ) . DIRECTORY_SEPARATOR. 'avatar' . DIRECTORY_SEPARATOR. $name;
          if(move_uploaded_file($tmpName,$targetPath)) {
              // add to database
              try {
                if($auth_user->updateAvatar($selected_user_id, $name)) {
                }
              } catch (PDOException $e) {
                $user_notif->set("Error", $e->getMessage());
              }
          } else {
            $error = "Sorry, there was an error uploading the avatar.";
          }

          if(empty($_POST['account_name'])) {
          $error = "Name is empty!";
          } else if(empty($_POST['account_email'])) {
            $error = "Email is empty!";
          } else if(empty($_POST['account_phone'])) {
            $error = "Phone is empty!";
          } else if(empty($_POST['account_address'])) {
            $error = "Address is empty!";
          } else {
            try {
              if($auth_user->edit($selected_user_id, $_POST['account_name'], $_POST['account_email'], $_POST['account_phone'], $_POST['account_address'])) {
                $user_notif->set("Success", "Your account is successfully updated!");
                $auth_user->redirect('index.php');
              }
            } catch (PDOException $e) {
              $user_notif->set("Error", $e->getMessage());
            }
          }
        }

      }

    }

?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Fontys Bank - Edit Account</title>
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
                <div class="box__title pad-bottom-10">Edit Account Details</div>
                <div class="box__container pad-top-10 pad-bottom-25">
                  <form class="pad-bottom-20" method="post" enctype="multipart/form-data">
                    <div class="field-group clear row">
                      <div class="col-12"> <code>Avatar</code>
                        <input type="file" class="field login__field" name="file" /> </div>
                    </div>
                    <div class="field-group clear row">
                      <div class="col-12"> <code>Name</code>
                        <input name="account_name" type="text" class="field login__field" placeholder="Name" value='<?php print($userRow["name"]) ?>' /> </div>
                    </div>
                    <div class="field-group clear row">
                      <div class="col-12"> <code>Email</code>
                        <input name="account_email" type="email" class="field login__field" placeholder="email@email.com" value='<?php print($userRow["email"]) ?>' /> </div>
                    </div>
                    <div class="field-group clear row">
                      <div class="col-12"> <code>Phone</code>
                        <input name="account_phone" type="text" class="field login__field" placeholder="0612345678" value='<?php print($userRow["phone"]) ?>' /> </div>
                    </div>
                    <div class="field-group clear row pad-bottom-10">
                      <div class="col-12"> <code>Address</code>
                        <input name="account_address" type="text" class="field login__field" placeholder="St. Local 12" value='<?php print($userRow["address"]) ?>' /> </div>
                    </div>
                    <button name="account_button" type="submit" class="button right">Save</button>
                    <button name="account_button_back" type="submit" class="button right" style="margin-right: 5px">Back</button>
                  </form>
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
