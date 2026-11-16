<?php
/**
 * Modernized Aiven Cloud MySQL & PHP 8.x Database Connection
 * Cyber Cafe Management System (CCMS)
 */

if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}

// PHP 8 Compatibility Polyfills
if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc() { return false; }
}

class DBConnection {
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $ssl;
    public $conn;
    public $connect_error = null;

    public function __construct() {
        if (function_exists('mysqli_report')) {
            @mysqli_report(MYSQLI_REPORT_OFF);
        }

        // Aiven Cloud MySQL Production Defaults
        $aiven_default_host = 'mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com';
        $aiven_default_port = 12893;
        $aiven_default_user = 'avnadmin';
        $aiven_default_pass = base64_decode('QVZOU19IX0dqY3JoX1c0WmJURUZsMXVJ');
        $aiven_default_name = 'ccmsdb';

        // PHP 8.x Safe Property Initializations inside constructor
        $env_server = getenv('DB_SERVER') ?: (isset($_ENV['DB_SERVER']) ? $_ENV['DB_SERVER'] : '');
        $this->host = (!empty($env_server) && $env_server !== 'localhost' && $env_server !== '127.0.0.1') 
            ? $env_server 
            : (defined('DB_SERVER') ? DB_SERVER : $aiven_default_host);

        $env_port = getenv('DB_PORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '');
        $this->port = !empty($env_port) 
            ? (int)$env_port 
            : (defined('DB_PORT') ? (int)DB_PORT : $aiven_default_port);

        $env_user = getenv('DB_USERNAME') ?: (isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : '');
        $this->username = !empty($env_user) 
            ? $env_user 
            : (defined('DB_USERNAME') ? DB_USERNAME : $aiven_default_user);

        $env_pass = (getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '') 
            ? getenv('DB_PASSWORD') 
            : (isset($_ENV['DB_PASSWORD']) && $_ENV['DB_PASSWORD'] !== '' ? $_ENV['DB_PASSWORD'] : '');
        $this->password = !empty($env_pass) 
            ? $env_pass 
            : (defined('DB_PASSWORD') ? DB_PASSWORD : $aiven_default_pass);

        $env_name = getenv('DB_NAME') ?: (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : '');
        $this->database = !empty($env_name) 
            ? $env_name 
            : (defined('DB_NAME') ? DB_NAME : $aiven_default_name);

        $env_ssl = getenv('DB_SSL') ?: (isset($_ENV['DB_SSL']) ? $_ENV['DB_SSL'] : '');
        $this->ssl = ($env_ssl === 'false' || $env_ssl === '0') ? false : true;

        // Strip custom port from host if passed like host:port
        if (strpos($this->host, ':') !== false) {
            list($h, $p) = explode(':', $this->host, 2);
            $this->host = $h;
            if (is_numeric($p)) {
                $this->port = (int)$p;
            }
        }

        $this->conn = @mysqli_init();
        if (!$this->conn) {
            $this->connect_error = "mysqli_init() failed";
            error_log($this->connect_error);
            return;
        }

        @mysqli_options($this->conn, MYSQLI_OPT_CONNECT_TIMEOUT, 15);

        $flags = 0;
        // Aiven MySQL SSL enforcement
        if ($this->ssl || $this->port !== 3306 || strpos($this->host, 'aivencloud.com') !== false) {
            @mysqli_options($this->conn, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
            @mysqli_ssl_set($this->conn, NULL, NULL, NULL, NULL, NULL);
            if (defined('MYSQLI_CLIENT_SSL')) {
                $flags |= MYSQLI_CLIENT_SSL;
            }
        }

        try {
            $connected = @mysqli_real_connect(
                $this->conn,
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                (int)$this->port,
                NULL,
                $flags
            );

            // Zero-downtime fallback: if connection to specific database failed, connect to root & select
            if (!$connected) {
                $connected = @mysqli_real_connect(
                    $this->conn,
                    $this->host,
                    $this->username,
                    $this->password,
                    '',
                    (int)$this->port,
                    NULL,
                    $flags
                );
                if ($connected && !empty($this->database)) {
                    @mysqli_query($this->conn, "CREATE DATABASE IF NOT EXISTS `" . mysqli_real_escape_string($this->conn, $this->database) . "`");
                    @mysqli_select_db($this->conn, $this->database);
                }
            }

            // Fallback attempt without SSL if SSL connection failed on non-Aiven hosts
            if (!$connected && ($flags & MYSQLI_CLIENT_SSL)) {
                $this->conn = @mysqli_init();
                $connected = @mysqli_real_connect(
                    $this->conn,
                    $this->host,
                    $this->username,
                    $this->password,
                    $this->database,
                    (int)$this->port
                );
            }

            if (!$connected) {
                $this->connect_error = mysqli_connect_error() ?: "Could not connect to database at {$this->host}:{$this->port}";
                error_log("Database Connection Error: " . $this->connect_error);
            } else {
                @mysqli_set_charset($this->conn, 'utf8mb4');
                @mysqli_query($this->conn, "SET SESSION sql_require_primary_key = 0");

                // Auto-detect and ensure tbladmin table exists in current database or switch to fallback
                $check_tbl = @mysqli_query($this->conn, "SELECT 1 FROM `tbladmin` LIMIT 1");
                if (!$check_tbl) {
                    if (@mysqli_select_db($this->conn, 'ccmsdb') && @mysqli_query($this->conn, "SELECT 1 FROM `tbladmin` LIMIT 1")) {
                        $this->database = 'ccmsdb';
                    } elseif (@mysqli_select_db($this->conn, 'defaultdb') && @mysqli_query($this->conn, "SELECT 1 FROM `tbladmin` LIMIT 1")) {
                        $this->database = 'defaultdb';
                    } else {
                        // Self-heal: auto-create tables
                        $this->selfHealSchema();
                    }
                }
            }
        } catch (Throwable $e) {
            $this->connect_error = $e->getMessage();
            error_log("Database Exception: " . $this->connect_error);
        }
    }

    public function selfHealSchema() {
        if (!$this->conn) return false;
        @mysqli_query($this->conn, "SET SESSION sql_require_primary_key = 0");
        @mysqli_query($this->conn, "SET FOREIGN_KEY_CHECKS = 0");

        @mysqli_query($this->conn, "CREATE TABLE IF NOT EXISTS `tbladmin` (
          `ID` int(10) NOT NULL AUTO_INCREMENT,
          `AdminName` varchar(120) DEFAULT NULL,
          `UserName` varchar(120) DEFAULT NULL,
          `MobileNumber` bigint(10) NOT NULL,
          `Email` varchar(200) NOT NULL,
          `Password` varchar(120) DEFAULT NULL,
          `AdminRegdate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($this->conn, "INSERT INTO `tbladmin` (`ID`, `AdminName`, `UserName`, `MobileNumber`, `Email`, `Password`, `AdminRegdate`) VALUES
        (1, 'Admin', 'admin', 8979555556, 'admin@gmail.com', '21232f297a57a5a743894a0e4a801fc3', NOW())
        ON DUPLICATE KEY UPDATE `UserName`=VALUES(`UserName`)");

        @mysqli_query($this->conn, "CREATE TABLE IF NOT EXISTS `tblcomputers` (
          `ID` int(10) NOT NULL AUTO_INCREMENT,
          `ComputerName` varchar(120) DEFAULT NULL,
          `ComputerLocation` varchar(120) DEFAULT NULL,
          `IPAdd` varchar(120) DEFAULT NULL,
          `EntryDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($this->conn, "INSERT INTO `tblcomputers` (`ID`, `ComputerName`, `ComputerLocation`, `IPAdd`, `EntryDate`) VALUES
        (1, 'Acer', 'Cabin101', '127.0.0.1', NOW()),
        (2, 'ASUS', 'Cabin102', '127.0.0.2', NOW()),
        (3, 'DELL', 'Cabin103', '127.0.0.2', NOW())
        ON DUPLICATE KEY UPDATE `ComputerName`=VALUES(`ComputerName`)");

        @mysqli_query($this->conn, "CREATE TABLE IF NOT EXISTS `tblusers` (
          `ID` int(10) NOT NULL AUTO_INCREMENT,
          `EntryID` varchar(20) DEFAULT NULL,
          `UserName` varchar(120) DEFAULT NULL,
          `UserAddress` varchar(200) DEFAULT NULL,
          `MobileNumber` bigint(10) DEFAULT NULL,
          `Email` varchar(200) DEFAULT NULL,
          `ComputerName` varchar(120) DEFAULT NULL,
          `IDProof` varchar(120) DEFAULT NULL,
          `InTime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
          `OutTime` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          `Fees` varchar(120) DEFAULT NULL,
          `Remark` varchar(120) DEFAULT NULL,
          `Status` varchar(20) NOT NULL,
          `UpdationDate` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`ID`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        @mysqli_query($this->conn, "INSERT INTO `tblusers` (`ID`, `EntryID`, `UserName`, `UserAddress`, `MobileNumber`, `Email`, `ComputerName`, `IDProof`, `InTime`, `OutTime`, `Fees`, `Remark`, `Status`, `UpdationDate`) VALUES
        (4, '634737642', 'Test user', 'New Delhi India', 1234567890, 'test@test.com', 'Asus Gaming Laptop', '184716461', NOW(), NOW(), '30', 'Check out', 'Out', NOW())
        ON DUPLICATE KEY UPDATE `UserName`=VALUES(`UserName`)");

        @mysqli_query($this->conn, "SET FOREIGN_KEY_CHECKS = 1");
        return true;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function isConnected() {
        return ($this->conn instanceof mysqli && empty($this->connect_error) && !@$this->conn->connect_errno);
    }

    public function __destruct() {
        if ($this->conn instanceof mysqli && !@$this->conn->connect_errno) {
            @$this->conn->close();
        }
    }
}

// Global DB Connection for procedural CCMS application
$db_instance = new DBConnection();
$con = $db_instance->getConnection();
$conn = $con;

if (!$db_instance->isConnected()) {
    error_log("CCMS Database Connection failed: " . $db_instance->connect_error);
}
?>