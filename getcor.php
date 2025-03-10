<?php
// Include the FPDF library
require_once 'vendor/setasign/fpdf/fpdf.php';

function createEnhancedCoordinateGrid() {
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    // Set title
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Coordinate Grid Reference (mm)', 0, 1, 'C');
    
    // Draw grid lines
    $pdf->SetDrawColor(200, 200, 200); // Light gray for minor lines
    
    // Minor grid lines (every 5mm)
    $pdf->SetLineWidth(0.1);
    for($x = 0; $x <= 210; $x += 5) {
        $pdf->Line($x, 0, $x, 297);
    }
    
    for($y = 0; $y <= 297; $y += 5) {
        $pdf->Line(0, $y, 210, $y);
    }
    
    // Major grid lines (every 10mm)
    $pdf->SetDrawColor(100, 100, 100); // Darker gray for major lines
    $pdf->SetLineWidth(0.2);
    for($x = 0; $x <= 210; $x += 10) {
        $pdf->Line($x, 0, $x, 297);
    }
    
    for($y = 0; $y <= 297; $y += 10) {
        $pdf->Line(0, $y, 210, $y);
    }
    
    // Add X axis labels at top
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetTextColor(255, 0, 0); // Red text
    
    // X axis labels (only at the top)
    for($x = 0; $x <= 210; $x += 10) {
        $pdf->Text($x, 5, $x);
    }
    
    // Y axis labels (only at the left side)
    for($y = 0; $y <= 297; $y += 10) {
        $pdf->Text(2, $y, $y);
    }
    
    // Add some key intersection points for reference
    $key_points = [
        [60, 60], [100, 100], [150, 150], 
        [60, 120], [105, 75], [150, 50]
    ];
    
    $pdf->SetFont('Arial', 'B', 6);
    foreach($key_points as $point) {
        $x = $point[0];
        $y = $point[1];
        // Draw a small marker
        $pdf->SetDrawColor(255, 0, 0);
        $pdf->SetFillColor(255, 0, 0);
        $pdf->Rect($x-1, $y-1, 2, 2, 'F');
        // Label the point
        $pdf->Text($x + 2, $y + 2, "($x,$y)");
    }
    
    // Output directly to browser
    $pdf->Output('coordinate_grid.pdf', 'I');
}

// Helper function to draw circles
function Circle($pdf, $x, $y, $r) {
    $pdf->SetFillColor(255, 0, 0);
    $pdf->Ellipse($x, $y, $r, $r, 'F');
}

// Helper function for ellipses
function Ellipse($pdf, $x, $y, $rx, $ry, $style='D') {
    if($style=='F')
        $op='f';
    elseif($style=='FD' || $style=='DF')
        $op='B';
    else
        $op='S';

    $lx=4/3*(M_SQRT2-1)*$rx;
    $ly=4/3*(M_SQRT2-1)*$ry;
    
    $pdf->_out(sprintf('%.2F %.2F m',($x+$rx)*$pdf->k,($pdf->h-$y)*$pdf->k));
    $pdf->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
        ($x+$rx)*$pdf->k,($pdf->h-$y-$ly)*$pdf->k,
        ($x+$lx)*$pdf->k,($pdf->h-$y-$ry)*$pdf->k,
        $x*$pdf->k,($pdf->h-$y-$ry)*$pdf->k));
    $pdf->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
        ($x-$lx)*$pdf->k,($pdf->h-$y-$ry)*$pdf->k,
        ($x-$rx)*$pdf->k,($pdf->h-$y-$ly)*$pdf->k,
        ($x-$rx)*$pdf->k,($pdf->h-$y)*$pdf->k));
    $pdf->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
        ($x-$rx)*$pdf->k,($pdf->h-$y+$ly)*$pdf->k,
        ($x-$lx)*$pdf->k,($pdf->h-$y+$ry)*$pdf->k,
        $x*$pdf->k,($pdf->h-$y+$ry)*$pdf->k));
    $pdf->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
        ($x+$lx)*$pdf->k,($pdf->h-$y+$ry)*$pdf->k,
        ($x+$rx)*$pdf->k,($pdf->h-$y+$ly)*$pdf->k,
        ($x+$rx)*$pdf->k,($pdf->h-$y)*$pdf->k));
    $pdf->_out($op);
}

// Generate and display the enhanced coordinate grid
createEnhancedCoordinateGrid();