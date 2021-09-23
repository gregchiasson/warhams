<?php

require_once('Renderer.php');

class newRenderer extends Renderer {
    const ONE_UP_WIDTH  = 8.5;
    const TWO_UP_WIDTH  = 5.5;
    const FOUR_UP_WIDTH = 4.25;

    const ONE_UP_HEIGHT  = 11;
    const TWO_UP_HEIGHT  = 8.5;
    const FOUR_UP_HEIGHT = 5.5;

    const ONE_UP  = 'one_up';
    const TWO_UP  = 'two_up';
    const FOUR_UP = 'four_up';

    protected $layout;
    protected $yOffset;
    protected $xOffset;

    // width is in pixels now
    protected function textHeight($text, $width=300, $fontSize=12, $font=null) {

        $draw = $this->getDraw();
        if($font) { $draw->setFont($font); }
        $draw->setFontSize($fontSize);
        $words  = explode(' ', $text);
        $line   = array_shift($words);
        $lines  = 1 + substr_count($text, "\n") + substr_count($text, "\r");

        foreach($words as $word) {
            $test = $this->image->queryFontMetrics($draw, $line);
            if($test['textWidth'] > $width) {
                $lines  += 1;
                $line   = $word;
            } else {
                $line   .= ' '.$word;
            }
        }
        $height = ($lines * ($draw->getFontSize() + 4));
        return $height; 
    }

    protected function renderText($x, $y, $text, $width=300, $fontSize=12, $font=null) {
        $draw = $this->getDraw();
        if($font) {
            $draw->setFont($font);
        }
        $draw->setFontSize($fontSize);

        $words  = explode(' ', $text);
        $output = array_shift($words);
        $line   = $output;
        $lines  = 1 + substr_count($text, "\n") + substr_count($text, "\r");

        foreach($words as $word) {
            $test = $this->image->queryFontMetrics($draw, $line . ' ' . $word);
            if($test['textWidth'] > $width) {
                $lines  += 1;
                $output .= "\n".$word;
                $line   = $word;
            } else {
                $output .= ' '.$word;
                $line   .= ' '.$word;
            }
        }

        $height = ($lines * ($draw->getFontSize() + 4));

        $this->image->annotateImage($draw, $x, $y, 0, $output);
        $this->image->drawImage($draw);
        return $height;
    }

    protected function setHeightAndWidth($layout=null) {
        $layout = $layout ? $layout : $this->layout;
        if($layout == newRenderer::ONE_UP) {
            $this->maxX = $this->res * newRenderer::ONE_UP_WIDTH;
            $this->maxY = $this->res * newRenderer::ONE_UP_HEIGHT;
        } else if($layout == newRenderer::TWO_UP) {
            $this->maxX = $this->res * newRenderer::TWO_UP_WIDTH;
            $this->maxY = $this->res * newRenderer::TWO_UP_HEIGHT;
        } else if($layout == newRenderer::FOUR_UP) {
            $this->maxX = $this->res * newRenderer::FOUR_UP_WIDTH;
            $this->maxY = $this->res * newRenderer::FOUR_UP_HEIGHT;
        }
    }

    public function getFontSize() {
        if($this->layout == newRenderer::ONE_UP) {
            return 19;
        } else {
            return 16;
        }
    }

    protected function renderLine() {
        $draw = $this->getDraw();
        $draw->setStrokeWidth(2);
        $draw->setStrokeColor('#000000');

        $x1 = $this->currentX + $this->margin;
        $y  = $this->currentY;
        $x2 = $this->currentX + $this->maxX - $this->margin;

        $draw->line($x1, $y, $x2, $y);
        $this->image->drawImage($draw);
        $this->currentY += 2;
    }

    protected function renderUnit($unit, $xOffset, $yOffset) {
    }

