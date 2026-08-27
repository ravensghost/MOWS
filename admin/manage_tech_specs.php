<?php
// admin/manage_tech_specs.php - Create and Manage Lore/Tech Entries
session_start();
require_once '../db.php';
require_once '../terms.php';

// Restrict access to Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied.");
}

$success = '';
$error = '';

// 1. Fetch the blueprint for 'tech_specs' to generate the form
$schema_stmt = $pdo->prepare("SELECT * FROM system_form_schemas WHERE form_target = 'tech_specs' ORDER BY tab_name ASC, sort_order ASC");
$schema_stmt->execute();
$schema_fields = $schema_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        
        // Action: Create New Entry
        if ($_POST['action'] === 'add_spec') {
            $title = trim($_POST['title']);
            $category = trim($_POST['category']);

            // Extract dynamic fields dynamically
            $dynamic_data = [];
            foreach ($schema_fields as $field) {
                $key = $field['field_key'];
                if (isset($_POST[$key])) {
                    $dynamic_data[$key] = trim($_POST[$key]);
                }
            }
            $json_data = json_encode($dynamic_data);

            if ($title && $category) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO tech_specs (title, category, dynamic_data) VALUES (?, ?, ?)");
                    $stmt->execute([$title, $category, $json_data]);
                    $success = "Database entry successfully created!";
                } catch (PDOException $e) {
                    $error = "Error saving entry. Please try again.";
                }
            } else {
                $error = "Title and Category are required.";
            }
        } 
        // Action: Delete Entry
        elseif ($_POST['action'] === 'delete_spec') {
            $spec_id = (int)$_POST['spec_id'];
            $pdo->prepare("DELETE FROM tech_specs WHERE id = ?")->execute([$spec_id]);
            $success = "Entry deleted.";
        }
    }
}

// Fetch existing entries
$specs = $pdo->query("SELECT * FROM tech_specs ORDER BY category ASC, title ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tech Specs & Lore Manager</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; color: #333; }
        .admin-container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; border-top: 5px solid #2c3e50; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2, h3 { color: #2c3e50; }
        .form-block { background: #f8f9fa; border: 1px solid #ddd; padding: 20px; margin-bottom: 30px; border-radius: 4px; border-left: 5px solid #18bc9c; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        input[type="text"], select, textarea { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        textarea { height: 80px; resize: vertical; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        button { padding: 10px 15px; background: #18bc9c; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
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
    <h2>Database & Lore Manager</h2>
    
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Form to Create New Spec -->
    <div class="form-block">
        <h3 style="margin-top: 0;">Add New Entry</h3>
        <form action="manage_tech_specs.php" method="POST">
            <input type="hidden" name="action" value="add_spec">
            
            <div class="grid-2">
                <div class="form-group">
                    <label>Entry Name / Title</label>
                    <input type="text" name="title" required placeholder="e.g., USS Enterprise, Vulcan">
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <!-- Users can type any category they want, allowing you to group things dynamically -->
                    <input type="text" name="category" required placeholder="e.g., Starships, Species, Locations" list="category-list">
                    <datalist id="category-list">
                        <option value="Starships">
                        <option value="Locations">
                        <option value="Species">
                        <option value="Equipment">
                    </datalist>
                </div>
            </div>

            <!-- Render Dynamic Fields from Universal Schema -->
            <?php if (empty($schema_fields)): ?>
                <div style="color: #777; font-style: italic; margin-bottom: 15px;">
                    No custom fields found. Go to the <a href="form_builder.php?target=tech_specs" style="color: #18bc9c;">Form Builder</a> to add fields for Tech Specs.
                </div>
            <?php else: ?>
                <div style="border-top: 1px solid #ddd; margin-top: 15px; padding-top: 15px;">
                    <h4 style="margin-top: 0; color: #555;">Custom Database Fields</h4>
                    <div class="grid-2">
                    <?php foreach ($schema_fields as $field): ?>
                        <div class="form-group">
                            <label><?php echo htmlspecialchars($field['field_label']); ?></label>
                            <?php if ($field['field_type'] === 'textarea'): ?>
                                <textarea name="<?php echo htmlspecialchars($field['field_key']); ?>"></textarea>
                            <?php else: ?>
                                <input type="text" name="<?php echo htmlspecialchars($field['field_key']); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <button type="submit">Save Database Entry</button>
        </form>
    </div>

    <!-- List of Existing Specs -->
    <h3>Database Catalog</h3>
    <?php if (empty($specs)): ?>
        <p>No entries have been added to the database yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Title</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($specs as $s): ?>
                <tr>
                    <td><span style="background: #e2e8f0; padding: 3px 8px; border-radius: 12px; font-size: 0.85em;"><?php echo htmlspecialchars($s['category']); ?></span></td>
                    <td><strong><?php echo htmlspecialchars($s['title']); ?></strong></td>
                    <td><?php echo date('M j, Y', strtotime($s['created_at'])); ?></td>
                    <td>
                        <form action="manage_tech_specs.php" method="POST" onsubmit="return confirm('Delete this entry?');">
                            <input type="hidden" name="action" value="delete_spec">
                            <input type="hidden" name="spec_id" value="<?php echo $s['id']; ?>">
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