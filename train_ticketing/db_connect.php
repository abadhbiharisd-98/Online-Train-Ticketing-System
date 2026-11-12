<?php
/**
 * Database Connection Configuration
 * Fully compatible with PHP 8.x, Aiven Cloud MySQL (SSL enforce), & Render deployments.
 */

if (!class_exists('DBConnection')) {
    class DBConnection {
        // Safe property declarations without function calls (PHP 8.x compliant)
        private $host;
        private $port;
        private $username;
        private $password;
        private $database;
        private $ssl;
        public $conn;

        public function __construct() {
            // Initialize properties safely inside the constructor
            $this->host = getenv('DB_SERVER') ?: (getenv('DB_HOST') ?: 'localhost');
            $this->port = (int)(getenv('DB_PORT') ?: 3306);
            $this->username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root');
            $this->password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
            $this->database = getenv('DB_NAME') ?: 'tsts_db';

            // Check if SSL is explicitly configured or auto-detect for Cloud MySQL (e.g., Aiven)
            $sslEnv = getenv('DB_SSL');
            if ($sslEnv !== false && $sslEnv !== '') {
                $this->ssl = filter_var($sslEnv, FILTER_VALIDATE_BOOLEAN) || strtolower($sslEnv) === 'require' || $sslEnv === '1';
            } else {
                // If connecting to a remote host (non-localhost) or custom port, enable SSL by default
                $this->ssl = ($this->host !== 'localhost' && $this->host !== '127.0.0.1');
            }

            $this->connect();
        }

        private function connect() {
            // Prevent unhandled fatal mysqli_sql_exception in PHP 8.1+
            mysqli_report(MYSQLI_REPORT_OFF);

            $this->conn = mysqli_init();
            if (!$this->conn) {
                error_log("Database Error: mysqli_init failed.");
                return;
            }

            // Connection timeout to avoid hanging reverse proxy
            $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);

            $flags = 0;
            if ($this->ssl) {
                // Enforce SSL for Aiven Cloud MySQL (require_secure_transport=ON)
                $this->conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
                $flags |= MYSQLI_CLIENT_SSL;
            }

            $connected = @$this->conn->real_connect(
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port,
                NULL,
                $flags
            );

            // Fallback: If SSL failed on localhost, retry without SSL
            if (!$connected && $this->ssl && ($this->host === 'localhost' || $this->host === '127.0.0.1')) {
                $this->conn = mysqli_init();
                $connected = @$this->conn->real_connect(
                    $this->host,
                    $this->username,
                    $this->password,
                    $this->database,
                    $this->port
                );
            }

            if (!$connected) {
                error_log("Database Connection Failed: (" . mysqli_connect_errno() . ") " . mysqli_connect_error());
            } else {
                $this->conn->set_charset("utf8mb4");
            }
        }

        public function getConnection() {
            return $this->conn;
        }
    }
}

// Global connection instance
$dbInstance = new DBConnection();
$conn = $dbInstance->getConnection();

// Safe query execution helper
if (!function_exists('safe_query')) {
    function safe_query($connection, $query_str) {
        if (!$connection || !($connection instanceof mysqli)) {
            return false;
        }
        $result = @$connection->query($query_str);
        return ($result && $result instanceof mysqli_result) ? $result : false;
    }
}
?>
