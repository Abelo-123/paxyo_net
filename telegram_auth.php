<?php
// telegram_auth.php
// DEBUG MODE: Display received data only

$tg_data_raw = $_GET['tg_data'] ?? '';

if (empty($tg_data_raw)) {
    echo "No Telegram data provided.";
    exit;
}

// Parse the query string into an array
parse_str($tg_data_raw, $auth_data);

echo "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1'></head><body style='background-color: white; color: black; font-family: sans-serif;'>";
echo "<h1>Received Telegram Data</h1>";
echo "<pre>";
print_r($auth_data);
echo "</pre>";

if (isset($auth_data['user'])) {
    echo "<h2>User Data JSON Decoded</h2>";
    $user_data = json_decode($auth_data['user'], true);
    echo "<pre>";
    print_r($user_data);
    echo "</pre>";
}
echo "</body></html>";
?>
