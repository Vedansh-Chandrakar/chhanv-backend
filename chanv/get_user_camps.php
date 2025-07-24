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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $userId = $input['userId'] ?? null;

    if (empty($userId)) {
        echo json_encode(['success' => false, 'message' => 'यूजर ID आवश्यक है']);
        exit;
    }

    // Get user's assigned camps - this would depend on your camp assignment logic
    // For now, getting all active camps where user can create health records
    $stmt = $conn->prepare("
        SELECT id, campName, location, date, startTime, endTime, address, description, coordinator, doctors, services, status 
        FROM camps 
        WHERE status IN ('active', 'ongoing', 'scheduled')
        AND date >= CURDATE() - INTERVAL 30 DAY
        ORDER BY date DESC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $camps = [];
    while ($row = $result->fetch_assoc()) {
        $camps[] = [
            'id' => $row['id'],
            'name' => $row['campName'],
            'location' => $row['location'],
            'date' => $row['date'],
            'startTime' => $row['startTime'],
            'endTime' => $row['endTime'],
            'address' => $row['address'],
            'description' => $row['description'],
            'coordinator' => $row['coordinator'],
            'doctors' => $row['doctors'],
            'services' => $row['services'],
            'status' => $row['status']
        ];
    }

    if (count($camps) > 0) {
        echo json_encode([
            'success' => true,
            'data' => ['camps' => $camps]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'कोई सक्रिय कैंप नहीं मिला'
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
