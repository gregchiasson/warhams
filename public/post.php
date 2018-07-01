<?php
# TODO: parse/render headcount of unit

# TODO: general look and feel updates, because it looks like trash at the moment:
# spacing, fonts, and iconography
# do unit card outline at the end, once we know the final y value (and set BG color on all content as needed)

# TODO github

error_reporting(E_ALL); 
ini_set('display_errors', TRUE); 
ini_set('display_startup_errors', TRUE); 

# your_list_[THIS].pdf
$descriptors = array('sucks', 'is_puke', 'made_me_cry', 'blows', 'stinks', 'fucks', 'reeks');

function stat_block($stats, $rows) {
    reset($stats);
    $skip  = key($stats);
    $block = array();

    foreach($rows as $r) {
        $c = $r->childNodes;
        if($c[0]->textContent == $skip) {
            $index = 0;
            foreach($r->childNodes as $cell) {
                $stat = trim($cell->textContent);
                if(array_key_exists($stat, $stats)) {
                    $stats[$stat] = $index;
                }
                $index += 1;
            }
        } else {
            $index = 0;
            $stat_block = array();
            foreach($r->childNodes as $cell) {
                $val = trim($cell->textContent);
                if(in_array($index, $stats)) {
                    $stat_block[array_search($index, $stats)] = $val;
                }
                $index += 1;
            }
            $block[] = $stat_block;
        }
    }
    return $block;
}

function get_draw() {
    $draw = new ImagickDraw();
    $draw->setFontFamily('Helvetica');
    return $draw;
}

function render_text($image, $draw, $x, $y, $text, $limit=50) {
    $text   = wordwrap($text, $limit, "\n", false);
    $lines  = substr_count($text, "\n") > 0 ? substr_count($text, "\n") : 1;
    $height = ($lines * ($draw->getFontSize() + 3)) + $y;

    $image->annotateImage($draw, $x, $y, 0, $text);
    return array($image, $height);
}

function render_line($x_offset, $max_x, $current_y, $image) {
    $stroke = 2;
    $draw   = get_draw();
    $draw->setStrokeWidth($stroke);
    $draw->setStrokeColor('#000000');
    $draw->line(50 + $x_offset, $current_y, ($max_x + $x_offset - 50), $current_y);
    $image->drawImage($draw);
    $current_y += $stroke;
    return array($image, $current_y);
}

function render_keywords($label, $data, $image, $current_y, $x_offset) {
    $draw = get_draw();
    $draw->setStrokeColor('#000000');
    $draw->setFillColor('#000000');
    $draw->setStrokeOpacity(0);
    $draw->setFillOpacity(1);
    $draw->setStrokeWidth(0);
    $draw->setFontSize(16);
    $draw->setFontWeight(600);
    list($image, $fake_y) = render_text($image, $draw, 80 + $x_offset, $current_y + 20, strtoupper("$label"), 40);
    $image->drawImage($draw);

    $draw = get_draw();
    $draw->setStrokeColor('#000000');
    $draw->setFillColor('#000000');
    $draw->setStrokeOpacity(0);
    $draw->setFillOpacity(1);
    $draw->setStrokeWidth(0);
    $draw->setFontSize(12);
    list($image, $current_y) = render_text($image, $draw, 190 + $x_offset, $current_y + 17, strtoupper(implode(', ', $data)), 75);
    $image->drawImage($draw);

    return array($image, $current_y);
}

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
                list($image, $nope) = render_text($image, $tdraw, $x, $current_y - 3, strtoupper($text));
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

function render_abilities($data, $image, $current_y, $x_offset) {
    $draw = get_draw();
    $draw->setStrokeColor('#000000');
    $draw->setFillColor('#000000');
    $draw->setStrokeOpacity(0);
    $draw->setFillOpacity(1);
    $draw->setStrokeWidth(0);
    $draw->setFontSize(16);
    $draw->setFontWeight(600);
    list($image, $fake_y) = render_text($image, $draw, 80 + $x_offset, $current_y + 20, strtoupper("ABILITIES"));
    $image->drawImage($draw);

    $draw = get_draw();
    $draw->setStrokeColor('#000000');
    $draw->setFillColor('#000000');
    $draw->setStrokeOpacity(0);
    $draw->setFillOpacity(1);
    $draw->setStrokeWidth(0);
    $draw->setFontSize(12);
    foreach($data as $label => $desc) {
        list($image, $current_y) = render_text($image, $draw, 190 + $x_offset, $current_y + 17, strtoupper($label).": $desc", 80);
    }
    return array($image, $current_y);
}

