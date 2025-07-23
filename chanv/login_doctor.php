<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];
$password = $data['password'];

$stmt = $conn->query("SELECT * FROM doctors WHERE email='".$email."' and password='".$password."'");
//

if($user = $stmt ->fetch_array())
{

    // Encoding the response as JSON and sending it back
    echo json_encode(array('name'=>$user[1],'email'=>$user[5],'role'=>'doctor'));
   
} 
