<?php
// write_post.php - Submit a new story post (with Quill Editor & Global Auth)
require_once 'auth.php'; // This replaces session_start, db.php, and the login check
$user_id = $current_user['id']; // Pulled directly from auth.php

$mission_id = isset($_GET['mission_id']) ? (int)$_GET['mission_id'] : (isset($_POST['mission_id']) ? (int)$_POST['mission_id'] : 0);
if (!$mission_id) die("Error: Mission ID missing.");

// Verify mission exists and is active
$stmt = $pdo->prepare("SELECT title, status FROM missions WHERE id = ?");
$stmt->execute([$mission_id]);
$mission = $stmt->fetch();

if (!$mission || $mission['status'] !== 'active') {
    die("Error: This mission does not exist or is closed to new posts.");
}

// Fetch user's characters to populate the dropdown
$char_stmt = $pdo->prepare("SELECT id, name FROM characters WHERE user_id = ? AND is_active = 1");
$char_stmt->execute([$user_id]);
$my_characters = $char_stmt->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $character_id = (int)$_POST['character_id'];
    
    // The HTML content from Quill is submitted via the hidden input
    $post_content = trim($_POST['post_content']);

    // Quill submits <p><br></p> if the editor is visually empty
    if (empty($post_content) || $post_content === '<p><br></p>' || !$character_id) {
        $error = "You must select a character and write some content.";
    } else {
        $owns_char = false;
        foreach ($my_characters as $c) {
            if ($c['id'] == $character_id) $owns_char = true;
        }

        if ($owns_char) {
            $insert_stmt = $pdo->prepare("INSERT INTO posts (mission_id, user_id, character_id, post_content) VALUES (?, ?, ?, ?)");
            $insert_stmt->execute([$mission_id, $user_id, $character_id, $post_content]);
            
            header("Location: view_mission.php?id=" . $mission_id);
            exit;
        } else {
            $error = "Invalid character selection.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Write Reply - <?php echo htmlspecialchars($mission['title']); ?></title>
    <!-- Include Quill Theme CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; padding: 20px; color: #333; }
        .editor-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-top: 5px solid #18bc9c; }
        h2 { margin-top: 0; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        label { display: block; font-weight: bold; margin-bottom: 8px; color: #2c3e50; }
        select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 1rem; }
        
        /* Quill specific overrides */
        #quill-editor { height: 400px; font-family: Arial, sans-serif; font-size: 1rem; }
        .ql-toolbar { background: #f8f9fa; border-radius: 4px 4px 0 0; border-color: #ccc !important; }
        .ql-container { border-radius: 0 0 4px 4px; border-color: #ccc !important; }
        
        button { padding: 12px 25px; background: #18bc9c; color: white; border: none; border-radius: 4px; font-weight: bold; font-size: 1rem; cursor: pointer; margin-top: 15px; }
        button:hover { background: #128f76; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        a.cancel-btn { color: #888; text-decoration: none; margin-left: 15px; font-weight: bold; }
    </style>
</head>
<body>

<div class="editor-container">
    <h2>Reply to: <?php echo htmlspecialchars($mission['title']); ?></h2>

    <?php if (empty($my_characters)): ?>
        <div class="error">
            You don't have any active characters! You must <a href="create_character.php">create a character</a> before you can write a post.
        </div>
    <?php else: ?>
        <?php if ($error): ?><div class="error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form action="write_post.php" method="POST" id="post-form">
            <input type="hidden" name="mission_id" value="<?php echo $mission_id; ?>">
            
            <div class="form-group">
                <label>Posting as Character:</label>
                <select name="character_id" required>
                    <option value="">-- Select your character --</option>
                    <?php foreach ($my_characters as $char): ?>
                        <option value="<?php echo $char['id']; ?>"><?php echo htmlspecialchars($char['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Post Content:</label>
                <!-- Hidden input to store the raw HTML output for PHP -->
                <input type="hidden" name="post_content" id="hidden_content">
                <!-- The visible div that Quill converts into the rich text editor -->
                <div id="quill-editor"></div>
            </div>

            <div>
                <button type="submit">Publish Post</button>
                <a href="view_mission.php?id=<?php echo $mission_id; ?>" class="cancel-btn">Cancel</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Include Quill JS Library -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    // Initialize Quill
    var quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: 'Write your character\'s actions and dialogue here...',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'], 
                [{ 'color': [] }, { 'background': [] }],          
                ['blockquote'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'align': [] }],
                ['link', 'clean']
            ]
        }
    });

    // Capture the HTML output right before the form submits
    var form = document.getElementById('post-form');
    form.onsubmit = function() {
        var hiddenInput = document.getElementById('hidden_content');
        hiddenInput.value = quill.root.innerHTML;
    };
</script>

</body>
</html>