

<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$t1 = $data['t1'];
$t2 = $data['t2'];

$stmt = $conn->query("SELECT * FROM users WHERE phoneNumber='".$t1."' and password='".$t2."' ");
//

if($user = $stmt ->fetch_array())
{

    // Encoding the response as JSON and sending it back
    echo json_encode($user[0].','.$user[3]);
   
} 
else
{
	  echo json_encode('0');
}
