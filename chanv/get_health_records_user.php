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
    
    if (!isset($input['patient_id'])) {
        throw new Exception('Patient ID is required');
    }
    
    $patientId = $input['patient_id'];

    
    // Query to get health records for a specific patient
    $query = "
        SELECT 
            hr.id,
            hr.patientId,
            hr.relativeId,
            hr.campname,
            hr.campdate as date,
            hr.reporttype,
            hr.doctorName as doctor_name,
            hr.Reports,
            hr.symptoms,
            hr.diagnosis,
            hr.medicines as treatment,
            hr.conditions,
            hr.notes,
            hr.createdAt,
            hr.updatedAt,
            
            -- Get vital signs
            v.blood_pressure,
            v.heart_rate as pulse,
            v.temperature,
            v.weight,
            v.height,
            v.blood_sugar,
            
            -- Get department from user or default
            u.department,
            u.fullname as patient_name
            
        FROM healthreports hr
        LEFT JOIN vital v ON hr.id = v.report_id
        LEFT JOIN users u ON hr.patientId = u.id
        WHERE hr.patientId = ?
        ORDER BY hr.createdAt DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $patientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $healthRecords = [];
    
    while ($row = $result->fetch_assoc()) {
        $healthRecords[] = [
            'id' => $row['id'],
            'patient_id' => $row['patientId'],
            'date' => $row['date'],
            'doctor_name' => $row['doctor_name'] ?: 'डॉ. अज्ञात',
            'department' => $row['department'] ?: 'सामान्य चिकित्सा',
            'diagnosis' => $row['diagnosis'] ?: 'कोई विशेष निदान नहीं',
            'treatment' => $row['treatment'] ?: 'कोई विशेष उपचार नहीं',
            'medicines' => $row['treatment'] ?: 'कोई दवा नहीं',
            'symptoms' => $row['symptoms'] ?: '',
            'conditions' => $row['conditions'] ?: 'सामान्य',
            'notes' => $row['notes'] ?: '',
            
            // Vital signs
            'temperature' => $row['temperature'] ?: 'सामान्य',
            'pulse' => $row['pulse'] ?: 'सामान्य', 
            'blood_pressure' => $row['blood_pressure'] ?: 'सामान्य',
            'weight' => $row['weight'] ?: 'दर्ज नहीं',
            'height' => $row['height'] ?: 'दर्ज नहीं',
            'blood_sugar' => $row['blood_sugar'] ?: 'दर्ज नहीं',
            
            'camp_name' => $row['campname'] ?: 'छांव स्वास्थ्य शिविर',
            'report_type' => $row['reporttype'] ?: 'सामान्य जांच',
            'created_at' => $row['createdAt'],
            'updated_at' => $row['updatedAt']
        ];
    }

    if (count($healthRecords) > 0) {
        echo json_encode([
            'success' => true,
            'posts' => $healthRecords,
            'total_records' => count($healthRecords),
            'message' => 'Health records found successfully'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'posts' => [],
            'total_records' => 0,
            'message' => 'No health records found for this patient'
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'posts' => [],
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
