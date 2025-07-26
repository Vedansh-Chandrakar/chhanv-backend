<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'db.php';

// Set UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");

try {
    // First update camp statuses based on current time
    $currentDate = date('m-d-Y');
    $currentTime = date('H:i:s');
    $currentDateTime = date('m-d-Y H:i:s');
    
    // Update camps to 'ongoing' if current date matches and current time is between start and end time
    $ongoingQuery = "UPDATE camps SET 
                     status = 'ongoing', 
                     updatedAt = '$currentDateTime'
                     WHERE DATE(date) = '$currentDate' 
                     AND TIME('$currentTime') >= TIME(startTime) 
                     AND TIME('$currentTime') <= TIME(endTime)
                     AND status = 'scheduled'";
    
    mysqli_query($conn, $ongoingQuery);
    
    // Update camps to 'completed' if current date matches and current time is after end time
    $completedQuery = "UPDATE camps SET 
                       status = 'completed', 
                       updatedAt = '$currentDateTime'
                       WHERE DATE(date) = '$currentDate' 
                       AND TIME('$currentTime') > TIME(endTime)
                       AND status IN ('scheduled', 'ongoing')";
    
    mysqli_query($conn, $completedQuery);
    
    // Update camps to 'completed' if date has passed
    $pastCompletedQuery = "UPDATE camps SET 
                           status = 'completed', 
                           updatedAt = '$currentDateTime'
                           WHERE DATE(date) < '$currentDate'
                           AND status IN ('scheduled', 'ongoing')";
    
    mysqli_query($conn, $pastCompletedQuery);
    
    // Now fetch all camps with updated statuses
    $query = "SELECT * FROM camps ORDER BY createdAt DESC";
    $result = mysqli_query($conn, $query);
    
    $posts = [];
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $posts[] = [
                'id' => $row['id'],
                'campName' => $row['campName'],
                'location' => $row['location'],
                'address' => $row['address'],
                'DATE' => $row['date'],
                'startTime' => $row['startTime'],
                'endTime' => $row['endTime'],
                'expectedBeneficiaries' => $row['expectedBeneficiaries'],
                'beneficiaries' => $row['beneficiaries'] ?? 0,
                'doctors' => $row['doctors'],
                'services' => $row['services'],
                'status' => $row['status'],
                'description' => $row['description'] ?? '',
                'coordinator' => $row['coordinator'] ?? '',
                'createdAt' => $row['createdAt'],
                'updatedAt' => $row['updatedAt']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'posts' => $posts,
        'message' => 'Camps retrieved with updated statuses',
        'currentTime' => $currentDateTime
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
