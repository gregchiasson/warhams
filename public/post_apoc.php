<?php
error_reporting(E_ALL); 
ini_set('display_errors', TRUE); 
ini_set('display_startup_errors', TRUE); 

ob_start();
?>
    <?php include('inc/header.php'); ?>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h3 class="panel-title">Results</h3>
        </div>
        <div class="panel-body">
<?php

require_once('../lib/apocRenderer.php');
require_once('../lib/apocParser.php');
require_once('../lib/Upload.php');

$downloads = array();
$error     = null;
try {
    // move file out of tmp dir:
    $input  = $_FILES['list'];

    $fileToParse = Upload::Process($input);

    if (substr(strtolower($fileToParse), -4) == '.ros'){
        $parser = new apocParser($fileToParse);
    } else {
        throw new Exception("File type is not accepted. Use .ros or .rosz.");    
    }
    
    if(!$error) {
        $UNITS     = $parser->units;
        $OUTFILE   = '/var/tmp/'.uniqid().'.pdf';
        $output    = new apocRenderer($OUTFILE, $UNITS);
        $downloads = $output->renderToOutFile();
    }

    $original = $downloads;
    foreach($downloads as $key => $file) {
        $new = str_replace('/var/tmp/', __DIR__.'/lists/', $file);
        copy($file, $new);
        $downloads[$key] = str_replace(__DIR__, '', $new);
        unlink("$file"); // big old waste of space
    }
} catch(Exception $e) {
    $error = $e->getMessage();
} ?>

<?php if($error) { ?>
    <?php print($error); ?>
    <?php ob_end_flush(); ?>
    </div>
    <?php include('inc/footer.php'); ?>
    </div>
<?php } else if(array_key_exists('summary', $downloads)) { ?>
    <?php ob_end_flush(); ?>
    <p><a href="<?php print($downloads['list']);?>" target="_blank">Click here to download/print list as a PDF</a></p>
    <p>List summary:</p>
    <p>
        <a href="<?php print($downloads['summary']);?>" target="_blank">
            <img src="<?php print($downloads['summary']);?>" style="width:500px; border:1px solid black"/>
        </a>
    </p>
    <p><a href="/apoc.php">Generate another</a></p>
    </div>
    </div>
    <?php include('inc/footer.php'); ?>
<?php } else {
    ob_end_clean();
    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="your_list_sucks.pdf"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.filesize($original['list']));
    readfile($original['list']);
} ?>
