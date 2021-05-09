<?php

require_once('Renderer.php');

class wh40kRenderer extends Renderer {
    protected function renderUnit($unit, $xOffset, $yOffset) {
        // half page landscape or full page portrait:
        $this->maxX = 144 * ($this->bigBoys ? 8.5 : 5.5);
        $this->maxY = 144 * ($this->bigBoys ? 11 : 8.5);

        $this->currentX = $xOffset;
        $this->currentY = $yOffset;

        # background (we crop and cover the corners later):
        $draw = $this->getDraw();
        $draw->setFillColor('#EEEEEE');
        $draw->rectangle(($this->margin + $this->currentX), 70, $this->maxX - $this->margin + 
                         $this->currentX, $this->maxY - $this->margin);
        $this->image->drawImage($draw);

        $this->renderHeader($unit);

        # model statlines:
        if(count($unit['model_stat'])) {
            $this->renderTable($unit['model_stat']);
        }
        # weapon statlines:
        if(count($unit['weapon_stat'])) {
            $this->renderLine();
            $this->renderTable($unit['weapon_stat']);
        }

        # wizard statlines:
        if(count($unit['powers']) > 0) {
            $needs_smite = true;
            foreach($unit['powers'] as $power) {
                if($power['Psychic Power'] == 'Smite') { $needs_smite = false; }
            }
            if($needs_smite) {
                array_unshift($unit['powers'], array(
                    'Psychic Power' => 'Smite',
                    'Warp Charge'   => 5,
                    'Range'         => '18"',
                    'Details'       => 'If manifested, the closest visible enemy unit within 18" of the psyker suffers D3 mortal wounds. If the result of the Psychic test was more than 10, the target suffers D6 mortal wounds instead.'
                ));
            }
            $this->renderLine();
            $this->renderTable($unit['powers']);
        }

        # abilities:
        if(count($unit['abilities']) > 0) {
            $this->renderLine();
            $this->renderAbilities('Abilities', $unit['abilities']);
        }

        # keywords and keyword-adjacents:
        $unit['points'] = array($unit['points']);
        $lists = array(
            'Rules'    => 'rules',
            'Factions' => 'factions',
            'Keywords' => 'keywords',
            'Contents' => 'roster'
        );
        foreach($lists as $label => $data) {
            if(count($unit[$data]) > 0) {
                $allCaps = $label == 'Roster' ? false : true;
                $this->renderLine();
                $this->renderKeywords($label, $unit[$data], $allCaps);
            }
        }

        # wound tracker:
        $hasTracks = false;
        foreach($unit['model_stat'] as $type) {
            if($type['W'] > 1) { $hasTracks = true; }
        }
        if($hasTracks) {
            $this->renderLine();
            $this->currentY += 5;
            $this->renderWoundBoxes($unit);
        }

        if(count($unit['wound_track']) > 0) {
            $this->renderLine();
            $this->renderTable($unit['wound_track']);
        }

        if(count($unit['explode_table']) > 0) {
            $this->renderLine();
            $this->renderTable($unit['explode_table']);
        }

        $this->renderBorder();
        $this->renderWatermark();
    }

    protected function renderHeader($unit) {
        $draw = $this->getDraw();
        $draw->setFillColor('#000000');
        $draw->setFillOpacity(1);
        $draw->polygon(array(
            array('x' => $this->currentX + $this->margin, 'y' => 70),
            array('x' => $this->currentX + 70, 'y' => $this->margin),
            array('x' => $this->currentX + $this->maxX - 70, 'y' => $this->margin),
            array('x' => $this->currentX + $this->maxX - $this->margin, 'y' => 70),
        ));
        $draw->rectangle(($this->margin + $this->currentX), 70, 
                         ($this->maxX + $this->currentX - $this->margin), 100);
        $this->image->drawImage($draw);

        # FOC slot and PL and points:
        $draw = $this->getDraw();
        $draw->setFillColor('#FFFFFF');
        $draw->setStrokeWidth(0);
        $draw->setFontSize(20);

        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $this->currentX + 58, 52);

