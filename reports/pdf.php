<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$fpdfPath = __DIR__ . '/../vendor/fpdf/fpdf.php';

if (!is_file($fpdfPath)) {
    render_error_page(
        503,
        'Library PDF belum tersedia',
        'File vendor/fpdf/fpdf.php tidak ditemukan. Letakkan library FPDF 1.8.6 pada folder vendor/fpdf, lalu muat ulang laporan.',
        'reports'
    );
}

require_once $fpdfPath;

function pdf_text(string $value): string
{
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $value);

        if ($converted !== false) {
            return $converted;
        }
    }

    return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? $value;
}

function pdf_fit_text(FPDF $pdf, string $value, float $maxWidth): string
{
    $text = pdf_text($value);

    if ($pdf->GetStringWidth($text) <= $maxWidth) {
        return $text;
    }

    $ellipsis = '...';

    while ($text !== '' && $pdf->GetStringWidth($text . $ellipsis) > $maxWidth) {
        $text = substr($text, 0, -1);
    }

    return $text . $ellipsis;
}

class InventoryReportPDF extends FPDF
{
    public $reportDate = '';
    public $showTableHeader = false;
    public $columnWidths = [12, 30, 85, 45, 35, 20, 50];
    public $columnHeaders = [
        'No',
        'Kode',
        'Nama Barang',
        'Kategori',
        'Harga',
        'Stok',
        'Nilai Persediaan',
    ];

    public function Header()
    {
        if ($this->PageNo() > 1 && $this->showTableHeader) {
            $this->drawTableHeader();
            return;
        }

        $this->SetTextColor(23, 23, 23);
        $this->SetFont('Helvetica', 'B', 15);
        $this->Cell(0, 7, pdf_text(APP_NAME), 0, 1, 'L');

        $this->SetFont('Helvetica', 'B', 11);
        $this->Cell(0, 6, 'Laporan Persediaan Barang', 0, 1, 'L');

        $this->SetFont('Helvetica', '', 8.5);
        $this->SetTextColor(105, 105, 115);
        $this->Cell(0, 5, 'Dibuat: ' . pdf_text($this->reportDate), 0, 1, 'L');

        $this->SetDrawColor(23, 23, 23);
        $this->SetLineWidth(0.45);
        $this->Line(10, $this->GetY() + 1, $this->GetPageWidth() - 10, $this->GetY() + 1);
        $this->SetLineWidth(0.2);
        $this->Ln(4);
    }

    public function Footer()
    {
        $this->SetY(-13);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(120, 120, 128);
        $this->Cell(0, 5, 'Halaman ' . $this->PageNo() . ' dari {nb}', 0, 0, 'R');
    }

    public function drawTableHeader(): void
    {
        $this->SetFont('Helvetica', 'B', 8.5);
        $this->SetFillColor(23, 23, 23);
        $this->SetTextColor(255, 255, 255);
        $this->SetDrawColor(23, 23, 23);

        foreach ($this->columnHeaders as $index => $header) {
            $align = $index === 0 || $index === 5 ? 'C' : ($index >= 4 ? 'R' : 'L');
            $this->Cell($this->columnWidths[$index], 8, $header, 0, 0, $align, true);
        }

        $this->Ln();
        $this->SetTextColor(23, 23, 23);
        $this->SetDrawColor(225, 225, 229);
        $this->SetFont('Helvetica', '', 8.5);
    }
}

try {
    $products = get_report_products($pdo);
} catch (PDOException $exception) {
    error_log('[REPORT_PDF] ' . $exception->getMessage());
    render_error_page(
        500,
        'Laporan PDF belum dapat dibuat',
        'Data laporan tidak dapat diambil karena terjadi kesalahan pada database.',
        'reports'
    );
}

$totalJenis = count($products);
$totalStok = 0;
$totalNilai = 0.0;

foreach ($products as $product) {
    $totalStok += (int) $product['stok'];
    $totalNilai += nilai_persediaan($product['harga'], $product['stok']);
}

