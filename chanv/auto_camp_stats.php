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
    // Get updated counts after status updates
    $totalQuery = "SELECT COUNT(*) as total FROM camps";
    $totalResult = mysqli_query($conn, $totalQuery);
    $totalCamps = mysqli_fetch_assoc($totalResult)['total'];
    
    $scheduledQuery = "SELECT COUNT(*) as count FROM camps WHERE status = 'scheduled'";
    $scheduledResult = mysqli_query($conn, $scheduledQuery);
    $scheduled = mysqli_fetch_assoc($scheduledResult)['count'];
    
    $ongoingQuery = "SELECT COUNT(*) as count FROM camps WHERE status = 'ongoing'";
    $ongoingResult = mysqli_query($conn, $ongoingQuery);
    $ongoing = mysqli_fetch_assoc($ongoingResult)['count'];
    
    $completedQuery = "SELECT COUNT(*) as count FROM camps WHERE status = 'completed'";
    $completedResult = mysqli_query($conn, $completedQuery);
    $completed = mysqli_fetch_assoc($completedResult)['count'];
    
    $cancelledQuery = "SELECT COUNT(*) as count FROM camps WHERE status = 'cancelled'";
    $cancelledResult = mysqli_query($conn, $cancelledQuery);
    $cancelled = mysqli_fetch_assoc($cancelledResult)['count'];
    
    $activeCamps = $scheduled + $ongoing;
    
    // Get total beneficiaries
    $beneficiariesQuery = "SELECT SUM(beneficiaries) as total FROM camps";
    $beneficiariesResult = mysqli_query($conn, $beneficiariesQuery);
    $totalBeneficiaries = mysqli_fetch_assoc($beneficiariesResult)['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalCamps' => (int)$totalCamps,
            'scheduled' => (int)$scheduled,
            'ongoing' => (int)$ongoing,
            'completed' => (int)$completed,
            'cancelled' => (int)$cancelled,
            'activeCamps' => (int)$activeCamps,
            'totalBeneficiaries' => (int)$totalBeneficiaries
        ],
        'message' => 'Camp statistics retrieved successfully'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving camp statistics: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
