<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'];

// Delete the camp with the given ID
$stmt = $conn->query("DELETE FROM camps WHERE id = " . $id);

// Fetch updated list of scheduled camps
$stmt = $conn->query("
    SELECT id, campName, location, date, startTime, endTime, address, coordinator, expectedBeneficiaries, doctors, services, status, beneficiaries 
    FROM camps 
    WHERE status = 'scheduled'
");

// Prepare response
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
