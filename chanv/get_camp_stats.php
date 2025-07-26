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
    // Get current date and time for status updates
    $currentDateTime = new DateTime();
    $currentDate = $currentDateTime->format('Y-m-d');
    $currentTime = $currentDateTime->format('H:i:s');
    
    // Update statuses first
    // Update to 'ongoing'
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
    
    // Update to 'completed'
    $stmt2 = $conn->prepare("
        UPDATE camps 
        SET status = 'completed', updatedAt = NOW() 
        WHERE (date < ? OR (date = ? AND endTime <= ?))
        AND status IN ('scheduled', 'ongoing')
    ");
    $stmt2->bind_param("sss", $currentDate, $currentDate, $currentTime);
    $stmt2->execute();
    
    // Get camp statistics
    $stats = [];
    
    // Total camps
    $totalCampsStmt = $conn->query("SELECT COUNT(*) as count FROM camps");
    $totalCamps = $totalCampsStmt->fetch_assoc()['count'];
    
    // Scheduled camps
    $scheduledStmt = $conn->query("SELECT COUNT(*) as count FROM camps WHERE status = 'scheduled'");
    $scheduled = $scheduledStmt->fetch_assoc()['count'];
    
    // Ongoing camps
    $ongoingStmt = $conn->query("SELECT COUNT(*) as count FROM camps WHERE status = 'ongoing'");
    $ongoing = $ongoingStmt->fetch_assoc()['count'];
    
    // Completed camps
    $completedStmt = $conn->query("SELECT COUNT(*) as count FROM camps WHERE status = 'completed'");
    $completed = $completedStmt->fetch_assoc()['count'];
    
    // Cancelled camps
    $cancelledStmt = $conn->query("SELECT COUNT(*) as count FROM camps WHERE status = 'cancelled'");
    $cancelled = $cancelledStmt->fetch_assoc()['count'];
    
    // Total beneficiaries served (from completed camps)
    $beneficiariesStmt = $conn->query("
        SELECT COALESCE(SUM(CAST(beneficiaries AS UNSIGNED)), 0) as total 
        FROM camps 
        WHERE status = 'completed' AND beneficiaries IS NOT NULL AND beneficiaries != ''
    ");
    $totalBeneficiaries = $beneficiariesStmt->fetch_assoc()['total'];
    
    // Active camps (ongoing + scheduled)
    $activeCamps = $scheduled + $ongoing;
    
    $stats = [
        'totalCamps' => (int)$totalCamps,
        'scheduled' => (int)$scheduled,
        'ongoing' => (int)$ongoing,
        'completed' => (int)$completed,
        'cancelled' => (int)$cancelled,
        'activeCamps' => (int)$activeCamps,
        'totalBeneficiaries' => (int)$totalBeneficiaries,
        'updatedAt' => $currentDateTime->format('Y-m-d H:i:s')
    ];
    
    $response = [
        'success' => true,
        'stats' => $stats
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error fetching camp statistics: ' . $e->getMessage(),
        'stats' => [
            'totalCamps' => 0,
            'scheduled' => 0,
            'ongoing' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'activeCamps' => 0,
            'totalBeneficiaries' => 0
        ]
    ];
    echo json_encode($response);
}
?>
