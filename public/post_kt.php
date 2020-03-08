<?php
error_reporting(E_ALL); 
ini_set('display_errors', TRUE); 
ini_set('display_startup_errors', TRUE); 

require_once('../lib/ktRenderer.php');
require_once('../lib/ktROSParser.php');

$error = null;

try {
    // move file out of tmp dir:
    $input = $_FILES['list'];
    $fileToParse = Upload::Process($input);

    if (substr(strtolower($fileToParse), -4) == '.ros'){
        $parser = new ktROSParser($fileToParse);
    } else {
        throw new Exception("We can only read your ros(z) files.");
    }

    if($error) {
        include('inc/header.php');
        print($error);
        include('inc/footer.php');
    } else {
        $UNITS = $parser->units;
        $OUTFILE = '/var/tmp/your_kill_team_sucks_'.rand(10000,99999).'.pdf';

        $output = new ktRenderer($OUTFILE, $UNITS);
        $output->renderToOutFile();

        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="your_kill_team_sucks.pdf"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: '.filesize($OUTFILE));
        readfile($OUTFILE);
    }
} catch(Exception $e) {
    print($e->getMessage());
}
