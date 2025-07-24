<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Extract fields
$name = trim($data['name'] ?? '');
$specialty = trim($data['specialty'] ?? '');
$phone = trim($data['phone'] ?? '');
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');
$experience = (int)($data['experience'] ?? 0);
$qualification = trim($data['qualification'] ?? '');
$assignedCamps = trim($data['assignedCamps'] ?? '');

// ✅ Validate inputs
if (
    empty($name) ||
    empty($specialty) ||
    empty($phone) ||
    empty($email) ||
    empty($password) ||
    $experience <= 0
) {
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

try {
    // ✅ Use prepared statements for security
    $stmt = $conn->prepare("
        INSERT INTO doctors 
        (name, specialization, phoneNo, email, password, experience, qualification, assignedCamps)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssssssss",
        $name,
        $specialty,
        $phone,
        $email,
        $password,
        $experience,
        $qualification,
        $assignedCamps
    );

    $stmt->execute();

    // ✅ Fetch updated doctor list
    $result = $conn->query("
        SELECT id, name, specialization, phoneNo, email, experience, qualification, assignedCamps
        FROM doctors
    ");

    $posts = [];

    while ($r = $result->fetch_assoc()) {
        // ✅ Filter out empty or invalid entries
        if (
            !empty($r['name']) &&
            !empty($r['specialization']) &&
            !empty($r['phoneNo']) &&
            !empty($r['email']) &&
            intval($r['experience']) > 0
        ) {
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
    }

    echo json_encode(['posts' => $posts]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>
