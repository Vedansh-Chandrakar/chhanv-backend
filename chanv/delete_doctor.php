<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// ── 1️⃣ Read incoming JSON ─────────────────────────────────────────────────────
$data = json_decode(file_get_contents('php://input'), true);
$id = isset($data['id']) ? intval($data['id']) : 0;

// ── 2️⃣ Delete doctor by ID if valid ───────────────────────────────────────────
if ($id > 0) {
    $conn->query("DELETE FROM doctors WHERE id = $id");
}

// ── 3️⃣ Fetch updated list of doctors ──────────────────────────────────────────
$stmt = $conn->query("
    SELECT id, name, specialization, phoneNo, email,
           experience, qualification, assignedCamps
    FROM doctors
");

$posts = [];
while ($r = $stmt->fetch_assoc()) {
    $posts[] = [
        "id"             => $r['id'],
        "name"           => $r['name'],
        "specialization" => $r['specialization'],
        "phoneNo"        => $r['phoneNo'],
        "email"          => $r['email'],
        "experience"     => $r['experience'],
        "qualification"  => $r['qualification'],
        "assignedCamps"  => $r['assignedCamps'],
    ];
}

// ── 4️⃣ Return JSON response ───────────────────────────────────────────────────
echo json_encode(['posts' => $posts]);
?>
