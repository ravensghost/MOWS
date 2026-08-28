<?php
// theme.php - Global Theme Engine
// Ensure this is called after db.php is loaded so $pdo is available

$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_row = $theme_stmt->fetch();
$theme_data = $theme_row ? json_decode($theme_row['setting_value'], true) : [];

$bg_color      = $theme_data['bg_color']      ?? '#f4f4f9';
$text_color    = $theme_data['text_color']    ?? '#333333';
$primary_color = $theme_data['primary_color'] ?? '#2c3e50';
$accent_color  = $theme_data['accent_color']  ?? '#18bc9c';
?>
<style>
    :root {
        --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
        --text-color: <?php echo htmlspecialchars($text_color); ?>;
        --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
        --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
    }
</style>