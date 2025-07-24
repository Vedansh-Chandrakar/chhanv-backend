<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");



$data = json_decode(file_get_contents('php://input'), true);
$id=$data['id'];
$patientName = $data['patientName'];
$patientId = $data['patientId'];


$camp = $data['camp'];

$visitDate =$data['visitDate'];
$checkupType = $data['checkupType'];
//================
$bloodPressure = $data['bloodPressure'];
$heartRate = $data['heartRate'];


$temperature = $data['temperature'];
$weight = $data['weight'];
$height = $data['height'];
$bmi = $data['bmi'];

$bmi = $data['bmi'];
$customTests = $data['customTests'];
$vtype='';
//$bloodPressure.','.$heartRate.','.$temperature.','.$weight.','.$height.','.$bmi.','.$customTests;
//=======================================

$symptoms = $data['symptoms'];
$diagnosis = $data['diagnosis'];
$medications = $data['medications'];
$status = $data['status'];
$doctorNotes = $data['doctorNotes'];


//$qry="INSERT INTO healthreports(`id`,`patientId`,`campname`,`campdate`,`reporttype`,`Reports`,`symptoms`,`diagnosis`,`medicines`,`condition`,`notes`) VALUES ('0',".$patientId.",'".$camp."','".$visitDate."','".$checkupType."','".$vtype."','".$symptoms ."','".$diagnosis."','".$medications."','".$status."','".$doctorNotes."')";
//echo json_encode(array("msg"=>$qry));	
//return;

// Insert into doctors table (id is auto-increment)
$conn->query("INSERT INTO healthreports(`id`,`patientId`,`campname`,`campdate`,`reporttype`,`Reports`,`symptoms`,`diagnosis`,`medicines`,`condition`,`notes`) 
    VALUES ('0',".$patientId.",'".$camp."','".$visitDate."','".$checkupType."','".$vtype."','".$symptoms ."','".$diagnosis."','".$medications."','".$status."','".$doctorNotes."')");




$stmt = $conn->query("
    SELECT `id`,`patientId`,`campname`,`campdate`,`reporttype`,`doctorName`,`Reports`,`symptoms`,`diagnosis`,`medicines`,`condition`,`notes`
    FROM healthreports
");


$posts = [];
while($r = $stmt->fetch_array()) {
    $posts[] = array(
	  "id"=>$r[0],
	  "patientId"=>$r[1],
	  "campname"=>$r[2],
	  "campdate"=>$r[3],
	  "reporttype"=>$r[4],
	  "doctorName"=>$r[5],
	  "Reports"=>$r[6],
	  "symptoms"=>$r[7],
 "diagnosis"=>$r[8],
	"medicines"=>$r[9],
"condition"=>$r[10],
      
      "notes"=>$r[11],
   
    );
}

$response['posts'] = $posts;

echo json_encode($response);
?>
