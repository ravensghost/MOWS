<?php
// admin/mission_manager.php - Manage Storylines
session_start();
require_once '../db.php';
require_once '../terms.php';
require_once '../discord.php';

// Restrict access to Admins and System Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$success = '';
$error = '';

// 1. Fetch the blueprint for 'missions' to handle dynamic data
$schema_stmt = $pdo->prepare("SELECT * FROM system_form_schemas WHERE form_target = 'missions' ORDER BY tab_name ASC, sort_order ASC");
$schema_stmt->execute();
$schema_fields = $schema_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Action: Create a New Mission
        if ($_POST['action'] === 'add_mission') {
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $status = $_POST['status'];

            // Extract dynamic fields based on the blueprint
            $dynamic_data = [];
            foreach ($schema_fields as $field) {
                $key = $field['field_key'];
                if (isset($_POST[$key])) {
                    $dynamic_data[$key] = trim($_POST[$key]);
                }
            }
            $json_data = json_encode($dynamic_data);

			if ($title && $description) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO missions (title, description, status, dynamic_data) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $status, $json_data]);
                    $success = "Mission successfully launched!";
                    
                    // --- ADD THIS BLOCK ---
                    if ($status === 'active') {
                        $msg = "A new storyline has been launched!\n\n**Briefing:**\n" . substr($description, 0, 300) . "...";
                        send_discord_webhook($pdo, "New Mission: " . $title, $msg, "e74c3c"); // Red embed
                    }
                    // ----------------------
                    
                } catch (PDOException $e) {
                    $error = "Error creating mission. Please try again.";
                }
            } else {
                $error = "Title and Description are required.";
            }
        } 
        // Action: Update Mission Status
        elseif ($_POST['action'] === 'update_status') {
            $mission_id = (int)$_POST['mission_id'];
            $new_status = $_POST['status'];
            
            $pdo->prepare("UPDATE missions SET status = ? WHERE id = ?")->execute([$new_status, $mission_id]);
            $success = "Mission status updated.";
        }
        // Action: Delete Mission
        elseif ($_POST['action'] === 'delete_mission') {
            $mission_id = (int)$_POST['mission_id'];
            // Cascade delete will automatically wipe all posts inside this mission
            $pdo->prepare("DELETE FROM missions WHERE id = ?")->execute([$mission_id]);
            $success = "Mission and all associated posts deleted.";
        }
    }
}

// Fetch all missions
$missions = $pdo->query("SELECT m.*, COUNT(p.id) AS post_count FROM missions m LEFT JOIN posts p ON m.id = p.mission_id GROUP BY m.id ORDER BY m.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mission Manager - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; color: #333; }
        .admin-container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; border-top: 5px solid #2c3e50; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2, h3 { color: #2c3e50; }
        .form-block { background: #f8f9fa; border: 1px solid #ddd; padding: 20px; margin-bottom: 30px; border-radius: 4px; border-left: 5px solid #18bc9c; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="text"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 100px; resize: vertical; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        button { padding: 10px 15px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button.btn-primary { background: #18bc9c; }
        button.btn-danger { background: #e74c3c; padding: 6px 10px; font-size: 0.9em; }
        button:hover { opacity: 0.9; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background-color: #f8f9fa; color: #2c3e50; }
        a.back-btn { color: #18bc9c; text-decoration: none; font-weight: bold; display: inline-block; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="admin-container">
    <a href="../index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h2>Mission & Storyline Manager</h2>
    
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Form to Create a New Mission -->
    <div class="form-block">
        <h3 style="margin-top: 0;">Launch New Storyline</h3>
        <form action="mission_manager.php" method="POST">
            <input type="hidden" name="action" value="add_mission">
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Mission Title</label>
                    <input type="text" name="title" required placeholder="e.g., Episode 1: First Contact">
                </div>
                <div class="form-group">
                    <label>Initial Status</label>
                    <select name="status" required>
                        <option value="planning">Planning (Hidden from active play)</option>
                        <option value="active" selected>Active (Open for posting)</option>
                        <option value="completed">Completed (Read-only)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Briefing / Description</label>
                <textarea name="description" required placeholder="Describe the setup and objectives for the players..."></textarea>
            </div>

            <!-- Render Dynamic Fields from Universal Schema -->
            <?php if (!empty($schema_fields)): ?>
                <div style="border-top: 1px solid #ddd; margin-top: 15px; padding-top: 15px;">
                    <h4 style="margin-top: 0; color: #555;">Custom Mission Data</h4>
                    <div class="grid-2">
                    <?php foreach ($schema_fields as $field): ?>
                        <div class="form-group">
                            <label><?php echo htmlspecialchars($field['field_label']); ?></label>
                            <?php if ($field['field_type'] === 'textarea'): ?>
                                <textarea name="<?php echo htmlspecialchars($field['field_key']); ?>" style="height: 60px;"></textarea>
                            <?php else: ?>
                                <input type="text" name="<?php echo htmlspecialchars($field['field_key']); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-primary">Launch Mission</button>
        </form>
    </div>

    <!-- List of Existing Missions -->
    <h3>Existing Storylines</h3>
    <?php if (empty($missions)): ?>
        <p>No missions have been created yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date Created</th>
                    <th>Posts</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($missions as $m): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($m['title']); ?></strong><br>
                        <a href="../view_mission.php?id=<?php echo $m['id']; ?>" style="font-size: 0.85em; color: #18bc9c;">View Thread</a>
                    </td>
                    <td><?php echo date('M j, Y', strtotime($m['created_at'])); ?></td>
                    <td><?php echo $m['post_count']; ?></td>
                    <td>
                        <!-- Inline Status Updater -->
                        <form action="mission_manager.php" method="POST" style="display: flex; gap: 5px; align-items: center;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="mission_id" value="<?php echo $m['id']; ?>">
                            <select name="status" onchange="this.form.submit()" style="padding: 4px; width: 110px; font-size: 0.9em;">
                                <option value="planning" <?php echo $m['status'] === 'planning' ? 'selected' : ''; ?>>Planning</option>
                                <option value="active" <?php echo $m['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="completed" <?php echo $m['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form action="mission_manager.php" method="POST" onsubmit="return confirm('Delete this mission and ALL POSTS inside it? This cannot be undone.');">
                            <input type="hidden" name="action" value="delete_mission">
                            <input type="hidden" name="mission_id" value="<?php echo $m['id']; ?>">
                            <button type="submit" class="btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>