<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($db->connect_errno) {
    error_log("DB connection error: " . $db->connect_error);
    echo json_encode([]);
    exit;
}

// Fetch active suppliers
$query = $db->query("SELECT id, name, email FROM suppliers WHERE status = 1 ORDER BY name ASC");

$suppliers = [];
if($query){
    while ($row = $query->fetch_assoc()) {
        $suppliers[] = [
            'id'    => $row['id'],
            'name'  => $row['name'],
            'email' => $row['email']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($suppliers);
exit;