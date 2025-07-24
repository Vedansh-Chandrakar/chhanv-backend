<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("db.php");
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$sql = "SELECT * FROM camps WHERE status='scheduled' ORDER BY date ASC"; // adjust condition if needed
$result = $conn->query($sql);

$camps = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $camps[] = $row;
    }
}

echo json_encode(["success" => true, "camps" => $camps]);

$conn->close();
?>
