<?php
require 'db.php';

// Set headers for CORS and JSON response
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Fetch all patients/users from all three tables
$posts = [];

try {
    // 1. Get data from users table
    $usersQuery = "
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
    
    $usersResult = $conn->query($usersQuery);
    
    if ($usersResult) {
        while ($row = $usersResult->fetch_assoc()) {
            $posts[] = [
                "id" => "user_" . $row['id'],
                "original_id" => $row['id'],
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
                "hasAyushmanCard" => $row['hasAyushmanCard'],
                "type" => "user"
            ];
        }
    }
    
    // 2. Get data from relatives table
    $relativesQuery = "
        SELECT 
            r.r_id,
            r.id as user_id,
            r.fullName,
            r.relation,
            r.dateOfBirth,
            r.bloodGroup,
            r.gender,
            r.phoneNumber,
            u.fullname as main_user_name
        FROM relatives r
        LEFT JOIN users u ON r.id = u.id
    ";
    
    $relativesResult = $conn->query($relativesQuery);
    
    if ($relativesResult) {
        while ($row = $relativesResult->fetch_assoc()) {
            $posts[] = [
                "id" => "relative_" . $row['r_id'],
                "original_id" => $row['r_id'],
                "name" => $row['fullName'],
                "email" => "", // Relatives don't have email
                "phone" => $row['phoneNumber'] ?? "",
                "dateOfBirth" => $row['dateOfBirth'],
                "age" => null, // Calculate age if needed
                "gender" => $row['gender'],
                "bloodGroup" => $row['bloodGroup'],
                "department" => "", // Relatives don't have department
                "address" => "", // Relatives don't have address
                "familyMembers" => 0,
                "hasAbhaId" => "",
                "hasAyushmanCard" => "",
                "relation" => $row['relation'],
                "main_user" => $row['main_user_name'],
                "type" => "relative"
            ];
        }
    }
    
    // 3. Get data from outsiders table
    $outsidersQuery = "
        SELECT 
            p_id,
            fullname,
            dateofbirth,
            age,
            bloodgroup,
            gender,
            phonenumber
        FROM outsiders
    ";
    
    $outsidersResult = $conn->query($outsidersQuery);
    
    if ($outsidersResult) {
        while ($row = $outsidersResult->fetch_assoc()) {
            $posts[] = [
                "id" => "outsider_" . $row['p_id'],
                "original_id" => $row['p_id'],
                "name" => $row['fullname'],
                "email" => "", // Outsiders don't have email
                "phone" => $row['phonenumber'],
                "dateOfBirth" => $row['dateofbirth'],
                "age" => (int)$row['age'],
                "gender" => $row['gender'],
                "bloodGroup" => $row['bloodgroup'],
                "department" => "", // Outsiders don't have department
                "address" => "", // Outsiders don't have address
                "familyMembers" => 0,
                "hasAbhaId" => "",
                "hasAyushmanCard" => "",
                "type" => "outsider"
            ];
        }
    }

    echo json_encode(["posts" => $posts]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
