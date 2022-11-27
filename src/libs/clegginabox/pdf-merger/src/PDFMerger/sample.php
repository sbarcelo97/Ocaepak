<?php

include 'PDFMerger.php';

$pdf = new PDFMerger();

$pdf->addPDF('samplepdfs/one.pdf', '1, 3, 4')
    ->addPDF('samplepdfs/two.pdf', '1-2')
    ->addPDF('samplepdfs/three.pdf', 'all')
    ->merge('file', 'samplepdfs/TEST2.pdf');
