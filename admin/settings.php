<?php
// admin/settings.php - Global Platform Settings
session_start();
require_once '../db.php';
require_once '../terms.php';

// Restrict access to Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$success = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Expected setting keys
    $keys_to_update = ['site_name', 'discord_webhook_url', 'registration_open'];

    try {
        $update_stmt = $pdo->prepare("UPDATE platform_settings SET setting_value = ? WHERE setting_key = ?");
        
        foreach ($keys_to_update as $key) {
            if (isset($_POST[$key])) {
                $update_stmt->execute([trim($_POST[$key]), $key]);
            }
        }
        $success = "Platform settings updated successfully!";
    } catch (PDOException $e) {
        $error = "An error occurred while saving settings.";
    }
}

// Fetch current settings to populate the form
$settings_raw = $pdo->query("SELECT setting_key, setting_value FROM platform_settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; color: #333; }
        .admin-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border-top: 5px solid #2c3e50; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        .form-group { margin-bottom: 25px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #2c3e50; }
        .helper-text { font-size: 0.85em; color: #666; margin-bottom: 8px; display: block; }
        input[type="text"], select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        
        button { padding: 12px 25px; background: #18bc9c; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        button:hover { background: #128f76; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; font-weight: bold; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; font-weight: bold; }
        
        a.back-btn { color: #18bc9c; text-decoration: none; font-weight: bold; display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="admin-container">
    <a href="../index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h2>Platform Settings</h2>
    
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <form action="settings.php" method="POST">
        
        <div class="form-group">
            <label>Platform Name</label>
            <span class="helper-text">Displayed on the login screen, emails, and global headers.</span>
            <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'MOWS Engine'); ?>" required>
        </div>

        <div class="form-group">
            <label>Discord Webhook URL</label>
            <span class="helper-text">Paste the full URL from your Discord Server Settings > Integrations > Webhooks. Leave blank to disable bot notifications.</span>
            <input type="text" name="discord_webhook_url" value="<?php echo htmlspecialchars($settings['discord_webhook_url'] ?? ''); ?>" placeholder="https://discord.com/api/webhooks/...">
        </div>

        <div class="form-group">
            <label>New User Registrations</label>
            <span class="helper-text">Toggle whether new players can currently sign up for your game.</span>
            <select name="registration_open">
                <option value="1" <?php echo (isset($settings['registration_open']) && $settings['registration_open'] == '1') ? 'selected' : ''; ?>>Open - Anyone can register</option>
                <option value="0" <?php echo (isset($settings['registration_open']) && $settings['registration_open'] == '0') ? 'selected' : ''; ?>>Closed - Halt new accounts</option>
            </select>
        </div>

        <button type="submit">Save Settings</button>
    </form>
</div>

</body>
</html>