        if($unit['slot'] != 'NA') {
            $gon = new Imagick();
            $gon->readImage('../assets/icon_'.$unit['slot'].'.png');
            $gon->resizeimage(35, 35, \Imagick::FILTER_LANCZOS, 1);
            $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $this->currentX + 63, 57);
        }

        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $this->currentX + 105, 52);
        $draw->setFont('../assets/title_font.otf');
        $draw->setFontSize(26);

        if(strlen($unit['power']) == 1) {
            $this->image->annotateImage($draw, 121 + $this->currentX, 84, 0, $unit['power']);
        } else {
            $this->image->annotateImage($draw, 114 + $this->currentX, 84, 0, $unit['power']);
        }

        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $this->currentX + 152, 52);
        $draw->setFont('../assets/title_font.otf');
        $draw->setFontSize(20);
        if(strlen($unit['points']) == 2) {
            $this->image->annotateImage($draw, 165 + $this->currentX, 82, 0, $unit['points']);
        } else {
            $this->image->annotateImage($draw, 158 + $this->currentX, 82, 0, $unit['points']);
        }

        # unit name:
        $iters = 0;
        $title_size = 28;
        $draw->setFontSize($title_size);
        $draw->setFont('../assets/title_font.otf');
        $title = $unit['customName'] ? $unit['customName'] : $unit['title'];
        $check = $this->image->queryFontMetrics($draw, strtoupper($title));
        $maxNameWidth = $this->bigBoys ? 600 : 420;
        while($iters < 6 && $check['textWidth'] > $maxNameWidth) {
            $iters += 1;
            $title_size -= 2;
            $draw->setFontSize($title_size);
            $check = $this->image->queryFontMetrics($draw, strtoupper($title));
        }
        $title_x =  ceil((($this->maxX * .5) + $this->currentX) - ($check['textWidth'] / 2));
        $this->image->annotateImage($draw, $title_x, 88, 0, strtoupper($title));
        $this->currentY += 100;
    }

    protected function renderAbilities($label='Abilities', $data=array()) {
        $fontSize = $this->getFontSize();
        $leftMargin = $this->bigBoys ? 250 : 190;
        $this->renderText($this->currentX + 80, $this->currentY + 20, strtoupper($label), 40, $fontSize);
        foreach($data as $label => $desc) {
            $content = trim(strtoupper($label).": $desc");
            $width = $this->bigBoys ? 100 : 69;
            $this->currentY += $this->renderText($this->currentX + $leftMargin, $this->currentY + 19, $content, $width, $fontSize);
        }
        $this->currentY += 5;
        return $this->currentY;
    }

    protected function renderWatermark() {
        $content = 'CREATED WITH BUTTSCRIBE: http://www.buttscri.be';
        $this->currentY += $this->renderText($this->currentX + 80, $this->currentY + 12, $content, 90);
    }

    protected function renderBorder() {
        $draw = $this->getDraw();
        $draw->setStrokeWidth(2);
        $draw->setStrokeColor('#222222');

        $start = array(50, 70);
        $segs = array(
            array(70, 50),
            array($this->maxX - 70, 50),
            array($this->maxX - 50, 70),
            array($this->maxX - 50, $this->currentY),
            array($this->maxX - 70, $this->currentY + 20),
            array(70, $this->currentY + 20),
            array(50, $this->currentY),
            array(50, 70)
        );
        foreach($segs as $end) {
            $draw->line($start[0] + $this->currentX, $start[1], $end[0] + $this->currentX, $end[1]);
            $start = $end;
        }
        $this->image->drawImage($draw);

        $draw = $this->getDraw();
        $draw->setFillColor('#FFFFFF');
        $draw->rectangle((50 + $this->currentX), $this->currentY + 22, $this->maxX - 50 + $this->currentX, $this->maxY - 50);
        $this->image->drawImage($draw);

        # the corners are all beefed up:
        $draw = $this->getDraw();
        $draw->setFillColor('#FFFFFF');
        $draw->setFillOpacity(1);
        $draw->polygon(array(
            array('x' => $this->currentX + 48, 'y' => $this->currentY + 22),
            array('x' => $this->currentX + 68, 'y' => $this->currentY + 22),
            array('x' => $this->currentX + 48, 'y' => $this->currentY + 2)
        ));
        $this->image->drawImage($draw);
        $draw = $this->getDraw();
        $draw->setFillColor('#FFFFFF');
        $draw->setFillOpacity(1);
        $draw->polygon(array(
            array('x' => $this->currentX + $this->maxX - 48, 'y' => $this->currentY + 22),
            array('x' => $this->currentX + $this->maxX - 68, 'y' => $this->currentY + 22),
            array('x' => $this->currentX + $this->maxX - 48, 'y' => $this->currentY + 2)
        ));
        $this->image->drawImage($draw);
    }

    protected function renderTable($rows=array(), $col_widths = array(), $width=null, $showHeaders=true) {
        # insanely dumb hack:
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
                'Characteristic 1' => $this->bigBoys ? 130 : 110,
                'Characteristic 2' => $this->bigBoys ? 130 : 110,
                'Characteristic 3' => $this->bigBoys ? 130 : 110
            );
        }

        if(!$width) { 
            $width = $this->bigBoys ? 1175 : 740;
        }

        for($i = 0; $i < count($rows); $i++) {
            $draw = $this->getDraw();
            $draw->setFillOpacity(1);

            # header row:
            if($i == 0 && $showHeaders) {
                $x      = $this->currentX + 60;
                $height = $this->currentY + 22;
                $draw->setFillColor('#AAAAAA');
                $draw->rectangle($this->currentX + $this->margin + 2, $this->currentY, ($this->currentX + $width), $height);
                $this->image->drawImage($draw);

                $first           = true;
                $new_y           = 0;
                $this->currentY = $height;
                foreach($rows[$i] as $stat => $val) {
                    $text = $stat;
                    if(strpos($stat, 'Characteristic') !== false) {
                        $text = 'Attribute';
                    } else if($stat == 'Warp Charge') {
                        $text = 'Manifest';
                    }
                    $hdraw = $this->getDrawFont();
                    $this->renderText($x, ($this->currentY - 3), strtoupper($text), $this->getfontSize());
                    if($first) {
                        $x     += $this->bigBoys ? 335 : 220;
                        $first = false;
                    } else if(array_key_exists($stat, $col_widths)) {
                        $x += $col_widths[$stat];
                    } else {
                        $x += $this->bigBoys ? 65 : 45;
                    }
               }
            }

            # data row:
            $height = 0;
            foreach($rows[$i] as $stat => $val) {
                # TODO: use the width attribute here, or base it on actual rendered width instead
                $char_limit = (($stat == 'Details' || $stat == 'roster') ? 55 : $this->bigBoys ? 45 : 30);
                $text    = wordwrap($val, $char_limit, "\n", false);
                $lines   = substr_count($text, "\n") + 1;
                $theight = $this->currentY + ($lines * ($this->getFontSize() + 4));
                if($theight > $height) {
                    $height = $theight;
                }
            }

            $draw = $this->getDraw();
            $draw->setFillOpacity(1);
            if($i % 2) {
                $draw->setFillColor('#EEEEEE');
            } else {
                $draw->setFillColor('#FFFFFF');
            }
            $draw->rectangle($this->currentX + $this->margin + 2, $this->currentY, 
                             ($this->currentX + $width), $height);
            $this->image->drawImage($draw);

            $x     = $this->currentX + 60;
            $new_y = 0;
            $first = true;
            foreach($rows[$i] as $stat => $val) {
                $fake_y = $this->renderText($x, ($this->currentY + 17), $val, $char_limit, $this->getFontSize());
                if($fake_y > $new_y) {
                    $new_y = $fake_y;
                }
                if($first) {
                    $x     += $this->bigBoys ? 335 : 220;
                    $first = false;
                } else if(array_key_exists($stat, $col_widths)) {
                    $x += $col_widths[$stat];
                } else {
                    $x += $this->bigBoys ? 65 : 45;
                }
            }
            $this->currentY += $new_y;
        }
    }

    protected function renderWoundBoxes($unit, $block=false) {
        # TODO: roster-based boxes:
        # 7 1W guys: X X X X X X 
        # 1 2W guy: X X
        # 1 2W guy: X X

        foreach($unit['model_stat'] as $type) {
            if($type['W'] > 1 || $this->isApoc) {
                $draw = $this->getDraw();
                $draw->setStrokeWidth(1);
                $draw->setFontSize($this->getFontSize());
                $draw->setFontWeight(600);

                $text   = wordwrap(strtoupper($type['Unit']), ($block ? 100 : 22), "\n", false);
                $lines  = substr_count($text, "\n") > 0 ? substr_count($text, "\n") : 1;
                $height = (($lines + 1) * ($draw->getFontSize() + 4));

                $lineHeight = 40;
                $height = $height < $lineHeight ? $lineHeight : $height;

                $this->image->annotateImage($draw, 80 + $this->currentX, $this->currentY + 20, 0, $text);
                $this->image->drawImage($draw);

                $boxSize   = 30;
                $boxOffset = ($this->bigBoys ? 400 : 340);
                if($block) {
                    $boxOffset = 90;
                    $this->currentY += 30;
                }
                $x = $this->currentX + $boxOffset;
                for($w = 0; $w < $type['W']; $w++) {
                    if($w % ($this->bigBoys ? 15 : 10 ) == 0 && $w > 0) {
                        $this->currentY += $boxSize + 10;
                        $x = $this->currentX + $boxOffset;
                    }
                    $draw->setStrokeOpacity(1);
                    $draw->setStrokeColor('#000000');
                    $draw->setFillColor('#FFFFFF');
                    $draw->rectangle($x, $this->currentY, ($x + $boxSize), ($this->currentY + $boxSize));
                    $this->image->drawImage($draw);
                    $x += $boxSize + 10;
                }
                $this->currentY += $height;
            }
        }
    }

    protected function renderKeywords($label="Something", $data=array(), $allCaps = true) {
        $text = strtoupper($label);
        $fontSize = $this->getFontSize(); 
        $this->renderText($this->currentX + 80, $this->currentY + 20, $text, 40, $fontSize);

        $data     = array_unique($data);
        $contents = implode(', ', $data);
        if($allCaps) {
            $contents = strtoupper(implode(', ', $data));
        }

        $leftMargin = $this->bigBoys ? 250 : 190;
        $width = $this->bigBoys ? 100 : 65;
        $this->currentY += $this->renderText($this->currentX + $leftMargin, $this->currentY + 19, $contents, $width, $fontSize);
        $this->currentY += 4;
        return $this->currentY;
    }

    public function renderCrusade($unit, $xOffset, $yOffset) {
        $noXP = false;
        if(in_array('Drone', $unit['keywords']) || in_array('Swarm', $unit['keywords'])) {
            $noXP = true;
        }
        $this->currentX = $xOffset + $this->margin;
        $this->currentY = $yOffset + $this->margin;

        $draw = $this->getDraw();
        $draw->setFillColor('#FFFFFF');
        $draw->setStrokeWidth(2);
        $draw->setStrokeColor('#222222');
        $draw->rectangle($this->currentX, $this->currentY,
                         $xOffset + $this->maxX - $this->margin, $this->maxY - $this->margin);
        $this->image->drawImage($draw);

        // headers
        $draw = $this->getDraw();
        $this->renderText($this->currentX + 20, $this->currentY + 30, "UNIT NAME", 50, 22);
        $this->renderText($this->currentX + 450, $this->currentY + 30, "CRUSADE POINTS", 50, 22);
        $this->currentY += 40;
        $draw->setFillColor('#EEEEEE');
        $draw->setStrokeWidth(0);
        $draw->rectangle($this->currentX + 20, $this->currentY, $xOffset + 420, $this->currentY + 40);
        $draw->rectangle($this->currentX + 450, $this->currentY, $xOffset + 730, $this->currentY + 40);
        $this->image->drawImage($draw);
        $this->renderText($this->currentX + 25, $this->currentY + 30, $unit['customName'], 50, 22);

        $this->currentY += 70;

        $this->labelBoxes(array('battles fought', 'battles survived'));
        $this->labelBoxes(array('Melee Kills', 'Ranged Kills', 'Psyker Kills'));

        // upgrades
        $this->currentX -= 60;

        $this->labelBox('Relics', 2);
        $this->labelBox('Warlord Traits', 2);
        if(!$noXP) {
            $this->labelBox('Battle Honors', 3);
            $this->labelBox('Battle Scars', 3);
        }

        // XP track 
        if(!$noXP) {
            $this->currentX += 60;
            $this->renderText($this->currentX + 20, $this->currentY + 30, "EXPERIENCE", 50, $this->getFontSize());
            $this->currentX += 170;
            $draw = $this->getDraw();
            $draw->setStrokeWidth(1);
            $boxSize = 30;
            $height  = $boxSize + 10;

            $x       = $this->currentX;
            for($w = 0; $w < 52; $w++) {
                if($w % 13 == 0 && $w > 0) {
                    $this->currentY += $height;
                    $x = $this->currentX;
                }
                $draw->setStrokeOpacity(1);
                $draw->setStrokeColor('#000000');
                if($w == 0) { 
                    $xx = $x;
                    $xy = $this->currentY;
                    $draw->setFillColor('#999999');
                } else if($w == 6 || $w == 16 || $w == 31 || $w == 51) {
                    $draw->setFillColor('#999999');
                } else {
                    $draw->setFillColor('#FFFFFF');
                }
                $draw->rectangle($x, $this->currentY, ($x + $boxSize), ($this->currentY + $boxSize));
                $this->image->drawImage($draw);
                $x += $boxSize + 10;
            }
            $this->currentY += $height;
            $this->renderText($xx + 5, $xy + 25, "X", 50, $this->getFontSize() + 10);
            $this->currentX -= 230;
        }

        $draw = $this->getDraw();
        $draw->setFillColor('#EEEEEE');
        $draw->rectangle(($this->margin + $this->currentX + 150), $this->currentY,
                         $this->maxX - $this->margin + $this->currentX, $this->maxY - $this->margin - 50);
        $this->image->drawImage($draw);
        $this->renderAbilities("Notes,\nUpgrades,\nWargear", array());
        $this->renderText($this->currentX + 210, $this->currentY + 20, $unit['notes'], 50, 22);
        $this->currentY = $this->maxY - $this->margin - 40;
    }

    public function labelBox($title, $lines=1) {
        $draw = $this->getDraw();
        $draw->setFillColor('#EEEEEE');
        $draw->rectangle(($this->margin + $this->currentX + 250), $this->currentY,
                          $this->maxX - $this->margin + $this->currentX, $this->currentY + (40 * $lines));
        $this->image->drawImage($draw);
        $this->renderAbilities($title, array());
        $this->currentY += (40 * $lines) + 10;
    }

    public function labelBoxes($titles) {
        $draw = $this->getDraw();

        $xStart = $this->currentX + 20;
        $height = 40;
        $margin = 20;
        $width  = intval((($this->maxX - $this->margin + $this->currentX) - ($this->margin + $this->currentX) - 10) / count($titles));
        foreach($titles as $title) {
            $this->renderText($xStart, $this->currentY, strtoupper($title), 50, $this->getFontSize());
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
            $this->maxX = 144 * 8.5;
            $this->maxY = 144 * 11;

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
                            'name'           => $unit['customName'],
                            'points'         => $unit['points'],
                            'power'          => $unit['power'],
                            'crusade points' => ' '
                        );
                    }
                }
            }

            $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, "Crusade Roster", 50, 32);

            // header
            $this->labelBox('Crusade Force Name');
            $this->labelBox('Crusade Faction');
            $this->labelBox('Player Name');

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
                'crusade points' => 69
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

    public function renderList() {
        $forces = array_shift($this->units);
        if(array_key_exists('0', $forces)) {
            $this->image->newImage($this->res * 8.5, $this->res * 11, new ImagickPixel('white'), 'pdf');
            $this->image->setResolution($this->res, $this->res);
            $this->image->setColorspace(Imagick::COLORSPACE_RGB);

            $this->currentY += 30;
            $this->maxX = 144 * 8.5;
            $this->maxY = 144 * 11;


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
                $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, "Army Roster ($tpl PL)", 50, 22);
            } else {
                $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, "Army Roster ($tp pts, $tpl PL) ".$force['cp']." CP", 50, 22);
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
                $this->currentY += $this->renderText($this->currentX + 50, $this->currentY + 20, $label, 150, 18);
                
                $unitColumns = array(
                    'name'   => $this->bigBoys ? 320 : 220,
                    'customName' => 240,
                    'slot'   => 50,
                    'roster' => $this->bigBoys ? 350 : 280,
                    'points' => 69,
                    'power'  => 69
                );
                if($this->isApoc) {
                    unset($unitColumns['points']);
                }
                $this->renderTable($allUnits, $unitColumns, $this->res * 8);
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

    public function renderToOutFile() {
        $files = array();
        if($this->crusade) {
            $summary  = $this->renderOrder();
        } else { 
            $summary  = $this->renderList();
        }
        if($summary) {
            $files['summary'] = $summary;
        }
        for($i = 0; $i < count($this->units); $i++) {
            if($this->bigBoys) {
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

            if(!$this->bigBoys) {
                if($this->crusade) {
                    $this->renderCrusade($this->units[$i], ($this->res * 5.5), 0);
                } else {
                    $i += 1;
                    if(array_key_exists($i, $this->units)) {
                        $this->renderUnit($this->units[$i], ($this->res * 5.5), 0);
                    }
                }
            }
            $this->image->writeImages($this->outFile, true);
        }
        $files['list'] = $this->outFile;
        return $files;
    }
}