    protected function renderAbilities($label='Abilities', $data=array()) {
        $x          = $this->currentX + $this->margin + 5;
        $fontSize   = $this->getFontSize();
        $leftMargin = 0;
        $width      = 100;
        $psyker     = null;
        $this->currentY += 20;

        if($this->layout == newRenderer::ONE_UP) {
            $leftMargin = 250;
            $width      = 900;
        } else if($this->layout == newRenderer::TWO_UP) {
            $leftMargin = 120;
            $width      = 620;
        } else if($this->layout == newRenderer::FOUR_UP) {
            $leftMargin = 120;
            $width      = 430;
        }
        $this->renderText($x, $this->currentY, strtoupper($label), 120, $fontSize);
        foreach($data as $label => $desc) {
            if(strtoupper($label) == 'PSYKER') {
                $psyker = trim(strtoupper($label).": $desc");
            } else {
                $content = trim(strtoupper($label).": $desc");
                $this->currentY += $this->renderText($x + $leftMargin, $this->currentY, $content, $width, $fontSize);
            }
        }
        // always show the "cast x deny y" bit last, if it's there.
        if($psyker) {
            $this->currentY += $this->renderText($x + $leftMargin, $this->currentY, $psyker, $width, $fontSize);
        }
        $this->currentY += 5;
        return $this->currentY;
    }

    protected function renderWatermark() {
        $content = 'https://www.buttscri.be';
        $x = $this->currentX + $this->margin + 5;
        $y = $this->maxY - $this->margin - 5 + $this->yOffset;
        $this->currentY += $this->renderText($x, $y, $content, 600);
    }

    protected function renderTable($rows=array(), $col_widths = array(), $width=740, $showHeaders=true) {
        # insanely dumb:
        if(empty($col_widths)) {
            $col_widths = array(
                'Range'       => $this->bigBoys ? 75 : 55,
                'Number'      => $this->bigBoys ? 75 : 55,
                'S'           => $this->bigBoys ? 55 : 35,
                'AP'          => $this->bigBoys ? 55 : 35,
                'Damage'      => $this->bigBoys ? 55 : 35,
                'Warp Charge' => $this->bigBoys ? 125 : 95,
                'Type'        => $this->bigBoys ? 115 : 85,
                'Remaining W' => $this->bigBoys ? 150 : 120,
                'Dice Roll'   => $this->bigBoys ? 130 : 100,
                'Distance'    => $this->bigBoys ? 130 : 100,
                'Abilities'   => $this->bigBoys ? 450 : 250,
                'Details'     => $this->bigBoys ? 550 : 320,
                'Characteristic 1' => $this->bigBoys ? 130 : 110,
                'Characteristic 2' => $this->bigBoys ? 130 : 110,
                'Characteristic 3' => $this->bigBoys ? 130 : 110
            );
        }

        $width = $this->maxX - $this->margin - 2;

        $draw = $this->getDraw();
        $draw->setFillOpacity(1);

        # header row:
        if($showHeaders) {
            $x      = $this->currentX + $this->margin + 5;
            $height = $this->currentY + 22;
            $draw->setFillColor('#AAAAAA');
            $draw->rectangle($this->currentX + $this->margin + 2, $this->currentY, ($this->currentX + $width), $height);
            $this->image->drawImage($draw);

            $first           = true;
            $new_y           = 0;
            $this->currentY = $height;
            foreach($rows[0] as $stat => $val) {
                $text = $stat;
                if(strpos($stat, 'Characteristic') !== false) {
                    $text = 'Attribute';
                } else if($stat == 'Warp Charge') {
                    $text = 'Manifest';
                }
                $hdraw = $this->getDrawFont();

                $col_width = $this->calcColWidth($stat, $first, $col_widths);
                $first = false;

                $this->renderText($x, ($this->currentY - 3), strtoupper($text), $col_width);
                $x += $col_width;
            }
        }

        # data rows:
        for($i = 0; $i < count($rows); $i++) {
            $height = 0;
            $first     = true;
            foreach($rows[$i] as $stat => $val) {
                $col_width = $this->calcColWidth($stat, $first, $col_widths);
                $first = false;

                $theight = $this->textHeight($val, $col_width);
                if($theight > $height) {
                    $height = $theight;
                }
            }

            $draw = $this->getDraw();
            $draw->setFillOpacity(.3);
            if($i % 2) {
                $draw->setFillColor('#EEEEEE');
            } else {
                $draw->setFillColor('#FFFFFF');
            }
            $draw->rectangle($this->currentX + $this->margin + 2, $this->currentY + 4, 
                             ($this->currentX + $width), $this->currentY + $height + 4);
            $this->image->drawImage($draw);


            $x     = $this->currentX + $this->margin + 5;
            $new_y = 0;

            $first = true;
            foreach($rows[$i] as $stat => $val) {
                $col_width = $this->calcColWidth($stat, $first, $col_widths);
                $first = false;

                $fake_y = $this->renderText($x, ($this->currentY + 17), $val, $col_width);
                if($fake_y > $new_y) {
                    $new_y = $fake_y;
                }
                $x += $col_width;
            }
            $this->currentY += $new_y;
        }
        $this->currentY += 5;
    }

