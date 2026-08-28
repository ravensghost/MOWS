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