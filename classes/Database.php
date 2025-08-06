<?php
/**
 * Simple Database Wrapper Class
 * Provides singleton pattern for database connections
 */

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        require_once 'includes/database.php';
        $this->connection = getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}
?>
