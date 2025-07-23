<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$data = json_decode(file_get_contents('php://input'), true);

// Fetch all doctors from the database with correct column names
$stmt = $conn->query("
    SELECT 
        id, 
        name, 
        specialization , 
        phoneNo , 
        email, 
        experience, 
        qualification, 
        assignedCamps 
    FROM doctors
");

$posts = [];

while ($r = $stmt->fetch_array()) {
   $posts[] = array(
    "id"            => $r['id'],
    "name"          => $r['name'],
    "specialty"     => $r['specialization'], // ✅ correct mapping
    "phone"         => $r['phoneNo'],        // ✅ correct mapping
    "email"         => $r['email'],
    "experience"    => $r['experience'],
    "qualification" => $r['qualification'],
    "assignedCamps" => $r['assignedCamps']
);

}

$response['posts'] = $posts;

// Send JSON response
echo json_encode($response);
?>