    protected function calcColWidth($stat, $first, $col_widths) {
        if($first) {
            $col_width = $this->bigBoys ? 335 : 220;
        } else if(array_key_exists($stat, $col_widths)) {
            $col_width = $col_widths[$stat];
        } else {
            $col_width = $this->bigBoys ? 65 : 45;
        }
        return $col_width;
    }

    protected function renderWoundBoxes($unit, $block=false, $boxSize=30) {
        $draw = $this->getDraw();
        $draw->setStrokeWidth(1);
        $draw->setFontSize($this->getFontSize());
        $draw->setFontWeight(600);

        foreach($unit['model_stat'] as $type) {
            if(is_numeric($type['W'])) {
                $label = strtoupper($type['Unit']);

                $perLine   = 12;
                $boxOffset = 320;
                if($this->layout == newRenderer::ONE_UP) {
                    $boxOffset = 400;
                    $perLine   = 24;
                }
                if($block) {
                    $boxOffset = 90;
                    $this->currentY += 30;
                }

                // get number of models
                $numModels = 1;
                foreach($unit['roster'] as $model) {
                    if(preg_match('/^(\d+)\s'.$type['Unit'].'(s)?$/', $model, $matches)) {
                        $numModels = $matches[1];
                        if($numModels > 1) {
                            $label = strtoupper($model);
                        }
                    }
                }

                $this->renderText($this->currentX + $boxOffset, $this->currentY - 10, $label, 500, 16);

                $x           = $this->currentX + $boxOffset;
                $boxes       = $type['W'] * $numModels;
                $modelWounds = $type['W'];
                $color       = '#FFFFFF';
                $draw->setFillColor($color);


                // avoid any weirdo dangling squares:
                if($boxes == $perLine + 1 || $boxes == $perLine + 2) {
                    $perLine = $boxes;
                }
    
                for($w = 0; $w < $boxes; $w++) {
                    if($w % $perLine == 0 && $w > 0) {
                        $this->currentY += $boxSize + 10;
                        $x = $this->currentX + $boxOffset;
                    }
                    $draw->setStrokeOpacity(1);
                    $draw->setStrokeColor('#000000');
                    if($w > 0 && $w % $modelWounds == 0) {
                        $color = $color == '#FFFFFF' ? '#EEEEEE' : '#FFFFFF';
                        $draw->setFillColor($color);
                    }
                    $draw->rectangle($x, $this->currentY, ($x + $boxSize), ($this->currentY + $boxSize));
                    $this->image->drawImage($draw);
                    $x += $boxSize + 10;
                }
                $this->currentY += $boxSize + 10;
            }
        }
    }

