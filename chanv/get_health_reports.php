<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=utf-8");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php'; // Database connection

// Set UTF8MB4 charset for MySQL connection
if ($conn) {
    $conn->set_charset("utf8mb4");
}

// Helper functions for status determination
function getBloodPressureStatus($bp) {
    if (empty($bp)) return 'अज्ञात';
    
    $parts = explode('/', $bp);
    if (count($parts) != 2) return 'अज्ञात';
    
    $systolic = (int)$parts[0];
    $diastolic = (int)$parts[1];
    
    if ($systolic < 120 && $diastolic < 80) return 'सामान्य';
    if ($systolic < 140 && $diastolic < 90) return 'ध्यान दें';
    return 'असामान्य';
}

function getHeartRateStatus($rate) {
    $rate = (int)$rate;
    if ($rate >= 60 && $rate <= 100) return 'सामान्य';
    if ($rate > 100) return 'ध्यान दें';
    return 'असामान्य';
}

function getTemperatureStatus($temp) {
    $temp = (float)$temp;
    if ($temp >= 97.0 && $temp <= 99.5) return 'सामान्य';
    if ($temp > 99.5) return 'ध्यान दें';
    return 'असामान्य';
}

function getBloodSugarStatus($sugar) {
    $sugar = (float)$sugar;
    if ($sugar >= 70 && $sugar <= 140) return 'सामान्य';
    if ($sugar > 140) return 'ध्यान दें';
    return 'असामान्य';
}

// Initialize response
$response = array();

try {
    // Check if patientId parameter exists
    if (!isset($_GET['patientId']) || empty($_GET['patientId'])) {
        $response = [
            "success" => false,
            "message" => "Patient ID is required"
        ];
        echo json_encode($response);
        exit;
    }

    $patientId = (int)$_GET['patientId'];

    // Check database connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Query to get health reports with vital signs
    $stmt = $conn->prepare("
        SELECT 
            hr.id,
            hr.patientId,
            hr.relativeId,
            hr.campname,
            hr.campdate,
            hr.reporttype,
            hr.doctorName,
            hr.Reports,
            hr.symptoms,
            hr.diagnosis,
            hr.medicines,
            hr.conditions,
            hr.notes,
            hr.createdAt,
            hr.updatedAt,
            v.blood_pressure,
            v.heart_rate,
            v.temperature,
            v.weight,
            v.height,
            v.blood_sugar,
            CASE 
                WHEN hr.relativeId IS NOT NULL THEN CAST(r.fullName AS CHAR CHARACTER SET utf8mb4)
                ELSE CAST(u.fullname AS CHAR CHARACTER SET utf8mb4)
            END as patientName,
            CASE 
                WHEN hr.relativeId IS NOT NULL THEN CAST(r.relation AS CHAR CHARACTER SET utf8mb4)
                ELSE CAST('मुख्य व्यक्ति' AS CHAR CHARACTER SET utf8mb4)
            END as relation,
            CASE 
                WHEN hr.relativeId IS NOT NULL THEN CAST(r.gender AS CHAR CHARACTER SET utf8mb4)
                ELSE CAST(u.gender AS CHAR CHARACTER SET utf8mb4)
            END as gender,
            CASE 
                WHEN hr.relativeId IS NOT NULL THEN CAST(r.bloodGroup AS CHAR CHARACTER SET utf8mb4)
                ELSE CAST(u.bloodGroup AS CHAR CHARACTER SET utf8mb4)
            END as bloodGroup,
            CASE 
                WHEN hr.relativeId IS NOT NULL THEN TIMESTAMPDIFF(YEAR, r.dateOfBirth, CURDATE())
                ELSE u.age
            END as age
        FROM healthreports hr
        LEFT JOIN vital v ON hr.id = v.report_id
        LEFT JOIN users u ON hr.patientId = u.id
        LEFT JOIN relatives r ON hr.relativeId = r.r_id
        WHERE hr.patientId = ?
        ORDER BY hr.campdate DESC, hr.createdAt DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Query preparation failed");
    }

    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    $employeeReports = [];
    $familyReports = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Format vital signs as tests array
            $tests = [];
            
            if ($row['blood_pressure']) {
                $tests[] = [
                    'name' => 'रक्तचाप (Blood Pressure)',
                    'value' => $row['blood_pressure'],
                    'normalRange' => '120/80 mmHg',
                    'status' => getBloodPressureStatus($row['blood_pressure'])
                ];
            }
            
            if ($row['heart_rate']) {
                $tests[] = [
                    'name' => 'हृदय गति (Heart Rate)',
                    'value' => $row['heart_rate'] . ' bpm',
                    'normalRange' => '60-100 bpm',
                    'status' => getHeartRateStatus($row['heart_rate'])
                ];
            }
            
            if ($row['temperature']) {
                $tests[] = [
                    'name' => 'तापमान (Temperature)',
                    'value' => $row['temperature'] . '°F',
                    'normalRange' => '98.6°F',
                    'status' => getTemperatureStatus($row['temperature'])
                ];
            }
            
            if ($row['weight']) {
                $tests[] = [
                    'name' => 'वजन (Weight)',
                    'value' => $row['weight'] . ' kg',
                    'normalRange' => 'BMI अनुसार',
                    'status' => 'सामान्य'
                ];
            }
            
            if ($row['height']) {
                $tests[] = [
                    'name' => 'कद (Height)',
                    'value' => $row['height'] . ' cm',
                    'normalRange' => 'व्यक्तिगत',
                    'status' => 'सामान्य'
                ];
            }
            
            if ($row['blood_sugar']) {
                $tests[] = [
                    'name' => 'रक्त शर्करा (Blood Sugar)',
                    'value' => $row['blood_sugar'] . ' mg/dL',
                    'normalRange' => '70-140 mg/dL',
                    'status' => getBloodSugarStatus($row['blood_sugar'])
                ];
            }

            $reportData = [
                'id' => (int)$row['id'],
                'patientId' => (int)$row['patientId'],
                'relativeId' => $row['relativeId'] ? (int)$row['relativeId'] : null,
                'campname' => $row['campname'],
                'campdate' => $row['campdate'],
                'reporttype' => $row['reporttype'],
                'doctorName' => $row['doctorName'],
                'reports' => $row['Reports'],
                'symptoms' => $row['symptoms'],
                'diagnosis' => $row['diagnosis'],
                'medicines' => $row['medicines'],
                'conditions' => $row['conditions'],
                'notes' => $row['notes'],
                'patientName' => $row['patientName'],
                'relation' => $row['relation'],
                'gender' => $row['gender'],
                'bloodGroup' => $row['bloodGroup'],
                'age' => (int)$row['age'],
                'tests' => $tests,
                'createdAt' => $row['createdAt'],
                'updatedAt' => $row['updatedAt']
            ];

            $reports[] = $reportData;

            // Separate employee and family reports
            if ($row['relativeId'] === null) {
                $employeeReports[] = $reportData;
            } else {
                $familyReports[] = $reportData;
            }
        }
    }

    $response = [
        "success" => true,
        "reports" => $reports,
        "employeeReports" => $employeeReports,
        "familyReports" => $familyReports,
        "totalReports" => count($reports),
        "message" => "Reports fetched successfully"
    ];

    $stmt->close();

} catch (Exception $e) {
    $response = [
        "success" => false,
        "message" => "Error: " . $e->getMessage(),
        "reports" => []
    ];
}

// Send JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
