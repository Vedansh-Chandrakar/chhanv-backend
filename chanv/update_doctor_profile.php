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
    $personalInfo = $input['personalInfo'];
    $professionalInfo = $input['professionalInfo'];
    
    try {
        // Update doctor information by email
        $query = "UPDATE doctors SET 
                    name = ?,
                    phoneNo = ?,
                    specialization = ?,
                    qualification = ?,
                    experience = ?,
                    hospitalname = ?,
                    hospitaltype = ?,
                    assignedCamps = ?,
                    updatedAt = NOW()
                  WHERE email = ?";
        
        $stmt = $conn->prepare($query);
        
        // Extract experience number from string like "5 वर्ष"
        $experienceNum = 0;
        if ($professionalInfo['experience']) {
            preg_match('/\d+/', $professionalInfo['experience'], $matches);
            $experienceNum = isset($matches[0]) ? (int)$matches[0] : 0;
        }
        
        $stmt->bind_param("ssssissss", 
            $personalInfo['name'],
            $personalInfo['phone'],
            $professionalInfo['specialization'],
            $professionalInfo['qualification'],
            $experienceNum,
            $professionalInfo['currentHospital'],
            $professionalInfo['hospitalType'],
            $professionalInfo['assignedCamps'],
            $email
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'डॉक्टर प्रोफाइल सफलतापूर्वक अपडेट हो गई'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'प्रोफाइल अपडेट करने में त्रुटि: ' . $stmt->error
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
