<?php
/**
 * Download one of the logged-in patient's assessments as a PDF, to print or
 * take to any doctor (including one who is not on this platform).
 *
 * The reading is matched on both pdid and the session's pid, so a patient can
 * only ever download their own.
 */
include("../connection.php");
include_once("../auth.php");
include_once("../prediction.php");
require_once("../lib/fpdf/fpdf.php");

date_default_timezone_set('Asia/Kathmandu');

$patient = requireRole($con, 'patient');
$userid = (int)$patient['pid'];
$record_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $con->prepare("SELECT * FROM patient_data WHERE pdid = ? AND pid = ?");
$stmt->bind_param("ii", $record_id, $userid);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
    http_response_code(404);
    echo "<p>No record found or you do not have permission to view it.</p>";
    exit();
}

$assessment = assessReading($con, $data);
$con->close();

if (!$assessment) {
    http_response_code(503);
    echo "<p>The prediction model could not be run, so the PDF cannot be created right now. Please try again later.</p>";
    exit();
}

/** FPDF's core fonts are Windows-1252; convert, dropping what cannot be shown. */
function pdfText($text)
{
    $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', (string)$text);
    return $converted === false ? preg_replace('/[^\x20-\x7E]/', '?', (string)$text) : $converted;
}

class AssessmentPdf extends FPDF
{
    public function Header()
    {
        $this->SetFillColor(0, 137, 123);
        $this->Rect(0, 0, $this->GetPageWidth(), 4, 'F');
        $this->SetY(12);
        $this->SetFont('Helvetica', 'B', 16);
        $this->SetTextColor(26, 32, 44);
        $this->Cell(0, 8, 'Heart Health Assessment', 0, 1);
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(113, 128, 150);
        $this->Cell(0, 5, 'Daaktar Sahaab', 0, 1);
        $this->Ln(2);
    }

    public function Footer()
    {
        $this->SetY(-14);
        $this->SetFont('Helvetica', '', 8);
        $this->SetTextColor(113, 128, 150);
        $this->Cell(0, 5, pdfText('Generated ' . date('j M Y, g:i A')), 0, 0, 'L');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'R');
    }

    public function SectionTitle($title)
    {
        $this->Ln(3);
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor(45, 55, 72);
        $this->Cell(0, 7, pdfText($title), 0, 1);
        $this->SetDrawColor(226, 232, 240);
        $this->Line($this->GetX(), $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());
        $this->Ln(2);
    }

    public function KeyValue($key, $value)
    {
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(113, 128, 150);
        $this->Cell(45, 6, pdfText($key));
        $this->SetTextColor(26, 32, 44);
        $this->Cell(0, 6, pdfText($value), 0, 1);
    }
}

$high = $assessment['high_risk'];
$pct = riskPercent($assessment['risk_score']);
$taken = strtotime($data['timestamp']);

