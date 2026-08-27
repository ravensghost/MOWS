<?php
// admin/theme.php - Edit Platform Theme
session_start();

// Notice the ../ to jump out of the admin folder and find the core files
require_once '../db.php';
require_once '../terms.php'; 

// Restrict access to Admins and System Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $theme_data = [
        'bg_color'      => $_POST['bg_color'],
        'text_color'    => $_POST['text_color'],
        'primary_color' => $_POST['primary_color'],
        'accent_color'  => $_POST['accent_color']
    ];
    
    $json_theme = json_encode($theme_data);
    $stmt = $pdo->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = 'platform_theme'");
    
    if ($stmt->execute([$json_theme])) {
        $success = "Theme updated successfully! The changes are now live.";
    }
}

// Load current theme data
$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_row = $theme_stmt->fetch();
$current_theme = json_decode($theme_row['setting_value'], true);

$bg_color      = $current_theme['bg_color']      ?? '#f4f4f9';
$text_color    = $current_theme['text_color']    ?? '#333333';
$primary_color = $current_theme['primary_color'] ?? '#2c3e50';
$accent_color  = $current_theme['accent_color']  ?? '#18bc9c';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Theme - MOWS Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: <?php echo htmlspecialchars($bg_color); ?>; color: <?php echo htmlspecialchars($text_color); ?>; padding: 20px; }
        .admin-container { max-width: 500px; margin: 0 auto; background: #fff; color: #333; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-top: 5px solid <?php echo htmlspecialchars($primary_color); ?>; }
        .form-group { margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        label { font-weight: bold; }
        input[type="color"] { border: none; width: 50px; height: 40px; cursor: pointer; padding: 0; background: none; }
        button { width: 100%; padding: 10px; background: <?php echo htmlspecialchars($primary_color); ?>; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { opacity: 0.9; }
        .success { color: green; margin-bottom: 15px; font-weight: bold; text-align: center; }
        a.back-btn { display: inline-block; margin-bottom: 15px; color: <?php echo htmlspecialchars($accent_color); ?>; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="admin-container">
    <!-- Notice the link points back to the root index -->
    <a href="../index.php" class="back-btn">&larr; Back to Main Dashboard</a>
    <h2>Platform Theme Settings</h2>
    
    <?php if ($success): ?>
        <div class="success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form action="theme.php" method="POST">
        <div class="form-group">
            <label for="bg_color">Background Color</label>
            <input type="color" id="bg_color" name="bg_color" value="<?php echo htmlspecialchars($bg_color); ?>">
        </div>
        <div class="form-group">
            <label for="text_color">Main Text Color</label>
            <input type="color" id="text_color" name="text_color" value="<?php echo htmlspecialchars($text_color); ?>">
        </div>
        <div class="form-group">
            <label for="primary_color">Primary Brand Color</label>
            <input type="color" id="primary_color" name="primary_color" value="<?php echo htmlspecialchars($primary_color); ?>">
        </div>
        <div class="form-group">
            <label for="accent_color">Accent Color (Links)</label>
            <input type="color" id="accent_color" name="accent_color" value="<?php echo htmlspecialchars($accent_color); ?>">
        </div>
        
        <button type="submit">Save Theme</button>
    </form>
</div>

</body>
</html>