<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);



$stmt = $conn->query("SELECT id, campName, location, DATE, startTime, endTime, address, coordinator, expectedBeneficiaries, doctors, services, STATUS, beneficiaries FROM camps where status='scheduled' ");
//

while($r = $stmt ->fetch_array())
{

    $posts[]=array("id"=>$r[0],"campName"=>$r[1],"location"=>$r[2],"DATE"=>$r[3],"startTime"=>$r[4],"endTime"=>$r[5],"address"=>$r[6],"coordinator"=>$r[7],"expectedBeneficiaries"=>$r[8],"doctors"=>$r[9],"services"=>$r[10],"STATUS"=>$r[11],"beneficiaries"=>$r[12]);

}
 $response['posts'] = $posts;

    // Encoding the response as JSON and sending it back
    echo json_encode($response);
?>
