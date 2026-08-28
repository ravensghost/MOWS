<?php
// index.php - Main Landing Page & User Dashboard
session_start();
require_once 'db.php';
require_once 'terms.php';

// Fetch Dynamic Theme Settings
$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_row = $theme_stmt->fetch();
$theme_data = $theme_row ? json_decode($theme_row['setting_value'], true) : [];

$bg_color      = $theme_data['bg_color']      ?? '#f4f4f9';
$text_color    = $theme_data['text_color']    ?? '#333333';
$primary_color = $theme_data['primary_color'] ?? '#2c3e50';
$accent_color  = $theme_data['accent_color']  ?? '#18bc9c';

// Determine if the user is authenticated
$is_logged_in = isset($_SESSION['user_id']);
$user_lang = 'en';

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    
    // Check status alongside normal data
    $stmt = $pdo->prepare("SELECT username, role, status, language_preference FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT username, role, status, language_preference FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Active Ban Check
    if (!$user || $user['status'] === 'banned') {
        session_unset();
        session_destroy();
        header("Location: login.php?banned=1");
        exit;
    }

    $user_lang = $user['language_preference'] ?? 'en';
    
    // ... rest of the character fetching query ...
    }

    if ($user) $user_lang = $user['language_preference'] ?? 'en';

    // Fetch user's active characters for the dashboard
    $char_stmt = $pdo->prepare("
        SELECT c.id, c.name, d.name AS dept_name, p.name AS pos_name 
        FROM characters c 
        LEFT JOIN departments d ON c.department_id = d.id 
        LEFT JOIN positions p ON c.position_id = p.id 
        WHERE c.user_id = ? AND c.is_active = 1
    ");
    $char_stmt->execute([$user_id]);
    $my_characters = $char_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($user_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_term($pdo, 'library', $user_lang); ?> Home</title>
	<!-- Link to global styles -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Dynamic Theme Injection -->
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

<header>
    <div class="logo"><?php echo get_term($pdo, 'library', $user_lang); ?> System</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="roster.php"><?php echo get_term($pdo, 'roster', $user_lang); ?></a>
        <a href="tech_specs.php">Database</a> <!-- NEW LINK -->
        <?php if ($is_logged_in): ?>
            <!-- Links shown only to users -->
            <a href="missions.php"><?php echo get_term($pdo, 'bookcase', $user_lang); ?></a>
            <a href="logout.php">Logout (<?php echo htmlspecialchars($user['username']); ?>)</a>
        <?php else: ?>
            <!-- Links shown only to guests -->
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <?php if ($is_logged_in): ?>
        <!-- ================= LOGGED IN DASHBOARD ================= -->
        <aside>
            <strong>Control Panel</strong>
            <ul>
                <li><a href="edit_bio.php">Edit <?php echo get_term($pdo, 'author', $user_lang); ?> Bio</a></li>
                <li><a href="pm_inbox.php">Private Messages</a></li>
                
				<?php if (in_array($user['role'], ['admin', 'system_admin'])): ?>
                    <li style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <strong>Admin Tools</strong>
                    </li>
                    <li><a href="admin/user_manager.php">Manage Users</a></li> <!-- NEW LINK -->
                    <li><a href="admin/roster_manager.php">Manage Hierarchy</a></li>                
                    <li><a href="admin/form_builder.php">Form Builder</a></li>
                    <li><a href="admin/manage_tech_specs.php">Manage Database</a></li>
                    <li><a href="admin/mission_manager.php">Manage Missions</a></li>
                    <li><a href="admin/terms.php">Edit Terminology</a></li>
                    <li><a href="admin/theme.php">Edit Platform Theme</a></li>
                    <li><a href="admin/settings.php">Platform Settings</a></li> <!-- NEW LINK -->
                <?php endif; ?>	
            </ul>
        </aside>

        <section>
            <h2>Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</h2>
            <p>Manage your active characters below, or check the roster to see the rest of the crew.</p>
            
            <?php if (empty($my_characters)): ?>
                <div style="padding: 20px; background: #e2e8f0; border-radius: 6px; margin-top: 20px;">
                    You don't have any characters assigned to the roster yet.
                </div>
            <?php else: ?>
                <?php foreach ($my_characters as $char): ?>
                    <div class="char-card">
                        <div class="char-info">
                            <strong><?php echo htmlspecialchars($char['name']); ?></strong>
                            <span><?php echo htmlspecialchars($char['pos_name'] ?? 'Unassigned'); ?> &bull; <?php echo htmlspecialchars($char['dept_name'] ?? 'No Department'); ?></span>
                        </div>
                        <div class="char-actions">
                            <a href="character_profile.php?id=<?php echo $char['id']; ?>" class="btn-view">View</a>
                            <a href="edit_character.php?id=<?php echo $char['id']; ?>" class="btn-edit">Edit</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <a href="create_character.php" class="create-btn">+ Create New Character</a>
        </section>

    <?php else: ?>
        <!-- ================= GUEST LANDING PAGE ================= -->
        <div class="hero-section">
            <h1>Welcome to the <?php echo get_term($pdo, 'library', $user_lang); ?></h1>
            <p>Dive into collaborative storytelling, manage detailed character service records, and explore our active deployments. Join the crew today to build your legacy.</p>
            
            <div class="cta-buttons">
                <a href="register.php" class="btn-primary">Create an Account</a>
                <a href="login.php" class="btn-secondary">Member Login</a>
            </div>
            
            <div style="margin-top: 2.5rem;">
                <a href="roster.php" style="color: var(--accent-color); font-weight: bold; text-decoration: none; font-size: 1.1rem;">&rarr; View the Public <?php echo get_term($pdo, 'roster', $user_lang); ?></a>
            </div>
        </div>
    <?php endif; ?>
</main>

<footer>
    &copy; <?php echo date('Y'); ?> MOWS Engine
</footer>

</body>
</html>