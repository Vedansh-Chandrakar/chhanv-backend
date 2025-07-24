<?php
require 'db.php';

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Fetch all patients/users from the database
$query = "
    SELECT 
        id,
        fullname,
        email,
        phoneNumber,
        dateOfBirth,
        age,
        gender,
        bloodGroup,
        department,
        address,
        familymember,
        hasAbhaId,
        hasAyushmanCard
    FROM users
";

$result = $conn->query($query);

$posts = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = [
            "id" => $row['id'],
            "name" => $row['fullname'],
            "email" => $row['email'],
            "phone" => $row['phoneNumber'],
            "dateOfBirth" => $row['dateOfBirth'],
            "age" => (int)$row['age'],
            "gender" => $row['gender'],
            "bloodGroup" => $row['bloodGroup'],
            "department" => $row['department'],
            "address" => $row['address'],
            "familyMembers" => (int)$row['familymember'],
            "hasAbhaId" => $row['hasAbhaId'],
            "hasAyushmanCard" => $row['hasAyushmanCard']
        ];
    }

    echo json_encode(["posts" => $posts]);
} else {
    // In case of DB error
    http_response_code(500);
    echo json_encode(["error" => "Database query failed."]);
}
?>
