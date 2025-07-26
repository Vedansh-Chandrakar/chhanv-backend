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

$data = json_decode(file_get_contents('php://input'), true);

try {
    // Prepare update statement - only update password if provided
    if (!empty($data['password'])) {
        // Store password as plain text (no hashing)
        
        $stmt = $conn->prepare("
            UPDATE doctors SET 
                name = ?, 
                hospitaltype = ?, 
                hospitalname = ?, 
                specialization = ?, 
                phoneNo = ?, 
                experience = ?, 
                email = ?, 
                qualification = ?, 
                assignedCamps = ?, 
                password = ?,
                updatedAt = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "sssssissssi",
            $data['name'],
            $data['hospitalType'],
            $data['hospitalName'],
            $data['specialty'],
            $data['phone'],
            $data['experience'],
            $data['email'],
            $data['qualification'],
            $data['assignedCamps'],
            $data['password'],
            $data['id']
        );
    } else {
        // Update without changing password
        $stmt = $conn->prepare("
            UPDATE doctors SET 
                name = ?, 
                hospitaltype = ?, 
                hospitalname = ?, 
                specialization = ?, 
                phoneNo = ?, 
                experience = ?, 
                email = ?, 
                qualification = ?, 
                assignedCamps = ?, 
                updatedAt = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "sssssisssi",
            $data['name'],
            $data['hospitalType'],
            $data['hospitalName'],
            $data['specialty'],
            $data['phone'],
            $data['experience'],
            $data['email'],
            $data['qualification'],
            $data['assignedCamps'],
            $data['id']
        );
    }
    
    if ($stmt->execute()) {
        // Fetch all doctors after successful update to return updated list
        $selectStmt = $conn->query("
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
        while ($r = $selectStmt->fetch_array()) {
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
                "status" => "active" // Default status
            ];
        }
        
        $response = [
            'success' => true,
            'message' => 'Doctor updated successfully',
            'posts' => $posts
        ];
        
    } else {
        $response = [
            'success' => false,
            'message' => 'Error updating doctor: ' . $stmt->error,
            'posts' => []
        ];
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Check if it's a duplicate email error
    if (strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), 'email') !== false) {
        $response = [
            'success' => false,
            'message' => 'Email already exists. Please use a different email address.',
            'posts' => []
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'posts' => []
        ];
    }
    echo json_encode($response);
}
?>
