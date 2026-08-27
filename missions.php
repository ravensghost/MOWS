<?php
// missions.php - Public Collaborative Storytelling Board
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

// Fetch all missions
$missions = $pdo->query("SELECT m.*, COUNT(p.id) AS post_count FROM missions m LEFT JOIN posts p ON m.id = p.mission_id GROUP BY m.id ORDER BY m.created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($user_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo get_term($pdo, 'bookcase', $user_lang); ?> - MOWS</title>
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
        body { font-family: Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); padding: 20px; }
        .missions-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .mission-card { background: #f8f9fa; border: 1px solid #ddd; padding: 20px; margin-bottom: 15px; border-radius: 6px; border-left: 5px solid var(--accent-color); }
        .mission-title { font-size: 1.3rem; font-weight: bold; color: var(--primary-color); text-decoration: none; display: block; margin-bottom: 8px; }
        .mission-title:hover { color: var(--accent-color); }
        .mission-meta { font-size: 0.9rem; color: #666; margin-bottom: 10px; }
        .mission-desc { color: #444; line-height: 1.5; }
        a.back-btn { display: inline-block; margin-bottom: 20px; color: var(--accent-color); text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="missions-container">
    <a href="index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h1>Active <?php echo get_term($pdo, 'bookcase', $user_lang); ?> Chapters</h1>

    <?php if (empty($missions)): ?>
        <p>No storyline arcs have been initiated yet.</p>
    <?php else: ?>
        <?php foreach ($missions as $mission): ?>
            <div class="mission-card">
                <a href="view_mission.php?id=<?php echo $mission['id']; ?>" class="mission-title">
                    <?php echo htmlspecialchars($mission['title']); ?>
                </a>
                <div class="mission-meta">
                    Status: <strong><?php echo ucfirst($mission['status']); ?></strong> &bull; 
                    Posts: <?php echo $mission['post_count']; ?> &bull; 
                    Started: <?php echo date('M j, Y', strtotime($mission['created_at'])); ?>
                </div>
                <div class="mission-desc">
                    <?php echo nl2br(htmlspecialchars(substr($mission['description'], 0, 200))); ?>
                    <?php echo strlen($mission['description']) > 200 ? '...' : ''; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>