<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
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
    // Check if userId parameter exists
    if (!isset($_GET['userId']) || empty($_GET['userId'])) {
        $response = [
            "success" => false,
            "message" => "User ID is required"
        ];
        echo json_encode($response);
        exit;
    }

    $userId = (int)$_GET['userId'];

    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Prepare and execute query to get family members
    $stmt = $conn->prepare("SELECT 
        r_id,
        fullName,
        relation,
        dateOfBirth,
        bloodGroup,
        gender,
        phoneNumber,
        createdAt
        FROM relatives 
        WHERE id = ? 
        ORDER BY createdAt DESC");
    
    if (!$stmt) {
        throw new Exception("Query preparation failed: " . $conn->error);
    }

    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $familyMembers = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Calculate age from date of birth
            $dob = new DateTime($row['dateOfBirth']);
            $today = new DateTime();
            $age = $today->diff($dob)->y;
            
            $familyMembers[] = [
                "r_id" => (int)$row['r_id'],
                "fullName" => $row['fullName'],
                "relation" => $row['relation'],
                "dateOfBirth" => $row['dateOfBirth'],
                "age" => $age,
                "bloodGroup" => $row['bloodGroup'],
                "gender" => $row['gender'],
                "phoneNumber" => $row['phoneNumber'],
                "createdAt" => $row['createdAt']
            ];
        }
    }

    $response = [
        "success" => true,
        "familyMembers" => $familyMembers,
        "count" => count($familyMembers),
        "message" => "Family members fetched successfully"
    ];

    $stmt->close();

} catch (Exception $e) {
    $response = [
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "familyMembers" => []
    ];
}

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
