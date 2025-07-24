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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    $phone = trim($input['phone'] ?? '');
    $patientType = trim($input['patientType'] ?? '');

    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'फोन नंबर आवश्यक है']);
        exit;
    }

    if (!preg_match('/^\d{10}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'कृपया 10 अंकों का वैध फोन नंबर दर्ज करें']);
        exit;
    }

    $response = ['success' => false, 'data' => null];

    switch ($patientType) {
        case 'employee':
            // Search for employee by phone number
            $stmt = $conn->prepare("SELECT id, fullname, email, phoneNumber, dateOfBirth, age, gender, bloodGroup, department, address, familymember FROM users WHERE phoneNumber = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $response = [
                    'success' => true,
                    'patientType' => 'employee',
                    'data' => [
                        'id' => $row['id'],
                        'name' => $row['fullname'],
                        'email' => $row['email'],
                        'phone' => $row['phoneNumber'],
                        'dateOfBirth' => $row['dateOfBirth'],
                        'age' => $row['age'],
                        'gender' => $row['gender'],
                        'bloodGroup' => $row['bloodGroup'],
                        'department' => $row['department'],
                        'address' => $row['address'],
                        'familyMember' => $row['familymember']
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'इस फोन नंबर से कोई कर्मचारी नहीं मिला'];
            }
            break;

        case 'relative':
            // First find the employee, then get all relatives
            $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE phoneNumber = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($employeeRow = $result->fetch_assoc()) {
                $employeeId = $employeeRow['id'];
                $employeeName = $employeeRow['fullname'];
                
                // Get all relatives of this employee
                $stmt = $conn->prepare("SELECT r_id, fullName, relation, dateOfBirth, bloodGroup, gender, phoneNumber FROM relatives WHERE id = ?");
                $stmt->bind_param("i", $employeeId);
                $stmt->execute();
                $relativesResult = $stmt->get_result();
                
                $relatives = [];
                while ($relativeRow = $relativesResult->fetch_assoc()) {
                    // Calculate age from date of birth
                    $dob = new DateTime($relativeRow['dateOfBirth']);
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;
                    
                    $relatives[] = [
                        'id' => $relativeRow['r_id'],
                        'name' => $relativeRow['fullName'],
                        'relation' => $relativeRow['relation'],
                        'dateOfBirth' => $relativeRow['dateOfBirth'],
                        'age' => $age,
                        'gender' => $relativeRow['gender'],
                        'bloodGroup' => $relativeRow['bloodGroup'],
                        'phone' => $relativeRow['phoneNumber'],
                        'employeeId' => $employeeId,
                        'employeeName' => $employeeName
                    ];
                }
                
                if (count($relatives) > 0) {
                    $response = [
                        'success' => true,
                        'patientType' => 'relative',
                        'data' => [
                            'employeeName' => $employeeName,
                            'employeeId' => $employeeId,
                            'relatives' => $relatives
                        ]
                    ];
                } else {
                    $response = ['success' => false, 'message' => 'इस कर्मचारी के कोई रिश्तेदार पंजीकृत नहीं हैं'];
                }
            } else {
                $response = ['success' => false, 'message' => 'इस फोन नंबर से कोई कर्मचारी नहीं मिला'];
            }
            break;

        case 'outsider':
            // Search for outsider by phone number
            $stmt = $conn->prepare("SELECT p_id, fullname, dateofbirth, age, bloodgroup, gender, phonenumber FROM outsiders WHERE phonenumber = ?");
            $stmt->bind_param("s", $phone);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $response = [
                    'success' => true,
                    'patientType' => 'outsider',
                    'data' => [
                        'id' => $row['p_id'],
                        'name' => $row['fullname'],
                        'dateOfBirth' => $row['dateofbirth'],
                        'age' => $row['age'],
                        'gender' => $row['gender'],
                        'bloodGroup' => $row['bloodgroup'],
                        'phone' => $row['phonenumber']
                    ]
                ];
            } else {
                $response = ['success' => false, 'message' => 'इस फोन नंबर से कोई व्यक्ति नहीं मिला'];
            }
            break;

        default:
            $response = ['success' => false, 'message' => 'कृपया मरीज़ का प्रकार चुनें'];
            break;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
