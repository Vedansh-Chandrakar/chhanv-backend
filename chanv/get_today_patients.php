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
    
    // Single query to get all counts at once
    $query = "
        SELECT 
            (SELECT COUNT(*) FROM users WHERE DATE(createdAt) = ?) as users_count,
            (SELECT COUNT(*) FROM relatives WHERE DATE(createdAt) = ?) as relatives_count,
            (SELECT COUNT(*) FROM outsiders) as outsiders_count
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $today, $today);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Calculate total (outsiders don't have createdAt so we count all)
    $totalTodayRecords = $result['users_count'] + $result['relatives_count'] + $result['outsiders_count'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'count' => (int)$totalTodayRecords,
            'breakdown' => [
                'users_today' => (int)$result['users_count'],
                'relatives_today' => (int)$result['relatives_count'],
                'outsiders_total' => (int)$result['outsiders_count']
            ]
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
