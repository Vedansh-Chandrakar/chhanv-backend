<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Fetch all doctors from the database
$stmt = $conn->query("
    SELECT 
        id, 
        name, 
        specialization, 
        phoneNo, 
        email, 
        experience, 
        qualification 
    FROM doctors
");

$posts = [];

while ($r = $stmt->fetch_array()) {
    $doctorName = $r['name'];

    // Get camps where this doctor is listed in the 'doctors' field (comma-separated names)
    $campStmt = $conn->prepare("SELECT campName FROM camps WHERE FIND_IN_SET(?, doctors)");
    $campStmt->bind_param("s", $doctorName);
    $campStmt->execute();
    $campResult = $campStmt->get_result();

    $campNames = [];
    while ($camp = $campResult->fetch_assoc()) {
        $campNames[] = $camp['campName'];
    }

    $posts[] = array(
        "id"            => $r['id'],
        "name"          => $r['name'],
        "specialty"     => $r['specialization'],
        "phone"         => $r['phoneNo'],
        "email"         => $r['email'],
        "experience"    => $r['experience'],
        "qualification" => $r['qualification'],
        "camps"         => $campNames
    );
}

$response['posts'] = $posts;

// Send JSON response
echo json_encode($response);
?>
