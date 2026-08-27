<?php
// admin/terms.php - Manage Platform Terminology
session_start();

require_once '../db.php';
require_once '../terms.php'; 

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'system_admin'])) {
    die("Access Denied. You do not have permission to view this page.");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Action A: Update existing terms
    if (isset($_POST['action']) && $_POST['action'] === 'update_terms') {
        if (!empty($_POST['terms'])) {
            $stmt = $pdo->prepare("UPDATE platform_terms SET term_value = ? WHERE term_key = ? AND lang_code = ?");
            foreach ($_POST['terms'] as $key_lang => $value) {
                list($term_key, $lang_code) = explode('|', $key_lang);
                $stmt->execute([trim($value), $term_key, $lang_code]);
            }
            $success = "Terminology updated successfully!";
        }
    } 
    // Action B: Add a completely new term or language mapping
    elseif (isset($_POST['action']) && $_POST['action'] === 'add_term') {
        $new_key = strtolower(trim($_POST['new_term_key']));
        $new_lang = strtolower(trim($_POST['new_lang_code']));
        $new_val = trim($_POST['new_term_value']);

        if ($new_key && $new_lang && $new_val) {
            try {
                $stmt = $pdo->prepare("INSERT INTO platform_terms (term_key, lang_code, term_value) VALUES (?, ?, ?)");
                $stmt->execute([$new_key, $new_lang, $new_val]);
                $success = "New term added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding term. That key and language combination might already exist.";
            }
        } else {
            $error = "All fields are required to add a new term.";
        }
    }
}

// Fetch all terms grouped alphabetically by language, then key
$stmt = $pdo->query("SELECT term_key, lang_code, term_value FROM platform_terms ORDER BY lang_code, term_key");
$terms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Terms - MOWS Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; color: #333; }
        .admin-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-top: 5px solid #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background-color: #f8f9fa; }
        input[type="text"] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px 15px; background: #2c3e50; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #1a252f; }
        .success { color: green; margin-bottom: 15px; font-weight: bold; }
        .error { color: red; margin-bottom: 15px; font-weight: bold; }
        .add-form { background: #f8f9fa; padding: 15px; border-radius: 4px; border: 1px solid #ddd; margin-top: 30px; display: flex; gap: 10px; align-items: center; }
        .add-form input { flex: 1; }
        a.back-btn { display: inline-block; margin-bottom: 15px; color: #18bc9c; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="admin-container">
    <a href="../index.php" class="back-btn">&larr; Back to Main Dashboard</a>
    <h2>Dynamic Terminology Editor</h2>
    
    <?php if ($success): ?><div class="success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <!-- Form to edit existing terms -->
    <form action="terms.php" method="POST">
        <input type="hidden" name="action" value="update_terms">
        <table>
            <thead>
                <tr>
                    <th>Language</th>
                    <th>Base Key</th>
                    <th>Display Translation</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($terms as $term): ?>
                <tr>
                    <td><?php echo htmlspecialchars(strtoupper($term['lang_code'])); ?></td>
                    <td><?php echo htmlspecialchars($term['term_key']); ?></td>
                    <td>
                        <!-- Array name format groups data so PHP can iterate through all rows instantly -->
                        <input type="text" 
                               name="terms[<?php echo htmlspecialchars($term['term_key'] . '|' . $term['lang_code']); ?>]" 
                               value="<?php echo htmlspecialchars($term['term_value']); ?>">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit">Save All Changes</button>
    </form>

    <!-- Form to add brand new terms -->
    <div class="add-form">
        <form action="terms.php" method="POST" style="width: 100%; display: flex; gap: 10px; align-items: center;">
            <input type="hidden" name="action" value="add_term">
            <input type="text" name="new_lang_code" placeholder="Lang (e.g., es)" required maxlength="10">
            <input type="text" name="new_term_key" placeholder="Key (e.g., department)" required>
            <input type="text" name="new_term_value" placeholder="Value (e.g., Division)" required>
            <button type="submit" style="background: #18bc9c;">Add New Term</button>
        </form>
    </div>
</div>

</body>
</html>