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

$id = $data['id'];
$campName = $data['campName'];
$location = $data['location'];
$date = $data['date'];
$startTime = $data['startTime'];
$endTime = $data['endTime'];
$address = $data['address'];
$description = $data['description'];
$expectedBeneficiaries = (int)$data['expectedBeneficiaries'];
$doctors = $data['doctors']; // comma-separated or JSON string
$services = $data['services']; // comma-separated or JSON string
$coordinator = $data['coordinator'];
$status = $data['status'];
$beneficiaries = (int)$data['beneficiaries'];
$createdBy = $data['createdBy'];
$createdAt = $data['createdAt'];


$stmt = $conn->query("
    INSERT INTO camps 
    (campName, location, date, startTime, endTime, address, description, expectedBeneficiaries, doctors, services, coordinator, status, beneficiaries, createdBy, createdAt) 
    VALUES (
        '$campName', 
        '$location', 
        '$date', 
        '$startTime', 
        '$endTime', 
        '$address', 
        '$description', 
        $expectedBeneficiaries, 
        '$doctors', 
        '$services', 
        '$coordinator', 
        '$status', 
        $beneficiaries, 
        '$createdBy', 
        '$createdAt'
    )
");


$stmt = $conn->query("
    SELECT id, campName, location, date, startTime, endTime, address, coordinator, expectedBeneficiaries, doctors, services, status, beneficiaries 
    FROM camps 
    WHERE status = 'scheduled'
");

while ($r = $stmt->fetch_array()) {
    $posts[] = array(
        "id" => $r[0],
        "campName" => $r[1],
        "location" => $r[2],
        "date" => $r[3],
        "startTime" => $r[4],
        "endTime" => $r[5],
        "address" => $r[6],
        "coordinator" => $r[7],
        "expectedBeneficiaries" => $r[8],
        "doctors" => $r[9],
        "services" => $r[10],
        "status" => $r[11],
        "beneficiaries" => $r[12]
    );
}

$response['posts'] = $posts;

echo json_encode($response);
?>