    protected function renderKeywords($label="Something", $data=array(), $allCaps = true) {
        $x = $this->currentX + $this->margin + 5;
        $y = $this->currentY + $this->getFontSize();
        $text = strtoupper($label);
        $fontSize = $this->getFontSize(); 
        $this->renderText($x, $y, $text, 120, $fontSize);

        $data     = array_unique($data);
        $contents = implode(', ', $data);
        if($allCaps) {
            $contents = strtoupper($contents);
        }

        $leftMargin = 0;
        $width      = 100;
        if($this->layout == newRenderer::ONE_UP) {
            $leftMargin = 250;
            $width      = 900;
        } else if($this->layout == newRenderer::TWO_UP) {
            $leftMargin = 120;
            $width      = 620;
        } else if($this->layout == newRenderer::FOUR_UP) {
            $leftMargin = 120;
            $width      = 430;
        }
        $this->currentY += $this->renderText($x + $leftMargin, $y, $contents, $width, $fontSize);
        $this->currentY += 4;
        return $this->currentY;
    }

    public function labelBoxes($titles) {
        $draw = $this->getDraw();

        $xStart = $this->currentX + 20;
        $height = 40;
        $margin = 20;
        $width  = intval((($this->maxX - $this->margin + $this->currentX) - ($this->margin + $this->currentX) - 10) / count($titles));
        foreach($titles as $title) {
            $this->renderText($xStart, $this->currentY, strtoupper($title), 500, $this->getFontSize());
            $draw->setFillColor('#EEEEEE');
            $draw->setStrokeWidth(0);
            $draw->rectangle($xStart, $this->currentY + 10, $xStart + $width - $margin, $this->currentY + $height + 10);
            $this->image->drawImage($draw);
            $xStart += $width;
        }
        $this->currentY += $height + $margin + 20;
    }

