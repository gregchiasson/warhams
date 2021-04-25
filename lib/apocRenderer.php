<?php

require_once('wh40kRenderer.php');

class apocRenderer extends wh40kRenderer {
    public function __construct($outFile, $units=array(), $bigBoys=false, $crusade=false) {
        $this->isApoc = true;
        parent::__construct($outFile, $units);
    }

    protected function renderUnit($unit, $xOffset, $yOffset) {
        $this->maxX = $this->res * 4;
        $this->maxY = $this->res * 5;

        $this->currentX = $xOffset;
        $this->currentY = $yOffset;

        $this->renderHeader($unit);

        $this->currentX -= 30;
        $this->renderKeywords('Models', $unit['roster'], false);
        $this->currentX += 30;

        $this->renderTable($unit['model_stat'], array(), $this->maxX);
        $this->renderLine();
        $this->renderTable($unit['weapon_stat'], array(), $this->maxX);

        $this->renderLine();
        $this->currentX -= 30;
        if(count($unit['abilities']) > 0) {
            $this->renderAbilities('Abilities', $unit['abilities']);
        }
        if(count($unit['rules']) > 0) {
            $this->renderKeywords('Rules', $unit['rules'], true);
        }

        # wound tracker:
        $this->currentX += 30;
        $this->renderLine();
        $this->currentX -= 30;
        $this->renderText($this->currentX + 80, $this->currentY + 20, 'WOUNDS:', 40, $this->getFontSize());
        $this->currentX += 110;
        $this->renderWoundBoxes($unit, true);
        $this->currentX -= 110;

        # keywords and keyword-adjacents:
        $this->currentY = $this->maxY + $yOffset - 50;
        $this->currentX += 30;
        $this->renderLine();
        $this->currentX -= 30;
        $this->renderKeywords('Factions', $unit['factions'], true);
        $this->renderKeywords('Keywords', $unit['keywords'], true);
        $this->renderWatermark();
    }

    protected function renderHeader($unit) {
        # FOC slot and PL and points:
        $draw = $this->getDraw();
        $draw->setFillColor('#000000');
        $draw->setStrokeWidth(0);
        $draw->setFontSize(20);

        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $this->currentX + 58, $this->currentY + 52);

        if($unit['slot'] != 'NA') {
            $gon = new Imagick();
            $gon->readImage('../assets/icon_'.$unit['slot'].'.png');
            $gon->resizeimage(35, 35, \Imagick::FILTER_LANCZOS, 1);
            $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $this->currentX + 63, $this->currentY + 57);
        }

        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $this->currentX + 105, $this->currentY + 52);
        $draw->setFont('../assets/title_font.otf');
        $draw->setFontSize(26);

        $draw->setFillColor('#FFFFFF');
        if(strlen($unit['power']) == 1) {
            $this->image->annotateImage($draw, 121 + $this->currentX, $this->currentY + 84, 0, $unit['power']);
        } else {
            $this->image->annotateImage($draw, 114 + $this->currentX, $this->currentY + 84, 0, $unit['power']);
        }

        # unit name:
        $draw->setFillColor('#000000');
        $iters = 0;
        $title_size = 28;
        $draw->setFontSize($title_size);
        $draw->setFont('../assets/title_font.otf');
        $check = $this->image->queryFontMetrics($draw, strtoupper($unit['title']));
        $maxNameWidth = 420;
        while($iters < 6 && $check['textWidth'] > $maxNameWidth) {
            $iters += 1;
            $title_size -= 2;
            $draw->setFontSize($title_size);
            $check = $this->image->queryFontMetrics($draw, strtoupper($unit['title']));
        }
        $title_x =  $this->currentX + 170;
        $this->image->annotateImage($draw, $title_x, $this->currentY + 90, 0, strtoupper($unit['title']));
        $this->currentY += 100;
        $this->renderLine();
    }

    public function renderToOutFile() {
        $files   = array();
        $summary = $this->renderList();
        $files['summary'] = $summary;

        for($i = 0; $i < count($this->units); $i++) {
            $height = $this->res * 11;
            $width  = $this->res * 8.5;
            $this->image->newImage($width, $height, new ImagickPixel('white'), 'pdf');
            $this->image->setResolution($this->res, $this->res);
            $this->image->setColorspace(Imagick::COLORSPACE_RGB);

            if(array_key_exists($i, $this->units)) { $this->renderUnit($this->units[$i], 0, 0); }
            $i += 1;
            if(array_key_exists($i, $this->units)) { $this->renderUnit($this->units[$i], ($width / 2), 0); }
            $i += 1;
            if(array_key_exists($i, $this->units)) { $this->renderUnit($this->units[$i], 0, ($height / 2)); }
            $i += 1;
            if(array_key_exists($i, $this->units)) { $this->renderUnit($this->units[$i],  ($width / 2), ($height / 2)); }

            $this->image->writeImages($this->outFile, true);
        }
        $files['list'] = $this->outFile;
        return $files;
    }
}
