<?php
// admin/form_builder.php - Universal Dynamic Form Builder
session_start();
require_once '../db.php';

// Restrict access to Admins and System Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$success = '';
$error = '';
$current_target = $_GET['target'] ?? 'characters';

// Handle CRUD Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'add_field') {
        $target = $_POST['form_target'];
        // Force the database key to be safe (lowercase, underscores instead of spaces)
        $key = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($_POST['field_key'])));
        $label = trim($_POST['field_label']);
        $type = $_POST['field_type'];
        $tab = trim($_POST['tab_name']) ?: 'General';
        $order = (int)$_POST['sort_order'];

        try {
            $stmt = $pdo->prepare("INSERT INTO system_form_schemas (form_target, field_key, field_label, field_type, tab_name, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$target, $key, $label, $type, $tab, $order]);
            $success = "Field successfully added to the $target form.";
            $current_target = $target; // Keep the user on the same form target
        } catch (PDOException $e) {
            $error = "Error: That Database Key already exists for this specific form.";
        }
    } elseif ($_POST['action'] === 'delete_field') {
        $id = (int)$_POST['field_id'];
        $current_target = $_POST['form_target'];
        $pdo->prepare("DELETE FROM system_form_schemas WHERE id = ?")->execute([$id]);
        $success = "Field deleted successfully.";
    }
}

// Fetch existing fields for the selected target
$stmt = $pdo->prepare("SELECT * FROM system_form_schemas WHERE form_target = ? ORDER BY tab_name ASC, sort_order ASC");
$stmt->execute([$current_target]);
$fields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group fields by tab for cleaner display
$grouped_fields = [];
foreach ($fields as $field) {
    $grouped_fields[$field['tab_name']][] = $field;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Form Builder - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; color: #333; }
        .container { max-width: 900px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; border-top: 5px solid #2c3e50; }
        .tabs-container { margin-bottom: 20px; }
        .tab-block { background: #f8f9fa; border: 1px solid #ddd; margin-bottom: 15px; padding: 15px; border-radius: 4px; }
        .field-row { display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee; align-items: center; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { padding: 10px 15px; background: #18bc9c; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button.danger { background: #e74c3c; padding: 5px 10px; }
        a.back-btn { color: #18bc9c; text-decoration: none; font-weight: bold; margin-bottom: 15px; display: inline-block; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-weight: bold; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
<div class="container">
    <a href="../index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h2>Universal Form Builder</h2>
    
    <?php if ($success): ?><div class="alert success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Target Selector -->
    <form method="GET" style="margin-bottom: 20px; background: #e2e8f0; padding: 15px; border-radius: 4px;">
        <label style="font-weight: bold; margin-right: 10px;">Select Entity to Edit:</label>
        <select name="target" onchange="this.form.submit()" style="width: auto; display: inline-block;">
            <option value="characters" <?php echo $current_target === 'characters' ? 'selected' : ''; ?>>Characters</option>
            <option value="missions" <?php echo $current_target === 'missions' ? 'selected' : ''; ?>>Missions</option>
            <option value="tech_specs" <?php echo $current_target === 'tech_specs' ? 'selected' : ''; ?>>Tech Specs</option>
        </select>
    </form>

    <!-- Existing Fields Display -->
    <div class="tabs-container">
        <h3 style="color: #2c3e50;">Active Fields for '<?php echo htmlspecialchars($current_target); ?>'</h3>
        <?php if (empty($grouped_fields)): ?>
            <p style="color: #777;">No fields have been created for this entity yet.</p>
        <?php else: ?>
            <?php foreach ($grouped_fields as $tab_name => $tab_fields): ?>
                <div class="tab-block">
                    <h4 style="margin-top: 0; color: #2c3e50; border-bottom: 2px solid #ccc; padding-bottom: 5px;">Tab: <?php echo htmlspecialchars($tab_name); ?></h4>
                    <?php foreach ($tab_fields as $field): ?>
                        <div class="field-row">
                            <div>
                                <strong><?php echo htmlspecialchars($field['field_label']); ?></strong> 
                                <span style="color: #777; font-size: 0.9em;">(Key: <?php echo htmlspecialchars($field['field_key']); ?> | Type: <?php echo htmlspecialchars($field['field_type']); ?> | Order: <?php echo $field['sort_order']; ?>)</span>
                            </div>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this field? User data associated with this specific key will be orphaned.');">
                                <input type="hidden" name="action" value="delete_field">
                                <input type="hidden" name="field_id" value="<?php echo $field['id']; ?>">
                                <input type="hidden" name="form_target" value="<?php echo htmlspecialchars($current_target); ?>">
                                <button type="submit" class="danger">Delete</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add New Field Form -->
    <div style="border-top: 2px solid #2c3e50; padding-top: 20px; margin-top: 30px;">
        <h3 style="color: #18bc9c;">Add New Field</h3>
        <form method="POST">
            <input type="hidden" name="action" value="add_field">
            <input type="hidden" name="form_target" value="<?php echo htmlspecialchars($current_target); ?>">
            
            <div class="form-grid">
                <div>
                    <label>Field Label (Display Text)</label>
                    <input type="text" name="field_label" placeholder="e.g., Eye Color" required>
                </div>
                <div>
                    <label>Database Key (Auto-formats to lowercase)</label>
                    <input type="text" name="field_key" placeholder="e.g., eye_color" required>
                </div>
                <div>
                    <label>Input Type</label>
                    <select name="field_type" required>
                        <option value="text">Short Text</option>
                        <option value="textarea">Long Text (Textarea)</option>
                    </select>
                </div>
                <div>
                    <label>Tab Name (Groups fields together)</label>
                    <input type="text" name="tab_name" placeholder="e.g., Physical Appearance" required>
                </div>
                <div>
                    <label>Sort Order (Display priority)</label>
                    <input type="number" name="sort_order" value="0" required>
                </div>
            </div>
            <button type="submit">Create Field</button>
        </form>
    </div>
</div>
</body>
</html>