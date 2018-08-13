<?php

require_once('wh40kRenderer.php');

class ktRenderer extends wh40kRenderer {

    protected $ktColor = '#FD6500';

    protected function renderUnit ($unit, $xOffset, $yOffset) {
        $this->maxX = 144 * 4.75;
        $this->maxY = 144 * 2.75;
        $this->currentX = $xOffset;
        $this->currentY = $yOffset + $this->margin;

        $draw = $this->getDraw();
        $this->image->annotateImage($draw, 121 + $this->currentX, $this->currentY, 0, $unit['points'].' points');

        if(count($unit['model_stat'])) {
            $this->renderTable($unit['model_stat']);
        }
        # weapon statlines:
        if(count($unit['weapon_stat'])) {
            $this->renderLine();
            $this->renderTable($unit['weapon_stat']);
        }
        # abilities:
        if(count($unit['abilities']) > 0) {
            $this->renderLine();
            $this->renderKeywords('Abilities', $unit['abilities']);
        }
        # abilities:
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
            $abilities = array();
            foreach($unit['abilities'] as $a => $value) { $abilities[] = $a; }
            foreach($unit['keywords'] as $a) { $keywords[] = $a; }
            $units[] = array(
                'Name'       => ' ',
                'Model Type' => $unit['title'],
                'Wargear'    => implode(', ', $guns),
                'EXP'        => ' ',
                'Specialism/Abilities' => implode(', ', $abilities),
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
        $this->renderTable($units);

        $this->image->writeImages($this->outFile, true);
    }

    public function renderBlank($x, $y, $text, $fontSize=12, $width=200) {
        $height = $fontSize + 20;
        $draw = $this->getDraw();
        $draw->setFillColor('#DDDDDD');
        $draw->rectangle($x, $y, $x + $width, $y + $height);
        $this->image->drawImage($draw);

        if($text !== null) {
            $draw = $this->getDraw();
            $draw->setFontSize($fontSize);
            $draw->setFont('../assets/kt_font.ttf');
            $this->image->annotateImage($draw, $x + 20, $y + 10 + $fontSize, 0, $text);
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
