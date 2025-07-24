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

// Set UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");

try {
    $limit = $_GET['limit'] ?? 10; // Default to 10 upcoming camps
    
    // Query to get upcoming camps
    $query = "
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
        WHERE date >= CURDATE()
        ORDER BY date ASC, startTime ASC
        LIMIT ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $camps = [];
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

    echo json_encode([
        'success' => true,
        'camps' => $camps,
        'totalCamps' => count($camps),
        'message' => 'Camps fetched successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'camps' => []
    ]);
}
?>
