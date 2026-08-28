<?php
// admin/roster_manager.php - Manage Departments and Positions
session_start();
require_once '../db.php';
require_once '../terms.php';

// Restrict access to Admins and System Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$success = '';
$error = '';

// Handle POST actions for CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_department') {
        $name = trim($_POST['dept_name']);
        $order = (int)$_POST['dept_order'];
        if ($name) {
            $stmt = $pdo->prepare("INSERT INTO departments (name, sort_order) VALUES (?, ?)");
            $stmt->execute([$name, $order]);
            $success = "Department successfully added.";
        }
    } elseif ($_POST['action'] === 'add_position') {
        $dept_id = (int)$_POST['department_id'];
        $name = trim($_POST['pos_name']);
        $order = (int)$_POST['pos_order'];
        if ($dept_id && $name) {
            $stmt = $pdo->prepare("INSERT INTO positions (department_id, name, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$dept_id, $name, $order]);
            $success = "Position successfully added.";
        }
    } elseif ($_POST['action'] === 'delete_dept') {
        $id = (int)$_POST['dept_id'];
        // Cascade deletion in the database schema automatically removes nested positions
        $pdo->prepare("DELETE FROM departments WHERE id = ?")->execute([$id]);
        $success = "Department deleted.";
    } elseif ($_POST['action'] === 'delete_pos') {
        $id = (int)$_POST['pos_id'];
        $pdo->prepare("DELETE FROM positions WHERE id = ?")->execute([$id]);
        $success = "Position deleted.";
    }
}

// Fetch all Departments and group Positions beneath them
$departments = $pdo->query("SELECT * FROM departments ORDER BY sort_order ASC")->fetchAll();
$positions_raw = $pdo->query("SELECT * FROM positions ORDER BY sort_order ASC")->fetchAll();

$positions = [];
foreach ($positions_raw as $pos) {
    $positions[$pos['department_id']][] = $pos;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Roster Manager - MOWS Admin</title>
	<!-- Notice the ../ to point back to the root folder -->
    <link rel="stylesheet" href="../style.css"> 
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

<div class="admin-container">
    <a href="../index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h2>Roster Manager</h2>
    
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <!-- Form: Add a New Department -->
    <div class="dept-block" style="border-color: #18bc9c;">
        <h3 style="margin-top: 0; color: #18bc9c;">Create New Department</h3>
        <form action="roster_manager.php" method="POST" class="form-row">
            <input type="hidden" name="action" value="add_department">
            <input type="text" name="dept_name" placeholder="e.g., Command, Flight" required style="flex: 1;">
            <input type="number" name="dept_order" placeholder="Sort (e.g., 1)" value="0" required style="width: 100px;">
            <button type="submit" style="background: #18bc9c;">Add Department</button>
        </form>
    </div>

    <!-- Iterate Existing Departments -->
    <?php foreach ($departments as $dept): ?>
        <div class="dept-block">
            <div class="dept-header">
                <strong><?php echo htmlspecialchars($dept['name']); ?> (Sort: <?php echo $dept['sort_order']; ?>)</strong>
                <form action="roster_manager.php" method="POST" style="margin: 0;" onsubmit="return confirm('WARNING: Deleting this department will delete all nested positions. Proceed?');">
                    <input type="hidden" name="action" value="delete_dept">
                    <input type="hidden" name="dept_id" value="<?php echo $dept['id']; ?>">
                    <button type="submit" class="danger">Delete Dept</button>
                </form>
            </div>

            <!-- Iterate Nested Positions -->
            <ul class="pos-list">
                <?php if (isset($positions[$dept['id']])): ?>
                    <?php foreach ($positions[$dept['id']] as $pos): ?>
                        <li class="pos-item">
                            <span>&#8627; <?php echo htmlspecialchars($pos['name']); ?> (Sort: <?php echo $pos['sort_order']; ?>)</span>
                            <form action="roster_manager.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="action" value="delete_pos">
                                <input type="hidden" name="pos_id" value="<?php echo $pos['id']; ?>">
                                <button type="submit" class="danger">X</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="pos-item" style="color: #888;"><em>No positions added yet.</em></li>
                <?php endif; ?>
            </ul>

            <!-- Form: Add a New Position -->
            <form action="roster_manager.php" method="POST" class="form-row" style="border-top: 1px solid #ccc; padding-top: 10px; margin-top: 10px;">
                <input type="hidden" name="action" value="add_position">
                <input type="hidden" name="department_id" value="<?php echo $dept['id']; ?>">
                <input type="text" name="pos_name" placeholder="New Position Name" required style="flex: 1;">
                <input type="number" name="pos_order" placeholder="Sort" value="0" required style="width: 70px;">
                <button type="submit">Add Position</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>