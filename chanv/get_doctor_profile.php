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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email'])) {
        echo json_encode([
            'success' => false,
            'message' => 'डॉक्टर email आवश्यक है'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $email = $input['email'];
    
    try {
        // Get doctor details by email
        $query = "SELECT * FROM doctors WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $doctor = $result->fetch_assoc();
            
            // Get doctor statistics
            $stats = [
                'totalPatients' => 0,
                'familiesServed' => 0,
                'campsOrganized' => 0,
                'yearsOfService' => 0
            ];
            
            // Calculate total patients from health records
            $patientsQuery = "SELECT COUNT(DISTINCT 
                CASE 
                    WHEN relativeId IS NOT NULL THEN CONCAT('relative_', relativeId)
                    ELSE CONCAT('patient_', patientId)
                END
            ) as total_patients FROM healthreports";
            $patientsResult = mysqli_query($conn, $patientsQuery);
            if ($patientsResult) {
                $stats['totalPatients'] = mysqli_fetch_assoc($patientsResult)['total_patients'];
            }
            
            // Calculate families served (total relatives + users + outsiders)
            $relativesCount = 0;
            $usersCount = 0;
            $outsidersCount = 0;
            
            $relativesResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM relatives");
            if ($relativesResult) {
                $relativesCount = mysqli_fetch_assoc($relativesResult)['count'];
            }
            
            $usersResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
            if ($usersResult) {
                $usersCount = mysqli_fetch_assoc($usersResult)['count'];
            }
            
            $outsidersResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM outsiders");
            if ($outsidersResult) {
                $outsidersCount = mysqli_fetch_assoc($outsidersResult)['count'];
            }
            
            $stats['familiesServed'] = $relativesCount + $usersCount + $outsidersCount;
            
            // Calculate camps organized
            $campsResult = mysqli_query($conn, "SELECT COUNT(*) as total_camps FROM camps");
            if ($campsResult) {
                $stats['campsOrganized'] = mysqli_fetch_assoc($campsResult)['total_camps'];
            }
            
            // Calculate years of service
            if ($doctor['createdAt']) {
                $createdDate = new DateTime($doctor['createdAt']);
                $currentDate = new DateTime();
                $interval = $createdDate->diff($currentDate);
                $stats['yearsOfService'] = $interval->y;
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'personalInfo' => [
                        'name' => $doctor['name'],
                        'email' => $doctor['email'],
                        'phone' => $doctor['phoneNo'],
                        'dateOfBirth' => '', // Not in schema
                        'gender' => '', // Not in schema
                        'bloodGroup' => '', // Not in schema
                        'address' => '', // Not in schema
                        'emergencyContact' => '' // Not in schema
                    ],
                    'professionalInfo' => [
                        'registrationNo' => (string)$doctor['id'], // Using ID as registration
                        'specialization' => $doctor['specialization'],
                        'qualification' => $doctor['qualification'] ?? '',
                        'experience' => $doctor['experience'] ? $doctor['experience'] . ' वर्ष' : '',
                        'currentHospital' => $doctor['hospitalname'] ?? '',
                        'hospitalType' => $doctor['hospitaltype'] ?? '',
                        'department' => '',
                        'joiningDate' => $doctor['createdAt'] ?? '',
                        'assignedCamps' => $doctor['assignedCamps'] ?? '',
                        'languages' => []
                    ],
                    'statistics' => $stats
                ],
                'message' => 'डॉक्टर प्रोफाइल सफलतापूर्वक प्राप्त हुई'
            ], JSON_UNESCAPED_UNICODE);
            
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'डॉक्टर नहीं मिला'
            ], JSON_UNESCAPED_UNICODE);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'केवल POST method allowed है'
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
