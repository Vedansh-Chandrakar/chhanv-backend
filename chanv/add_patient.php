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

// Check if data is received
if (!$data) {
    echo json_encode(["success" => false, "message" => "No data received."]);
    exit;
}

// Assign variables and handle optional email
$name = $data['name'];
$age = $data['age'];
$email = isset($data['email']) && !empty($data['email']) ? $data['email'] : null; // Make email optional, store as NULL if not provided
$phone = $data['phone'];
$password = $data['password'];
$dateOfBirth = $data['dateOfBirth'];
$gender = $data['gender'];
$bloodGroup = $data['bloodGroup'];
$address = $data['address'];
$familyMembers = $data['familyMembers'];
$department = $data['department'];
$hasAbhaId = $data['hasAbhaId'];
$hasAyushmanCard = $data['hasAyushmanCard'];

// Use prepared statements to prevent SQL injection
$sql_insert = "INSERT INTO users(fullname, email, phoneNumber, PASSWORD, dateOfBirth, age, gender, bloodGroup, department, address, familymember, hasAbhaId, hasAyushmanCard) 
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt_insert = $conn->prepare($sql_insert);
// 's' for string, 'i' for integer. Adjust types as per your DB schema.
$stmt_insert->bind_param("sssssisssiiss", 
    $name, 
    $email, 
    $phone, 
    $password, 
    $dateOfBirth, 
    $age, 
    $gender, 
    $bloodGroup, 
    $department, 
    $address, 
    $familyMembers, 
    $hasAbhaId, 
    $hasAyushmanCard
);

if (!$stmt_insert->execute()) {
    echo json_encode(["success" => false, "message" => "Error adding patient: " . $stmt_insert->error]);
    exit;
}
$stmt_insert->close();

// Fetch the updated list of all patients
$sql_select = "SELECT id, fullname, email, phoneNumber, dateOfBirth, age, gender, bloodGroup, department, address, familymember, hasAbhaId, hasAyushmanCard FROM users ORDER BY id DESC";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->execute();
$result = $stmt_select->get_result();

$posts = [];
while ($r = $result->fetch_assoc()) {
    $posts[] = [
        "id" => $r['id'],
        "name" => $r['fullname'],
        "email" => $r['email'],
        "phone" => $r['phoneNumber'],
        "dateOfBirth" => $r['dateOfBirth'],
        "age" => $r['age'],
        "gender" => $r['gender'],
        "bloodGroup" => $r['bloodGroup'],
        "department" => $r['department'],
        "address" => $r['address'],
        "familyMembers" => $r['familymember'],
        "hasAbhaId" => $r['hasAbhaId'],
        "hasAyushmanCard" => $r['hasAyushmanCard']
    ];
}

$stmt_select->close();
$conn->close();

echo json_encode(['posts' => $posts]);
?>
