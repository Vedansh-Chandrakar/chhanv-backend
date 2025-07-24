<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("db.php");


// Get patientId from request (can also use reportId or relativeId)
$patientId = $_GET['patientId'];


$sql = "SELECT * FROM healthreports WHERE patientId =".$patientId;
$result = $conn->query($sql);


$reports = [];


  while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
 
}
echo json_encode(["success" => true, "reports" => $reports]);
$result->close();
$conn->close();
?>
