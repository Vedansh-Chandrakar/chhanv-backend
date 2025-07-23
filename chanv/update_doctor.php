<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// ── 1. Read incoming JSON  ─────────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);

$id             = intval($data['id']); // Safe conversion to integer
$name           = $data['name'];
$specialty      = $data['specialty'];
$phone          = $data['phone'];
$email          = $data['email'];
$experience     = intval($data['experience']);
$qualification  = $data['qualification'];
$assignedCamps  = $data['assignedCamps'];
$status         = $data['status'];
$password       = $data['password'];

// ── 2. UPDATE the record ───────────────────────────────────────────────────────
$stmt = $conn->query(
    "UPDATE doctors SET
        name = '".$name."',
        specialty = '".$specialty."',
        phone = '".$phone."',
        email = '".$email."',
        experience = ".$experience.",
        qualification = '".$qualification."',
        assignedCamps = '".$assignedCamps."',
        status = '".$status."',
        password = '".$password."'
     WHERE id = ".$id
);

// ── 3. Return updated list of active doctors ───────────────────────────────────
$stmt = $conn->query(
    "SELECT id, name, specialty, phone, email, 
            experience, qualification, assignedCamps, status
     FROM doctors
     WHERE status = 'active'"
);

$posts = [];
while ($r = $stmt->fetch_array()) {
    $posts[] = array(
        "id"            => $r[0],
        "name"          => $r[1],
        "specialty"     => $r[2],
        "phone"         => $r[3],
        "email"         => $r[4],
        "experience"    => $r[5],
        "qualification" => $r[6],
        "assignedCamps" => $r[7],
        "status"        => $r[8],
    );
}

$response['posts'] = $posts;
echo json_encode($response);
?>
