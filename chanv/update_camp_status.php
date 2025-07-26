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
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || !isset($input['status'])) {
        throw new Exception('Camp ID and status are required');
    }
    
    $campId = $input['id'];
    $newStatus = $input['status'];
    $currentDateTime = date('Y-m-d H:i:s');
    
    // Validate status
    $allowedStatuses = ['scheduled', 'ongoing', 'completed', 'cancelled'];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception('Invalid status provided');
    }
    
    // Update camp status
    $updateQuery = "UPDATE camps SET 
                    status = ?, 
                    updatedAt = ?
                    WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "ssi", $newStatus, $currentDateTime, $campId);
    
    if (mysqli_stmt_execute($stmt)) {
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        
        if ($affectedRows > 0) {
            // Get updated camp data
            $selectQuery = "SELECT * FROM camps WHERE id = ?";
            $selectStmt = mysqli_prepare($conn, $selectQuery);
            mysqli_stmt_bind_param($selectStmt, "i", $campId);
            mysqli_stmt_execute($selectStmt);
            $result = mysqli_stmt_get_result($selectStmt);
            
            if ($camp = mysqli_fetch_assoc($result)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Camp status updated successfully',
                    'camp' => [
                        'id' => $camp['id'],
                        'campName' => $camp['campName'],
                        'location' => $camp['location'],
                        'address' => $camp['address'],
                        'date' => $camp['date'],
                        'startTime' => $camp['startTime'],
                        'endTime' => $camp['endTime'],
                        'expectedBeneficiaries' => $camp['expectedBeneficiaries'],
                        'beneficiaries' => $camp['beneficiaries'] ?? 0,
                        'doctors' => $camp['doctors'],
                        'services' => $camp['services'],
                        'status' => $camp['status'],
                        'description' => $camp['description'] ?? '',
                        'coordinator' => $camp['coordinator'] ?? '',
                        'createdAt' => $camp['createdAt'],
                        'updatedAt' => $camp['updatedAt']
                    ]
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception('Camp not found after update');
            }
        } else {
            throw new Exception('No camp found with provided ID');
        }
    } else {
        throw new Exception('Failed to update camp status: ' . mysqli_error($conn));
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
