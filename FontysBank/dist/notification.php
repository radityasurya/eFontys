<?php


class Notification
{

    private $_message;
    private $_status;

    public function __construct()
    {
    }

    public function set($s, $m) {
        $this->_status = $s;
        $this->_message = $m;
        $_SESSION['user_notif_start'] = time();
        $_SESSION['user_notif_expire'] = $_SESSION['user_notif_start'] + (0.1 * 60);
        return true;
    }

    public function getMessage() {
        return $this->_message;
    }

    public function getStatus() {
        if(!empty($_SESSION['user_notif_start'])) {
          $now = time(); // Checking the time now when home page starts.

          if ($now > $_SESSION['user_notif_expire']) {
           $this->clear();
          } else {
              return $this->_status;
          }
        }
    }

    public function clear() {
        $this->_message = "";
        $this->_status = "";
    }

}
?>
