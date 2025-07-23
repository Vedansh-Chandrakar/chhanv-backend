<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];
$password = $data['password'];

$stmt = $conn->query("SELECT * FROM admins WHERE email='".$email."' and password='".$password."' and role='admin' ");
//

if($user = $stmt ->fetch_array())
{

    // Encoding the response as JSON and sending it back
    echo json_encode(array('name'=>$user[1],'email'=>$user[2],'role'=>$user[4]));

} else {
    // If no user is found, return an error response
    echo json_encode(array('error' => 'Invalid email or password'));
}