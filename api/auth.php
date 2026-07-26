<?php
// ============================================================
// AI Meeting Minutes Summarizer - Auth API Endpoint
// ============================================================

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Read JSON input if sent via fetch POST body
$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

if (isset($input['action'])) {
    $action = $input['action'];
}

$db = getDBConnection();

switch ($action) {
    case 'register':
        handleRegister($db, $input);
        break;
    case 'login':
        handleLogin($db, $input);
        break;
    case 'logout':
        handleLogout();
        break;
    case 'me':
        handleCheckSession($db);
        break;
    default:
        sendJsonResponse(false, [], 'Invalid authentication action', 400);
}

function handleRegister($db, $input) {
    $fullName = trim($input['full_name'] ?? '');
    $email    = trim(strtolower($input['email'] ?? ''));
    $password = $input['password'] ?? '';
    $role     = trim($input['role'] ?? 'Student/Member');

    if (empty($fullName) || empty($email) || empty($password)) {
        sendJsonResponse(false, [], 'All required fields must be filled.', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(false, [], 'Invalid email address format.', 400);
    }

    if (strlen($password) < 6) {
        sendJsonResponse(false, [], 'Password must be at least 6 characters.', 400);
    }

    // Database or Demo Mode Check
    if (!$db) {
        // Fallback for demo when MySQL is offline
        $_SESSION['user_id'] = 999;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        sendJsonResponse(true, [
            'id' => 999,
            'full_name' => $fullName,
            'email' => $email,
            'role' => $role,
            'is_demo' => true
        ], 'Registration successful (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            sendJsonResponse(false, [], 'Email address is already registered.', 409);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullName, $email, $passwordHash, $role]);

        $userId = $db->lastInsertId();

        $_SESSION['user_id']   = $userId;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        sendJsonResponse(true, [
            'id'        => $userId,
            'full_name' => $fullName,
            'email'     => $email,
            'role'      => $role
        ], 'Registration successful! Welcome aboard.');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}

function handleLogin($db, $input) {
    $email    = trim(strtolower($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($email) || empty($password)) {
        sendJsonResponse(false, [], 'Please provide both email and password.', 400);
    }

    // Database or Demo Mode Check
    if (!$db) {
        // Fallback for demo mode (accept default credentials or demo login)
        $_SESSION['user_id']   = 1;
        $_SESSION['user_name'] = 'Alex Morgan (Demo User)';
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'Team Lead';

        sendJsonResponse(true, [
            'id'        => 1,
            'full_name' => 'Alex Morgan',
            'email'     => $email,
            'role'      => 'Team Lead',
            'is_demo'   => true
        ], 'Login successful (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            sendJsonResponse(false, [], 'Invalid email or password.', 401);
        }

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        sendJsonResponse(true, [
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => $user['role']
        ], 'Login successful!');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}

function handleLogout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    sendJsonResponse(true, [], 'Logged out successfully.');
}

function handleCheckSession($db) {
    if (isset($_SESSION['user_id'])) {
        sendJsonResponse(true, [
            'id'        => $_SESSION['user_id'],
            'full_name' => $_SESSION['user_name'] ?? 'User',
            'email'     => $_SESSION['user_email'] ?? '',
            'role'      => $_SESSION['user_role'] ?? 'Member'
        ], 'Active session.');
    } else {
        sendJsonResponse(false, [], 'No active session.', 401);
    }
}
