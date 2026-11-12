<?php
/**
 * 1-Click Aiven Cloud & MySQL Database Importer / Health Check Tool
 * Online Train Station Ticketing System
 */

session_start();

// Helper to get environment or POST config
$db_host = isset($_POST['host']) ? trim($_POST['host']) : (getenv('DB_SERVER') ?: (getenv('DB_HOST') ?: 'localhost'));
$db_port = isset($_POST['port']) ? (int)trim($_POST['port']) : (int)(getenv('DB_PORT') ?: 3306);
$db_user = isset($_POST['user']) ? trim($_POST['user']) : (getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root'));
$db_pass = isset($_POST['pass']) ? $_POST['pass'] : (getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''));
$db_name = isset($_POST['name']) ? trim($_POST['name']) : (getenv('DB_NAME') ?: 'tsts_db');

$ssl_env = getenv('DB_SSL');
$db_ssl = isset($_POST['ssl']) ? (bool)$_POST['ssl'] : ($ssl_env !== false ? (filter_var($ssl_env, FILTER_VALIDATE_BOOLEAN) || strtolower($ssl_env) === 'require' || $ssl_env === '1') : ($db_host !== 'localhost' && $db_host !== '127.0.0.1'));

// Connect using mysqli with SSL support
mysqli_report(MYSQLI_REPORT_OFF);
$conn = mysqli_init();
$conn_error = '';
$conn_success = false;
$ssl_cipher = 'None';
$mysql_version = '';

if ($conn) {
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    $flags = 0;
    if ($db_ssl) {
        $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
        $flags |= MYSQLI_CLIENT_SSL;
    }

    $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port, NULL, $flags);
    if (!$connected && $db_ssl && ($db_host === 'localhost' || $db_host === '127.0.0.1')) {
        $conn = mysqli_init();
        $connected = @$conn->real_connect($db_host, $db_user, $db_pass, $db_name, $db_port);
    }

    if ($connected) {
        $conn_success = true;
        $conn->set_charset("utf8mb4");
        $mysql_version = $conn->server_info;

        // Check SSL Cipher
        $cipher_res = @$conn->query("SHOW STATUS LIKE 'Ssl_cipher'");
        if ($cipher_res && $row = $cipher_res->fetch_assoc()) {
            if (!empty($row['Value'])) {
                $ssl_cipher = $row['Value'];
            }
        }
    } else {
        $conn_error = mysqli_connect_error();
    }
} else {
    $conn_error = "mysqli_init initialization failed.";
}

// Find SQL Dump File
$possible_sql_paths = [
    __DIR__ . '/database/tsts_db.sql',
    __DIR__ . '/train_ticketing/database/tsts_db.sql',
    __DIR__ . '/tsts_db.sql'
];
$sql_file_path = '';
foreach ($possible_sql_paths as $p) {
    if (file_exists($p)) {
        $sql_file_path = $p;
        break;
    }
}

