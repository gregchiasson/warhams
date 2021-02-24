<?php
# TODO also, techpriest enginseer text overlaps the edge of the dataslate - it's where it explains the master of machines rule or QUESTOR MECHANICUS models (line length on abilities)
# TODO Grey Knight Paladins are a squad of 3, Paragon and 2 Paladins, but the buttscribe output only has wound boxes for a paragon and one paladin (general headcount issue, semi-known - see also IG squads and Terminarors)
# TODO (big one) improve print quality

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

require_once('../lib/wh40kRenderer.php');
require_once('../lib/wh40kHTMLParser.php');
require_once('../lib/wh40kROSParser.php');
require_once('../lib/Upload.php');

$downloads = array();
$error     = null;
try {
    // move file out of tmp dir:
    $input  = $_FILES['list'];

    $fileToParse = Upload::Process($input);

    if (substr(strtolower($fileToParse), -4) == '.ros'){
        $parser = new wh40kROSParser($fileToParse);
    } elseif (substr(strtolower($fileToParse), -5) == '.html'){
        $parser = new wh40kHTMLParser($fileToParse); 
    } else {
        throw new Exception("File type is not accepted. Use .ros, .rosz or .html.");    
    }
    
    if(!$error) {
        $UNITS     = $parser->units;
        $OUTFILE   = '/var/tmp/'.uniqid().'.pdf';
        $output    = new wh40kRenderer($OUTFILE, $UNITS, array_key_exists('big_data_sheet_appreciator', $_POST));
        $downloads = $output->renderToOutFile();
    }

    # TODO: move downloads to S3, and serve from there
    $original = $downloads;
    foreach($downloads as $key => $file) {
        $new = str_replace('/var/tmp/', __DIR__.'/lists/', $file);
        copy($file, $new);
        $downloads[$key] = str_replace(__DIR__, '', $new);
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
    <p>And here's a convenient list summary, that you can feel free to link this on Discord/Forums, or send to your TO (click for big):</p>
    <a href="<?php print($downloads['summary']);?>" target="_blank">
        <img src="<?php print($downloads['summary']);?>" style="width:500px; border:1px solid black"/>
    </a>
    <p><a href="40k.php">Build another list</a>
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