function render_unit($unit, $image, $x_offset) {
    # landscape, obvi
    $max_x = 144 * 5.5;
    $max_y = 144 * 8.5;

    # border (lines, octogon, etc:
    $draw = get_draw();
    $draw->setStrokeColor('#333333');
    $draw->setFillColor('#FFFFFF');
    $draw->setStrokeOpacity(1);
    $draw->setFillOpacity(1);
    $draw->setStrokeWidth(2);
    $draw->rectangle(
        (50 + $x_offset), 50, ($max_x + $x_offset - 50), ($max_y - 50)
    );
    $image->drawImage($draw);

    # title bar:
    $draw = get_draw();
    $draw->setFillOpacity(1);
    $draw->setFillColor('#222222');
    $draw->setStrokeWidth(0);
    $draw->rectangle(
        (50 + $x_offset), 50, ($max_x + $x_offset - 50), 100
    );
    $image->drawImage($draw);

    $draw = get_draw();
    $draw->setFillColor('#FFFFFF');
    $draw->setStrokeWidth(0);
    $draw->setFontSize(20);
    $image->annotateImage($draw, 60 + $x_offset, 80, 0, $unit['slot']);
    $image->annotateImage($draw, 100 + $x_offset, 70, 0, $unit['power'].' PL');
    $image->annotateImage($draw, 100 + $x_offset, 90, 0, '('.$unit['points'].' pts)');
    $draw->setFontSize(25);
    $image->annotateImage($draw, 200 + $x_offset, 86, 0, $unit['title']);

    $current_y = 100;

    # unit roster:
    if(count($unit['roster']) > 0) {
        list($image, $current_y) = render_keywords('Contents', $unit['roster'], $image, $current_y, $x_offset);
        list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
    }

    # model statlines:
    list($image, $current_y) = render_table($image, $x_offset, $current_y, $unit['model_stat']);

    # weapon statlines:
    list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
    list($image, $current_y) = render_table($image, $x_offset, $current_y, $unit['weapon_stat']);

    # wizard statlines:
    if(count($unit['powers']) > 0) {
        array_unshift($unit['powers'], array(
            'Psychic Power' => 'Smite', 
            'Warp Charge'   => 5, 
            'Range'         => '18"', 
            'Details'       => 'If manifested, the closest visible enemy unit within 18" of the psyker suffers D3 mortal wounds. If the result of the Psychic test was more than 10, the target suffers D6 mortal wounds instead.'
        )); 
        list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
        list($image, $current_y) = render_table($image, $x_offset, $current_y, $unit['powers']);
    }

    # abilities:
    list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
    list($image, $current_y) = render_abilities($unit['abilities'], $image, $current_y, $x_offset);

    # rules:
    if(count($unit['rules']) > 0) {
        list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
        list($image, $current_y) = render_keywords('Rules', $unit['rules'], $image, $current_y, $x_offset);
    }

    # faction keywords:
    if(count($unit['factions']) > 0) {
        list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
        list($image, $current_y) = render_keywords('Factions', $unit['factions'], $image, $current_y, $x_offset);
    }

    # non-faction keywords:
    if(count($unit['keywords']) > 0) {
        list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
        list($image, $current_y) = render_keywords('Keywords', $unit['keywords'], $image, $current_y, $x_offset);
    }

    # wound counters
    # line:
    list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
    $current_y += 10;
    foreach($unit['model_stat'] as $type) {
        if($type['W'] > 1) {
            $draw = get_draw();
            $draw->setStrokeColor('#000000');
            $draw->setFillColor('#000000');
            $draw->setStrokeOpacity(0);
            $draw->setFillOpacity(1);
            $draw->setStrokeWidth(1);
            $draw->setFontSize(16);
            $draw->setFontWeight(600);
            $image->annotateImage($draw, 80 + $x_offset, $current_y + 20, 0, strtoupper($type['Unit']));
            $image->drawImage($draw);

            $x = 340 + $x_offset;
            for($w = 0; $w < $type['W']; $w++) {
                if($w % 10 == 0 && $w > 0) {
                    $current_y += 40;
                    $x = 340 + $x_offset;
                }
                $draw->setStrokeOpacity(1);
                $draw->setStrokeColor('#000000');
                $draw->setFillColor('#FFFFFF');
                $draw->rectangle($x, $current_y, ($x + 30), ($current_y + 30));
                $image->drawImage($draw);
                $x += 40;
            }
            $current_y += 40;
        }
    }

    if(count($unit['wound_track']) > 0) {
        list($image, $current_y) = render_line($x_offset, $max_x, $current_y, $image);
        list($image, $current_y) = render_table($image, $x_offset, $current_y, $unit['wound_track']);
    }

    return $image;
}