    public function renderOrder() {
        $forces = array_shift($this->units);
        if(array_key_exists('0', $forces)) {
            $this->bigBoys = true; // enforce big boy mode on this page
            $this->image->newImage($this->res * 8.5, $this->res * 11, new ImagickPixel('white'), 'pdf');
            $this->image->setResolution($this->res, $this->res);
            $this->image->setColorspace(Imagick::COLORSPACE_RGB);

            $this->currentY += 30;

            $tpl = 0;
            $tp  = 0;
            $allUnits = array();
            foreach($forces as $force) {
                foreach($force['units'] as $slot => $units) {
                    foreach($units as $unit) {
                        $tp  += $unit['points'];
                        $tpl += $unit['power'];
                        $allUnits[] = array(
                            'datasheet'      => $unit['name'],
                            'name'           => ' ',
                            'points'         => $unit['points'],
                            'power'          => $unit['power'],
                            'crusade points' => ' '
                        );
                    }
                }
            }

            $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, "Crusade Roster", 300, 32);

            // header
            foreach(array('Crusade Force Name', 'Crusade Faction', 'Player Name') as $h) {
                $draw = $this->getDraw();
                $draw->setFillColor('#EEEEEE');
                $draw->rectangle(($this->margin + $this->currentX + 250), $this->currentY,
                                 $this->maxX - $this->margin + $this->currentX, $this->currentY + 40);
                $this->image->drawImage($draw);
                $this->renderText($this->currentX + 30, $this->currentY + 30, $h, 250, 24);
                $this->currentY += 50;
            }

            $this->renderTable(array(array(
                'battles'               => ' ',
                'wins'                  => ' ',
                'requisition points'    => ' ',
                'supply limit (pts/pl)' => '            pts /           PL',
                'supply used (pts/pl)'  => "    $tp pts/$tpl PL"
            )), array(
                'wins'                  => 200,
                'requisition points'    => 200,
                'supply limit (pts/pl)' => 200,
                'supply used (pts/pl)'   => 200 
            ));

            $this->currentY += 50;

            // units
            $this->renderTable($allUnits, array(
                'name'   => 450,
                'points' => 69,
                'power'  => 69,
                'crusade points' => 200
            ));

            $this->currentY += 50;

            // notes
            $draw = $this->getDraw();
            $draw->setFillColor('#EEEEEE');
            $draw->rectangle(($this->margin + $this->currentX + 150), $this->currentY,
                             $this->maxX - $this->margin + $this->currentX, $this->maxY - $this->margin - 50);
            $this->image->drawImage($draw);
            $this->renderAbilities('Notes', array());
            $this->currentY = $this->maxY - $this->margin - 40;

            $this->renderWatermark();

            $url = '/var/tmp/'.uniqid().'.png';
            $preview = clone $this->image;

            $preview->setFormat('gif');
            $preview->trimImage(0);
            $preview->borderImage('white', 10, 10);
            $preview->writeImages($url, false);

            $this->bigBoys = false; // the rest of the crusade roster has to be in small boys mode
            return $url;
        } else {
            // not a roster, abort!
            array_unshift($this->units, $forces);
            return null;
        }
    }

    protected function renderBorder() {
        $draw = $this->getDraw();
        $draw->setFillOpacity(0);
        $draw->setStrokeColor('#222222');
        $draw->setStrokeWidth(2);

        $x1 = $this->currentX + $this->margin;
        $y1 = $this->currentY + $this->margin;
        $x2 = $this->currentX + $this->maxX - $this->margin;
        $y2 = $this->currentY + $this->maxY - $this->margin;

        $draw->rectangle($x1, $y1, $x2, $y2);

        $this->image->drawImage($draw);
    }

    public function renderList() {
        $forces = array_shift($this->units);
        if(array_key_exists('0', $forces)) {
            $this->image->newImage($this->res * 8.5, $this->res * 11, new ImagickPixel('white'), 'pdf');
            $this->image->setResolution($this->res, $this->res);
            $this->image->setColorspace(Imagick::COLORSPACE_RGB);

            $this->currentY += 30;

            $tpl = 0;
            $tp  = 0;
            foreach($forces as $force) {
                foreach($force['units'] as $slot => $units) {
                    foreach($units as $unit) {
                        $tp  += $unit['points'];
                        $tpl += $unit['power'];
                    }
                }
            }

            if($this->isApoc) {
                $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, "Army Roster ($tpl PL)", 900, 28);
            } else {
                $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, "Army Roster ($tp pts, $tpl PL) ".$force['cp']." CP", 1000, 26);
            }

            foreach($forces as $force) {
                $pts = 0;
                $pl  = 0;
                $allUnits = array();
                foreach($force['units'] as $slot => $units) {
                    foreach($units as $unit) {
                        if($this->isApoc) {
                            unset($unit['points']);
                        }
                        $allUnits[] = $unit;
                        if(!$this->isApoc) {
                            $pts += $unit['points'];
                        }
                        $pl  += $unit['power'];
                    }
                }

                if($this->isApoc) {
                    $label = $force['faction'].' '.$force['detachment']." ($pl PL)";
                } else {
                    $label = $force['faction'].' '.$force['detachment']." ($pts pts, $pl PL)";
                }
                $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, $label, 900, 18);
                
                $unitColumns = array(
                    'name'   => $this->bigBoys ? 350 : 220,
                    'customName' => 300,
                    'slot'   => 50,
                    'roster' => $this->bigBoys ? 270 : 250,
                    'points' => 69,
                    'power'  => 69
                );
                if($this->isApoc) {
                    unset($unitColumns['points']);
                }
                $this->renderTable($allUnits, $unitColumns, $this->res * 8.5);
            }
            $this->renderWatermark();

            $url = '/var/tmp/'.uniqid().'.png';
            $preview = clone $this->image;

            $preview->setFormat('gif');
            $preview->trimImage(0);
            $preview->borderImage('white', 10, 10);
            $preview->writeImages($url, false);

            return $url;
        } else {
            // not a roster, abort!
            array_unshift($this->units, $forces);
            return null;
        }
    }

    protected function logoWrapper($x, $y) {
        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $x, $y);
    }

    public function renderToOutFile() {
        if($this->skipDuplicates && !$this->crusade) {
            $hashes = array();
            $tmp    = array();
            foreach($this->units as $unit) {
                // hash the thing
                $hash = md5(serialize($unit));
                // if that hash exists, dump it, else add it to tmp
                if(!in_array($hash, $hashes)) {
                    $tmp[] = $unit;
                }
                // add hash to hashes
                $hashes[] = $hash;
            }
            // replace units with tmp
            $this->units = $tmp;
        }

        $files = array();

        $currentLayout = $this->layout;

        $this->layout = newRenderer::ONE_UP;
        $this->maxX = 144 * 8.5;
        $this->maxY = 144 * 11;

        if($this->crusade) {
            $summary  = $this->renderOrder();
        } else { 
            $summary  = $this->renderList();
        }
        if($summary) {
            $files['summary'] = $summary;
        }

        $this->layout = $currentLayout;
        $this->setHeightAndWidth($currentLayout);

        for($i = 0; $i < count($this->units); $i++) {
            if($this->layout == newRenderer::ONE_UP || $this->layout == newRenderer::FOUR_UP) {
                $height = $this->res * 11;
                $width  = $this->res * 8.5;
            } else {
                $height = $this->res * 8.5;
                $width  = $this->res * 11;
            }
            $this->image->newImage($width, $height, new ImagickPixel('white'), 'pdf');
            $this->image->setResolution($this->res, $this->res);
            $this->image->setColorspace(Imagick::COLORSPACE_RGB);

            if(array_key_exists($i, $this->units)) {
                $this->renderUnit($this->units[$i], 0, 0);
            }

            if($this->layout == newRenderer::TWO_UP) {
                if($this->crusade) {
                    $this->renderCrusade($this->units[$i], $width / 2, 0);
                } else {
                    $i += 1;
                    if(array_key_exists($i, $this->units)) {
                        $this->renderUnit($this->units[$i], $width / 2, 0);
                    }
                }
            }

            if($this->layout == newRenderer::FOUR_UP) {
                $i += 1;
                if(array_key_exists($i, $this->units)) { $this->renderUnit($this->units[$i], ($width / 2), 0); }
                $i += 1;
                if(array_key_exists($i, $this->units)) { $this->renderUnit($this->units[$i], 0, ($height / 2)); }
                $i += 1;
                if(array_key_exists($i, $this->units)) { $this->renderUnit($this->units[$i],  ($width / 2), ($height / 2)); }
            }

            $this->image->writeImages($this->outFile, true);
        }
        $files['list'] = $this->outFile;
        return $files;
    }

    function renderCrusade($unit, $xOffset, $yOffset) { 
        $fields = array(
            array('label' => 'UNIT NAME', 'format' => 'text', 'size' => 50,
                  'sort'  => 1, 'value'  => $unit['customName'] ? $unit['customName'] : $unit['title']),
            array('label' => 'CRUSADE POINTS', 'format' => 'text', 'size' => 50,
                  'sort'  => 2, 'value'  => ''),
            array('label' => 'Battles Fought', 'format' => 'text', 'size' => 40,
                  'sort'  => 3, 'value'  => ''),
            array('label' => 'Battles Survived', 'format' => 'text', 'size' => 40,
                  'sort'  => 4, 'value'  => ''),
            array('label' => 'Melee Kills', 'format' => 'tally', 'size' => 30,
                  'sort'  => 9, 'value'  => ''),
            array('label' => 'Ranged Kills', 'format' => 'tally', 'size' => 30,
                  'sort'  => 10, 'value'  => ''),
            array('label' => 'Psychic Kills', 'format' => 'tally', 'size' => 30,
                  'sort'  => 11, 'value'  => ''),
            array('label' => 'Notes', 'format' => 'textarea', 'size' => 5,
                  'sort'  => 13, 'value'  => $unit['notes'])
        );

        if(!in_array('Drone', $unit['keywords']) && !in_array('Swarm', $unit['keywords'])) {
            $fields[] = array(
                'label'  => 'Relics',
                'format' => 'textarea',
                'size'   => 2,
                'sort'   => 5,
                'value'  => ''
            );
            $fields[] = array(
                'label'  => 'Warlord Traits',
                'format' => 'textarea',
                'size'   => 2,
                'sort'   => 6,
                'value'  => ''
            );
            $fields[] = array(
                'label'  => 'Battle Honors',
                'format' => 'textarea',
                'size'   => 3,
                'sort'   => 7,
                'value'  => ''
            );
            $fields[] = array(
                'label'  => 'Battle Scars',
                'format' => 'textarea',
                'size'   => 3,
                'sort'   => 8,
                'value'  => ''
            );
            $fields[] = array(
                'label'  => 'Experience',
                'format' => 'boxes',
                'size'   => 60,
                'sort'   => 12,
                'value'  => ''
            );
        }
        $this->renderTracking($fields, $xOffset, $yOffset);
    }

    function renderTracking($fields, $xOffset, $y) {
        usort($fields, function($a, $b) {
            if($a['sort'] == $b['sort']) {
                return 0;
            }
            return ($a['sort'] < $b['sort']) ? -1 : 1;
        });

        $x          = $xOffset;
        $y          = 40;

        $tallest    = 0;
        $lineWidth  = 0;
        $fieldWidth = 0;
        $height     = 0;
        $margin     = 20;

        // TODO convert all the goddamn weidths to px
        foreach($fields as $field) {
            switch($field['format']) {
                case 'textarea':
                    $fieldWidth = 100;
                    $height = ($field['size'] * 30);
                    break;
                case 'tally':
                case 'text':
                    $fieldWidth = $field['size'];
                    $height = 40;
                    break;
                case 'boxes':
                    $height = ceil($field['size'] / 15) * 40;
                    break;
            }

            $lineOffset = ($lineWidth / 100) * 144 * 5; // TODO: not hardcode to .5 of 2-up
            $pixWidth   = ($fieldWidth / 100) * 144 * 5; // TODO: not hardcode to .5 of 2-up

            if($fieldWidth + $lineWidth > 100) {
                $lineWidth = 0;
                $y += $tallest + 30;
                $x = $xOffset;
                $tallest = 0;
            }

            if($height > $tallest) {
                $tallest = $height;
            }

            switch($field['format']) {
                case 'textarea':
                    $height = $field['size'] * 30;
                    $this->renderInput($x, $y, $pixWidth - $margin, $height, $field);
                    break;
                case 'tally':
                    $field['value'] = str_repeat('|', intval($field['value']));
                    $this->renderInput($x, $y, $pixWidth - $margin, $height, $field);
                    break;
                case 'text':
                    $this->renderInput($x, $y, $pixWidth - $margin, $height, $field);
                    break;
                case 'boxes':
                    $this->renderText($x, $y, $field['label'], $pixWidth, 18);
                    $this->renderBoxes($x, $y + 10, 15, $field['size'], $field['value']);
                    break;
            }
            $lineWidth += $fieldWidth;
            $x += $pixWidth;
        }
    }

    protected function renderInput($x, $y, $pixWidth, $height, $field) {
        $this->renderText($x, $y + 3, $field['label'], $pixWidth, 18);
        $draw = $this->getDraw();
        $draw->setFillColor('#EEEEEE');
        $draw->setStrokeColor('#222222');
        $draw->rectangle($x, $y + 10, $x + $pixWidth, $y + 10 + $height);
        $this->image->drawImage($draw);
        $this->renderText($x + 10, $y + 35, $field['value'], $pixWidth, 18);
    }

    protected function renderBoxes($xOffset, $y, $perLine, $limit, $filled) {
        $x    = $xOffset;
        $draw = $this->getDraw();
        $draw->setStrokeWidth(1);
        $draw->setFillColor('#222222');
        $draw->setStrokeOpacity(1);
        $draw->setStrokeColor('#000000');

        for($w = 0; $w < $limit; $w++) {
            if($w % $perLine == 0 && $w > 0) {
                $y += 40;
                $x = $xOffset;
            }
            if($w >= $filled) {
                $draw->setFillColor('#FFFFFF');
            }
            $draw->rectangle($x, $y, ($x + 30), ($y + 30));
            $this->image->drawImage($draw);
            $x += 40;
        }
    }
}
