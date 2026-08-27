<?php
// tech_specs.php - Public Database Directory
session_start();
require_once 'db.php';
require_once 'terms.php';

$user_lang = 'en';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT language_preference FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) $user_lang = $user['language_preference'];
}

// Fetch theme settings
$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_data = json_decode($theme_stmt->fetch()['setting_value'], true);
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
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
        body { font-family: Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); padding: 20px; }
        .directory-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: var(--primary-color); border-bottom: 2px solid var(--accent-color); padding-bottom: 10px; margin-top: 0; }
        .category-block { margin-bottom: 30px; }
        .category-title { background: #f8f9fa; padding: 10px 15px; border-left: 5px solid var(--primary-color); color: var(--primary-color); font-size: 1.2em; margin-bottom: 15px; font-weight: bold; border-radius: 0 4px 4px 0; }
        
        .entry-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; }
        .entry-card { border: 1px solid #eee; padding: 15px; border-radius: 6px; transition: 0.2s; background: #fff; }
        .entry-card:hover { border-color: var(--accent-color); box-shadow: 0 2px 8px rgba(0,0,0,0.05); transform: translateY(-2px); }
        .entry-card a { color: var(--primary-color); text-decoration: none; font-weight: bold; display: block; font-size: 1.1em; }
        .entry-card a:hover { color: var(--accent-color); }
        
        a.back-btn { display: inline-block; margin-bottom: 20px; color: var(--accent-color); text-decoration: none; font-weight: bold; }
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