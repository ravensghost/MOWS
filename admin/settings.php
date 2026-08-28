<?php
// admin/settings.php - Global Platform Settings
require_once '../auth.php'; // Uses your new global auth

// Restrict access to Admins
if (!isset($current_user) || !in_array($current_user['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$success = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

// Fetch current settings to populate the form and theme
$settings_raw = $pdo->query("SELECT setting_key, setting_value FROM platform_settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($settings_raw as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Extract Theme Colors for the CSS variables
$theme_data = isset($settings['platform_theme']) ? json_decode($settings['platform_theme'], true) : [];
$bg_color      = $theme_data['bg_color']      ?? '#f4f4f9';
$text_color    = $theme_data['text_color']    ?? '#333333';
$primary_color = $theme_data['primary_color'] ?? '#2c3e50';
$accent_color  = $theme_data['accent_color']  ?? '#18bc9c';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Settings - Admin</title>
    <!-- Notice the ../ to point back to the root folder -->
    <link rel="stylesheet" href="../style.css"> 
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
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