<?php
    require_once("session.php");
    require_once("user.php");

    $auth_user = new User();

    $user_id = $_SESSION['user_session'];
    $user_role = $_SESSION['user_role_session'];
    $user_notif = $_SESSION['user_notif'];

    $token = $_SESSION['token'];

    $stmt = $auth_user->runQuery("SELECT user.id, user.role, user.username, account.name, account.balance, account.address, account.phone, account.email, account.avatar FROM account, user WHERE account.id = user.id AND user.id = :user_id GROUP BY account.id");
    $stmt->execute(array(":user_id"=>$user_id));

    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $getUser = $auth_user->runQuery("SELECT * FROM user");

    $memberList = array();

    if($getUser->execute()) {
      while ($row = $getUser->fetch(PDO::FETCH_ASSOC)) {
        $memberList[] = $row;
      }
    }

    $getTransaction = $auth_user->runQuery("SELECT transaction.date, transaction.description, transaction.type, transaction.amount FROM transaction WHERE transaction.user_id = :user_id");

    $transactionList = array();
    if($getTransaction->execute(array(":user_id"=>$user_id))) {
      while ($row = $getTransaction->fetch(PDO::FETCH_ASSOC)) {
        $transactionList[] = $row;
      }
    }

    if(isset($_POST['deposit_button'])) {
      $deposit_amount = $_POST['deposit_amount'];

      if (empty($deposit_amount)) {
        $error = "Please enter the deposit amount!";
        $user_notif->set("Error", $error);
      } else if ($deposit_amount <= 0) {
        $error = "Deposit amount cannot be negative!";
        $user_notif->set("Error", $error);
      } else {
        if (!empty($_POST['token'])) {
            if (hash_equals($_POST['token'], $_SESSION['token'])) {
                 // Proceed to process the form data
                try {
                  if($auth_user->deposit($user_id, $deposit_amount)) {

                    if($auth_user->record("deposit", $deposit_amount)) {
                      $user_notif->set("Success", "€" . $deposit_amount . " is successfully deposited!");
                      $auth_user->redirect('index.php');

                    }

                  }
                } catch (PDOException $e) {
                  $user_notif->set("Error", $e->getMessage());
                }
            } else {
                 // Log this as a warning and keep an eye on these attempts
                $user_notif->set("Error", "CSRF Token is different!");
            }
        }

      }
    }

    if(isset($_POST['transfer_button'])) {
      $transfer_amount = $_POST['transfer_amount'];
      $transfer_recipient = $_POST['transfer_recipient'];

          if (empty($transfer_recipient)) {
            $error = "Recipient cannot be empty!";
            $user_notif->set("Error", $error);
          } else if ($transfer_amount <= 0) {
            $error = "Transfer amount cannot be negative!";
            $user_notif->set("Error", $error);
          } else if (empty($transfer_recipient)) {
            $error = "Transfer amount cannot be empty!";
            $user_notif->set("Error", $error);
          } else {
            if (!empty($_POST['token'])) {
              if (hash_equals($_POST['token'], $_SESSION['token'])) {
                   // Proceed to process the form data
                  try {
              if (!$auth_user->isUserExist($transfer_recipient)) {
                $user_notif->set("Error", "Transfer recipient " . $transfer_recipient . " is not found!");
                  $auth_user->redirect('index.php');
              } else if ($userRow['username'] == $transfer_recipient) {
                $user_notif->set("Error", "Cannot transfer to yourself!");
                  $auth_user->redirect('index.php');
              } else {
                if($auth_user->transfer($user_id, $transfer_recipient, $transfer_amount)) {
                  if($auth_user->record("transfer", $transfer_amount, $transfer_recipient)) {
                    $user_notif->set("Success", "€" . $transfer_amount . " is transferred successfully to " . $transfer_recipient . "!");
                    $auth_user->redirect('index.php');
                  }
                }
              }
            } catch (PDOException $e) {
              $user_notif->set("Error", $e->getMessage());
            }
              } else {
                   // Log this as a warning and keep an eye on these attempts
                $user_notif->set("Error", "CSRF Token is different!");
              }
            }

          }
        }

        //xss mitigation functions
        function xssafe($data, $encoding='UTF-8')
        {
           return htmlspecialchars($data,ENT_QUOTES | ENT_HTML401,$encoding);
        }
        function xecho($data)
        {
           echo xssafe($data);
        }

