<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php'; // Database connection

// Initialize response
$response = array();

try {
    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get recent camps with optional limit
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    
    $stmt = $conn->prepare("
        SELECT 
            id,
            campName,
            location,
            date,
            startTime,
            endTime,
            address,
            description,
            coordinator,
            expectedBeneficiaries,
            doctors,
            services,
            status,
            beneficiaries,
            createdAt,
            updatedAt
        FROM camps
        ORDER BY date DESC, createdAt DESC
        LIMIT ?
    ");
    
    if (!$stmt) {
        throw new Exception("Query preparation failed");
    }

    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $camps = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $camps[] = [
                'id' => (int)$row['id'],
                'campName' => $row['campName'],
                'location' => $row['location'],
                'date' => $row['date'],
                'startTime' => $row['startTime'],
                'endTime' => $row['endTime'],
                'address' => $row['address'],
                'description' => $row['description'],
                'coordinator' => $row['coordinator'],
                'expectedBeneficiaries' => (int)$row['expectedBeneficiaries'],
                'doctors' => $row['doctors'],
                'services' => $row['services'],
                'status' => $row['status'],
                'beneficiaries' => $row['beneficiaries'],
                'createdAt' => $row['createdAt'],
                'updatedAt' => $row['updatedAt']
            ];
        }
    }

    $response = [
        "success" => true,
        "camps" => $camps,
        "totalCamps" => count($camps),
        "message" => "Camps fetched successfully"
    ];

    $stmt->close();

} catch (Exception $e) {
    $response = [
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "camps" => []
    ];
}

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
