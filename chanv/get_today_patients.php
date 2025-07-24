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
    // Get today's count from all three tables: relatives, users, outsiders
    $today = date('Y-m-d');
    
    // Count from relatives table
    $relativesQuery = "SELECT COUNT(*) as count FROM relatives WHERE DATE(createdAt) = ?";
    $stmt1 = $conn->prepare($relativesQuery);
    $stmt1->bind_param("s", $today);
    $stmt1->execute();
    $relativesCount = $stmt1->get_result()->fetch_assoc()['count'];
    
    // Count from users table
    $usersQuery = "SELECT COUNT(*) as count FROM users WHERE DATE(createdAt) = ?";
    $stmt2 = $conn->prepare($usersQuery);
    $stmt2->bind_param("s", $today);
    $stmt2->execute();
    $usersCount = $stmt2->get_result()->fetch_assoc()['count'];
    
    // Count from outsiders table
    $outsidersQuery = "SELECT COUNT(*) as count FROM outsiders WHERE DATE(createdAt) = ?";
    $stmt3 = $conn->prepare($outsidersQuery);
    $stmt3->bind_param("s", $today);
    $stmt3->execute();
    $outsidersCount = $stmt3->get_result()->fetch_assoc()['count'];
    
    // Total count
    $totalTodayRecords = $relativesCount + $usersCount + $outsidersCount;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'count' => (int)$totalTodayRecords,
        ],
        'message' => 'आज के नए रिकॉर्ड की संख्या सफलतापूर्वक प्राप्त हुई'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
