<?php
// download_pdf.php — Fixed UTF-8 symbols for clean PDF output
session_start();
$plan = $_SESSION['trip_plan'] ?? null;
if (!$plan) {
    die("No trip plan found. <a href='index.php'>Go back</a>");
}

require_once __DIR__ . "/lib/fpdf/fpdf.php";

function num($n) { return number_format((int)$n); }

// Convert or remove unsupported characters
function clean($text) {
    $replace = [
        "â€”" => " - ", "—" => " - ",
        "â€“" => " - ", "–" => " - ",
        "â€¢" => "* ", "•" => "* ",
        "â€™" => "'", "’" => "'",
        "â€œ" => '"', "â€" => '"',
        "â€¦" => "...", "…" => "...",
        "â€“" => "-", "–" => "-",
        "â€¢" => "*", "•" => "*",
        "₹" => "Rs ", "â‚¹" => "Rs ",
        "→" => "->", "â†’" => "->",
        "ðŸš" => "", "ðŸ’°" => "", "ðŸ—“" => "",
        "ï¸" => "", "ðŸ" => "", "Â" => ""
    ];
    return utf8_decode(strtr($text, $replace));
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial", "B", 16);
$pdf->Cell(0, 10, clean("Smart Trip Planner - Trip Summary"), 0, 1);

$pdf->SetFont("Arial", "", 12);
$m = $plan['meta'];
$c = $plan['cost'];
$routes = $plan['all_routes'];

$pdf->Ln(4);
$pdf->Cell(0, 7, clean("From: {$m['from']} -> {$m['to']}"), 0, 1);
$pdf->Cell(0, 7, clean("Days: {$m['days']} | People: {$m['people']} | Type: {$m['type']}"), 0, 1);
$pdf->Cell(0, 7, clean("Best Transport: ".strtoupper($m['mode'])." | Stay: {$m['stay']}"), 0, 1);

$pdf->Ln(4);
$pdf->SetFont("Arial", "B", 12);
$pdf->Cell(0, 7, clean("Transport Options"), 0, 1);
$pdf->SetFont("Arial", "", 11);

foreach (['bus','train','flight'] as $mode) {
    if (!isset($routes[$mode])) continue;
    $line = strtoupper($mode) . ": Distance: " . round($routes[$mode]['distance']) . " km | Cost: Rs " . num($routes[$mode]['cost']) . " | Time: ~" . $routes[$mode]['time'] . " hrs";
    $pdf->MultiCell(0, 6, clean($line));
}

$pdf->Ln(3);
$pdf->SetFont("Arial", "B", 12);
$pdf->Cell(0, 7, clean("Budget Summary (INR)"), 0, 1);
$pdf->SetFont("Arial", "", 11);
$pdf->Cell(0, 6, clean("Transport: Rs " . num($c['transport'])), 0, 1);
$pdf->Cell(0, 6, clean("Stay: Rs " . num($c['stay'])), 0, 1);
$pdf->Cell(0, 6, clean("Food: Rs " . num($c['food'])), 0, 1);
$pdf->Cell(0, 6, clean("Activities: Rs " . num($c['activities'])), 0, 1);
$pdf->Cell(0, 8, clean("Total: Rs " . num($c['total'])), 0, 1);

$pdf->Ln(3);
$pdf->SetFont("Arial", "B", 12);
$pdf->Cell(0, 7, clean("Daily Itinerary"), 0, 1);
$pdf->SetFont("Arial", "", 11);

foreach ($plan['itinerary'] as $day) {
    $pdf->SetFont("Arial", "B", 11);
    $pdf->Cell(0, 6, clean("Day " . $day['day']), 0, 1);
    $pdf->SetFont("Arial", "", 11);

    if (empty($day['items'])) {
        $pdf->Cell(0, 6, clean("Free day / Explore local markets"), 0, 1);
        continue;
    }

    foreach ($day['items'] as $it) {
        $line = "* " . $it['name'] . " - " . $it['time'] . "h | Rs " . num($it['cost']);
        $pdf->MultiCell(0, 6, clean($line));
    }
    $pdf->Ln(1);
}

// Filename format: TripPlan_From_To_Date_Time.pdf
$date = date("Ymd_His");
$fromSafe = preg_replace('/\s+/', '', $m['from']);
$toSafe = preg_replace('/\s+/', '', $m['to']);
$filename = "TripPlan_{$fromSafe}_{$toSafe}_{$date}.pdf";

$pdf->Output("D", $filename);
exit;
?>
