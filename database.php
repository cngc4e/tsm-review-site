<?php

include_once 'config.php';

class Database extends PDO {
    private static $instance = null;

    public function __construct() {
        try {
            parent::__construct(sprintf("mysql:host=%s;dbname=%s", Config::$db_servername, Config::$db_name), Config::$db_username, Config::$db_password);
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
 
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
}
