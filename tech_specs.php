<?php
// tech_specs.php
require_once 'auth.php';
require_once 'terms.php';

$user_id = $current_user['id'];

$user_lang = 'en';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT language_preference FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) $user_lang = $user['language_preference'];
}

// Fetch theme settings safely
$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_row = $theme_stmt->fetch();
$theme_data = $theme_row ? json_decode($theme_row['setting_value'], true) : [];

$bg_color      = $theme_data['bg_color']      ?? '#f4f4f9';
$text_color    = $theme_data['text_color']    ?? '#333333';
$primary_color = $theme_data['primary_color'] ?? '#2c3e50';
$accent_color  = $theme_data['accent_color']  ?? '#18bc9c';

// Fetch all database entries and group by category
$specs_raw = $pdo->query("SELECT id, title, category FROM tech_specs ORDER BY category ASC, title ASC")->fetchAll(PDO::FETCH_ASSOC);

$specs = [];
foreach ($specs_raw as $row) {
    $specs[$row['category']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($user_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title>Database & Lore - MOWS</title>
<!-- Notice the ../ to point back to the root folder -->
    <link rel="stylesheet" href="style.css"> 
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

<div class="directory-container">
    <a href="index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h1>Platform Database</h1>

    <?php if (empty($specs)): ?>
        <p>The database is currently empty.</p>
    <?php else: ?>
        <?php foreach ($specs as $category => $entries): ?>
            <div class="category-block">
                <div class="category-title"><?php echo htmlspecialchars($category); ?></div>
                <div class="entry-grid">
                    <?php foreach ($entries as $entry): ?>
                        <div class="entry-card">
                            <a href="view_tech_spec.php?id=<?php echo $entry['id']; ?>">
                                <?php echo htmlspecialchars($entry['title']); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>