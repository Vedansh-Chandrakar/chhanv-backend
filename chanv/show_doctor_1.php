<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $stmt = $conn->query("
        SELECT 
            id, 
            name, 
            hospitaltype, 
            hospitalname, 
            specialization, 
            phoneNo, 
            experience, 
            email, 
            qualification, 
            assignedCamps 
        FROM doctors 
        ORDER BY name ASC
    ");
    
    $posts = [];
    while ($r = $stmt->fetch_array()) {
        $posts[] = [
            "id" => $r[0],
            "name" => $r[1],
            "hospitalType" => $r[2],
            "hospitalName" => $r[3],
            "specialty" => $r[4], // Using specialty for consistency with frontend
            "specialization" => $r[4], // Also include specialization as per schema
            "phone" => $r[5], // Using phone for consistency with frontend
            "phoneNo" => $r[5], // Also include phoneNo as per schema
            "experience" => $r[6],
            "email" => $r[7],
            "qualification" => $r[8],
            "assignedCamps" => $r[9],
            "avatar" => "" // Default empty avatar
        ];
    }
    
    $response = [
        'success' => true,
        'posts' => $posts
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error fetching doctors: ' . $e->getMessage(),
        'posts' => []
    ];
    echo json_encode($response);
}
?>
