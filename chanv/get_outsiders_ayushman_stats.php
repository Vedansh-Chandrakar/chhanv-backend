<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

include 'db.php';

try {
    // Get total outsiders from outsiders table
    $outsidersQuery = "SELECT COUNT(*) as total_outsiders FROM outsiders";
    $outsidersResult = $conn->query($outsidersQuery);
    $totalOutsiders = 0;
    
    if ($outsidersResult && $outsidersResult->num_rows > 0) {
        $row = $outsidersResult->fetch_assoc();
        $totalOutsiders = (int)$row['total_outsiders'];
    }

    // Get total Ayushman beneficiaries from users table
    $ayushmanQuery = "SELECT COUNT(*) as total_ayushman FROM users WHERE hasAyushmanCard = 'yes'";
    $ayushmanResult = $conn->query($ayushmanQuery);
    $totalAyushman = 0;
    
    if ($ayushmanResult && $ayushmanResult->num_rows > 0) {
        $row = $ayushmanResult->fetch_assoc();
        $totalAyushman = (int)$row['total_ayushman'];
    }

    // Return the statistics
    $response = array(
        'success' => true,
        'data' => array(
            'totalOutsiders' => $totalOutsiders,
            'totalAyushmanBeneficiaries' => $totalAyushman
        )
    );

    echo json_encode($response);

} catch (Exception $e) {
    $response = array(
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    );
    echo json_encode($response);
}

$conn->close();
?>
