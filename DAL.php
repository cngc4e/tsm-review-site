<?php

require_once "database.php";
require_once "siteutils.php";

final class User {
    public $user_id;
    public $username;
    public $email;
    public $tfm_user;
    public $discord_user;
    
    public function __construct($array) {
        $this->user_id = $array["user_id"];
        $this->username = $array["username"];
        $this->email = $array["email"];
        $this->tfm_user = $array["tfm_user"];
        $this->discord_user = $array["discord_user"];
    }
    
    public function getDisplayName() {
        $n = $this->username ;
        if ($this->tfm_user) {
            $n .= " ({$this->tfm_user})";
        } else if ($this->discord_user) {
            $n .= " ({$this->discord_user})";
        }
        return s($n);
    }
}

class DAL {
    public static function getUser($id) : User {
        $db = Database::getInstance();
        $cmd = $db->prepare('SELECT username, tfm_user FROM users WHERE user_id = :id');
        $cmd->bindParam(':id', $_SESSION['user_id']);
        $cmd->execute();
        
        $row = $cmd->fetch(PDO::FETCH_ASSOC);
        
        return new User($row);
    }
    
    /* returns either true (first time) or the last time since sending if message should send, false otherwise */
    public static function getDiscordShouldSend() {
        $db = Database::getInstance();
        $cmd = $db->prepare('SELECT needs_send, last_time FROM discord_webhooks LIMIT 1');
        $cmd->execute();
        
        $row = $cmd->fetch(PDO::FETCH_ASSOC);
        
        if ($row["last_time"] == null) {
            return true;
        }
        
        return $row["needs_send"] ? $row["last_time"] : false;
    }
    
    public static function setDiscordShouldSend($b) : void {
        $db = Database::getInstance();
        
        $cmd = $db->prepare('SELECT COUNT(*) FROM discord_webhooks');
        $cmd->execute();
        $count = $cmd->fetch(PDO::FETCH_NUM)[0];
        
        if ($count == 0) {
            $cmd = $db->prepare('INSERT INTO discord_webhooks (needs_send, last_time) VALUES (:b, CURRENT_TIMESTAMP())');
            $cmd->bindParam(':b', $b);
        } else {
            $q = 'UPDATE discord_webhooks SET needs_send = :b';
            if (!$b) {  // assume this means stats up till current have been sent
                $q .= ', last_time = CURRENT_TIMESTAMP()';
            }
            $cmd = $db->prepare($q);
            $cmd->bindParam(':b', $b);
        }
        $cmd->execute();
    }
}