$pdf = new InventoryReportPDF('L', 'mm', 'A4');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 18);
$pdf->AliasNbPages();
$pdf->SetTitle('Laporan Persediaan Barang');
$pdf->SetAuthor(APP_NAME);
$pdf->SetCreator(APP_NAME);
$pdf->reportDate = format_tanggal_panjang(date('Y-m-d')) . ', ' . date('H:i') . ' WIB';

$pdf->AddPage();
$pdf->showTableHeader = true;
$pdf->SetTextColor(23, 23, 23);

if (!$products) {
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetDrawColor(225, 225, 229);
    $pdf->Cell(0, 24, 'Belum ada data barang pada database.', 1, 1, 'C');

    $pdf->Ln(3);
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell(
        0,
        6,
        'Total jenis barang: 0   |   Total stok: 0   |   Total nilai: ' . pdf_text(format_rupiah(0)),
        0,
        1,
        'L'
    );
} else {
    $pdf->drawTableHeader();

    $rowHeight = 7.5;
    $pageLimit = $pdf->GetPageHeight() - 18;
    $widths = $pdf->columnWidths;

    foreach ($products as $index => $product) {
        if ($pdf->GetY() + $rowHeight > $pageLimit) {
            $pdf->AddPage();
        }

        $fill = $index % 2 === 1;
        if ($fill) {
            $pdf->SetFillColor(248, 248, 250);
        }

        $pdf->SetFont('Helvetica', '', 8.3);
        $pdf->SetTextColor(45, 45, 52);

        $pdf->Cell($widths[0], $rowHeight, (string) ($index + 1), 'B', 0, 'C', $fill);
        $pdf->Cell($widths[1], $rowHeight, pdf_fit_text($pdf, (string) $product['kode_barang'], $widths[1] - 3), 'B', 0, 'L', $fill);
        $pdf->Cell($widths[2], $rowHeight, pdf_fit_text($pdf, (string) $product['nama_barang'], $widths[2] - 3), 'B', 0, 'L', $fill);
        $pdf->Cell($widths[3], $rowHeight, pdf_fit_text($pdf, (string) $product['nama_kategori'], $widths[3] - 3), 'B', 0, 'L', $fill);
        $pdf->Cell($widths[4], $rowHeight, pdf_text(format_rupiah($product['harga'])), 'B', 0, 'R', $fill);
        $pdf->Cell($widths[5], $rowHeight, format_angka($product['stok']), 'B', 0, 'R', $fill);
        $pdf->Cell($widths[6], $rowHeight, pdf_text(format_rupiah(nilai_persediaan($product['harga'], $product['stok']))), 'B', 0, 'R', $fill);
        $pdf->Ln();
    }

    if ($pdf->GetY() + 10 > $pageLimit) {
        $pdf->AddPage();
    }

    $pdf->SetFont('Helvetica', 'B', 8.7);
    $pdf->SetFillColor(241, 236, 255);
    $pdf->SetTextColor(85, 38, 199);
    $pdf->SetDrawColor(220, 211, 250);

    $pdf->Cell(
        $widths[0] + $widths[1] + $widths[2] + $widths[3] + $widths[4],
        8,
        'TOTAL',
        'B',
        0,
        'R',
        true
    );
    $pdf->Cell($widths[5], 8, format_angka($totalStok), 'B', 0, 'R', true);
    $pdf->Cell($widths[6], 8, pdf_text(format_rupiah($totalNilai)), 'B', 0, 'R', true);
    $pdf->Ln(10);

    $pdf->SetFont('Helvetica', '', 8.3);
    $pdf->SetTextColor(105, 105, 115);
    $pdf->Cell(
        0,
        5,
        'Ringkasan: ' . $totalJenis . ' jenis barang, ' . format_angka($totalStok) . ' unit stok, nilai ' . pdf_text(format_rupiah($totalNilai)) . '.',
        0,
        1,
        'L'
    );
}

$pdf->SetFont('Helvetica', 'I', 8);
$pdf->SetTextColor(125, 125, 133);
$pdf->Cell(0, 5, 'Laporan dibuat otomatis dari data terbaru pada database aplikasi.', 0, 1, 'L');

$filename = 'laporan-persediaan-' . date('Y-m-d') . '.pdf';
$pdf->Output('I', $filename);
exit;
