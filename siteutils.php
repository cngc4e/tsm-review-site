<?php

include_once 'database.php';

function s($str) {
    return htmlspecialchars($str);
}

class SiteUtils {
    /*
     * Create and set a cookie token for session user. 
     * Call after session_start() and after $_SESSION['user_id'] is set.
     */
    public static function createSetCookieToken() {
        $cookietoken = md5(random_bytes(10));
        
        $db = Database::getInstance();
        $cmd = $db->prepare('REPLACE INTO session_tokens (user_id, tokenhash, time_created) VALUES(:u_id, :tokenh, CURRENT_TIMESTAMP())');
        $cmd->bindValue(':u_id', $_SESSION['user_id']);
        $cmd->bindValue(':tokenh', password_hash($cookietoken, PASSWORD_DEFAULT));
        if (!$cmd->execute()) {
            // Database error.
            return -1;
        }
        
        $expiry = time() + 86400*30; /* 30 days */
        setcookie("session_token", $cookietoken, $expiry);
        setcookie("session_user_id", $_SESSION['user_id'], $expiry);

        return 0;
    }
    
    /* 
     * Renews the PHP session if user has a valid cookie token. 
     * Additionally resets the cookie token after doing so.
     * Call after session_start().
     */
    public static function cookieSessionRenew() {
        if (isset($_SESSION['user_id']) || empty($_COOKIE['session_token']) || empty($_COOKIE['session_user_id'])) {
            // Already logged in or has no cookie token, session not renewed.
            return 1;
        }
        
        $db = Database::getInstance();
        $cmd = $db->prepare('SELECT user_id, tokenhash from session_tokens WHERE user_id = :u_id');
        $cmd->bindValue(':u_id', $_COOKIE['session_user_id']);
        if (!$cmd->execute()) {
            // Database error.
            return -1;
        }
            
        $res = $cmd->fetch(PDO::FETCH_ASSOC);
        $tokenhash = $res['tokenhash'];
        $user_id = $res['user_id'];
        
        if (!password_verify($_COOKIE['session_token'], $tokenhash)) {
            // Invalid cookie token, session not renewed.
            return 1;
        }
        
        // Successfully renewed session.
        $_SESSION['user_id'] = $user_id;
        // Recreate the cookie token
        self::createSetCookieToken();
        return 0;
    }
    
    /* Terminate log in session and clear cookies */
    public static function logOut() {
        $_SESSION = array();
        $expired = time() - 3600;
        setcookie("session_token", null, $expired);
        setcookie("session_user_id", null, $expired);
    }
}
