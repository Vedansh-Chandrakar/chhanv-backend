<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);

$name = $data['name'];
$specialty = $data['specialty'];
$phone = $data['phone'];
$email = $data['email'];
$password = $data['password'];
$experience = (int)$data['experience'];
$qualification = $data['qualification'];
$assignedCamps = $data['assignedCamps'];

// Insert into doctors table (id is auto-increment)
$stmt = $conn->query("
    INSERT INTO doctors 
    (name, specialization, phoneNo, email, password, experience, qualification, assignedCamps) 
    VALUES (
        '".$name."',
        '".$specialty."',
        '".$phone."',
        '".$email."',
        '".$password."',
        ".$experience.",
        '".$qualification."',
        '".$assignedCamps."'
    )
");

// ✅ Fetch updated doctors list
$stmt = $conn->query("
    SELECT id, name, specialization, phoneNo, email, experience, qualification, assignedCamps 
    FROM doctors
");

$posts = [];
while($r = $stmt->fetch_array()) {
    $posts[] = array(
        "id" => $r[0],
        "name" => $r[1],
        "specialization" => $r[2],
        "phoneNo" => $r[3],
        "email" => $r[4],
        "experience" => $r[5],
        "qualification" => $r[6],
        "assignedCamps" => $r[7],
    );
}

$response['posts'] = $posts;

echo json_encode($response);
?>
