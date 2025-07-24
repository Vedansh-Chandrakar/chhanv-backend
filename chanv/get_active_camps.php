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
    // Get active camps count (status = 'active' or 'ongoing')
    $query = "SELECT COUNT(*) as active_camps 
              FROM camps 
              WHERE status IN ('active', 'ongoing', 'scheduled') 
              AND date >= CURDATE()";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $activeCamps = $row['active_camps'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'count' => (int)$activeCamps
            ],
            'message' => 'सक्रिय शिविरों की संख्या सफलतापूर्वक प्राप्त हुई'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'डेटा प्राप्त करने में त्रुटि: ' . mysqli_error($conn)
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
