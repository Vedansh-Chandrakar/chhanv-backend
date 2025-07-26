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

try {
    // Get current date and time
    $currentDateTime = new DateTime();
    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('H:i:s');
    
    // First update statuses based on current time
    // Update to 'ongoing' for camps that should be running now
    $stmt1 = $conn->prepare("
        UPDATE camps 
        SET status = 'ongoing', updatedAt = NOW() 
        WHERE date = ? 
        AND startTime <= ? 
        AND endTime > ? 
        AND status = 'scheduled'
    ");
    $stmt1->bind_param("sss", $currentDate, $currentTime, $currentTime);
    $stmt1->execute();
    
    // Update to 'completed' for camps that have ended
    $stmt2 = $conn->prepare("
        UPDATE camps 
        SET status = 'completed', updatedAt = NOW() 
        WHERE (date < ? OR (date = ? AND endTime <= ?))
        AND status IN ('scheduled', 'ongoing')
    ");
    $stmt2->bind_param("sss", $currentDate, $currentDate, $currentTime);
    $stmt2->execute();
    
    // Now get all camps with updated status
    $stmt = $conn->query("
        SELECT 
            id, 
            campName, 
            location, 
            date, 
            startTime, 
            endTime, 
            address, 
            coordinator, 
            expectedBeneficiaries, 
            doctors, 
            services, 
            status, 
            beneficiaries 
        FROM camps 
        ORDER BY date DESC, startTime DESC
    ");
    
    $posts = [];
    while ($r = $stmt->fetch_array()) {
        $posts[] = [
            "id" => $r[0],
            "campName" => $r[1],
            "location" => $r[2],
            "DATE" => $r[3],
            "startTime" => $r[4],
            "endTime" => $r[5],
            "address" => $r[6],
            "coordinator" => $r[7],
            "expectedBeneficiaries" => $r[8],
            "doctors" => $r[9],
            "services" => $r[10],
            "STATUS" => $r[11],
            "beneficiaries" => $r[12]
        ];
    }
    
    $response['posts'] = $posts;
    $response['success'] = true;
    $response['updatedAt'] = $currentDateTime->format('Y-m-d H:i:s');
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error fetching camps: ' . $e->getMessage(),
        'posts' => []
    ];
    echo json_encode($response);
}
?>
