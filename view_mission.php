<?php
// view_mission.php - Read the Story Thread (Paginated)
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

$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$mission_id) die("Error: Mission ID missing.");

// Fetch Mission Data
$stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ?");
$stmt->execute([$mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) die("Error: Mission not found.");

// --- Pagination Logic ---
$posts_per_page = 15;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Count total posts to calculate pages
$count_stmt = $pdo->prepare("SELECT COUNT(id) FROM posts WHERE mission_id = ?");
$count_stmt->execute([$mission_id]);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);
if ($total_pages == 0) $total_pages = 1; // Default to at least 1 page if empty

if ($current_page > $total_pages) $current_page = $total_pages;

$offset = ($current_page - 1) * $posts_per_page;

// Fetch posts for the current page
// Variables are cast to integers above, making it safe to interpolate directly into the LIMIT clause
$post_stmt = $pdo->prepare("
    SELECT p.*, c.name AS char_name, c.avatar, d.name AS dept_name, u.username
    FROM posts p 
    JOIN characters c ON p.character_id = c.id 
    LEFT JOIN departments d ON c.department_id = d.id
    JOIN users u ON p.user_id = u.id 
    WHERE p.mission_id = ? 
    ORDER BY p.created_at ASC
    LIMIT $offset, $posts_per_page
");
$post_stmt->execute([$mission_id]);
$posts = $post_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Theme
$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_data = json_decode($theme_stmt->fetch()['setting_value'], true);
$bg_color      = $theme_data['bg_color']      ?? '#f4f4f9';
$text_color    = $theme_data['text_color']    ?? '#333333';
$primary_color = $theme_data['primary_color'] ?? '#2c3e50';
$accent_color  = $theme_data['accent_color']  ?? '#18bc9c';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($user_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($mission['title']); ?> - MOWS</title>
    <!-- Include Quill Core CSS for rendering blockquotes and lists properly -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.core.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
        body { font-family: Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); padding: 20px; line-height: 1.6; }
        .thread-container { max-width: 1000px; margin: 0 auto; }
        
        .mission-header { background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; border-top: 5px solid var(--primary-color); }
        .mission-header h1 { margin: 0 0 10px 0; color: var(--primary-color); }
        
        .post-card { display: flex; background: #fff; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; }
        .post-author { flex: 0 0 220px; background: #f8f9fa; padding: 20px; border-right: 1px solid #eee; text-align: center; }
        .post-author .char-name { font-weight: bold; font-size: 1.1rem; color: var(--primary-color); margin-bottom: 5px; }
        .post-author .char-dept { font-size: 0.9rem; color: #666; margin-bottom: 15px; }
        .post-author .player-name { font-size: 0.85rem; color: #888; border-top: 1px solid #ddd; padding-top: 10px; }
        .post-author img { width: 120px; height: 120px; object-fit: cover; border-radius: 50%; margin-bottom: 10px; border: 3px solid #eee; }
        
        .post-body { flex: 1; padding: 20px 30px; }
        .post-meta { font-size: 0.85rem; color: #999; margin-bottom: 15px; text-align: right; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .post-content { color: #333; font-size: 1.05rem; }
        
        /* Pagination Controls */
        .pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin: 30px 0; font-weight: bold; }
        .pagination a { padding: 8px 12px; background: #fff; border: 1px solid #ccc; color: var(--primary-color); text-decoration: none; border-radius: 4px; }
        .pagination a:hover { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }
        .pagination span { padding: 8px 12px; color: #777; }
        
        .action-bar { text-align: right; margin-bottom: 40px; }
        a.btn { display: inline-block; padding: 12px 25px; background: var(--accent-color); color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
        a.btn:hover { opacity: 0.9; }
        a.back-btn { color: var(--accent-color); text-decoration: none; font-weight: bold; margin-bottom: 20px; display: inline-block; }
        
    </style>
</head>
<body>

<div class="thread-container">
    <a href="missions.php" class="back-btn">&larr; Back to <?php echo get_term($pdo, 'bookcase', $user_lang); ?></a>
    
    <div class="mission-header">
        <h1><?php echo htmlspecialchars($mission['title']); ?></h1>
        <p><strong>Status:</strong> <?php echo ucfirst($mission['status']); ?></p>
        <div><?php echo nl2br(htmlspecialchars($mission['description'])); ?></div>
    </div>

    <!-- Top Pagination (Only show if multiple pages) -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=1">&laquo; First</a>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=<?php echo $current_page - 1; ?>">Prev</a>
            <?php endif; ?>
            
            <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=<?php echo $current_page + 1; ?>">Next</a>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=<?php echo $total_pages; ?>">Last &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div style="text-align: center; padding: 40px; background: #fff; border-radius: 8px; color: #777;">
            The story hasn't started yet. Be the first to write a post!
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post-card">
                <div class="post-author">
                    <div class="post-author">
                    <img src="uploads/avatars/<?php echo htmlspecialchars($post['avatar'] ?? 'default_avatar.png'); ?>" alt="Avatar">
                    <div class="char-name"><?php echo htmlspecialchars($post['char_name']); ?></div>
                    <div class="char-dept"><?php echo htmlspecialchars($post['dept_name'] ?? 'Unassigned'); ?></div>
                    <div class="player-name">Played by: <?php echo htmlspecialchars($post['username']); ?></div>
                </div>
                    <div class="char-name"><?php echo htmlspecialchars($post['char_name']); ?></div>
                    <div class="char-dept"><?php echo htmlspecialchars($post['dept_name'] ?? 'Unassigned'); ?></div>
                    <div class="player-name">Played by: <?php echo htmlspecialchars($post['username']); ?></div>
                </div>
                <div class="post-body">
                    <div class="post-meta">Posted on <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?></div>
                    <!-- Outputting directly from DB. Quill HTML handles the formatting. -->
                    <div class="post-content ql-editor">
                        <?php echo $post['post_content']; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Bottom Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=1">&laquo; First</a>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=<?php echo $current_page - 1; ?>">Prev</a>
            <?php endif; ?>
            
            <span>Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></span>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=<?php echo $current_page + 1; ?>">Next</a>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>&page=<?php echo $total_pages; ?>">Last &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Action Bar -->
    <?php if (isset($_SESSION['user_id']) && $mission['status'] === 'active'): ?>
        <div class="action-bar">
            <!-- Ensure new posts route the user back to the LAST page of the thread -->
            <a href="write_post.php?mission_id=<?php echo $mission['id']; ?>" class="btn">+ Write a Reply</a>
        </div>
    <?php elseif ($mission['status'] !== 'active'): ?>
        <div class="action-bar" style="color: #777; font-style: italic;">
            This storyline is closed to new replies.
        </div>
    <?php endif; ?>
</div>

</body>
</html>