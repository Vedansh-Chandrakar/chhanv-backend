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

    // Extract form data
    $patientType = $input['patientType'] ?? '';
    $patientId = $input['patientId'] ?? null;
    $relativeId = $input['relativeId'] ?? null;
    $campId = $input['campId'] ?? null;
    $campName = $input['campName'] ?? '';
    $campDate = $input['campDate'] ?? '';
    $reportType = $input['reportType'] ?? 'नियमित';
    $doctorName = $input['doctorName'] ?? '';
    $symptoms = $input['symptoms'] ?? '';
    $diagnosis = $input['diagnosis'] ?? '';
    $medicines = $input['medicines'] ?? '';
    $conditions = $input['conditions'] ?? 'स्थिर';
    $notes = $input['notes'] ?? '';
    
    // Vital signs data
    $vitals = $input['vitals'] ?? [];
    $bloodPressure = $vitals['bloodPressure'] ?? null;
    $heartRate = $vitals['heartRate'] ?? null;
    $temperature = $vitals['temperature'] ?? null;
    $weight = $vitals['weight'] ?? null;
    $height = $vitals['height'] ?? null;
    $bloodSugar = $vitals['bloodSugar'] ?? null;

    // Validation
    if (empty($patientType)) {
        echo json_encode(['success' => false, 'message' => 'मरीज़ का प्रकार आवश्यक है']);
        exit;
    }

    if (empty($patientId)) {
        echo json_encode(['success' => false, 'message' => 'मरीज़ ID आवश्यक है']);
        exit;
    }

    if (empty($campName) || empty($campDate)) {
        echo json_encode(['success' => false, 'message' => 'कैंप की जानकारी आवश्यक है']);
        exit;
    }

    if (empty($diagnosis)) {
        echo json_encode(['success' => false, 'message' => 'निदान आवश्यक है']);
        exit;
    }

    // Start transaction
    $conn->autocommit(false);

    try {
        // Prepare reports data for JSON storage
        $reportsData = [
            'patientType' => $patientType,
            'reportType' => $reportType,
            'vitals' => $vitals,
            'createdAt' => date('Y-m-d H:i:s')
        ];

        // Insert health report
        $stmt = $conn->prepare("
            INSERT INTO healthreports 
            (patientId, relativeId, campname, campdate, reporttype, doctorName, Reports, symptoms, diagnosis, medicines, conditions, notes, createdAt, updatedAt, camp_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
        ");

        $reportsJson = json_encode($reportsData, JSON_UNESCAPED_UNICODE);
        
        $stmt->bind_param(
            "iissssssssssi",
            $patientId,
            $relativeId,
            $campName,
            $campDate,
            $reportType,
            $doctorName,
            $reportsJson,
            $symptoms,
            $diagnosis,
            $medicines,
            $conditions,
            $notes,
            $campId
        );

        if (!$stmt->execute()) {
            throw new Exception('स्वास्थ्य रिकॉर्ड सेव करने में त्रुटि: ' . $stmt->error);
        }

        $reportId = $conn->insert_id;

        // Insert vital signs if provided
        if ($bloodPressure || $heartRate || $temperature || $weight || $height || $bloodSugar) {
            $vitalStmt = $conn->prepare("
                INSERT INTO vital 
                (report_id, blood_pressure, heart_rate, temperature, weight, height, blood_sugar) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $vitalStmt->bind_param(
                "isidddd",
                $reportId,
                $bloodPressure,
                $heartRate,
                $temperature,
                $weight,
                $height,
                $bloodSugar
            );

            if (!$vitalStmt->execute()) {
                throw new Exception('महत्वपूर्ण संकेतक सेव करने में त्रुटि: ' . $vitalStmt->error);
            }
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'स्वास्थ्य रिकॉर्ड सफलतापूर्वक सेव हो गया',
            'data' => [
                'reportId' => $reportId,
                'patientType' => $patientType,
                'patientId' => $patientId,
                'relativeId' => $relativeId
            ]
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->autocommit(true);
$conn->close();
?>