?>
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Fontys Bank - Dashboard</title>
    <meta name="description" content="">
    <meta name="theme-color" content="#f7c824">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="img/favicon.png">
    <link rel="stylesheet" href="css/styles.css"> </head>

  <body>
    <header>
      <div class="container">
        <div class="row clear pad-top-5 pad-bottom-5">
          <div class="col-5 col-4-m col-2-l header__logo">Fontys Bank</div>
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
      <?php if(!empty($user_notif->getStatus()) && $user_notif->getStatus() == "Error") { ?>
       <div class="col-12 col-12-m col-12-l">
         <div style="padding-bottom: 10px;" class="box box--notification box--notification-error">
          <span style="font-weight: bold;"><?php echo $user_notif->getStatus(); ?>:</span>
          <span class="center"><?php echo $user_notif->getMessage(); ?></span>
          <a href="#" class="right close"></a>
         </div>
       </div>
      <?php } ?>
       <?php if(!empty($user_notif->getStatus()) && $user_notif->getStatus() == "Success") { ?>
       <div class="col-12 col-12-m col-12-l">
         <div style="padding-bottom: 10px;" class="box box--notification box--notification-success">
          <span style="font-weight: bold;"><?php echo $user_notif->getStatus(); ?>:</span>
          <span class="center"><?php echo $user_notif->getMessage(); ?></span>
          <a href="#" class="right close"></a>
         </div>
       </div>
      <?php } ?>
        <div class="col-12 col-5-m col-4-l">
          <div class="box box--balance">
            <div class="box__title ">Balance</div>
            <div class="box__container">
              <h2>€ <?php print($userRow['balance']) ?></h2> </div>
          </div>



          <div class="box">

            <div class="box__title pad-bottom-10">Account Details</div>

             <div class="avatar center" style="background-image: url(<?php echo 'avatar/' . $userRow['avatar'] ?>)"></div>
            <div class="box__container pad-top-10 pad-bottom-40">
              <ul class="box__data pad-bottom-10">
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Name</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php xecho($userRow['name']); ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Role</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php xecho($user_role) ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Email</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php xecho($userRow['email']) ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Phone</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php xecho($userRow['phone']) ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Address</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php xecho($userRow['address']) ?>
                    </div>
                  </div>
                </li>
              </ul>
              <a href='account.php?action=edit' class="button right">Edit</a>
            </div>
          </div>
        </div>
        <div class="col-12 col-7-m col-8-l right">
          <div class="row clear padding-bottom-15">
            <div class="col-12 col-12-m col-12-l">
              <div class="box">
                <div class="box__title">Transcations</div>
                <div class="box__container pad-top-10 pad-bottom-20">
                  <table>
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Amount(€)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $i=0;
                        foreach (array_reverse($transactionList) as $value) {
                        if($i==11) break;
                      ?>
                        <tr>
                          <td>
                            <?php echo $value['date'] ?>
                          </td>
                          <td>
                            <?php echo $value['description'] ?>
                          </td>
                          <td>
                            <?php echo $value['type'] ?>
                          </td>
                          <td class="center"> <?php echo $value['amount'] ?>
                          </td>
                        </tr>
                        <?php $i++; }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <?php
                        if($user_role == "Administrator") { ?>
              <div class="col-12 col-12-m col-12-l">
                <div class="box">
                  <div class="box__title">Members <span class="right" style="font-weight: 500"><a href='register.php'>Add</a></span></div>
                  <div class="box__container pad-top-10 pad-bottom-20">
                    <table>
                      <thead>
                        <tr>
                          <th>ID</th>
                          <th>Username</th>
                          <th>Role</th>
                          <th>Action</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        foreach ($memberList as $value) { ?>
                          <tr>
                            <td>
                              <?php echo $value['id'] ?>
                            </td>
                            <td>
                              <?php echo $value['username'] ?>
                            </td>
                            <td>
                              <?php echo $value['role'] ?>
                            </td>
                            <td class="center"> <a href='account.php?action=edit&id=<?php print($value['id']) ?>'>Edit</a>
                              <?php if($value['role'] != 'Administrator') { ?>
                                <a href='account.php?action=delete&id=<?php print($value['id']) ?>'>Remove</a>
                                <?php } ?>
                            </td>
                          </tr>
                          <?php }
                      ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              <?php
                        }
                    ?>
                <div class="col-12 col-12-m col-6-l">
                  <div class="box">
                    <div class="box__title">Deposit Money</div>
                    <div class="box__container box__container--center pad-bottom-20 pad-top-35">
                      <div class="row clear padding-bottom-15">
                        <form method="post">
                          <div class="col-12 box__input"> <span class="col-4 money">€</span>
                            <input type="number" class="col-8" id="textDeposit" placeholder="Amount" name="deposit_amount" min="1" max="99999">
                            <input type="hidden" name="token" value="<?php echo $token; ?>" />
                            </div>
                          <div class="col-12 right">
                            <button type="submit" name="deposit_button" class="button col-8 right">Deposit</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-12-m col-6-l">
                  <div class="box">
                    <div class="box__title">Transfer Money</div>
                    <div class="box__container box__container--center pad-bottom-10 pad-top-15">
                      <div class="row clear padding-bottom-15">
                        <form method="post">
                          <div class="col-12 box__input"> <span class="col-4 money">To</span>
                            <input name="transfer_recipient" type="text" class="col-8" id="textDeposit" placeholder="Recipient Username"> </div>
                          <div class="col-12 box__input"> <span class="col-4 money">€</span>
                            <input name="transfer_amount" type="number" class="col-8" id="textDeposit" placeholder="Amount" min="1" max="99999">
                            <input type="hidden" name="token" value="<?php echo $token; ?>" /></div>
                          <div class="col-12 right">
                            <button name="transfer_button" type="submit" class="button col-8 right">Transfer</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
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
