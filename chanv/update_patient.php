<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Get incoming JSON
$data = json_decode(file_get_contents('php://input'), true);

// 2. Extract and sanitize input
$id              = intval($data['id']);
$fullname        = $data['name'] ?? '';
$email           = $data['email'] ?? '';
$phoneNumber     = $data['phone'] ?? '';
$password        = $data['password'] ?? '';
$dateOfBirth     = $data['dateOfBirth'] ?? '';
$age             = intval($data['age'] ?? 0);
$gender          = $data['gender'] ?? '';
$bloodGroup      = $data['bloodGroup'] ?? '';
$department      = $data['department'] ?? '';
$address         = $data['address'] ?? '';
$familymember    = intval($data['familyMembers'] ?? 0); // match with frontend
$hasAbhaid       = $data['hasAbhaId'] ?? 'no';
$hasAyushmanCard = $data['hasAyushmanCard'] ?? 'no';
$updatedAt       = $data['updatedAt'] ?? date('Y-m-d H:i:s');

// 3. Prepare and execute update query
$stmt = $conn->prepare("
    UPDATE users SET
        fullname = ?, email = ?, phoneNumber = ?, password = ?, dateOfBirth = ?, age = ?,
        gender = ?, bloodGroup = ?, department = ?, address = ?, familymember = ?,
        hasAbhaid = ?, hasAyushmanCard = ?, updatedAt = ?
    WHERE id = ?
");

$stmt->bind_param(
    "sssssissssisssi",
    $fullname, $email, $phoneNumber, $password, $dateOfBirth, $age,
    $gender, $bloodGroup, $department, $address, $familymember,
    $hasAbhaid, $hasAyushmanCard, $updatedAt, $id
);

$stmt->execute();
$stmt->close();

// 4. Return updated list of patients
$result = $conn->query("
    SELECT id, fullname, email, phoneNumber, password, dateOfBirth, age, gender,
           bloodGroup, department, address, familymember, hasAbhaid, hasAyushmanCard
    FROM users
");

$posts = [];
while ($r = $result->fetch_assoc()) {
    $posts[] = array(
        "id"              => $r['id'],
        "name"            => $r['fullname'],
        "email"           => $r['email'],
        "phone"           => $r['phoneNumber'],
        "password"        => $r['password'],
        "dateOfBirth"     => $r['dateOfBirth'],
        "age"             => $r['age'],
        "gender"          => $r['gender'],
        "bloodGroup"      => $r['bloodGroup'],
        "department"      => $r['department'],
        "address"         => $r['address'],
        "familyMembers"   => $r['familymember'],
        "hasAbhaId"       => $r['hasAbhaid'],
        "hasAyushmanCard" => $r['hasAyushmanCard']
    );
}

echo json_encode(['posts' => $posts]);
?>
