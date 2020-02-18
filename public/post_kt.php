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
    move_uploaded_file($input['tmp_name'], "/var/tmp/".$input['name']);
    $in_path = "/var/tmp/".$input['name'];
    $tmp = file_get_contents($in_path);
    $tmp = str_replace('& ', '&amp; ', $tmp);
    file_put_contents($in_path, $tmp);

    if(substr($input['name'], -5) == '.rosz') { 
        #unzip file
        $zip = new ZipArchive;
        $res = $zip->open("/var/tmp/".$input['name']);
        $zip->extractTo('/var/tmp/');
        $zip->close();

        if(!file_exists("/var/tmp/".str_replace('.rosz', '.ros', $input['name']))) {
            $error = ("<h2>Invalid File Format</h2> <p>I dunno why this happens sometimes - the ZipArchive library just does this sometimes where the zip file isn't seen as valid by PHP, even though command-line unzip commands work fine (literally the error constant is 'NOZIP'). It's messed up! Try saving the list as .ros in BattleScribe, or unzipping the ROSZ into a ROS. Sorry. :(</p>");
        } else {
            $parser = new ktROSParser("/var/tmp/".str_replace('.rosz', '.ros', $input['name']));
        }
    } else if(substr($input['name'], -4) == '.ros') { 
        $parser = new ktROSParser("/var/tmp/".$input['name']);
    } else {
        $error = ("<h2>Invalid File Format</h2> <p>I dunno why this happens sometimes - the ZipArchive library just does this sometimes where the zip file isn't seen as valid by PHP, even though command-line unzip commands work fine (literally the error constant is 'NOZIP'). It's messed up! Try saving the list as .ros in BattleScribe, or unzipping the ROSZ into a ROS. Sorry. :(</p>");
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
