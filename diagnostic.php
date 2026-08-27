<?php
// diagnostic.php - System & Environment Check
session_start();

$checks = [];

// 1. PHP Version Check
$php_version = phpversion();
$checks['PHP Version (>= 7.4)'] = [
    'status' => version_compare($php_version, '7.4.0', '>='),
    'message' => "Running PHP " . $php_version
];

// 2. Required Extensions
$checks['PDO MySQL Extension'] = [
    'status' => extension_loaded('pdo_mysql'),
    'message' => extension_loaded('pdo_mysql') ? 'Loaded' : 'Missing pdo_mysql'
];
$checks['JSON Extension'] = [
    'status' => extension_loaded('json'),
    'message' => extension_loaded('json') ? 'Loaded' : 'Missing json'
];

// 3. Directory Permissions
$avatar_dir = 'uploads/avatars';
if (!is_dir($avatar_dir)) {
    // Attempt to create it if it doesn't exist
    @mkdir($avatar_dir, 0755, true);
}
$checks['Avatar Upload Directory'] = [
    'status' => is_dir($avatar_dir) && is_writable($avatar_dir),
    'message' => (is_dir($avatar_dir) && is_writable($avatar_dir)) ? 'Writable (/uploads/avatars)' : 'Not Writable or Missing. Please create /uploads/avatars and set permissions to 755 or 777.'
];

// 4. Database Connection & Schema
$db_connected = false;
$missing_tables = [];
$expected_tables = [
    'users', 'characters', 'departments', 'positions', 
    'system_form_schemas', 'platform_settings', 'missions', 
    'posts', 'private_messages', 'tech_specs'
];

try {
    if (file_exists('db.php')) {
        require_once 'db.php';
        $db_connected = true;
        
        // Check Tables
        $stmt = $pdo->query("SHOW TABLES");
        $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($expected_tables as $table) {
            if (!in_array($table, $existing_tables)) {
                $missing_tables[] = $table;
            }
        }
    } else {
        $checks['Database Connection'] = ['status' => false, 'message' => 'db.php file is missing.'];
    }
} catch (PDOException $e) {
    $checks['Database Connection'] = ['status' => false, 'message' => 'Connection Failed: ' . $e->getMessage()];
}

if ($db_connected) {
    $checks['Database Connection'] = ['status' => true, 'message' => 'Successfully connected to the database.'];
    $checks['Database Schema'] = [
        'status' => empty($missing_tables),
        'message' => empty($missing_tables) ? 'All required tables exist.' : 'Missing tables: ' . implode(', ', $missing_tables)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MOWS System Diagnostics</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; color: #333; padding: 40px; }
        .diagnostic-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-top: 5px solid #2c3e50; }
        h1 { margin-top: 0; color: #2c3e50; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .check-item { display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #eee; }
        .check-item:last-child { border-bottom: none; }
        .status-icon { font-size: 1.5em; margin-right: 15px; width: 30px; text-align: center; }
        .pass { color: #18bc9c; }
        .fail { color: #e74c3c; }
        .check-details h3 { margin: 0 0 5px 0; font-size: 1.1em; color: #2c3e50; }
        .check-details p { margin: 0; color: #666; font-size: 0.9em; }
        .warning-box { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; border: 1px solid #ffeeba; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

<div class="diagnostic-container">
    <h1>System Diagnostics</h1>
    
    <?php foreach ($checks as $title => $result): ?>
        <div class="check-item">
            <div class="status-icon <?php echo $result['status'] ? 'pass' : 'fail'; ?>">
                <?php echo $result['status'] ? '&#10004;' : '&#10008;'; ?>
            </div>
            <div class="check-details">
                <h3><?php echo htmlspecialchars($title); ?></h3>
                <p><?php echo htmlspecialchars($result['message']); ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="warning-box">
        &#9888; Security Notice: If all checks are passing, delete this file (diagnostic.php) immediately to prevent unauthorized users from viewing your server configuration.
    </div>
</div>

</body>
</html>