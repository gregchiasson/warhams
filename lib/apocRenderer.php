<?php

require_once('newRenderer.php');

class apocRenderer extends newRenderer {
    public function __construct($outFile, $units=array(), $bigBoys=false, $tracking=false, $reference=true, $skipDuplicates=false) {
        $this->isApoc = true;
        parent::__construct($outFile, $units);
        $this->layout = newRenderer::FOUR_UP;
        $this->setHeightAndWidth();
        $this->margin = 20;
    }

    protected function renderUnit($unit, $xOffset, $yOffset) {
        $this->currentX = $xOffset;
        $this->currentY = $yOffset;
        $this->xOffset = $xOffset;
        $this->yOffset = $yOffset;

        $this->renderBorder();
        $this->currentY += $this->margin;
        $this->renderHeader($unit);

        $this->renderKeywords('Models', $unit['roster'], false);
        $this->renderTable($unit['model_stat'], array(), $this->maxX);
        $this->renderLine();
        $this->renderTable($unit['weapon_stat'], array(), $this->maxX);

        $this->renderLine();
        if(count($unit['abilities']) > 0) {
            $this->renderAbilities('Abilities', $unit['abilities']);
            $this->currentY -= 20;
        }
        if(count($unit['rules']) > 0) {
            $this->renderKeywords('Rules', $unit['rules'], true);
        }

        # wound tracker:
        $this->renderLine();
        $this->renderText($this->currentX + $this->margin + 5, $this->currentY + 20, 'WOUNDS:', 400, $this->getFontSize());
        $this->currentX += 60;
        $this->renderWoundBoxes($unit, true);
        $this->currentX -= 60;

        # keywords and keyword-adjacents:
        $this->renderLine();
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

        $x = $this->currentX + $this->margin;
        $y = $this->currentY;

        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $x + 3, $y + 3);

        if($unit['slot'] != 'NA') {
            $gon = new Imagick();
            $gon->readImage('../assets/icon_'.$unit['slot'].'.png');
            $gon->resizeimage(35, 35, \Imagick::FILTER_LANCZOS, 1);
            $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $x + 9, $y + 7);
        }

        $gon = new Imagick();
        $gon->readImage('../assets/octagon.png');
        $gon->resizeimage(45, 45, \Imagick::FILTER_LANCZOS, 1);
        $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $x + 55, $y + 3);
        $draw->setFont('../assets/title_font.otf');
        $draw->setFontSize(26);

        $draw->setFillColor('#FFFFFF');
        if(strlen($unit['power']) == 1) {
            $this->image->annotateImage($draw, 71 + $x, $y + 33, 0, $unit['power']);
        } else if(strlen($unit['power']) == 3) {
            $this->image->annotateImage($draw, 56 + $x, $y + 33, 0, $unit['power']);
        } else{
            $this->image->annotateImage($draw, 64 + $x, $y + 33, 0, $unit['power']);
        }

        # unit name:
        $draw->setFillColor('#000000');
        $iters = 0;
        $title = $unit['customName'] ? $unit['customName'] : $unit['title'];
        $title_size = 28;
        $draw->setFontSize($title_size);
        $draw->setFont('../assets/title_font.otf');
        $check = $this->image->queryFontMetrics($draw, strtoupper($title));
        $maxNameWidth = 420;
        while($iters < 6 && $check['textWidth'] > $maxNameWidth) {
            $iters += 1;
            $title_size -= 2;
            $draw->setFontSize($title_size);
            $check = $this->image->queryFontMetrics($draw, strtoupper($unit['title']));
        }
        $title_x =  $x + 110;
        $this->image->annotateImage($draw, $title_x, $y + 40, 0, strtoupper($title));
        $this->currentY += 50;
        $this->renderLine();
    }
}
