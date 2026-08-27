<?php
// discord.php - Webhook Integration Engine
function send_discord_webhook($pdo, $title, $description, $color_hex = "18bc9c") {
    // Fetch the URL from the database
    $stmt = $pdo->query("SELECT setting_value FROM platform_settings WHERE setting_key = 'discord_webhook_url'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $webhook_url = $row ? $row['setting_value'] : '';

    // Abort if no URL is configured
    if (empty($webhook_url)) {
        return false;
    }

    // Convert hex color to decimal for Discord's API
    $decimal_color = hexdec(str_replace('#', '', $color_hex));
    $timestamp = date("c", strtotime("now"));

    // Build the JSON payload using Discord's Rich Embed schema
    $json_data = json_encode([
        "embeds" => [
            [
                "title" => $title,
                "description" => $description,
                "color" => $decimal_color,
                "timestamp" => $timestamp
            ]
        ]
    ]);

    // Fire the cURL request
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}
?>