<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php'; // Database connection

// Initialize response
$response = array();

try {
    // Only allow POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response = [
            "success" => false,
            "message" => "Only POST method allowed"
        ];
        echo json_encode($response);
        exit;
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response = [
            "success" => false,
            "message" => "Invalid JSON input"
        ];
        echo json_encode($response);
        exit;
    }

    // Validate required fields
    $required_fields = ['userId', 'fullName', 'relation', 'dateOfBirth', 'gender', 'phoneNumber'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $response = [
                "success" => false,
                "message" => "Required field missing: " . $field
            ];
            echo json_encode($response);
            exit;
        }
    }

    // Sanitize and validate inputs
    $userId = (int)$input['userId'];
    $fullName = trim($input['fullName']);
    $relation = trim($input['relation']);
    $dateOfBirth = trim($input['dateOfBirth']);
    $bloodGroup = isset($input['bloodGroup']) && !empty(trim($input['bloodGroup'])) ? trim($input['bloodGroup']) : null;
    $gender = trim($input['gender']);
    $phoneNumber = trim($input['phoneNumber']);

    // Validate phone number
    if (!preg_match('/^[0-9]{10}$/', $phoneNumber)) {
        $response = [
            "success" => false,
            "message" => "Invalid phone number format. Please provide 10 digit number."
        ];
        echo json_encode($response);
        exit;
    }

    // Validate gender
    $valid_genders = ['male', 'female', 'other'];
    if (!in_array($gender, $valid_genders)) {
        $response = [
            "success" => false,
            "message" => "Invalid gender. Use: male, female, or other"
        ];
        echo json_encode($response);
        exit;
    }

    // Validate blood group if provided
    if ($bloodGroup) {
        $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!in_array($bloodGroup, $valid_blood_groups)) {
            $response = [
                "success" => false,
                "message" => "Invalid blood group"
            ];
            echo json_encode($response);
            exit;
        }
    }

    // Validate date format
    $date_check = DateTime::createFromFormat('Y-m-d', $dateOfBirth);
    if (!$date_check || $date_check->format('Y-m-d') !== $dateOfBirth) {
        $response = [
            "success" => false,
            "message" => "Invalid date format. Use YYYY-MM-DD"
        ];
        echo json_encode($response);
        exit;
    }

    // Check if user exists
    $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->bind_param("i", $userId);
    $user_check->execute();
    $user_result = $user_check->get_result();
    
    if ($user_result->num_rows === 0) {
        $response = [
            "success" => false,
            "message" => "User not found"
        ];
        echo json_encode($response);
        exit;
    }
    $user_check->close();

    // Insert family member
    $stmt = $conn->prepare("INSERT INTO relatives (id, fullName, relation, dateOfBirth, bloodGroup, gender, phoneNumber) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("issssss", $userId, $fullName, $relation, $dateOfBirth, $bloodGroup, $gender, $phoneNumber);
    
    if ($stmt->execute()) {
        $relative_id = $conn->insert_id;
        
        $response = [
            "success" => true,
            "message" => "Family member added successfully",
            "data" => [
                "r_id" => $relative_id,
                "id" => $userId,
                "fullName" => $fullName,
                "relation" => $relation,
                "dateOfBirth" => $dateOfBirth,
                "bloodGroup" => $bloodGroup,
                "gender" => $gender,
                "phoneNumber" => $phoneNumber
            ]
        ];
    } else {
        throw new Exception("Failed to insert family member: " . $stmt->error);
    }

    $stmt->close();

} catch (Exception $e) {
    $response = [
        "success" => false,
        "message" => "Error: " . $e->getMessage()
    ];
}

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
