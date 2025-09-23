<?php
// Template: default.php
// This is the fallback template for all letter types.
// Variables available: $surat, $app_settings, $housing_name, $signature_image_path, $stamp_image_path, $rt_head_name

if (!isset($pdf)) {
    $pdf = new PDF();
    $pdf->AliasNbPages();
    $pdf->AddPage();
}

$pdf->SetFont('Times', '', 12);

// --- Surat Content ---
$pdf->SetFont('Times', 'BU', 14);
$pdf->Cell(0, 7, 'SURAT PENGANTAR', 0, 1, 'C');
$pdf->SetFont('Times', '', 12);
$pdf->Cell(0, 7, 'Nomor: ' . ($surat['nomor_surat'] ?: '.../.../...'), 0, 1, 'C');
$pdf->Ln(10);

$pdf->MultiCell(0, 6, 'Yang bertanda tangan di bawah ini, Ketua RT ' . $housing_name . ', dengan ini menerangkan bahwa:', 0, 'J');
$pdf->Ln(5);

// --- Data Warga ---
$pdf->Cell(10);
$pdf->Cell(50, 7, 'Nama Lengkap', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(0, 7, $surat['nama_lengkap'], 0, 1);

$pdf->Cell(10);
$pdf->Cell(50, 7, 'NIK', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(0, 7, $surat['nik'], 0, 1);

$tgl_lahir_formatted = $surat['tgl_lahir'] ? date('d F Y', strtotime($surat['tgl_lahir'])) : '-';
$pdf->Cell(10);
$pdf->Cell(50, 7, 'Tanggal Lahir', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(0, 7, $tgl_lahir_formatted, 0, 1);

$pdf->Cell(10);
$pdf->Cell(50, 7, 'Pekerjaan', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->Cell(0, 7, $surat['pekerjaan'] ?: '-', 0, 1);

$pdf->Cell(10);
$pdf->Cell(50, 7, 'Alamat', 0, 0);
$pdf->Cell(5, 7, ':', 0, 0);
$pdf->MultiCell(0, 7, $surat['alamat'], 0, 'J');
$pdf->Ln(5);

// --- Keperluan ---
$pdf->MultiCell(0, 6, 'Adalah benar warga kami yang berdomisili di alamat tersebut di atas. Surat keterangan ini dibuat sebagai pengantar untuk keperluan:', 0, 'J');
$pdf->Ln(5);

$pdf->SetFont('Times', 'B', 12);
$pdf->MultiCell(0, 7, strtoupper($surat['keperluan']), 0, 'C');
$pdf->Ln(5);

$pdf->SetFont('Times', '', 12);
$pdf->MultiCell(0, 6, 'Demikian surat pengantar ini dibuat untuk dapat dipergunakan sebagaimana mestinya.', 0, 'J');
$pdf->Ln(15);

// --- Tanda Tangan ---
$pdf->Cell(100); // Geser ke kanan
$pdf->Cell(0, 6, '................, ' . date('d F Y'), 0, 1, 'L');
$pdf->Cell(100);
$pdf->Cell(0, 6, 'Ketua RT ' . $housing_name, 0, 1, 'L');
$pdf->Ln(5);

$signature_y_pos = $pdf->GetY();
$full_signature_path = $signature_image_path ? PROJECT_ROOT . '/' . $signature_image_path : null;
if ($signature_image_path && file_exists($full_signature_path)) {
    $pdf->Image($full_signature_path, 115, $signature_y_pos, 30, 0, 'PNG');
}
$full_stamp_path = $stamp_image_path ? PROJECT_ROOT . '/' . $stamp_image_path : null;
if ($stamp_image_path && file_exists($full_stamp_path)) {
    $pdf->Image($full_stamp_path, 105, $signature_y_pos - 10, 30, 30, 'PNG');
}
$pdf->SetY($signature_y_pos + 20);

$pdf->Cell(100);
$pdf->SetFont('Times', 'BU', 12);
$pdf->Cell(0, 6, '( ' . $rt_head_name . ' )', 0, 1, 'L');

$pdf->Output('D', 'Surat_Pengantar_' . str_replace(' ', '_', $surat['nama_lengkap']) . '.pdf');