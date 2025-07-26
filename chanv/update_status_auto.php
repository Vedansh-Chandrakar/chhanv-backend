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
    $currentDate = date('m-d-Y');
    $currentTime = date('H:i:s');
    $currentDateTime = date('m-d-Y H:i:s');
    
    // Update camps to 'ongoing' if current date matches and current time is between start and end time
    $ongoingQuery = "UPDATE camps SET 
                     status = 'ongoing', 
                     updatedAt = '$currentDateTime'
                     WHERE DATE(date) = '$currentDate' 
                     AND TIME('$currentTime') >= TIME(startTime) 
                     AND TIME('$currentTime') <= TIME(endTime)
                     AND status = 'scheduled'";
    
    $ongoingResult = mysqli_query($conn, $ongoingQuery);
    $ongoingAffected = mysqli_affected_rows($conn);
    
    // Update camps to 'completed' if current date matches and current time is after end time
    $completedQuery = "UPDATE camps SET 
                       status = 'completed', 
                       updatedAt = '$currentDateTime'
                       WHERE DATE(date) = '$currentDate' 
                       AND TIME('$currentTime') > TIME(endTime)
                       AND status IN ('scheduled', 'ongoing')";
    
    $completedResult = mysqli_query($conn, $completedQuery);
    $completedAffected = mysqli_affected_rows($conn);
    
    // Update camps to 'completed' if date has passed
    $pastCompletedQuery = "UPDATE camps SET 
                           status = 'completed', 
                           updatedAt = '$currentDateTime'
                           WHERE DATE(date) < '$currentDate'
                           AND status IN ('scheduled', 'ongoing')";
    
    $pastCompletedResult = mysqli_query($conn, $pastCompletedQuery);
    $pastCompletedAffected = mysqli_affected_rows($conn);
    
    // Get updated counts
    $totalQuery = "SELECT COUNT(*) as total FROM camps";
    $totalResult = mysqli_query($conn, $totalQuery);
    $totalCamps = mysqli_fetch_assoc($totalResult)['total'];
    
    $scheduledQuery = "SELECT COUNT(*) as count FROM camps WHERE status = 'scheduled'";
    $scheduledResult = mysqli_query($conn, $scheduledQuery);
    $scheduled = mysqli_fetch_assoc($scheduledResult)['count'];
    
    $ongoingStatsQuery = "SELECT COUNT(*) as count FROM camps WHERE status = 'ongoing'";
    $ongoingStatsResult = mysqli_query($conn, $ongoingStatsQuery);
    $ongoing = mysqli_fetch_assoc($ongoingStatsResult)['count'];
    
    $completedStatsQuery = "SELECT COUNT(*) as count FROM camps WHERE status = 'completed'";
    $completedStatsResult = mysqli_query($conn, $completedStatsQuery);
    $completed = mysqli_fetch_assoc($completedStatsResult)['count'];
    
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
        'message' => 'Camp statuses updated successfully',
        'stats' => [
            'totalCamps' => (int)$totalCamps,
            'scheduled' => (int)$scheduled,
            'ongoing' => (int)$ongoing,
            'completed' => (int)$completed,
            'cancelled' => (int)$cancelled,
            'activeCamps' => (int)$activeCamps,
            'totalBeneficiaries' => (int)$totalBeneficiaries
        ],
        'updates' => [
            'ongoingUpdated' => $ongoingAffected,
            'completedUpdated' => $completedAffected + $pastCompletedAffected
        ],
        'currentTime' => $currentDateTime
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating camp statuses: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
