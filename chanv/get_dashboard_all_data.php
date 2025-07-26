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
    $response = [
        'success' => true,
        'data' => [
            'kpi' => [],
            'chartData' => [],
            'activities' => []
        ],
        'message' => 'डैशबोर्ड डेटा सफलतापूर्वक प्राप्त हुआ'
    ];

    // 1. Get KPI data
    // Total Camps
    $campsQuery = "SELECT COUNT(*) as total_camps FROM camps";
    $campsResult = mysqli_query($conn, $campsQuery);
    $totalCamps = mysqli_fetch_assoc($campsResult)['total_camps'];

    // Total Patients (from users + relatives + outsiders)
    $patientsQuery = "
        SELECT 
            (SELECT COUNT(*) FROM users) + 
            (SELECT COUNT(*) FROM relatives) + 
            (SELECT COUNT(*) FROM outsiders) as total_patients
    ";
    $patientsResult = mysqli_query($conn, $patientsQuery);
    $totalPatients = mysqli_fetch_assoc($patientsResult)['total_patients'];

    // Total Doctors
    $doctorsQuery = "SELECT COUNT(*) as total_doctors FROM doctors";
    $doctorsResult = mysqli_query($conn, $doctorsQuery);
    $totalDoctors = mysqli_fetch_assoc($doctorsResult)['total_doctors'];

    // Monthly Beneficiaries
    $currentMonth = date('Y-m');
    $usersQuery = "SELECT COUNT(*) as user_count FROM users WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$currentMonth'";
    $usersResult = mysqli_query($conn, $usersQuery);
    $usersCount = mysqli_fetch_assoc($usersResult)['user_count'];

    $relativesQuery = "SELECT COUNT(*) as relative_count FROM relatives WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$currentMonth'";
    $relativesResult = mysqli_query($conn, $relativesQuery);
    $relativesCount = mysqli_fetch_assoc($relativesResult)['relative_count'];

    $outsidersQuery = "SELECT COUNT(*) as outsider_count FROM outsiders";
    $outsidersResult = mysqli_query($conn, $outsidersQuery);
    $outsidersCount = mysqli_fetch_assoc($outsidersResult)['outsider_count'];

    $totalMonthlyBeneficiaries = $usersCount + $relativesCount + $outsidersCount;

    $response['data']['kpi'] = [
        'totalCamps' => (int)$totalCamps,
        'totalPatients' => (int)$totalPatients,
        'totalDoctors' => (int)$totalDoctors,
        'monthlyBeneficiaries' => (int)$totalMonthlyBeneficiaries
    ];

    // 2. Get Chart Data (last 6 months)
    $chartData = [];
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthName = date('M Y', strtotime("-$i months"));
        
        // Get camps count for this month
        $campsMonthQuery = "SELECT COUNT(*) as camp_count FROM camps WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$month'";
        $campsMonthResult = mysqli_query($conn, $campsMonthQuery);
        $campsCount = mysqli_fetch_assoc($campsMonthResult)['camp_count'];
        
        // Get beneficiaries count for this month
        $usersMonthQuery = "SELECT COUNT(*) as user_count FROM users WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$month'";
        $usersMonthResult = mysqli_query($conn, $usersMonthQuery);
        $usersMonthCount = mysqli_fetch_assoc($usersMonthResult)['user_count'];
        
        $relativesMonthQuery = "SELECT COUNT(*) as relative_count FROM relatives WHERE DATE_FORMAT(createdAt, '%Y-%m') = '$month'";
        $relativesMonthResult = mysqli_query($conn, $relativesMonthQuery);
        $relativesMonthCount = mysqli_fetch_assoc($relativesMonthResult)['relative_count'];
        
        $totalBeneficiaries = $usersMonthCount + $relativesMonthCount;
        
        $chartData[] = [
            'महीना' => $monthName,
            'शिविर' => (int)$campsCount,
            'लाभार्थी' => (int)$totalBeneficiaries
        ];
    }
    $response['data']['chartData'] = $chartData;

    // 3. Get Recent Activities
    $activitiesQuery = "SELECT 
                        id,
                        campName,
                        location,
                        date,
                        createdAt,
                        status
                      FROM camps 
                      ORDER BY createdAt DESC 
                      LIMIT 10";
    
    $activitiesResult = mysqli_query($conn, $activitiesQuery);
    $activities = [];
    
    if ($activitiesResult) {
        while ($row = mysqli_fetch_assoc($activitiesResult)) {
            $activities[] = [
                'id' => (int)$row['id'],
                'action' => 'नया शिविर बनाया गया',
                'details' => $row['campName'] . ' - ' . $row['location'],
                'timestamp' => date('d M Y, h:i A', strtotime($row['createdAt'])),
                'date' => $row['date'],
                'status' => $row['status']
            ];
        }
    }
    $response['data']['activities'] = $activities;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
