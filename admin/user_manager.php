<?php
// admin/user_manager.php - Manage Player Accounts
session_start();
require_once '../db.php';

// Restrict access to Admins
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$current_user_role = $_SESSION['role'];
$success = '';
$error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_user_id = (int)$_POST['user_id'];
    
    // Security check: Fetch target user's current role to prevent altering system_admins
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $target_user = $stmt->fetch();

    if ($target_user) {
        // Prevent standard admins from modifying system_admins or themselves
        $can_modify = true;
        if ($target_user['role'] === 'system_admin' && $current_user_role !== 'system_admin') {
            $can_modify = false;
            $error = "You do not have clearance to modify a System Admin.";
        }
        if ($target_user_id === $_SESSION['user_id'] && $_POST['action'] !== 'update_password') {
            $can_modify = false;
            $error = "You cannot alter your own role or ban yourself.";
        }

        if ($can_modify) {
            // Action: Change Role
            if ($_POST['action'] === 'update_role') {
                $new_role = $_POST['role'];
                if (in_array($new_role, ['user', 'admin', 'system_admin'])) {
                    $pdo->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$new_role, $target_user_id]);
                    $success = "Role updated for {$target_user['username']}.";
                }
            }
            // Action: Change Status (Ban/Unban)
            elseif ($_POST['action'] === 'update_status') {
                $new_status = $_POST['status'];
                if (in_array($new_status, ['active', 'banned'])) {
                    $pdo->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $target_user_id]);
                    $success = "Account status updated for {$target_user['username']}.";
                }
            }
            // Action: Reset Password
            elseif ($_POST['action'] === 'reset_password') {
                // Generate a random 8-character alphanumeric password
                $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hashed_password, $target_user_id]);
                $success = "Password reset for <strong>{$target_user['username']}</strong>. Their new temporary password is: <code style='background:#eee;padding:2px 6px;border-radius:4px;'>{$new_password}</code>";
            }
        }
    }
}

// Fetch all users
$users = $pdo->query("SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Manager - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; color: #333; }
        .admin-container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; border-top: 5px solid #2c3e50; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #2c3e50; margin-top: 0; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #2c3e50; font-weight: bold; }
        
        select { padding: 6px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9em; }
        button { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 0.9em; color: white; }
        .btn-update { background: #3498db; }
        .btn-update:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .badge { padding: 3px 8px; border-radius: 12px; font-size: 0.8em; font-weight: bold; text-transform: uppercase; }
        .badge-banned { background: #f8d7da; color: #721c24; }
        .badge-active { background: #d4edda; color: #155724; }
        
        a.back-btn { color: #18bc9c; text-decoration: none; font-weight: bold; display: inline-block; margin-bottom: 20px; }
        .action-form { display: flex; gap: 5px; margin-bottom: 5px; }
    </style>
</head>
<body>

<div class="admin-container">
    <a href="../index.php" class="back-btn">&larr; Back to Dashboard</a>
    <h2>Player Account Manager</h2>
    
    <?php if ($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Status</th>
                <th>Role Control</th>
                <th>Account Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <strong><?php echo htmlspecialchars($u['username']); ?></strong><br>
                    <span style="font-size:0.85em; color:#777;"><?php echo htmlspecialchars($u['email']); ?></span>
                </td>
                <td>
                    <?php if ($u['status'] === 'banned'): ?>
                        <span class="badge badge-banned">Banned</span>
                    <?php else: ?>
                        <span class="badge badge-active">Active</span>
                    <?php endif; ?>
                </td>
                
                <!-- Role Update Form -->
                <td>
                    <form class="action-form" action="user_manager.php" method="POST">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <select name="role">
                            <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>Player</option>
                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            <?php if ($current_user_role === 'system_admin'): ?>
                                <option value="system_admin" <?php echo $u['role'] === 'system_admin' ? 'selected' : ''; ?>>System Admin</option>
                            <?php endif; ?>
                        </select>
                        <button type="submit" class="btn-update">Update</button>
                    </form>
                </td>
                
                <!-- Status Update Form -->
                <td>
                    <form class="action-form" action="user_manager.php" method="POST">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <select name="status">
                            <option value="active" <?php echo $u['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="banned" <?php echo $u['status'] === 'banned' ? 'selected' : ''; ?>>Banned</option>
                        </select>
                        <button type="submit" class="btn-update">Set</button>
                    </form>
                </td>
                
                <!-- Password Reset -->
                <td>
                    <form action="user_manager.php" method="POST" onsubmit="return confirm('Generate a new random password for <?php echo addslashes($u['username']); ?>?');">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                        <button type="submit" class="btn-danger">Reset Password</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>