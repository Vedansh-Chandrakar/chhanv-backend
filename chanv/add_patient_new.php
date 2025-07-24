<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php';

// Set UTF8MB4 charset
if ($conn) {
    $conn->set_charset("utf8mb4");
}

$response = array();

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }

    // Validate required fields
    if (!isset($data['name']) || !isset($data['dateOfBirth']) || !isset($data['age']) || !isset($data['gender']) || !isset($data['isRelative'])) {
        $response = [
            "success" => false,
            "message" => "सभी आवश्यक फ़ील्ड भरें"
        ];
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $name = trim($data['name']);
    $dateOfBirth = $data['dateOfBirth'];
    $age = (int)$data['age'];
    $bloodGroup = isset($data['bloodGroup']) ? $data['bloodGroup'] : null;
    $gender = $data['gender'];
    $phone = isset($data['phone']) ? trim($data['phone']) : '';
    $isRelative = $data['isRelative']; // 'yes', 'no'
    $relativePhone = isset($data['relativePhone']) ? trim($data['relativePhone']) : '';
    $relation = isset($data['relation']) ? trim($data['relation']) : '';

    // Scenario 1: Relative of employee
    if ($isRelative === 'yes') {
        // Validate required fields for relative
        if (empty($relativePhone) || empty($relation)) {
            $response = [
                "success" => false,
                "message" => "कर्मचारी का मोबाइल नंबर और रिश्ता आवश्यक है"
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Check if employee exists with this phone number
        $checkEmployeeQuery = "SELECT id, fullname FROM users WHERE phoneNumber = ?";
        $checkStmt = $conn->prepare($checkEmployeeQuery);
        $checkStmt->bind_param("s", $relativePhone);
        $checkStmt->execute();
        $employeeResult = $checkStmt->get_result();

        if ($employeeResult->num_rows === 0) {
            $response = [
                "success" => false,
                "message" => "इस मोबाइल नंबर से कोई कर्मचारी नहीं मिला। कृपया सही नंबर दर्ज करें।"
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $employeeData = $employeeResult->fetch_assoc();
        $employeeId = $employeeData['id'];

        // Insert into relatives table
        $insertRelativeQuery = "
            INSERT INTO relatives (id, fullName, relation, dateOfBirth, bloodGroup, gender, phoneNumber) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $insertStmt = $conn->prepare($insertRelativeQuery);
        $insertStmt->bind_param("issssss", 
            $employeeId, 
            $name, 
            $relation, 
            $dateOfBirth, 
            $bloodGroup, 
            $gender, 
            $phone
        );

        if ($insertStmt->execute()) {
            $response = [
                "success" => true,
                "message" => "रिश्तेदार सफलतापूर्वक जोड़ा गया",
                "data" => [
                    "type" => "relative",
                    "id" => $conn->insert_id,
                    "employeeName" => $employeeData['fullname'],
                    "relativeName" => $name,
                    "relation" => $relation
                ]
            ];
        } else {
            throw new Exception("रिश्तेदार जोड़ने में त्रुटि: " . $conn->error);
        }

    } 
    // Scenario 2: Non-relative (outsider)
    else if ($isRelative === 'no') {
        // Validate phone number for outsiders
        if (empty($phone)) {
            $response = [
                "success" => false,
                "message" => "फोन नंबर आवश्यक है"
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validate phone number format
        if (!preg_match('/^\d{10}$/', $phone)) {
            $response = [
                "success" => false,
                "message" => "फोन नंबर 10 अंकों का होना चाहिए"
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Check if phone number already exists in outsiders table
        $checkOutsiderQuery = "SELECT p_id FROM outsiders WHERE phonenumber = ?";
        $checkStmt = $conn->prepare($checkOutsiderQuery);
        $checkStmt->bind_param("s", $phone);
        $checkStmt->execute();
        $outsiderResult = $checkStmt->get_result();

        if ($outsiderResult->num_rows > 0) {
            $response = [
                "success" => false,
                "message" => "यह फोन नंबर पहले से पंजीकृत है"
            ];
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Insert into outsiders table
        $insertOutsiderQuery = "
            INSERT INTO outsiders (fullname, dateofbirth, age, bloodgroup, gender, phonenumber) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $insertStmt = $conn->prepare($insertOutsiderQuery);
        $insertStmt->bind_param("ssisss", 
            $name, 
            $dateOfBirth, 
            $age, 
            $bloodGroup, 
            $gender, 
            $phone
        );

        if ($insertStmt->execute()) {
            $response = [
                "success" => true,
                "message" => "नया मरीज़ सफलतापूर्वक जोड़ा गया",
                "data" => [
                    "type" => "outsider",
                    "id" => $conn->insert_id,
                    "name" => $name,
                    "phone" => $phone
                ]
            ];
        } else {
            throw new Exception("मरीज़ जोड़ने में त्रुटि: " . $conn->error);
        }

    } else {
        $response = [
            "success" => false,
            "message" => "कृपया बताएं कि आप कर्मचारी के रिश्तेदार हैं या नहीं"
        ];
    }

} catch (Exception $e) {
    $response = [
        "success" => false,
        "message" => "त्रुटि: " . $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
