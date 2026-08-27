<?php
// character_profile.php - Public Character View (With Avatar)
session_start();
require_once 'db.php';
require_once 'terms.php';

$char_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$char_id) die("Error: No character ID specified.");

// Fetch Character and Core Relational Data (c.* automatically includes the new avatar column)
$stmt = $pdo->prepare("
    SELECT c.*, d.name AS dept_name, p.name AS pos_name, u.username 
    FROM characters c
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN positions p ON c.position_id = p.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$char_id]);
$character = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$character) die("Error: Character not found.");

// Decode dynamic JSON data
$dynamic_data = json_decode($character['dynamic_data'], true) ?: [];

// Fetch form schema
$schema_stmt = $pdo->prepare("SELECT * FROM system_form_schemas WHERE form_target = 'characters' ORDER BY tab_name ASC, sort_order ASC");
$schema_stmt->execute();
$schema_fields = $schema_stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_fields = [];
foreach ($schema_fields as $field) {
    $grouped_fields[$field['tab_name']][] = $field;
}

// Fetch theme settings
$theme_stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'platform_theme'");
$theme_data = json_decode($theme_stmt->fetch()['setting_value'], true);
$bg_color      = $theme_data['bg_color']      ?? '#f4f4f9';
$text_color    = $theme_data['text_color']    ?? '#333333';
$primary_color = $theme_data['primary_color'] ?? '#2c3e50';
$accent_color  = $theme_data['accent_color']  ?? '#18bc9c';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($character['name']); ?> - Service Record</title>
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
        body { font-family: Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); padding: 20px; line-height: 1.6; }
        .profile-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; }
        
        .profile-header { background: var(--primary-color); color: white; padding: 40px 20px 30px 20px; text-align: center; position: relative; }
        .profile-avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.2); box-shadow: 0 4px 8px rgba(0,0,0,0.3); margin-top: -10px; margin-bottom: 15px; }
        .profile-header h1 { margin: 0 0 10px 0; font-size: 2.5em; }
        .profile-header .subtitle { font-size: 1.2em; opacity: 0.9; }
        .profile-header .player-credit { margin-top: 15px; font-size: 0.9em; background: rgba(0,0,0,0.2); display: inline-block; padding: 5px 15px; border-radius: 20px; }
        
        a.back-btn { color: white; text-decoration: none; position: absolute; left: 20px; top: 20px; font-weight: bold; background: rgba(255,255,255,0.2); padding: 5px 10px; border-radius: 4px; }
        a.back-btn:hover { background: rgba(255,255,255,0.4); }

        .profile-content { padding: 30px; }
        .section-block { margin-bottom: 30px; }
        .section-block h2 { color: var(--primary-color); border-bottom: 2px solid var(--accent-color); padding-bottom: 5px; margin-bottom: 15px; }
        
        .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .data-item { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
        .data-item.full-width { grid-column: 1 / -1; }
        
        .data-label { font-weight: bold; color: var(--primary-color); font-size: 0.9em; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; display: block; }
        .data-value { color: #444; }
        .empty-value { color: #999; font-style: italic; }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="profile-header">
        <a href="roster.php" class="back-btn">&larr; Return to Roster</a>
        
        <img src="uploads/avatars/<?php echo htmlspecialchars($character['avatar'] ?? 'default_avatar.png'); ?>" class="profile-avatar" alt="Character Avatar">
        
        <h1><?php echo htmlspecialchars($character['name']); ?></h1>
        <div class="subtitle">
            <?php echo htmlspecialchars($character['pos_name'] ?? 'Unassigned'); ?> &bull; <?php echo htmlspecialchars($character['dept_name'] ?? 'No Department'); ?>
        </div>
        <div class="player-credit">Played by: <?php echo htmlspecialchars($character['username']); ?></div>
        
        <?php 
        $is_admin = isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'system_admin']);
        $is_owner = isset($_SESSION['user_id']) && $_SESSION['user_id'] === $character['user_id'];
        
        if ($is_owner || $is_admin): 
        ?>
            <br><br>
            <a href="edit_character.php?id=<?php echo $char_id; ?>" style="color: white; border: 1px solid white; padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 0.9em;">&#9998; Edit Character</a>
        <?php endif; ?>
    </div>

    <div class="profile-content">
        <?php if (empty($grouped_fields)): ?>
            <p>No extended service record data available.</p>
        <?php else: ?>
            <?php foreach ($grouped_fields as $tab_name => $fields): ?>
                <div class="section-block">
                    <h2><?php echo htmlspecialchars($tab_name); ?></h2>
                    <div class="data-grid">
                        <?php foreach ($fields as $field): ?>
                            <?php 
                                $key = $field['field_key'];
                                $value = $dynamic_data[$key] ?? '';
                                $is_textarea = ($field['field_type'] === 'textarea');
                                $grid_class = $is_textarea ? 'data-item full-width' : 'data-item';
                            ?>
                            <div class="<?php echo $grid_class; ?>">
                                <span class="data-label"><?php echo htmlspecialchars($field['field_label']); ?></span>
                                <div class="data-value">
                                    <?php if ($value === ''): ?>
                                        <span class="empty-value">Not specified</span>
                                    <?php else: ?>
                                        <?php echo nl2br(htmlspecialchars($value)); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>