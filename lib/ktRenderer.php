<?php

require_once('wh40kRenderer.php');

class ktRenderer extends wh40kRenderer {

    protected $ktColor = '#B23A07';

    protected function renderUnit ($unit, $xOffset, $yOffset) {
        $this->maxX = 144 * 4.75;
        $this->maxY = 144 * 2.75;
        $this->currentX = $xOffset;
        $this->currentY = $yOffset + $this->margin;

/*
        $draw = $this->getDraw();
        $draw->setFillColor('#333333');
        $draw->rectangle($xOffset, $yOffset, $xOffset + $this->maxX, $yOffset + $this->maxY);
        $draw->setFillColor('#EEEEEE');
        $draw->setStrokeWidth(2);
        $draw->setStrokeColor($this->ktColor);
        $draw->rectangle($this->margin, $this->margin,
                         (($this->maxX + $xOffset) - $this->margin),
                         (($this->maxY + $yOffset) - $this->margin)
        );
        $this->image->drawImage($draw);
*/

        $draw = $this->getDraw();
        $this->image->annotateImage($draw, 121 + $this->currentX, $this->currentY, 0, $unit['points'].' points');

        if(count($unit['model_stat'])) {
            $colWidths = array(
                'Name' => 100,
                'M'    => 50,
                'WS'   => 50,
                'BS'   => 50,
                'S'    => 50,
                'T'    => 50,
                'W'    => 50,
                'A'    => 50,
                'Ld'   => 50,
                'Sv'   => 50
            );
            $this->renderTable($unit['model_stat'], $colWidths, 600);
        }

        # weapon statlines:
        if(count($unit['weapon_stat'])) {
            $colWidths = array(
                'Weapon'    => 120,
                'Range'     => 100,
                'Type'      => 120,
                'S'         => 50,
                'AP'        => 50,
                'D'         => 50
            );
            $this->renderLine();
            $this->renderTable($unit['weapon_stat'], $colWidths, 600);
        }

        if(count($unit['abilities']) > 0) {
            $this->renderLine();
            $this->renderKeywords('Abilities', $unit['abilities']);
        }

        if(count($unit['keywords']) > 0) {
            $this->renderLine();
            $this->renderKeywords('Specialism', $unit['keywords']);
        }

        # 10 xp boxes, 3 FW boxes, conval, new recruit
    }

    public function renderList() {
        // print roster sheet first:
        $this->image->newImage($this->res * 8.5, $this->res * 11, new ImagickPixel('white'), 'pdf');
        $this->image->setResolution($this->res, $this->res);
        $this->image->setColorspace(Imagick::COLORSPACE_RGB);

        $units = array();
        $totalPoints = 0;
        foreach($this->units as $unit) {
            $totalPoints += $unit['points'];
            $guns      = array();
            $abilities = array();
            foreach($unit['weapon_stat'] as $gun) {
                $guns[] = $gun['Weapon'];
            }
            $units[] = array(
                'Name'       => ' ',
                'Model Type' => $unit['title'],
                'Wargear'    => implode(', ', $guns),
                'EXP'        => ' ',
                'Specialism/Abilities' => implode(', ', $unit['abilities']),
                'Pts' => $unit['points']
            );
        }

        $draw = $this->getDraw();
        $draw->setFillColor('#333333');
        $draw->rectangle(0, 0, $this->res * 8.5, $this->res * 11);

        $draw->setFillColor('#EEEEEE');
        $draw->setStrokeWidth(2);
        $draw->setStrokeColor($this->ktColor);
        $draw->rectangle($this->margin, $this->margin, 
                         (($this->res * 8.5) - $this->margin),
                         (($this->res * 11) - $this->margin)
        );
        $this->image->drawImage($draw);
        $this->currentY = $this->margin;

        $draw = $this->getDraw();
        $draw->setFillColor($this->ktColor);
        $draw->setFontSize(52);
        $draw->setFont('../assets/kt_title_font.ttf');
        $this->image->annotateImage($draw, 350, 120, 0, 'COMMAND ROSTER');
        $this->image->drawImage($draw);

        $draw = $this->getDraw();
        $draw->setStrokeWidth(2);
        $draw->setStrokeColor($this->ktColor);
        $draw->line(70, 130, 1150, 130);
        $this->image->drawImage($draw);

        $y = $this->margin + 90;
        $x = $this->margin + 20;
        $headings = array('Player Name', 'Faction', 'Mission', 'Background', 'Squad Quirk');
        foreach($headings as $text) {
            $this->renderBlank($x + 255, $y, null, 16, 250);
            $y += $this->renderHeading($x, $y, $text, 16, 250);
            $y += 5;
        }

        $h = $this->renderHeading($x + 510, $this->margin + 90, 'Resources', 16, 200);
        $y = $this->margin + 90 + $h + 5;
        $x = $this->margin + 530;
        $headings = array('Intelligence', 'Materiel', 'Morale', 'Territory');
        foreach($headings as $text) {
            $y += $this->renderBlank($x, $y, $text, 16, 200);
            $y += 5;
        }

        $this->renderHeading(785, 140, 'Force Pts', 16, 150);
        $this->renderBlank(940, 140, $totalPoints, 16, 210);

        $this->renderHeading(785, 181, 'Force Name', 16, 150);
        $this->renderBlank(940, 181, null, 16, 210);

        $this->currentY = 400;
        $colWidths = array(
            'Name'                 => 200,
            'Model Type'           => 200,
            'Wargear'              => 250,
            'EXP'                  => 75,
            'Specialism/Abilities' => 290,
            'Pts'                  => 75
        );
        $this->renderTable($units, $colWidths, 1155);

        $this->image->writeImages($this->outFile, true);
    }