$pdf = new AssessmentPdf('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(18, 18, 18);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetTitle(pdfText('Heart Health Assessment - ' . $patient['pname']));
$pdf->SetAuthor('Daaktar Sahaab');
$pdf->AddPage();

// ---- Patient ------------------------------------------------------------
$pdf->SectionTitle('Patient');
$pdf->KeyValue('Name', $patient['pname']);
$pdf->KeyValue('Date of birth', date('j M Y', strtotime($patient['pdob'])));
$pdf->KeyValue('Assessment taken', date('j M Y, g:i A', $taken));
$pdf->KeyValue('Reference', 'Assessment #' . (int)$data['pdid']);

// ---- Result -------------------------------------------------------------
$pdf->SectionTitle('Result');
$contentW = $pdf->GetPageWidth() - 36;
$y = $pdf->GetY();

// Tinted panel with a coloured edge, like the on-screen card.
$tone = $high ? [229, 62, 62] : [56, 161, 105];
$tint = $high ? [255, 245, 245] : [240, 255, 244];
$pdf->SetFillColor($tint[0], $tint[1], $tint[2]);
$pdf->Rect(18, $y, $contentW, 34, 'F');
$pdf->SetFillColor($tone[0], $tone[1], $tone[2]);
$pdf->Rect(18, $y, 2, 34, 'F');

$pdf->SetXY(25, $y + 4);
$pdf->SetFont('Helvetica', 'B', 14);
$pdf->SetTextColor($high ? 197 : 39, $high ? 48 : 103, $high ? 48 : 73);
$pdf->Cell(110, 7, $high ? 'High Risk of Heart Disease' : 'Low Risk of Heart Disease');
$pdf->SetFont('Helvetica', 'B', 20);
$pdf->SetTextColor(26, 32, 44);
// Score and "/100" share the space right of the title, up to the margin.
$pdf->Cell($contentW - 129, 7, (string)$pct, 0, 0, 'R');
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(113, 128, 150);
$pdf->Cell(12, 7, ' /100', 0, 1);

// Score meter with the 50-point threshold marked.
$meterX = 25;
$meterY = $y + 15;
$meterW = $contentW - 14;
$pdf->SetFillColor(237, 242, 247);
$pdf->Rect($meterX, $meterY, $meterW, 3, 'F');
$pdf->SetFillColor($tone[0], $tone[1], $tone[2]);
$pdf->Rect($meterX, $meterY, max($meterW * $pct / 100, 1), 3, 'F');
$pdf->SetDrawColor(74, 85, 104);
$pdf->SetLineWidth(0.4);
$pdf->Line($meterX + $meterW / 2, $meterY - 1.2, $meterX + $meterW / 2, $meterY + 4.2);
$pdf->SetLineWidth(0.2);

$pdf->SetXY(25, $meterY + 6);
$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(74, 85, 104);
$pdf->MultiCell($meterW, 4.5, pdfText(
    'Risk score ' . $pct . ' out of 100: the average vote of the model\'s decision trees. '
    . 'Scores above 50 (marked on the bar) are classed as high risk.'
));
$pdf->SetY($y + 37);

// Straight under the result, so it is never separated from it.
$pdf->SetFillColor(255, 248, 230);
$pdf->SetTextColor(93, 109, 126);
$pdf->SetFont('Helvetica', '', 8.5);
$pdf->MultiCell(0, 4.5, pdfText(
    'Medical disclaimer: this result comes from a statistical model and is not a diagnosis. '
    . 'It is not a substitute for professional medical advice, diagnosis, or treatment. '
    . 'Please discuss it with a qualified doctor or cardiologist.'
), 0, 'L', true);
$pdf->Ln(3);

if ($assessment['reasons']) {
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(45, 55, 72);
    $pdf->Cell(0, 6, $high ? 'Key contributing risk factors' : 'Health factors to keep in mind', 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->SetTextColor(74, 85, 104);
    foreach ($assessment['reasons'] as $reason) {
        $pdf->Cell(5, 5, chr(149));
        $pdf->MultiCell(0, 5, pdfText($reason));
    }
}

// ---- Values -------------------------------------------------------------
$pdf->SectionTitle('Submitted values');
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetFillColor(0, 137, 123);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell($contentW * 0.55, 7, '  Measure', 0, 0, 'L', true);
$pdf->Cell($contentW * 0.45, 7, '  Value', 0, 1, 'L', true);

$pdf->SetFont('Helvetica', '', 9.5);
$pdf->SetTextColor(26, 32, 44);
$pdf->SetDrawColor(226, 232, 240);
$shade = false;
foreach (ASSESSMENT_FIELDS as $name => $field) {
    $pdf->SetFillColor($shade ? 247 : 255, $shade ? 250 : 255, $shade ? 252 : 255);
    $pdf->Cell($contentW * 0.55, 6, pdfText('  ' . $field['label']), 'B', 0, 'L', true);
    $pdf->Cell($contentW * 0.45, 6, pdfText('  ' . describeAssessmentValue($name, $data[$name])), 'B', 1, 'L', true);
    $shade = !$shade;
}

$filename = 'heart-assessment-' . date('Y-m-d', $taken) . '-' . (int)$data['pdid'] . '.pdf';
$pdf->Output('D', $filename);
