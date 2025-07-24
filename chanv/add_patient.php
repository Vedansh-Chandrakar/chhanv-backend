<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");



$data = json_decode(file_get_contents('php://input'), true);
$id=$data['id'];
$name = $data['name'];
$age = $data['age'];
$email = $data['email'];
$phone = $data['phone'];
$password = $data['password'];
$dateOfBirth = $data['dateOfBirth'];
$dateOfBirth = $data['dateOfBirth'];
$gender = $data['gender'];
$bloodGroup =$data['bloodGroup'];
$address = $data['address'];
$familyMembers = $data['familyMembers'];
$department = $data['department'];
$hasAbhaId = $data['hasAbhaId'];
$hasAyushmanCard = $data['hasAyushmanCard'];

  
// Insert into doctors table (id is auto-increment)
$conn->query("INSERT INTO users(id,fullname,email,phoneNumber,PASSWORD,dateOfBirth,age,gender,bloodGroup,department,address,familymember,hasAbhaId,hasAyushmanCard) 
    VALUES ('0','".$name."','".$email."','".$phone."','".$password."','".$dateOfBirth."',".$age.",'".$gender."','".$bloodGroup."','".$department."','".$address."',".$familyMembers.",'".$hasAbhaId."','".$hasAyushmanCard."')");


$stmt = $conn->query("
    SELECT id,fullname,email,phoneNumber,dateOfBirth,age,gender,bloodGroup,department,address,familymember,hasAbhaId,hasAyushmanCard 
    FROM users
");

	

$posts = [];
while($r = $stmt->fetch_array()) {
    $posts[] = array(
	  "id"=>$r[0],
	  "name"=>$r[1],
	  "email"=>$r[2],
	  "phone"=>$r[3],
	  "dateOfBirth"=>$r[4],
	  "age"=>$r[5],
	  "gender"=>$r[6],
	  "bloodGroup"=>$r[7],
 "department"=>$r[8],
	"address"=>$r[9],
"familyMembers"=>$r[10],
      
      "hasAbhaId"=>$r[11],
	  "hasAyushmanCard"=>$r[12],
   
    );
}

$response['posts'] = $posts;

echo json_encode($response);
?>
