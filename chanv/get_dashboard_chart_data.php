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
    // Get chart data for last 6 months
    $chartData = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M Y', strtotime("-$i months"));
        
        // Get camps count for this month
        $campsQuery = "SELECT COUNT(*) as camp_count FROM camps WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$month'";
        $campsResult = mysqli_query($conn, $campsQuery);
        $campsCount = mysqli_fetch_assoc($campsResult)['camp_count'];
        
        // Get beneficiaries count for this month (users + relatives)
        $usersQuery = "SELECT COUNT(*) as user_count FROM users WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$month'";
        $usersResult = mysqli_query($conn, $usersQuery);
        $usersCount = mysqli_fetch_assoc($usersResult)['user_count'];
        
        $relativesQuery = "SELECT COUNT(*) as relative_count FROM relatives WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$month'";
        $relativesResult = mysqli_query($conn, $relativesQuery);
        $relativesCount = mysqli_fetch_assoc($relativesResult)['relative_count'];
        
        $totalBeneficiaries = $usersCount + $relativesCount;
        
        $chartData[] = [
            'महीना' => $monthName,
            'शिविर' => (int)$campsCount,
            'लाभार्थी' => (int)$totalBeneficiaries
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $chartData,
        'message' => 'चार्ट डेटा सफलतापूर्वक प्राप्त हुआ'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