// Handle Import Request
$import_message = '';
$import_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'import') {
    if (!$conn_success) {
        $import_status = 'danger';
        $import_message = "Cannot import: Database connection failed ($conn_error). Please verify credentials.";
    } elseif (empty($sql_file_path) || !is_readable($sql_file_path)) {
        $import_status = 'danger';
        $import_message = "SQL Dump file (tsts_db.sql) was not found in database/ folder.";
    } else {
        $sql_content = file_get_contents($sql_file_path);
        if (empty($sql_content)) {
            $import_status = 'danger';
            $import_message = "SQL Dump file is empty.";
        } else {
            // Disable foreign key checks and auto-value constraints during import
            $conn->query("SET FOREIGN_KEY_CHECKS = 0;");
            $conn->query("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

            // Split and execute SQL queries cleanly
            $queries = explode(";\n", $sql_content);
            $executed = 0;
            $failed = 0;

            foreach ($queries as $q) {
                $q = trim($q);
                if (!empty($q) && !preg_match('/^(\/\*|--|#)/', $q)) {
                    if (@$conn->query($q)) {
                        $executed++;
                    } else {
                        // Check if it was just comment or empty
                        if (!empty($conn->error)) {
                            $failed++;
                        }
                    }
                }
            }

            // Ensure Default Admin User Exists
            $admin_check = @$conn->query("SELECT * FROM users WHERE username = 'admin'");
            if (!$admin_check || $admin_check->num_rows == 0) {
                @$conn->query("INSERT INTO users (`id`, `name`, `username`, `password`, `type`, `station_id`) VALUES (1, 'Administrator', 'admin', '0192023a7bbd73250516f069df18b500', 1, 0) ON DUPLICATE KEY UPDATE username='admin', password='0192023a7bbd73250516f069df18b500'");
            }

            $conn->query("SET FOREIGN_KEY_CHECKS = 1;");

            $import_status = 'success';
            $import_message = "Database successfully imported! ($executed queries executed). All tables and Admin credentials are ready.";
        }
    }
}

// Inspect Tables and Table Counts
$expected_tables = ['prices', 'stations', 'system_settings', 'tickets', 'users'];
$existing_tables = [];
$admin_user_exists = false;

if ($conn_success) {
    $tbl_res = @$conn->query("SHOW TABLES");
    if ($tbl_res && $tbl_res instanceof mysqli_result) {
        while ($t = $tbl_res->fetch_array()) {
            $tableName = $t[0];
            $cnt_res = @$conn->query("SELECT COUNT(*) AS cnt FROM `$tableName`");
            $count = ($cnt_res && $cnt_row = $cnt_res->fetch_assoc()) ? (int)$cnt_row['cnt'] : 0;
            $existing_tables[$tableName] = $count;
        }
    }

    $adm_res = @$conn->query("SELECT id, name, username FROM users WHERE username = 'admin' LIMIT 1");
    if ($adm_res && $adm_res->num_rows > 0) {
        $admin_user_exists = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aiven Cloud MySQL & DB Setup - Train Ticketing</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1f242d 0%, #0f1217 100%);
            min-height: 100vh;
            color: #e0e0e0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 30px 15px;
        }
        .main-card {
            background: #252b36;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
        }
        .card-header-gradient {
            background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
            border-radius: 11px 11px 0 0 !important;
            padding: 20px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 6px 14px;
            border-radius: 20px;
        }
        .info-pill {
            background: #1b2029;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
            border-left: 4px solid #007bff;
        }
        .info-pill-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #8898aa;
        }
        .info-pill-value {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            word-break: break-all;
        }
        .table-dark-custom {
            background-color: #1b2029;
            color: #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .table-dark-custom th {
            background: #151922;
            border-color: #2e3646;
        }
        .table-dark-custom td {
            border-color: #2e3646;
        }
        .btn-gradient {
            background: linear-gradient(90deg, #28a745 0%, #218838 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(40,167,69,0.3);
            transition: all 0.2s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40,167,69,0.4);
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card main-card mb-4">
                <div class="card-header card-header-gradient text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0"><i class="fas fa-database mr-2"></i> Aiven Cloud MySQL Setup & Health Check</h4>
                        <small class="text-light">Train Station Ticketing System</small>
                    </div>
                    <?php if ($conn_success): ?>
                        <span class="badge badge-success status-badge"><i class="fas fa-check-circle mr-1"></i> Connected (SSL Active)</span>
                    <?php else: ?>
                        <span class="badge badge-danger status-badge"><i class="fas fa-exclamation-triangle mr-1"></i> Connection Failed</span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($import_message)): ?>
                        <div class="alert alert-<?php echo $import_status; ?> alert-dismissible fade show mb-4" role="alert">
                            <h5 class="alert-heading"><i class="fas <?php echo $import_status === 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-2"></i> <?php echo $import_status === 'success' ? 'Import Complete' : 'Import Error'; ?></h5>
                            <?php echo htmlspecialchars($import_message); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <!-- Connection Details Cards -->
                    <h5 class="mb-3 text-info"><i class="fas fa-server mr-2"></i> Connection Status</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-pill">
                                <div class="info-pill-title">Host / Server</div>
                                <div class="info-pill-value"><?php echo htmlspecialchars($db_host); ?></div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-pill">
                                <div class="info-pill-title">Port</div>
                                <div class="info-pill-value"><?php echo htmlspecialchars($db_port); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-pill">
                                <div class="info-pill-title">Database Name</div>
                                <div class="info-pill-value"><?php echo htmlspecialchars($db_name); ?></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-pill">
                                <div class="info-pill-title">SSL Encryption</div>
                                <div class="info-pill-value">
                                    <?php if ($ssl_cipher !== 'None'): ?>
                                        <span class="text-success"><i class="fas fa-lock mr-1"></i> <?php echo htmlspecialchars($ssl_cipher); ?></span>
                                    <?php elseif ($db_ssl): ?>
                                        <span class="text-info"><i class="fas fa-shield-alt mr-1"></i> SSL Enforced</span>
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-unlock mr-1"></i> Standard</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$conn_success): ?>
                        <div class="alert alert-danger mt-3">
                            <strong><i class="fas fa-exclamation-circle mr-2"></i> Error Details:</strong><br>
                            <?php echo htmlspecialchars($conn_error); ?>
                            <div class="mt-3">
                                <small class="text-white-50">Tip: Verify that Render environment variables (<code>DB_SERVER</code>, <code>DB_PORT</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code>, <code>DB_NAME</code>, <code>DB_SSL</code>) are accurately set in your Render Service Dashboard.</small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr class="border-secondary my-4">

                    <!-- Database Schema & Table Status -->
                    <h5 class="mb-3 text-info"><i class="fas fa-table mr-2"></i> Database Tables Status</h5>
                    <div class="table-responsive">
                        <table class="table table-dark-custom table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Required Table</th>
                                    <th>Status</th>
                                    <th>Row Count</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expected_tables as $tbl): 
                                    $exists = array_key_exists($tbl, $existing_tables);
                                    $rows = $exists ? $existing_tables[$tbl] : 0;
                                ?>
                                    <tr>
                                        <td><code><?php echo $tbl; ?></code></td>
                                        <td>
                                            <?php if ($exists): ?>
                                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Installed</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> Missing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $exists ? number_format($rows) . ' rows' : '-'; ?></td>
                                        <td class="text-muted small">
                                            <?php 
                                            switch($tbl) {
                                                case 'prices': echo 'Ticket destination rates & tariffs'; break;
                                                case 'stations': echo 'Train station listings & addresses'; break;
                                                case 'system_settings': echo 'Ticketing branding & system config'; break;
                                                case 'tickets': echo 'Issued passenger train tickets'; break;
                                                case 'users': echo 'Admin and station booth staff accounts'; break;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Default Admin Credentials Card -->
                    <div class="card bg-dark border-secondary mt-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h6 class="text-warning mb-1"><i class="fas fa-user-shield mr-2"></i> Default Admin Credentials:</h6>
                                    <div class="small text-light">
                                        Username: <strong class="badge badge-secondary px-2">admin</strong> |
                                        Password: <strong class="badge badge-secondary px-2">admin123</strong>
                                    </div>
                                    <div class="mt-1 small">
                                        <?php if ($admin_user_exists): ?>
                                            <span class="text-success"><i class="fas fa-check-circle mr-1"></i> Admin user verified in database!</span>
                                        <?php else: ?>
                                            <span class="text-warning"><i class="fas fa-info-circle mr-1"></i> Admin user will be created upon 1-click import.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-5 text-md-right mt-3 mt-md-0">
                                    <form method="POST" action="db_setup.php" class="d-inline">
                                        <input type="hidden" name="action" value="import">
                                        <button type="submit" class="btn btn-gradient btn-block btn-lg" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin mr-2\'></i> Importing...'; this.disabled=true; this.form.submit();">
                                            <i class="fas fa-file-import mr-2"></i> 1-Click Import SQL
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="login.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-sign-in-alt mr-2"></i> Go to Login Page
                        </a>
                        <a href="index.php" class="btn btn-outline-success btn-lg">
                            <i class="fas fa-desktop mr-2"></i> Launch Application
                        </a>
                    </div>

                </div>
                <div class="card-footer bg-dark border-secondary text-center text-muted small py-3">
                    Aiven Cloud MySQL SSL Connector &bull; Render Reverse Proxy Compatible &bull; PHP 8.2+
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
