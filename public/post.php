<?php
error_reporting(E_ALL); 
ini_set('display_errors', TRUE); 
ini_set('display_startup_errors', TRUE); 

require_once('../lib/wh40kRenderer.php');
require_once('../lib/wh40kHTMLParser.php');
require_once('../lib/wh40kROSParser.php');

function render_table($image, $x_offset, $current_y, $rows) {
    $col_widths = array(
        'Range'       => 55,
        'Warp Charge' => 95,
        'Type'        => 75,
        'Remaining W' => 120,
        'Characteristic 1' => 110,
        'Characteristic 2' => 110,
        'Characteristic 3' => 110
    );
    for($i = 0; $i < count($rows); $i++) {
        $tdraw = get_draw();
        $tdraw->setFillColor('#000000');

        $draw = get_draw();
        $draw->setFillOpacity(1);

        # header row:
        if($i == 0) {
            $x = $x_offset + 60;
    
            $height = $current_y + 22;

            $draw->setFillColor('#AAAAAA');
            $draw->rectangle($x_offset + 50 + 2, $current_y, ($x_offset + 740), $height);
            $image->drawImage($draw);

            $tdraw->setFontSize(14);
            $tdraw->setFontWeight(600);

            $new_y     = 0;
            $current_y = $height;
            $first     = true;
            foreach($rows[$i] as $stat => $val) {
                $text = $stat;
                if(strpos($stat, 'Characteristic') !== false) {
                    $text = 'Attribute'; 
                } else if($stat == 'Warp Charge') {
                    $text = 'Manifest';
                }
                $hdraw = get_draw_font();
                list($image, $nope) = render_text($image, $hdraw, $x, $current_y - 3, strtoupper($text));
                if($first) {
                    $x     += 220;
                    $first = false;
                } else if(array_key_exists($stat, $col_widths)) {
                    $x += $col_widths[$stat];
                } else { 
                    $x += 45;
                }
            }
        }

        # data row:
        $tdraw->setFontSize(12);
        $tdraw->setFontWeight(400);
        $height = 0;
        foreach($rows[$i] as $stat => $val) {
            $char_limit = ($stat == 'Details' ? 55 : 33);
            $text    = wordwrap($val, $char_limit, "\n", false);
            $lines   = substr_count($text, "\n") > 0 ? substr_count($text, "\n") : 1;
            $theight = ($lines * ($tdraw->getFontSize() + 3)) + $current_y + 17;
            if($theight > $height) {
                $height = $theight;
            }
        }

        $draw = get_draw();
        $draw->setFillOpacity(1);
        if($i % 2) {
            $draw->setFillColor('#EEEEEE');
        } else {
            $draw->setFillColor('#FFFFFF');
        }
        $draw->rectangle($x_offset + 50 + 2, $current_y, ($x_offset + 740), $height);
        $image->drawImage($draw);

        $x     = $x_offset + 60;
        $new_y = 0;
        $first = true;
        foreach($rows[$i] as $stat => $val) {
            $tdraw->setFontWeight(400);
            if($first) { 
                $tdraw->setFontWeight(600);
            }
            list($image, $fake_y) = render_text($image, $tdraw, $x, $current_y + 17, $val, $char_limit);
            if($fake_y > $new_y) {
                $new_y = $fake_y;
            }
            if($first) {
                $x     += 220;
                $first = false;
            } else if(array_key_exists($stat, $col_widths)) {
                $x += $col_widths[$stat];
            } else {
                $x += 45;
            }
        }
        $current_y = $new_y;
    }
    return array($image, $new_y);
}

try {
    // move file out of tmp dir:
    $input = $_FILES['list'];
    move_uploaded_file($input['tmp_name'], "/var/tmp/".$input['name']);
    $in_path = "/var/tmp/".$input['name'];
    $tmp = file_get_contents($in_path);
    $tmp = str_replace('& ', '&amp; ', $tmp);
    file_put_contents($in_path, $tmp);

    if(substr($input['name'], -5) == '.html') { 
        $parser = new wh40kHTMLParser("/var/tmp/".$input['name']);
    } else if(substr($input['name'], -5) == '.rosz') { 
        #unzip file
        $zip = new ZipArchive;
        $res = $zip->open("/var/tmp/".$input['name']);
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
/*
*/
} catch(Exception $e) {
    print($e->getMessage());
}
