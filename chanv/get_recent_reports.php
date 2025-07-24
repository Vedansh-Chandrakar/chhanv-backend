<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include 'db.php';

// Check database connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error,
        'reports' => []
    ]);
    exit;
}

// Set UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");

try {
    // Get patientId from query parameters
    $patientId = $_GET['patientId'] ?? '';
    $limit = $_GET['limit'] ?? 5; // Default to 5 recent reports
    
    if (empty($patientId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Patient ID is required',
            'reports' => []
        ]);
        exit;
    }

    // Query to get recent health reports for home screen
    $query = "
        SELECT 
            hr.id,
            hr.campname,
            hr.campdate,
            hr.reporttype,
            hr.doctorName,
            hr.conditions,
            hr.createdAt,
            CASE 
                WHEN hr.relativeId IS NOT NULL THEN CAST(r.fullName AS CHAR CHARACTER SET utf8mb4)
                ELSE CAST(u.fullname AS CHAR CHARACTER SET utf8mb4)
            END as patientName,
            CASE 
                WHEN hr.relativeId IS NOT NULL THEN CAST(r.relation AS CHAR CHARACTER SET utf8mb4)
                ELSE 'मुख्य व्यक्ति'
            END as relation
        FROM healthreports hr
        LEFT JOIN users u ON hr.patientId = u.id
        LEFT JOIN relatives r ON hr.relativeId = r.r_id
        WHERE hr.patientId = ?
        ORDER BY hr.campdate DESC, hr.createdAt DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $stmt->bind_param("ii", $patientId, $limit);
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        // Format date
        $date = new DateTime($row['campdate']);
        $formattedDate = $date->format('d/m/Y');
        
        $reports[] = [
            'id' => (int)$row['id'],
            'type' => $row['reporttype'] . ' जांच',
            'status' => $row['conditions'] ?? 'सामान्य',
            'date' => $formattedDate,
            'doctor' => $row['doctorName'],
            'location' => $row['campname'],
            'patientName' => $row['patientName'],
            'relation' => $row['relation']
        ];
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'message' => 'Reports fetched successfully',
        'debug' => [
            'patientId' => $patientId,
            'totalFound' => count($reports),
            'query' => $query
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'reports' => []
    ]);
}
?>
