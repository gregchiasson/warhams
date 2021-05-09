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
        $x = $this->currentX + $this->margin + 5;
        $this->currentY += 20;
        $fontSize = $this->getFontSize();

        $leftMargin = 0;
        $width      = 100;
        if($this->layout == newRenderer::ONE_UP) {
            $leftMargin = 250;
            $width      = 100;
        } else if($this->layout == newRenderer::TWO_UP) {
            $leftMargin = 190;
            $width      = 69;
        } else if($this->layout == newRenderer::FOUR_UP) {
            $leftMargin = 120;
            $width      = 55;
        }
        $this->renderText($x, $this->currentY, strtoupper($label), 40, $fontSize);
        foreach($data as $label => $desc) {
            $content = trim(strtoupper($label).": $desc");
            $this->currentY += $this->renderText($x + $leftMargin, $this->currentY, $content, $width, $fontSize);
        }
        $this->currentY += 5;
        return $this->currentY;
    }

    protected function renderWatermark() {
        $content = 'CREATED WITH BUTTSCRIBE: http://www.buttscri.be';
        $x = $this->currentX + $this->margin + 5;
        $y = $this->maxY - $this->margin - 5 + $this->yOffset;
        $this->currentY += $this->renderText($x, $y, $content, 90);
    }

    protected function renderTable($rows=array(), $col_widths = array(), $width=740, $showHeaders=true) {
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

        $width = $this->maxX - $this->margin - 2;

        for($i = 0; $i < count($rows); $i++) {
            $draw = $this->getDraw();
            $draw->setFillOpacity(1);

            # header row:
            if($i == 0 && $showHeaders) {
                $x      = $this->currentX + $this->margin + 5;
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

            $x     = $this->currentX + $this->margin + 5;
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
        $x = $this->currentX + $this->margin + 5;
        $y = $this->currentY + $this->getFontSize();
        $text = strtoupper($label);
        $fontSize = $this->getFontSize(); 
        $this->renderText($x, $y, $text, 40, $fontSize);

        $data     = array_unique($data);
        $contents = implode(', ', $data);
        if($allCaps) {
            $contents = strtoupper($contents);
        }

        $leftMargin = 0;
        $width      = 100;
        if($this->layout == newRenderer::ONE_UP) {
            $leftMargin = 250;
            $width      = 100;
        } else if($this->layout == newRenderer::TWO_UP) {
            $leftMargin = 190;
            $width      = 69;
        } else if($this->layout == newRenderer::FOUR_UP) {
            $leftMargin = 120;
            $width      = 45;
        }
        $this->currentY += $this->renderText($x + $leftMargin, $y, $contents, $width, $fontSize);
        $this->currentY += 4;
        return $this->currentY;
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

    // TODO
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
                    'name'   => $this->bigBoys ? 350 : 220,
                    'customName' => 300,
                    'slot'   => 50,
                    'roster' => $this->bigBoys ? 350 : 280,
                    'points' => 69,
                    'power'  => 69
                );
                if($this->isApoc) {
                    unset($unitColumns['points']);
                }
                // TODO override this so its ONE_UP instead of whatever the renderer settings are
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

    public function renderToOutFile() {
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
}
