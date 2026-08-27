<?php
// view_tech_spec.php - Read a specific database entry
session_start();
require_once 'db.php';

$spec_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$spec_id) die("Error: Entry ID missing.");

// Fetch the entry
$stmt = $pdo->prepare("SELECT * FROM tech_specs WHERE id = ?");
$stmt->execute([$spec_id]);
$spec = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spec) die("Error: Entry not found.");

// Decode dynamic JSON data
$dynamic_data = json_decode($spec['dynamic_data'], true) ?: [];

// Fetch form schema to know labels and tabs
$schema_stmt = $pdo->prepare("SELECT * FROM system_form_schemas WHERE form_target = 'tech_specs' ORDER BY tab_name ASC, sort_order ASC");
$schema_stmt->execute();
$schema_fields = $schema_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group fields by tab/category
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
    <title><?php echo htmlspecialchars($spec['title']); ?> - Database</title>
    <style>
        :root {
            --bg-color: <?php echo htmlspecialchars($bg_color); ?>;
            --text-color: <?php echo htmlspecialchars($text_color); ?>;
            --primary-color: <?php echo htmlspecialchars($primary_color); ?>;
            --accent-color: <?php echo htmlspecialchars($accent_color); ?>;
        }
        body { font-family: Arial, sans-serif; background-color: var(--bg-color); color: var(--text-color); padding: 20px; line-height: 1.6; }
        .spec-container { max-width: 900px; margin: 0 auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); overflow: hidden; }
        
        .spec-header { background: var(--primary-color); color: white; padding: 30px 20px; position: relative; }
        .spec-header h1 { margin: 0 0 5px 0; font-size: 2.2em; }
        .spec-header .category-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 0.9em; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        
        a.back-btn { color: white; text-decoration: none; position: absolute; right: 20px; top: 20px; font-weight: bold; background: rgba(0,0,0,0.3); padding: 8px 12px; border-radius: 4px; }
        a.back-btn:hover { background: rgba(0,0,0,0.5); }

        .spec-content { padding: 30px; }
        .section-block { margin-bottom: 35px; }
        .section-block h2 { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 5px; margin-bottom: 20px; font-size: 1.4em; }
        
        .data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .data-item { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
        .data-item.full-width { grid-column: 1 / -1; }
        
        .data-label { font-weight: bold; color: var(--primary-color); font-size: 0.85em; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; border-bottom: 1px solid #ddd; padding-bottom: 4px; }
        .data-value { color: #444; }
        .empty-value { color: #aaa; font-style: italic; }
    </style>
</head>
<body>

<div class="spec-container">
    <div class="spec-header">
        <a href="tech_specs.php" class="back-btn">&larr; Return to Database</a>
        <h1><?php echo htmlspecialchars($spec['title']); ?></h1>
        <div class="category-badge"><?php echo htmlspecialchars($spec['category']); ?></div>
    </div>

    <div class="spec-content">
        <?php if (empty($grouped_fields)): ?>
            <p>No extended data points are recorded for this entry.</p>
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
                                        <span class="empty-value">No data available</span>
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