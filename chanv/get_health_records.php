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
    
    // Default filters
    $searchTerm = $input['searchTerm'] ?? '';
    $statusFilter = $input['statusFilter'] ?? 'all';
    $checkupTypeFilter = $input['checkupTypeFilter'] ?? 'all';
    $limit = $input['limit'] ?? 50;
    $offset = $input['offset'] ?? 0;

    // Build the main query
    $whereConditions = [];
    $params = [];
    $types = '';

    // Search term condition
    if (!empty($searchTerm)) {
        $whereConditions[] = "(
            CAST(u.fullname AS CHAR CHARACTER SET utf8mb4) LIKE ? OR 
            CAST(r.fullName AS CHAR CHARACTER SET utf8mb4) LIKE ? OR 
            CAST(o.fullname AS CHAR CHARACTER SET utf8mb4) LIKE ? OR 
            CAST(u.phoneNumber AS CHAR CHARACTER SET utf8mb4) LIKE ? OR 
            CAST(r.phoneNumber AS CHAR CHARACTER SET utf8mb4) LIKE ? OR 
            CAST(o.phonenumber AS CHAR CHARACTER SET utf8mb4) LIKE ?
        )";
        $searchPattern = "%$searchTerm%";
        $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        $types .= 'ssssss';
    }

    // Status filter
    if ($statusFilter !== 'all') {
        $whereConditions[] = "hr.conditions = ?";
        $params[] = $statusFilter;
        $types .= 's';
    }

    // Checkup type filter
    if ($checkupTypeFilter !== 'all') {
        $whereConditions[] = "hr.reporttype = ?";
        $params[] = $checkupTypeFilter;
        $types .= 's';
    }

    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

    // Main query with JOINs to get patient information
    $query = "
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
            
            -- Patient information (employee/user)
            u.fullname as user_name,
            u.phoneNumber as user_phone,
            u.age as user_age,
            u.gender as user_gender,
            u.address as user_address,
            u.bloodGroup as user_blood_group,
            u.department as user_department,
            
            -- Relative information
            r.fullName as relative_name,
            r.phoneNumber as relative_phone,
            r.gender as relative_gender,
            r.dateOfBirth as relative_dob,
            r.bloodGroup as relative_blood_group,
            r.relation as relative_relation,
            
            -- Outsider information
            o.fullname as outsider_name,
            o.phonenumber as outsider_phone,
            o.age as outsider_age,
            o.gender as outsider_gender,
            o.bloodgroup as outsider_blood_group,
            
            -- Vital signs
            v.blood_pressure,
            v.heart_rate,
            v.temperature,
            v.weight,
            v.height,
            v.blood_sugar
            
        FROM healthreports hr
        LEFT JOIN users u ON hr.patientId = u.id AND hr.relativeId IS NULL
        LEFT JOIN relatives r ON hr.relativeId = r.r_id
        LEFT JOIN users u2 ON r.id = u2.id
        LEFT JOIN outsiders o ON hr.patientId = o.p_id AND hr.relativeId IS NULL AND u.id IS NULL
        LEFT JOIN vital v ON hr.id = v.report_id
        $whereClause
        ORDER BY hr.createdAt DESC
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $healthRecords = [];
    while ($row = $result->fetch_assoc()) {
        // Determine patient type and extract patient info
        $patientInfo = [];
        if ($row['relativeId']) {
            // This is a relative record
            $dob = new DateTime($row['relative_dob']);
            $now = new DateTime();
            $age = $now->diff($dob)->y;
            
            $patientInfo = [
                'id' => $row['relativeId'],
                'name' => $row['relative_name'],
                'phone' => $row['relative_phone'],
                'age' => $age,
                'gender' => $row['relative_gender'],
                'bloodGroup' => $row['relative_blood_group'],
                'type' => 'relative',
                'relation' => $row['relative_relation'],
                'address' => ''
            ];
        } elseif ($row['user_name']) {
            // This is an employee/user record
            $patientInfo = [
                'id' => $row['patientId'],
                'name' => $row['user_name'],
                'phone' => $row['user_phone'],
                'age' => $row['user_age'],
                'gender' => $row['user_gender'],
                'bloodGroup' => $row['user_blood_group'],
                'type' => 'employee',
                'department' => $row['user_department'],
                'address' => $row['user_address']
            ];
        } elseif ($row['outsider_name']) {
            // This is an outsider record
            $patientInfo = [
                'id' => $row['patientId'],
                'name' => $row['outsider_name'],
                'phone' => $row['outsider_phone'],
                'age' => $row['outsider_age'],
                'gender' => $row['outsider_gender'],
                'bloodGroup' => $row['outsider_blood_group'],
                'type' => 'outsider',
                'address' => ''
            ];
        }

        // Parse symptoms and medicines
        $symptoms = !empty($row['symptoms']) ? explode(',', $row['symptoms']) : [];
        $symptoms = array_map('trim', $symptoms);
        
        $medicines = !empty($row['medicines']) ? explode(',', $row['medicines']) : [];
        $medicines = array_map('trim', $medicines);

        // Build vitals object
        $vitals = [
            'bloodPressure' => $row['blood_pressure'],
            'heartRate' => $row['heart_rate'],
            'temperature' => $row['temperature'],
            'weight' => $row['weight'],
            'height' => $row['height'],
            'bloodSugar' => $row['blood_sugar']
        ];

        // Calculate BMI if weight and height are available
        if ($row['weight'] && $row['height']) {
            $heightInMeters = $row['height'] / 100;
            $bmi = round($row['weight'] / ($heightInMeters * $heightInMeters), 1);
            $vitals['bmi'] = $bmi;
        }

        // Parse additional tests from Reports JSON if needed
        $customTests = [];
        if (!empty($row['Reports'])) {
            $reportsData = json_decode($row['Reports'], true);
            if ($reportsData && isset($reportsData['vitals'])) {
                $customTests = $reportsData['vitals'];
            }
        }
        $vitals['customTests'] = $customTests;

        $healthRecords[] = [
            'id' => 'HR' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
            'patientName' => $patientInfo['name'],
            'patientId' => 'P' . str_pad($patientInfo['id'], 3, '0', STR_PAD_LEFT),
            'age' => $patientInfo['age'],
            'gender' => $patientInfo['gender'],
            'phone' => $patientInfo['phone'],
            'address' => $patientInfo['address'] ?? '',
            'bloodGroup' => $patientInfo['bloodGroup'],
            'patientType' => $patientInfo['type'],
            'department' => $patientInfo['department'] ?? null,
            'relation' => $patientInfo['relation'] ?? null,
            'camp' => $row['campname'],
            'visitDate' => $row['campdate'],
            'checkupType' => $row['reporttype'],
            'doctorName' => $row['doctorName'],
            'vitals' => $vitals,
            'symptoms' => $symptoms,
            'diagnosis' => $row['diagnosis'],
            'medications' => $medicines,
            'status' => $row['conditions'],
            'doctorNotes' => $row['notes'],
            'createdAt' => $row['createdAt'],
            'updatedAt' => $row['updatedAt']
        ];
    }

    // Get total count for pagination
    $countQuery = "
        SELECT COUNT(*) as total
        FROM healthreports hr
        LEFT JOIN users u ON hr.patientId = u.id AND hr.relativeId IS NULL
        LEFT JOIN relatives r ON hr.relativeId = r.r_id
        LEFT JOIN outsiders o ON hr.patientId = o.p_id AND hr.relativeId IS NULL AND u.id IS NULL
        $whereClause
    ";

    $countStmt = $conn->prepare($countQuery);
    if (!empty($params) && count($params) > 2) {
        // Remove limit and offset params for count query
        $countParams = array_slice($params, 0, -2);
        $countTypes = substr($types, 0, -2);
        if (!empty($countParams)) {
            $countStmt->bind_param($countTypes, ...$countParams);
        }
    }
    
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $totalRecords = $countResult->fetch_assoc()['total'];

    // Calculate statistics
    $statistics = [
        'totalRecords' => $totalRecords,
        'healthyPatients' => 0,
        'stablePatients' => 0,
        'needsAttention' => 0,
        'criticalCases' => 0
    ];

    foreach ($healthRecords as $record) {
        switch ($record['status']) {
            case 'स्वस्थ':
                $statistics['healthyPatients']++;
                break;
            case 'स्थिर':
                $statistics['stablePatients']++;
                break;
            case 'ध्यान चाहिए':
                $statistics['needsAttention']++;
                break;
            case 'गंभीर':
                $statistics['criticalCases']++;
                break;
        }
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'records' => $healthRecords,
            'statistics' => $statistics,
            'pagination' => [
                'total' => $totalRecords,
                'limit' => $limit,
                'offset' => $offset,
                'hasMore' => ($offset + $limit) < $totalRecords
            ]
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'सर्वर त्रुटि: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
