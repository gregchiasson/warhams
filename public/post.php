<?php
error_reporting(E_ALL); 
ini_set('display_errors', TRUE); 
ini_set('display_startup_errors', TRUE); 

require_once('../lib/wh40kRenderer.php');
require_once('../lib/wh40kHTMLParser.php');
require_once('../lib/wh40kROSParser.php');

try {
    // move file out of tmp dir:
    $input  = $_FILES['list'];
    $inPath = "/var/tmp/".$input['name'];
    move_uploaded_file($input['tmp_name'], $inPath);

    if(substr($input['name'], -5) == '.html') {
        # escape these (should fix zip files, while im at it):
        $tmp = file_get_contents($inPath);
        $tmp = str_replace('& ', '&amp; ', $tmp);
        file_put_contents($inPath, $tmp);
        $parser = new wh40kHTMLParser("/var/tmp/".$input['name']);
    } else if(substr($input['name'], -5) == '.rosz') { 
        #unzip file
        $zip = new ZipArchive;
        $res = $zip->open($inPath);
        if($res == ZipArchive::ER_NOZIP) {
            print("<h2>I fucked up!</h2> <p>I dunno why this happens sometimes - the ZipArchive library just does this sometimes where the zip file isn't seen as valid by PHP, even though command-line unzip commands work fine (literally the error constant is 'NOZIP'). It's fucked up!. Try unzipping the ROSZ into a ROS, or use the HTML upload. Sorry, but I'm working on it!</p>");
            exit();
        }
        $zip->extractTo('/var/tmp/');
        $zip->close();
        $parser = new wh40kROSParser("/var/tmp/".str_replace('.rosz', '.ros', $input['name']));
    } else if(substr($input['name'], -4) == '.ros') { 
        $parser = new wh40kROSParser("/var/tmp/".$input['name']);
    }

    $UNITS = $parser->units;
    $OUTFILE = '/var/tmp/your_list_sucks_'.rand(10000,99999).'.pdf';

    $output = new wh40kRenderer($OUTFILE, $UNITS);
    $output->renderToOutFile();

    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="your_list_sucks.pdf"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.filesize($OUTFILE));
    readfile($OUTFILE);
} catch(Exception $e) {
    print($e->getMessage());
}
