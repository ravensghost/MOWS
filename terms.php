<?php
// terms_db.php - Dynamic Terminology Engine (Database Version)
require_once 'db.php';

function get_term($pdo, $term_key, $user_language = 'en') {
    // Check the database for the translated term
    $stmt = $pdo->prepare("SELECT term_value FROM platform_terms WHERE term_key = ? AND lang_code = ?");
    $stmt->execute([$term_key, $user_language]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['term_value'];
    }
    
    // Fallback: If no translation exists, return the default English term
    $stmt_fallback = $pdo->prepare("SELECT term_value FROM platform_terms WHERE term_key = ? AND lang_code = 'en'");
    $stmt_fallback->execute([$term_key]);
    $fallback_result = $stmt_fallback->fetch();
    
    return $fallback_result ? $fallback_result['term_value'] : ucfirst($term_key);
}
?>