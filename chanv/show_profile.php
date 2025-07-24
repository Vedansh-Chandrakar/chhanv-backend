<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json");

require 'db.php'; // Database connection

// Initialize response
$response = array();

try {
    // Check if phoneNumber parameter exists
    if (!isset($_GET['phoneNumber']) || empty($_GET['phoneNumber'])) {
        $response = [
            "success" => false,
            "message" => "Phone number is required"
        ];
        echo json_encode($response);
        exit;
    }

    $phoneNumber = trim($_GET['phoneNumber']); // Clean input

    // Validate phone number format (basic validation)
    if (!preg_match('/^[0-9]{10}$/', $phoneNumber)) {
        $response = [
            "success" => false,
            "message" => "Invalid phone number format. Please provide 10 digit number."
        ];
        echo json_encode($response);
        exit;
    }

    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Prepare and execute query (MySQLi style)
    $stmt = $conn->prepare("SELECT 
        id,
        fullname,
        email,
        phoneNumber,
        dateOfBirth,
        age,
        gender,
        bloodGroup,
        department,
        address,
        familymember,
        hasAbhaId,
        hasAyushmanCard,
        createdAt
        FROM users 
        WHERE phoneNumber = ? 
        LIMIT 1");
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("s", $phoneNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Clean and format the data
        $userData = [
            "id" => (int)$user['id'],
            "fullname" => $user['fullname'] ?? '',
            "email" => $user['email'] ?? '',
            "phoneNumber" => $user['phoneNumber'] ?? '',
            "dateOfBirth" => $user['dateOfBirth'] ?? '',
            "age" => $user['age'] ? (int)$user['age'] : null,
            "gender" => $user['gender'] ?? '',
            "bloodGroup" => $user['bloodGroup'] ?? '',
            "department" => $user['department'] ?? '',
            "address" => $user['address'] ?? '',
            "familymember" => (int)$user['familymember'],
            "hasAbhaId" => $user['hasAbhaId'] ?? '',
            "hasAyushmanCard" => $user['hasAyushmanCard'] ?? '',
            "createdAt" => $user['createdAt'] ?? ''
        ];

        $response = [
            "success" => true,
            "posts" => [$userData], // React Native expects array format
            "message" => "User profile fetched successfully"
        ];
    } else {
        $response = [
            "success" => false,
            "message" => "User not found with phone number: " . $phoneNumber,
            "posts" => []
        ];
    }

    $stmt->close();

} catch (Exception $e) {
    $response = [
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "posts" => []
    ];
}

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>