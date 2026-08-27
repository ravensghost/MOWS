<?php
// roster.php - Public Character Roster (With Avatars)
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

$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_data = json_decode($theme_stmt->fetch()['setting_value'], true);
$bg_color      = $theme_data['bg_color']      ?? '#f4f4f9';
$text_color    = $theme_data['text_color']    ?? '#333333';
$primary_color = $theme_data['primary_color'] ?? '#2c3e50';
$accent_color  = $theme_data['accent_color']  ?? '#18bc9c';

// Added c.avatar to the SELECT statement
$query = "
    SELECT 
        d.name AS dept_name, 
        p.name AS pos_name, 
        c.id AS char_id, 
        c.name AS char_name, 
        c.avatar,
        c.dynamic_data, 
        u.username
    FROM departments d
    LEFT JOIN positions p ON d.id = p.department_id
    LEFT JOIN characters c ON p.id = c.position_id AND c.is_active = 1
    LEFT JOIN users u ON c.user_id = u.id
    ORDER BY d.sort_order ASC, p.sort_order ASC
";
$roster_results = $pdo->query($query)->fetchAll();

$roster = [];
foreach ($roster_results as $row) {
    $dept = $row['dept_name'];
    $pos = $row['pos_name'];
    if (!isset($roster[$dept])) $roster[$dept] = [];
    if (!isset($roster[$dept][$pos])) $roster[$dept][$pos] = [];
    
    if ($row['char_id']) {
        $roster[$dept][$pos][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($user_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo get_term($pdo, 'roster', $user_lang); ?> - MOWS</title>
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
        body { font-family: Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); padding: 20px; }
        .roster-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .department-block { margin-bottom: 30px; border-left: 5px solid var(--primary-color); padding-left: 15px; }
        h2 { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 5px; margin-top: 0; }
        h3 { color: #555; margin-bottom: 10px; margin-top: 20px; font-size: 1.1em; }
        
        /* Flexbox adjustments for avatar integration */
        .character-card { background: #f8f9fa; padding: 15px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ddd; display: flex; gap: 15px; align-items: center; }
        .roster-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary-color); flex-shrink: 0; }
        .char-info-block { flex: 1; }
        
        .character-name { font-size: 1.2rem; font-weight: bold; }
        .character-name a { color: var(--primary-color); text-decoration: none; }
        .character-name a:hover { color: var(--accent-color); text-decoration: underline; }
        .bio-excerpt { font-size: 0.9rem; color: #666; margin-top: 8px; line-height: 1.4; }
        a.back-btn { display: inline-block; margin-bottom: 20px; color: var(--accent-color); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="roster-container">
    <a href="index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h1><?php echo get_term($pdo, 'library', $user_lang); ?> <?php echo get_term($pdo, 'roster', $user_lang); ?></h1>

    <?php if (empty($roster)): ?>
        <p>The roster is currently empty.</p>
    <?php else: ?>
        <?php foreach ($roster as $dept_name => $positions): ?>
            <div class="department-block">
                <h2><?php echo get_term($pdo, 'department', $user_lang); ?>: <?php echo htmlspecialchars($dept_name); ?></h2>
                
                <?php foreach ($positions as $pos_name => $characters): ?>
                    <h3><?php echo htmlspecialchars($pos_name); ?></h3>
                    
                    <?php if (empty($characters)): ?>
                        <div class="character-card"><em>Vacant</em></div>
                    <?php else: ?>
                        <?php foreach ($characters as $char): ?>
                            <?php 
                                $char_data = json_decode($char['dynamic_data'], true) ?: [];
                                $excerpt = $char_data['personality_overview'] ?? $char_data['physical_desc'] ?? 'Service record on file.';
                                $excerpt = substr($excerpt, 0, 150) . (strlen($excerpt) > 150 ? '...' : '');
                            ?>
                            <div class="character-card">
                                <img src="uploads/avatars/<?php echo htmlspecialchars($char['avatar'] ?? 'default_avatar.png'); ?>" class="roster-avatar" alt="Avatar">
                                <div class="char-info-block">
                                    <div class="character-name">
                                        <a href="character_profile.php?id=<?php echo $char['char_id']; ?>">
                                            <?php echo htmlspecialchars($char['char_name']); ?>
                                        </a>
                                    </div>
                                    <div class="bio-excerpt">
                                        <strong>Player:</strong> <?php echo htmlspecialchars($char['username']); ?><br>
                                        <strong>File Excerpt:</strong> <?php echo htmlspecialchars($excerpt); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>