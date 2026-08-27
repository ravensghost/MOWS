<?php
// pm_read.php - Read a Private Message (Quill formatting enabled)
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
$msg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$msg_id) die("Error: Message ID missing.");

// Fetch message and verify ownership
$stmt = $pdo->prepare("
    SELECT pm.*, s.username AS sender_name, r.username AS receiver_name 
    FROM private_messages pm 
    JOIN users s ON pm.sender_id = s.id 
    JOIN users r ON pm.receiver_id = r.id 
    WHERE pm.id = ? AND (pm.sender_id = ? OR pm.receiver_id = ?)
");
$stmt->execute([$msg_id, $user_id, $user_id]);
$msg = $stmt->fetch();

if (!$msg) die("Access Denied: You do not have permission to read this message.");

// Mark as read if the current user is the receiver
if ($msg['receiver_id'] == $user_id && $msg['is_read'] == 0) {
    $pdo->prepare("UPDATE private_messages SET is_read = 1 WHERE id = ?")->execute([$msg_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($msg['subject']); ?> - MOWS</title>
    <!-- Include Quill Core CSS to format blockquotes and lists -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.core.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f9; padding: 20px; color: #333; }
        .read-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-top: 5px solid #2c3e50; }
        .msg-header { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
        .msg-header h2 { margin: 0 0 10px 0; color: #2c3e50; }
        .meta { color: #666; font-size: 0.95em; }
        
        /* Message body styling for Quill outputs */
        .msg-body { margin-top: 20px; padding: 15px; background: #fafafa; border-radius: 4px; border: 1px solid #eee; }
        
        .actions { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
        a.btn { display: inline-block; padding: 10px 20px; background: #2c3e50; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
        a.btn-reply { background: #18bc9c; margin-right: 10px; }
        a.btn:hover { opacity: 0.9; }
    </style>
</head>
<body>

<div class="read-container">
    <div class="msg-header">
        <h2><?php echo htmlspecialchars($msg['subject']); ?></h2>
        <div class="meta">
            <strong>From:</strong> <?php echo htmlspecialchars($msg['sender_name']); ?> <br>
            <strong>To:</strong> <?php echo htmlspecialchars($msg['receiver_name']); ?> <br>
            <strong>Date:</strong> <?php echo date('F j, Y, g:i A', strtotime($msg['created_at'])); ?>
        </div>
    </div>
    
    <!-- Render raw HTML from Quill, styled by ql-editor -->
    <div class="msg-body ql-editor">
        <?php echo $msg['message']; ?>
    </div>

    <div class="actions">
        <?php if ($msg['sender_id'] != $user_id): ?>
            <a href="pm_send.php?reply_to=<?php echo $msg['id']; ?>" class="btn btn-reply">&#10554; Reply</a>
        <?php endif; ?>
        <a href="pm_inbox.php" class="btn">Back to Inbox</a>
    </div>
</div>

</body>
</html>