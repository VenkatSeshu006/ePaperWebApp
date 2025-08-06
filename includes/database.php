<?php
/**
 * Database Connection Class
 * E-Paper CMS v2.0
 */

// Database configuration constants
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'epaper_cms');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');
if (!defined('DB_PORT')) define('DB_PORT', 3306);

// Site configuration constants
if (!defined('SITE_TITLE')) define('SITE_TITLE', 'E-Paper CMS');
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

/**
 * Database class providing PDO interface for compatibility
 */
class Database {
    private static $instance = null;
    private $pdo = null;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET . ";port=" . DB_PORT;
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            if (DEBUG_MODE) {
                die("Database Connection Error: " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    public function getPDO() {
        return $this->pdo;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            // For SELECT queries, return the statement for fetching
            if (stripos(trim($sql), 'SELECT') === 0) {
                return $stmt;
            }
            
            // For INSERT, UPDATE, DELETE queries, return success boolean
            return $result;
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage() . " SQL: " . $sql);
            throw $e;
        }
    }
    
    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
    
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    public function commit() {
        return $this->pdo->commit();
    }
    
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    public function rowCount() {
        // This method needs to be called on a statement, not the database
        throw new Exception("rowCount() must be called on a PDOStatement object");
    }
}

// Global connection variable for backward compatibility
$conn = null;

/**
 * Get database connection
 * @return PDO
 */
function getConnection() {
    global $conn;
    if ($conn === null) {
        $conn = Database::getInstance()->getConnection();
    }
    return $conn;
}

/**
 * Get database instance
 * @return Database
 */
function db() {
    return Database::getInstance();
}

/**
 * Get PDO connection
 * @return PDO
 */
function getPDO() {
    return Database::getInstance()->getPDO();
}
?>
