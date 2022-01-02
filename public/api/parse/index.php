<?php
require_once('../../../lib/wh40kROSParser.php');
require_once('../../../lib/Upload.php');

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

try {
    $output     = 'unknown error';
    $fileToParse = null;

    if(array_key_exists('list', $_FILES)) {
        $input = $_FILES['list'];

        $fileToParse = Upload::Process($input);
        if($fileToParse) {
            $parser = new wh40kROSParser($fileToParse);
            $UNITS  = $parser->units;

            $short  = array();
            $shorts = array_shift($UNITS);

            foreach($shorts as $detachment) {
                foreach($detachment['units'] as $section) {
                    foreach($section as $unit) {
                        $short[] = $unit;
                    }
                }
            }

            $output = json_encode(array(
                'short' => $short,
                'long'  => $UNITS
            ));
        } else {
            $output = 'no army specified';
        }
    } else {
        $output = 'no file specified';
    }

    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.strlen($output));
    print($output);
} catch(Exception $e) {
    print($e);
}
