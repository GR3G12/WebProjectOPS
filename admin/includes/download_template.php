<?php
require __DIR__ . '/../../vendor/autoload.php'; // Adjust the path as needed

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Create a new Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set school name
$schoolName = "ACTS COMPUTER COLLEGE"; // Change this to your school name
$sheet->setCellValue('A1', $schoolName);
$sheet->mergeCells('A1:P1'); // Merge cells across all columns
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set school address
$schoolAddress = "EGK Bldg, P. Guevarra Ave., Sta. Cruz, Laguna  Tel. No. 501-1484"; // Change this to your address
$sheet->setCellValue('A2', $schoolAddress);
$sheet->mergeCells('A2:P2'); // Merge cells across all columns
$sheet->getStyle('A2')->getFont()->setBold(false)->setSize(12);
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Set column headers (now in row 3)
$headers = [
    'Student Number', 'Password', 'First Name', 'Middle Name', 'Last Name',
    'Student Type', 'Tuition Status', 'Email', 'Course', 'Year Level', 
    'Section', 'Semester', 'Total Tuition Fee', 'Tuition Fee Discount', 
    'Down Payment', 'Profile Image URL'
];

$column = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($column . '4', $header);
    $sheet->getStyle($column . '4')->getFont()->setBold(true);
    $column++;
}

// Auto-size columns
foreach (range('A', 'P') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set headers for file download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="import_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
