<?php
// telegram_auth.php
// Place this file in the root directory of your website (e.g., public_html/)

require_once 'inc/connect.php';
include 'inc/session.php';
include 'inc/connect2.php';

// 1. Configuration
$BOT_TOKEN = 'YOUR_BOT_TOKEN_HERE'; // <--- IMPORTANT: REPLACE THIS WITH YOUR ACTUAL BOT TOKEN

// 2. Helper function to validate Telegram data
function checkTelegramAuthorization($auth_data, $bot_token) {
    if (!isset($auth_data['hash'])) {
        return false;
    }

    $check_hash = $auth_data['hash'];
    unset($auth_data['hash']);
    
    $data_check_arr = [];
    foreach ($auth_data as $key => $value) {
        $data_check_arr[] = $key . '=' . $value;
    }
    sort($data_check_arr);
    $data_check_string = implode("\n", $data_check_arr);
    
    $secret_key = hash_hmac('sha256', $bot_token, "WebAppData", true);
    $hash = hash_hmac('sha256', $data_check_string, $secret_key);
    
    if (strcmp($hash, $check_hash) !== 0) {
        return false;
    }
    
    if ((time() - $auth_data['auth_date']) > 86400) {
        return false; // Data is outdated
    }
    
    return true;
}

// 3. Get Data
$tg_data_raw = $_GET['tg_data'] ?? '';

if (empty($tg_data_raw)) {
    die("No Telegram data provided.");
}

// Parse the query string into an array
parse_str($tg_data_raw, $auth_data);

// 4. Validate
if (!checkTelegramAuthorization($auth_data, $BOT_TOKEN)) {
    // For debugging, you might want to log this or show an error
    // die("Data is NOT from Telegram");
    // NOTE: If you haven't set the BOT_TOKEN yet, this will fail.
    // For now, if token is default, we might skip validation or warn.
    if ($BOT_TOKEN === 'YOUR_BOT_TOKEN_HERE') {
        echo "Please configure your Bot Token in telegram_auth.php";
        exit;
    }
    die("Unauthorized: Invalid Telegram signature.");
}

// 5. Extract User Info
$user_json = $auth_data['user'] ?? '{}';
$user_data = json_decode($user_json, true);

if (!$user_data) {
    die("Invalid user data.");
}

$tg_id = $user_data['id'];
$first_name = $user_data['first_name'] ?? '';
$last_name = $user_data['last_name'] ?? '';
$full_name = trim($first_name . ' ' . $last_name);
$tg_username = $user_data['username'] ?? '';

// We will use 'tg_<id>' as the unique username in our DB to ensure uniqueness and persistence
// You could also use the Telegram username, but it can change.
$db_username = "tg_" . $tg_id; 
$db_email = $tg_id . "@telegram.paxyo.com"; // Dummy email

// 6. Check if user exists
$sql = "SELECT * FROM auth WHERE username = :username";
$statement = $con->prepare($sql);
$statement->execute(array(':username' => $db_username));
$row = $statement->fetch();

if ($row) {
    // --- USER EXISTS: LOG IN ---
    $id = $row['id'];
    $username = $row['username'];
    $fromid = $row['referedby'];
    
    // Set Session
    $_SESSION['id'] = $id;
    $_SESSION['email'] = $row['email'];
    $_SESSION['username'] = $username;
    $_SESSION['pass'] = $row['password']; // Note: storing hash in session might be what existing code does
    
    // Set Cookies (copied from login/setAuthCookie.php logic)
    $hour = time() + (20 * 365 * 24 * 60 * 60);
    $secure = isset($_SERVER['HTTPS']);
    $httponly = true;
    
    setcookie("idd", $id, $hour , "/", "paxyo.com", $secure, $httponly);
    setcookie("id", $id, $hour, "/", "paxyo.com", $secure, $httponly);
    setcookie("in", "2", $hour , "/", "paxyo.com", $secure, $httponly);
    setcookie("username", $username, $hour, "/", "paxyo.com", $secure, $httponly);
    setcookie("fromid", $fromid, $hour, "/", "paxyo.com", $secure, $httponly);
    
    // Update Last Login
    $time = time() + 10;
    $update_sql = "UPDATE `auth` SET `last_login` = $time WHERE `id` = $id";
    mysqli_query($conn, $update_sql); // Note: using $conn from connect2.php for mysqli
    
    // Redirect
    header("location: smm.php");
    exit;
    
} else {
    // --- USER DOES NOT EXIST: REGISTER THEN LOG IN ---
    
    // Generate random password
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_password = '';
    for ($i = 0; $i < 12; $i++) {
        $index = rand(0, strlen($characters) - 1);
        $random_password .= $characters[$index];
    }
    $hash = password_hash($random_password, PASSWORD_DEFAULT);
    
    $referedby = $_COOKIE['fromid'] ?? 111; // Default referrer
    $referdate = date("Y-m-d");
    $pnumber = 0; // Dummy phone number
    $verified = '1'; // Auto verified
    
    try {
        $sql = "INSERT INTO auth(username, email, password, pnumber, referedby, referdate, verified) VALUES(:username, :email, :password, :pnumber, :referedby, :referdate, :v)";
        $statement = $con->prepare($sql);
        
        $result = $statement->execute(array(
            ':username' => $db_username,
            ':email' => $db_email,
            ':password' => $hash,
            ':pnumber' => $pnumber,
            ':referedby' => $referedby,
            ':referdate' => $referdate,
            ':v' => $verified
        ));
        
        if ($result) {
            // Get the newly created ID
            $new_user_id = $con->lastInsertId();
            
            // Set Session
            $_SESSION['id'] = $new_user_id;
            $_SESSION['email'] = $db_email;
            $_SESSION['username'] = $db_username;
            $_SESSION['pass'] = $hash;
            
            // Set Cookies
            $hour = time() + (20 * 365 * 24 * 60 * 60);
            $secure = isset($_SERVER['HTTPS']);
            $httponly = true;
            
            setcookie("idd", $new_user_id, $hour , "/", "paxyo.com", $secure, $httponly);
            setcookie("id", $new_user_id, $hour, "/", "paxyo.com", $secure, $httponly);
            setcookie("in", "2", $hour , "/", "paxyo.com", $secure, $httponly);
            setcookie("username", $db_username, $hour, "/", "paxyo.com", $secure, $httponly);
            setcookie("fromid", $referedby, $hour, "/", "paxyo.com", $secure, $httponly);
            
            // Redirect
            header("location: smm.php");
            exit;
            
        } else {
            die("Registration failed.");
        }
        
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}
?>
