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
    // Get recent activities (recent camps created)
    $query = "SELECT 
                id,
                campName,
                location,
                date,
                createdAt,
                status
              FROM camps 
              ORDER BY createdAt DESC 
              LIMIT 10";
    
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $activities = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $activities[] = [
                'id' => $row['id'],
                'action' => 'नया शिविर बनाया गया',
                'details' => $row['campName'] . ' - ' . $row['location'],
                'timestamp' => date('d M Y, h:i A', strtotime($row['createdAt'])),
                'date' => $row['date'],
                'status' => $row['status']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $activities,
            'message' => 'हाल की गतिविधियां सफलतापूर्वक प्राप्त हुईं'
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
