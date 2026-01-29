<?php
include '../../../init.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

$keyword = $_POST['keyword'] ?? '';
$branch  = 'PURCHASING';

if ($keyword == '') exit;

// employees nga WALA PA naa sa tbl_system_user
// ug branch = PURCHASING
$sql = "
    SELECT e.idcode, e.firstname, e.lastname
    FROM tbl_employees e
    LEFT JOIN tbl_system_user u 
        ON e.idcode = u.idcode
    WHERE u.idcode IS NULL
      AND e.branch = ?
      AND (
            e.firstname LIKE ?
         OR e.lastname LIKE ?
         OR CONCAT(e.firstname,' ',e.lastname) LIKE ?
      )
    ORDER BY e.lastname
    LIMIT 10
";

$like = "%{$keyword}%";
$stmt = $db->prepare($sql);
$stmt->bind_param("ssss", $branch, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="text-muted">No available employee</div>';
    exit;
}

while ($row = $result->fetch_assoc()) {
    echo '
    <div class="select-employee"
        data-idcode="'.$row['idcode'].'"
        data-firstname="'.$row['firstname'].'"
        data-lastname="'.$row['lastname'].'">
        '.$row['firstname'].' '.$row['lastname'].' ('.$row['idcode'].')
    </div>';
}
