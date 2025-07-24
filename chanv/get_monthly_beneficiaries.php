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
    // Get monthly beneficiaries count (users + relatives + outsiders)
    $currentMonth = date('Y-m');
    
    // Count users created this month
    $usersQuery = "SELECT COUNT(*) as user_count FROM users WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$currentMonth'";
    $usersResult = mysqli_query($conn, $usersQuery);
    $usersCount = mysqli_fetch_assoc($usersResult)['user_count'];
    
    // Count relatives created this month
    $relativesQuery = "SELECT COUNT(*) as relative_count FROM relatives WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$currentMonth'";
    $relativesResult = mysqli_query($conn, $relativesQuery);
    $relativesCount = mysqli_fetch_assoc($relativesResult)['relative_count'];
    
    // Count outsiders (assuming they don't have createdAt, count all for now)
    $outsidersQuery = "SELECT COUNT(*) as outsider_count FROM outsiders";
    $outsidersResult = mysqli_query($conn, $outsidersQuery);
    $outsidersCount = mysqli_fetch_assoc($outsidersResult)['outsider_count'];
    
    $totalMonthlyBeneficiaries = $usersCount + $relativesCount + $outsidersCount;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'count' => (int)$totalMonthlyBeneficiaries,
            'breakdown' => [
                'users' => (int)$usersCount,
                'relatives' => (int)$relativesCount,
                'outsiders' => (int)$outsidersCount
            ]
        ],
        'message' => 'मासिक लाभार्थियों की संख्या सफलतापूर्वक प्राप्त हुई'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
