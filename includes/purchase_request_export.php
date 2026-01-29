<?php
// ==========================
// INIT & DB
// ==========================
include '../../../init.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // PhpSpreadsheet autoload

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if ($db->connect_error) {
    die('DB Connection failed');
}

// ==========================
// INPUT (GET or POST)
// ==========================
$from   = $_GET['from'] ?? $_POST['from'] ?? '';
$to     = $_GET['to'] ?? $_POST['to'] ?? '';
$status = $_GET['status'] ?? $_POST['status'] ?? '';

if (!$from || !$to) {
    die('Invalid date range');
}

$fromDate = date('Y-m-d', strtotime($from));
$toDate   = date('Y-m-d', strtotime($to));

// ==========================
// QUERY
// ==========================
$sql = "
    SELECT 
        pr.id,
        pr.pr_number,
        pr.request_date,
        pr.requested_by,
        pr.remarks,
        pr.status,
        pr.approved_by,
        pr.approved_at,
        SUM(ri.total_estimated) AS total_estimated
    FROM purchase_request pr
    LEFT JOIN purchase_request_items ri ON ri.pr_id = pr.id
    WHERE DATE(pr.request_date) BETWEEN ? AND ?
";

$params = [$fromDate, $toDate];
$types  = "ss";

if ($status !== '') {
    $sql .= " AND pr.status = ?";
    $params[] = $status;
    $types .= "s";
}

$sql .= " GROUP BY pr.id ORDER BY pr.request_date DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// ==========================
// CREATE EXCEL
// ==========================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Purchase Requests');

// HEADERS
$headers = [
	'#',
    'P.R. Number',
    'Date',
    'Requested By',
    'Remarks',
    'Status',
    'Approved By',
    'Approved At',
    'Total Estimated'
];

$colLetters = range('A', 'Z'); // enough for up to 26 columns
foreach ($headers as $i => $header) {
    $sheet->setCellValue($colLetters[$i] . '1', $header);
    $sheet->getStyle($colLetters[$i] . '1')->getFont()->setBold(true);
    $sheet->getColumnDimension($colLetters[$i])->setAutoSize(true);
}

// DATA ROWS
$rowNum = 2;
$counter = 1; // numbering start
$max = 250; // max length for remarks

while ($row = $result->fetch_assoc()) {

    $displayStatus = strtoupper(str_replace('_', ' ', $row['status']));
    $remarksShort = mb_strimwidth($row['remarks'], 0, $max, '...');

    $sheet->setCellValue('A' . $rowNum, $counter); // <- numbering
    $sheet->setCellValue('B' . $rowNum, $row['pr_number']);
    $sheet->setCellValue('C' . $rowNum, $row['request_date']);
    $sheet->setCellValue('D' . $rowNum, $row['requested_by']);
    $sheet->setCellValue('E' . $rowNum, $remarksShort);
    $sheet->setCellValue('F' . $rowNum, $displayStatus);
    $sheet->setCellValue('G' . $rowNum, $row['approved_by']);
    $sheet->setCellValue('H' . $rowNum, $row['approved_at']);
    $sheet->setCellValue('I' . $rowNum, $row['total_estimated']);

    $rowNum++;
    $counter++; // increment numbering
}

// FORMAT TOTAL ESTIMATED COLUMN
$sheet->getStyle("I2:I" . ($rowNum - 1))
      ->getNumberFormat()
      ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

// FREEZE HEADER ROW
$sheet->freezePane('A2');

// ==========================
// DOWNLOAD AS EXCEL
// ==========================
$filename = "purchase_request_{$fromDate}_to_{$toDate}.xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