$SLOTS = array(
    'HQ'                  => 'HQ',
    'Troops'              => 'TR',
    'Elites'              => 'EL',
    'Fast Attack'         => 'FA',
    'Heavy Support'       => 'HS',
    'Flyer'               => 'FL',
    'Dedicated Transport' => 'DT',
    'Lord of War'         => 'LW'
);

try {
    $input = $_FILES['list'];
    move_uploaded_file($input['tmp_name'], "/var/tmp/".$input['name']);

    $doc = new DOMDocument();
    $doc->loadHTMLFile("/var/tmp/".$input['name']);

    $ds = $doc->getElementsByTagName('li');

    $UNITS = array();

    foreach($ds as $d) {
    if($d->getAttribute('class') == 'rootselection') {
        $clean = array(
            'slot'        => 'None',      # FOC slot
            'power'       => 0,           # PL, points to come later
            'points'      => 0,           # its later now
            'title'       => 'unit name', # tactical squad
            'model_stat'  => array(),     # name M WS BS etc
            'weapon_stat' => array(),     # name range type etc
            'wound_track' => array(),     # remaining stat1 stat2 etc
            'powers'      => array(),     # MUSCLE WIZARDS ONLY
            'abilities'   => array(),     # Deep Strike, etc
            'rules'       => array(),     # ATSKNF, etc
            'factions'    => array(),     # IMPERIUM, etc
            'roster'      => array(),     # 7 marines, 1 heavy weapon, sarge, etc
            'keywords'    => array()      # INFANTRY, TANK, etc
        );

        foreach($d->childNodes as $c) { 
            if($c->nodeName == 'h4') {
                # TITLE, POWER, POINTS:
                $m       = null;
                preg_match('/(.*)\s+\[(\d+) PL, (\d+)pts\]$/', $c->textContent, $m);
                if($m) {
                    $clean['title']  = $m[1];
                    $clean['power']  = $m[2];
                    $clean['points'] = $m[3];
                }
            } else if($c->nodeName == 'p' && $c->getAttribute('class') == 'category-names') {
                # KEYWORDS, FACTIONS, FOC SLOT:
                $keywords = explode(',', $c->textContent);
                $keywords[0] = trim($keywords[0]);
                $keywords[0] = str_replace('Categories: ', '', $keywords[0]);
                foreach($keywords as $k) {
                    $k = trim($k);
                    if(strpos($k, 'Faction: ') === 0) {
                        $clean['factions'][] = str_replace('Faction: ', '', $k);
                    } else {
                        if(array_key_exists($k, $SLOTS)) {
                            $clean['slot'] = $SLOTS[$k];
                        } else {
                            $clean['keywords'][] = $k;
                        }
                    }
                }
                sort($clean['keywords']);
                sort($clean['factions']);
            } else if($c->nodeName == 'p' && $c->getAttribute('class') == 'rule-names') {
                # AYSKNF, etc:
                $keywords = explode(',', $c->textContent);
                $keywords[0] = trim($keywords[0]);
                $keywords[0] = str_replace('Rules: ', '', $keywords[0]);
                foreach($keywords as $k) {
                    $k = trim($k);
                    $clean['rules'][] = $k;
                }
                sort($clean['rules']);
/*
            } else if($c->nodeName == 'p') {
                foreach($c->childNodes as $cc) {
                    if($cc->nodeName == 'span' && $cc->textContent = 'Selections') {
                        $clean['roster'][] = $cc->childNodes[1]->textContent;
                    }
                }
            } else if($c->nodeName == 'ul') {
                foreach($c->childNodes as $cc) {
                    if($cc->nodeName == 'li') {
                        $clean['roster'] = $cc->childNode[0]->textContent;
                    }
                }
*/
            } else if($c->nodeName == 'table') {
                # ABILITIES, STATS, WEAPONS:
                $rows  = $c->childNodes;
                $hr    = $rows[0];
                $type  = $hr->childNodes[0]->textContent;
                $type  = preg_replace('/^(.+)\s\(.*$/', '$1', $type); # eg: Wound Track (Knights)
                switch($type) {
                    case 'Abilities':
                        foreach($rows as $r) {
                            $c = $r->childNodes;
                            if($c[0]->textContent != 'Abilities') {
                                $clean['abilities'][$c[0]->textContent] = $c[2]->textContent;
                            }
                        }
                        ksort($clean['abilities']);
                        break;
                    case 'Psyker':
                        $cast = $rows[1]->childNodes[2]->textContent;
                        $deny = $rows[1]->childNodes[3]->textContent;
                        $cast .= $cast != 1 ? ' psychic powers' : ' psychic power';
                        $deny .= $deny != 1 ? ' psychic powers' : ' psychic power';
                        $clean['abilities']['Psyker'] = "This model can attempt to manifest $cast in each friendly Psychic phase, and attempt to deny $deny in each enemy Psychic phase.";
                        break;
                    case 'Transport':
                        $clean['abilities']['Transport'] = $rows[1]->childNodes[2]->textContent;
                        break;
                    case 'Unit':
                        $stats = array('Unit' => 0, 'M'  => 0, 'WS'   => 0, 'BS'   => 0, 
                                       'S'    => 0, 'T'  => 0, 'W'    => 0,
                                       'A'    => 0, 'Ld' => 0, 'Save' => 0);
                        $clean['model_stat'] = stat_block($stats, $rows);
                        break;
                    case 'Weapon':
                        $stats = array('Weapon' => 0, 'Range'  => 0, 'Type' => 0,
                                       'S'      => 0, 'AP'     => 0, 'D'    => 0, 'Abilities' => 0);
                        $clean['weapon_stat'] = stat_block($stats, $rows);
                        break;
                    case 'Psychic Power':
                        $stats = array('Psychic Power' => 0, 'Warp Charge' => 0, 
                                        'Range'        => 0, 'Details'     => 0);
                        $clean['powers'] = stat_block($stats, $rows);
                        break;
                    case 'Wound Track':
                        $stats = array();
                        foreach($rows[0]->childNodes as $header) {
                            $header = trim($header->textContent);
                            if(strlen($header) && $header != 'Ref') {
                                $stats[$header] = 0;
                            }
                        }
                        $clean['wound_track'] = stat_block($stats, $rows);
                        break;
                }
            }
        }
        ksort($clean);
        if($clean['points'] || $clean['power']) {
            $UNITS[] = $clean;
        }
    }
    }

    $OUTFILE     = '/var/tmp/your_list_'.$descriptors[array_rand($descriptors)].'_'.rand(10000,99999).'.pdf';
    $OUTFILE     = '/var/tmp/your_list_sucks.pdf';

    $PDF   = new Imagick();
    $index = 0;
    for($i = 0; $i < count($UNITS); $i++) {
        $PDF->newImage(144 * 11, 144 * 8.5, new ImagickPixel('white'), 'pdf');
        $PDF->setResolution(144, 144);
        $PDF->setColorspace(Imagick::COLORSPACE_RGB);

        if(array_key_exists($i, $UNITS)) {
            $PDF = render_unit($UNITS[$i], $PDF, 0);
        }
        $i += 1;
        if(array_key_exists($i, $UNITS)) {
            $PDF = render_unit($UNITS[$i], $PDF, (144 * 5.5));
        }

        $PDF->writeImages($OUTFILE, true);
    }
} catch(Exception $e) {
    print($e->getMessage());
}

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="your_list_'.$descriptors[array_rand($descriptors)].'.pdf"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: '.filesize($OUTFILE));
readfile($OUTFILE);
