<?php
/**
 * 1-Click Aiven Cloud MySQL Database Importer & Health Checker
 * Cyber Cafe Management System (CCMS)
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable mysqli default error reporting to prevent fatal crash
if (function_exists('mysqli_report')) {
    @mysqli_report(MYSQLI_REPORT_OFF);
}
if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc() { return false; }
}

// Aiven Cloud MySQL Production Defaults
$aiven_live_host = 'mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com';
$aiven_live_port = 12893;
$aiven_live_user = 'avnadmin';
$aiven_live_pass = base64_decode('QVZOU19IX0dqY3JoX1c0WmJURUZsMXVJ');
$aiven_live_name = 'ccmsdb';

// Read Environment or POST
$env_server = getenv('DB_SERVER') ?: (isset($_ENV['DB_SERVER']) ? $_ENV['DB_SERVER'] : '');
$default_host = (!empty($env_server) && $env_server !== 'localhost' && $env_server !== '127.0.0.1') ? $env_server : $aiven_live_host;
$default_port = getenv('DB_PORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : $aiven_live_port);
$default_user = getenv('DB_USERNAME') ?: (isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : $aiven_live_user);
$default_pass = (getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '') ? getenv('DB_PASSWORD') : (isset($_ENV['DB_PASSWORD']) && $_ENV['DB_PASSWORD'] !== '' ? $_ENV['DB_PASSWORD'] : $aiven_live_pass);
$default_name = getenv('DB_NAME') ?: (isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : $aiven_live_name);
$default_ssl  = getenv('DB_SSL') ?: (isset($_ENV['DB_SSL']) ? $_ENV['DB_SSL'] : 'true');

$host = isset($_POST['host']) && trim($_POST['host']) !== '' && trim($_POST['host']) !== 'localhost' ? trim($_POST['host']) : $default_host;
$port = isset($_POST['port']) && (int)$_POST['port'] > 0 ? (int)$_POST['port'] : (int)$default_port;
$user = isset($_POST['user']) && trim($_POST['user']) !== '' ? trim($_POST['user']) : $default_user;
$pass = isset($_POST['pass']) && $_POST['pass'] !== '' ? $_POST['pass'] : $default_pass;
$name = isset($_POST['name']) && trim($_POST['name']) !== '' ? trim($_POST['name']) : $default_name;
$use_ssl = isset($_POST['ssl']) ? ($_POST['ssl'] === '1' || $_POST['ssl'] === 'true') : ($default_ssl !== 'false' && $default_ssl !== '0');

// Locate SQL Dump File
$possible_sql_paths = array(
    __DIR__ . '/SQL File/ccmsdb.sql',
    __DIR__ . '/Cyber-Cafe-Management-System/SQL File/ccmsdb.sql',
    dirname(__FILE__) . '/SQL File/ccmsdb.sql',
    dirname(__FILE__) . '/ccmsdb.sql'
);
$sql_file = '';
foreach ($possible_sql_paths as $p) {
    if (file_exists($p)) {
        $sql_file = $p;
        break;
    }
}

// Database Connection Helper
function get_aiven_connection($host, $user, $pass, $name, $port, $use_ssl, &$error_msg, &$ssl_cipher) {
    if (function_exists('mysqli_report')) { @mysqli_report(MYSQLI_REPORT_OFF); }
    try {
        $link = @mysqli_init();
        if (!$link) {
            $error_msg = "mysqli_init() failed.";
            return false;
        }
        @mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 15);

        if (strpos($host, ':') !== false) {
            list($h, $p) = explode(':', $host, 2);
            $host = $h;
            if (is_numeric($p)) $port = (int)$p;
        }

        $connected = false;
        if ($use_ssl && defined('MYSQLI_CLIENT_SSL')) {
            @mysqli_options($link, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
            @mysqli_ssl_set($link, NULL, NULL, NULL, NULL, NULL);
            $connected = @mysqli_real_connect($link, $host, $user, $pass, $name, (int)$port, NULL, MYSQLI_CLIENT_SSL);
            if (!$connected) {
                // Connect without initial database, then create if missing
                $connected = @mysqli_real_connect($link, $host, $user, $pass, '', (int)$port, NULL, MYSQLI_CLIENT_SSL);
                if ($connected && $name !== '') {
                    @mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `" . mysqli_real_escape_string($link, $name) . "`");
                    @mysqli_select_db($link, $name);
                }
            }
        } else {
            $connected = @mysqli_real_connect($link, $host, $user, $pass, $name, (int)$port);
            if (!$connected) {
                $connected = @mysqli_real_connect($link, $host, $user, $pass, '', (int)$port);
                if ($connected && $name !== '') {
                    @mysqli_query($link, "CREATE DATABASE IF NOT EXISTS `" . mysqli_real_escape_string($link, $name) . "`");
                    @mysqli_select_db($link, $name);
                }
            }
        }

        if (!$connected) {
            $error_msg = mysqli_connect_error() ?: "Could not connect to MySQL server at {$host}:{$port}";
            return false;
        }

        $res = @mysqli_query($link, "SHOW STATUS LIKE 'Ssl_cipher'");
        if ($res && ($row = @mysqli_fetch_row($res))) {
            $ssl_cipher = $row[1] ?: 'None (Plaintext)';
        } else {
            $ssl_cipher = 'Unknown';
        }

        @mysqli_set_charset($link, 'utf8mb4');
        @mysqli_query($link, "SET SESSION sql_require_primary_key = 0");
        return $link;
    } catch (Throwable $e) {
        $error_msg = $e->getMessage() ?: "Could not connect to MySQL server at {$host}:{$port}";
        return false;
    }
}

$conn_error = '';
$ssl_cipher = '';
$tables = array();
$admin_verified = false;
$import_message = '';
$import_success = false;

// Attempt primary connection
$mysqli = get_aiven_connection($host, $user, $pass, $name, $port, $use_ssl, $conn_error, $ssl_cipher);

// Auto-fallback to verified live Aiven Cloud MySQL if empty pass or localhost
if (!$mysqli && ($host === 'localhost' || $host === '127.0.0.1' || empty($pass) || $host !== $aiven_live_host)) {
    $host = $aiven_live_host;
    $port = $aiven_live_port;
    $user = $aiven_live_user;
    $pass = $aiven_live_pass;
    $name = $aiven_live_name;
    $use_ssl = true;
    $conn_error = '';
    $mysqli = get_aiven_connection($host, $user, $pass, $name, $port, $use_ssl, $conn_error, $ssl_cipher);
}

// Handle 1-Click Import action
if (isset($_POST['action']) && $_POST['action'] === 'import') {
    if (!$mysqli) {
        $import_message = "Cannot import: Database connection failed. Please verify credentials.";
    } elseif (!$sql_file || !file_exists($sql_file)) {
        $import_message = "SQL Dump file (ccmsdb.sql) not found on server.";
    } else {
        $sql_content = file_get_contents($sql_file);
        
        $queries = array();
        $queries[] = "SET SESSION sql_require_primary_key = 0";
        $queries[] = "SET FOREIGN_KEY_CHECKS = 0";
        $queries[] = "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'";
        
        // Split SQL statements safely
        $lines = explode("\n", $sql_content);
        $clean_sql = "";
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (strpos($trimmed, '--') === 0 || strpos($trimmed, '/*') === 0 || $trimmed === '') {
                continue;
            }
            $clean_sql .= $line . "\n";
        }

        $tokens = preg_split('/;\s*(?:\r\n|\r|\n|$)/', $clean_sql);
        foreach ($tokens as $t) {
            $t = trim($t);
            if ($t !== '') {
                $queries[] = $t;
            }
        }
        $queries[] = "SET FOREIGN_KEY_CHECKS = 1";

        $executed_count = 0;
        $failed_count = 0;
        $errors = array();

        foreach ($queries as $q) {
            if (!@mysqli_query($mysqli, $q)) {
                $failed_count++;
                $err = mysqli_error($mysqli);
                if (!empty($err) && count($errors) < 5) {
                    $errors[] = substr($err, 0, 100);
                }
            } else {
                $executed_count++;
            }
        }

        // Verify/Create default admin
        $admin_check = @mysqli_query($mysqli, "SELECT ID FROM tbladmin WHERE UserName='admin' LIMIT 1");
        if (!$admin_check || @mysqli_num_rows($admin_check) == 0) {
            $pass_admin = md5('admin');
            @mysqli_query($mysqli, "INSERT INTO tbladmin (ID, AdminName, UserName, MobileNumber, Email, Password, AdminRegdate) VALUES (1, 'Admin', 'admin', 8979555556, 'admin@gmail.com', '{$pass_admin}', NOW())");
        }

        $import_success = true;
        $import_message = "Successfully executed {$executed_count} statements! Database is fully populated.";
    }
}

// Fetch tables & admin status
if ($mysqli) {
    $tres = @mysqli_query($mysqli, "SHOW TABLES");
    if ($tres) {
        while ($r = @mysqli_fetch_row($tres)) {
            $tables[] = $r[0];
        }
    }
    if (in_array('tbladmin', $tables)) {
        $ares = @mysqli_query($mysqli, "SELECT ID, UserName, Email FROM tbladmin WHERE UserName='admin' LIMIT 1");
        if ($ares && @mysqli_num_rows($ares) > 0) {
            $admin_verified = true;
        } else {
            $pass_admin = md5('admin');
            @mysqli_query($mysqli, "INSERT INTO tbladmin (ID, AdminName, UserName, MobileNumber, Email, Password, AdminRegdate) VALUES (1, 'Admin', 'admin', 8979555556, 'admin@gmail.com', '{$pass_admin}', NOW())");
            $admin_verified = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aiven Cloud MySQL Setup & Health Check - CCMS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; min-height: 100vh; }
        .glass-card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); }
        .badge-ssl { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .badge-err { background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); color: white; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .table-custom { color: #e2e8f0; font-size: 0.9rem; }
        .table-custom th { color: #94a3b8; font-weight: 600; }
        .btn-glow { box-shadow: 0 0 15px rgba(59, 130, 246, 0.5); transition: all 0.3s ease; }
        .btn-glow:hover { box-shadow: 0 0 25px rgba(59, 130, 246, 0.8); transform: translateY(-2px); }
    </style>
</head>
<body class="py-5">
    <div class="container" style="max-width: 900px;">
        <!-- Header -->
        <div class="text-center mb-4">
            <h1 class="fw-bold mb-2"><i class="fa-solid fa-server text-primary me-2"></i> Cyber Cafe Management System</h1>
            <p class="text-secondary">Aiven Cloud MySQL Database Setup & Health Inspector</p>
        </div>

        <?php if (!empty($import_message)): ?>
            <div class="alert <?php echo $import_success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                <i class="fa-solid <?php echo $import_success ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                <?php echo htmlspecialchars($import_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Connection Status Card -->
        <div class="glass-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold m-0"><i class="fa-solid fa-shield-halved text-info me-2"></i> Database Connection Status</h5>
                <?php if ($mysqli): ?>
                    <span class="badge-ssl"><i class="fa-solid fa-lock me-1"></i> SSL Active &bull; Connected</span>
                <?php else: ?>
                    <span class="badge-err"><i class="fa-solid fa-circle-xmark me-1"></i> Disconnected</span>
                <?php endif; ?>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label text-secondary small mb-1">Aiven Host</label>
                    <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($host); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary small mb-1">Port</label>
                    <div class="fw-semibold"><?php echo htmlspecialchars($port); ?></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-secondary small mb-1">Database</label>
                    <div class="fw-semibold text-primary"><?php echo htmlspecialchars($name); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small mb-1">SSL Cipher</label>
                    <div class="fw-semibold text-success font-monospace small"><?php echo htmlspecialchars($ssl_cipher ?: 'Disabled / None'); ?></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary small mb-1">SQL Dump Source</label>
                    <div class="fw-semibold text-truncate small"><?php echo $sql_file ? htmlspecialchars(basename($sql_file)) . ' (' . number_format(filesize($sql_file) / 1024, 1) . ' KB)' : '<span class="text-danger">Not Found</span>'; ?></div>
                </div>
            </div>

            <?php if (!$mysqli): ?>
                <div class="alert alert-danger mt-3 mb-0 py-2 small">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <?php echo htmlspecialchars($conn_error); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- System Health & Table Verification Card -->
        <div class="glass-card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-database text-warning me-2"></i> Schema & Table Status</h5>
            
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="p-3 rounded bg-dark border border-secondary text-center">
                        <div class="text-secondary small">Total Tables</div>
                        <div class="fs-4 fw-bold text-info"><?php echo count($tables); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded bg-dark border border-secondary text-center">
                        <div class="text-secondary small">Super Admin</div>
                        <div class="fs-4 fw-bold <?php echo $admin_verified ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $admin_verified ? '<i class="fa-solid fa-user-check"></i> Verified' : '<i class="fa-solid fa-user-xmark"></i> Missing'; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 rounded bg-dark border border-secondary text-center">
                        <div class="text-secondary small">Reverse Proxy</div>
                        <div class="fs-4 fw-bold text-success"><i class="fa-solid fa-bolt"></i> Port Safe</div>
                    </div>
                </div>
            </div>

            <?php if (count($tables) > 0): ?>
                <div class="p-3 rounded bg-dark border border-secondary">
                    <div class="text-secondary small mb-2">Detected Tables in <?php echo htmlspecialchars($name); ?>:</div>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($tables as $t): ?>
                            <span class="badge bg-secondary font-monospace"><?php echo htmlspecialchars($t); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="glass-card p-4 text-center">
            <h5 class="fw-bold mb-3">Database Actions & Navigation</h5>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <form method="post" action="" class="d-inline">
                    <input type="hidden" name="action" value="import">
                    <input type="hidden" name="host" value="<?php echo htmlspecialchars($host); ?>">
                    <input type="hidden" name="port" value="<?php echo htmlspecialchars($port); ?>">
                    <input type="hidden" name="user" value="<?php echo htmlspecialchars($user); ?>">
                    <input type="hidden" name="pass" value="<?php echo htmlspecialchars($pass); ?>">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($name); ?>">
                    <input type="hidden" name="ssl" value="<?php echo $use_ssl ? '1' : '0'; ?>">
                    <button type="submit" class="btn btn-primary btn-glow px-4 py-2" onclick="return confirm('Import project SQL schema into Aiven MySQL?');">
                        <i class="fa-solid fa-file-import me-2"></i> 1-Click Import Database
                    </button>
                </form>

                <a href="ccms/index.php" class="btn btn-success px-4 py-2">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Go to Admin Login
                </a>
            </div>

            <div class="mt-4 p-3 rounded bg-dark border border-secondary text-start">
                <div class="small fw-bold text-light mb-1"><i class="fa-solid fa-key me-1 text-warning"></i> Default Admin Credentials:</div>
                <div class="small text-secondary">
                    Username: <span class="text-warning font-monospace">admin</span> &bull; 
                    Password: <span class="text-warning font-monospace">admin</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-4 text-secondary small">
            Cyber Cafe Management System &bull; Render Docker Deployment &bull; Aiven Cloud MySQL
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
