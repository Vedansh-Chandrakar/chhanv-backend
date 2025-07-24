<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Get JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Extract and sanitize input
$id = (int)($data['id'] ?? 0);
$name = trim($data['name'] ?? '');
$specialization = trim($data['specialty'] ?? ''); // match with 'specialty' input
$phoneNo = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$experience = (int)($data['experience'] ?? 0);
$qualification = trim($data['qualification'] ?? '');
$assignedCamps = trim($data['assignedCamps'] ?? '');
$updatedAt = date('Y-m-d H:i:s');

// ✅ Validate required fields
if (
    $id <= 0 ||
    empty($name) ||
    empty($specialization) ||
    empty($phoneNo) ||
    empty($email) ||
    $experience <= 0
) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // ✅ Update query: with or without password
    if (!empty($password)) {
        $stmt = $conn->prepare("
            UPDATE doctors SET  
                name = ?, 
                specialization = ?, 
                phoneNo = ?, 
                email = ?, 
                password = ?, 
                experience = ?, 
                qualification = ?, 
                assignedCamps = ?, 
                updatedAt = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "sssssisssi",
            $name,
            $specialization,
            $phoneNo,
            $email,
            $password,
            $experience,
            $qualification,
            $assignedCamps,
            $updatedAt,
            $id
        );
    } else {
        $stmt = $conn->prepare("
            UPDATE doctors SET  
                name = ?, 
                specialization = ?, 
                phoneNo = ?, 
                email = ?, 
                experience = ?, 
                qualification = ?, 
                assignedCamps = ?, 
                updatedAt = ?
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssisssi",
            $name,
            $specialization,
            $phoneNo,
            $email,
            $experience,
            $qualification,
            $assignedCamps,
            $updatedAt,
            $id
        );
    }

    $stmt->execute();

    // ✅ Fetch updated doctor list
    $result = $conn->query("
        SELECT id, name, specialization, phoneNo, email, experience, qualification, assignedCamps
        FROM doctors
    ");

    $posts = [];

    while ($r = $result->fetch_assoc()) {
        $posts[] = array(
            "id" => $r['id'],
            "name" => $r['name'],
            "specialization" => $r['specialization'],
            "phoneNo" => $r['phoneNo'],
            "email" => $r['email'],
            "experience" => $r['experience'],
            "qualification" => $r['qualification'],
            "assignedCamps" => $r['assignedCamps'],
        );
    }

    echo json_encode(['posts' => $posts]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
