<?php
    require_once("session.php");
    require_once("user.php");

    $auth_user = new User();
    $user_id = $_SESSION['user_session'];
    $user_role = $_SESSION['user_role_session'];

    $stmt = $auth_user->runQuery("SELECT user.id, user.role, user.username, account.name, account.balance, account.address, account.phone, account.email FROM account, user WHERE account.id = user.id AND user.id = :user_id GROUP BY account.id");
    $stmt->execute(array(":user_id"=>$user_id));

    $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $getUser = $auth_user->runQuery("SELECT * FROM user");
    $memberList = array();

    if($getUser->execute()) {
      while ($row = $getUser->fetch(PDO::FETCH_ASSOC)) {
        $memberList[] = $row;
      }
    }

    if(isset($_POST['deposit_button'])) {
      $deposit_amount = $_POST['deposit_amount'];

      if ($deposit_amount <= 0) {
        $error[] = "Deposit amount cannot be negative!";
      } else {
        try {
          if($auth_user->deposit($user_id, $deposit_amount)) {
            $auth_user->redirect('index.php');
          }
        } catch (PDOException $e) {
          echo $e->getMessage();
        }
      }
    }

    if(isset($_POST['transfer_button'])) {
      $transfer_amount = $_POST['transfer_amount'];
      $transfer_recipient = $_POST['transfer_recipient'];

          if ($transfer_amount <= 0) {
            $error[] = "Transfer amount cannot be negative!";
          } else if ($transfer_recipient == "") {
            $error[] = "Recipient cannot be null!";
          } else {
            try {
              if($auth_user->transfer($user_id, $transfer_recipient, $transfer_amount)) {
                $auth_user->redirect('index.php');
              }
            } catch (PDOException $e) {
              echo $e->getMessage();
            }
          }
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
          <div class="col-5 col-4-m col-2-l header__logo"> Fontys Bank </div>
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
        <div class="col-12 col-5-m col-4-l">
          <div class="box box--balance">
            <div class="box__title ">Balance</div>
            <div class="box__container">
              <h2>€ <?php print($userRow['balance']) ?></h2> </div>
          </div>
          <div class="box">
            <div class="box__title">Account Details</div>
            <div class="box__container pad-top-10 pad-bottom-40">
              <ul class="box__data pad-bottom-10">
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Name</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php print($userRow['name']) ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Role</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php print($user_role) ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Email</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php print($userRow['email']) ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Phone</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php print($userRow['phone']) ?>
                    </div>
                  </div>
                </li>
                <li class="pad-bottom-5">
                  <div class="clear row-m">
                    <div class="col-4-m col-12-l"> <code>Address</code> </div>
                    <div class="col-9-m col-12-l">
                      <?php print($userRow['address']) ?>
                    </div>
                  </div>
                </li>
              </ul>
              <a href='account.php?id=<?php print($user_id) ?>' class="button right">Edit</a>
            </div>
          </div>
        </div>
        <div class="col-12 col-7-m col-8-l right">
          <div class="row clear padding-bottom-15">
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
                            <td class="center"> <a href='account.php?id=<?php print($value['id']) ?>'>Edit</a> <?php if($value['role'] != 'Administrator') { ?>
                              <a href='account.php?delete_id=<?php print($value['id']) ?>'>Remove</a>
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
                            <input type="number" class="col-8" id="textDeposit" placeholder="Amount" name="deposit_amount"> </div>
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
                            <input name="transfer_recipient" type="text" class="col-8" id="textDeposit" placeholder="Recipient"> </div>
                          <div class="col-12 box__input"> <span class="col-4 money">€</span>
                            <input name="transfer_amount" type="number" class="col-8" id="textDeposit" placeholder="Amount"> </div>
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
