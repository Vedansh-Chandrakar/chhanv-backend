<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$phone = trim($data['t1']);
$password = trim($data['t2']);

try {
    // Use prepared statements to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, name, phoneNumber, password FROM users WHERE phoneNumber = ? AND role = 'user'");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        // Verify password (assuming passwords are hashed)
        if (password_verify($password, $user['password'])) {
            // Login successful
            $response = [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'phone' => $user['phoneNumber']
                ]
            ];
            echo json_encode($response);
        } else {
            // Invalid password
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } else {
        // User not found
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    error_log($e->getMessage()); // Log error for debugging
}

$conn->close();
?>