    public function renderTable($rows=array(), $colWidths = array(), $width=740, $showHeaders=true) {
        if($showHeaders) {
            $x = $this->margin + 20 + $this->currentX;
            foreach($colWidths as $col => $width) {
                $this->renderHeading($x, $this->currentY, $col, 16, $width - 5);
                $x += $width;
            }
            $this->currentY += 42;
        }

        $colWidths = array_values($colWidths);
        for($i = 0; $i < count($rows); $i++) {
            $j      = 0;
            $height = 0;
            foreach($rows[$i] as $stat => $val) {
                $charLimit = $colWidths[$j] / 4;
                $text      = wordwrap($val, $charLimit, "\n", false);
                $lines     = substr_count($text, "\n") > 0 ? substr_count($text, "\n") : 1;
                $theight   = ($lines * (17 + 3)) + 17;
                if($theight > $height) {
                    $height = $theight;
                }
                $j += 1;
            }

            $x     = $this->currentX + 70;
            $new_y = 0;
            $first = true;
            $j     = 0;
            foreach($rows[$i] as $stat => $val) {
                $charLimit = $colWidths[$j] / 4;
                $text      = wordwrap($val, $charLimit, "\n", false);
                $this->renderBlank($x, $this->currentY, $text, 13, $colWidths[$j] - 5, $height);
                $x += $colWidths[$j];
                $j += 1;
            }
            $this->currentY += $height + 5;
        }
    }

    public function renderBlank($x, $y, $text, $fontSize=12, $width=200, $height=0) {
        if($height == 0) {
            $height = $fontSize + 20;
        }
        $draw = $this->getDraw();
        $draw->setFillColor('#DDDDDD');
        $draw->rectangle($x, $y, $x + $width, $y + $height);
        $this->image->drawImage($draw);

        if($text !== null) {
            $draw = $this->getDraw();
            $draw->setFontSize($fontSize);
            $draw->setFont('../assets/kt_font.ttf');
            $this->image->annotateImage($draw, $x + 20, $y + 7 + $fontSize, 0, $text);
            $this->image->drawImage($draw);
        }
        return $height;
    }

    public function renderHeading($x, $y, $text, $fontSize=12, $width=200) {
        $height = $fontSize + 20;
        $draw = $this->getDraw();
        $draw->setFillColor($this->ktColor);
        $draw->rectangle($x, $y, $x + $width, $y + $height);
        $this->image->drawImage($draw);

        $draw = $this->getDraw();
        $draw->setFontSize($fontSize);
        $draw->setFont('../assets/kt_title_font.ttf');
        $this->image->annotateImage($draw, $x + 20, $y + 10 + $fontSize, 0, strtoupper($text));
        $this->image->drawImage($draw);
        return $height;
    }

    public function renderToOutFile() {
        $this->renderList();
        for($i = 0; $i < count($this->units); $i++) {
            $this->image->newImage($this->res * 8.5, $this->res * 11, new ImagickPixel('white'), 'pdf');
            $this->image->setResolution($this->res, $this->res);
            $this->image->setColorspace(Imagick::COLORSPACE_RGB);

            for($r = 0; $r < 4; $r++) {
                $deltaY = $this->res * $r * 2.75; 
                if(array_key_exists($i, $this->units)) {
                    $this->renderUnit($this->units[$i], 0, $deltaY);
                }
                $i += 1;
                if(array_key_exists($i, $this->units)) {
                    $this->renderUnit($this->units[$i], ($this->res * 4.25), $deltaY);
                }
                $i += 1;
            }
            $this->image->writeImages($this->outFile, true);
        }
    }
}
