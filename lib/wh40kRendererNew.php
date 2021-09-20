<?php

require_once('newRenderer.php');

class wh40kRendererNew extends newRenderer {
    public function __construct($outFile, $units=array(), $bigBoys=false, $crusade=false, $skipDuplicates=false) {
        $this->image = new Imagick();
        $this->units = $units;

        if($bigBoys || $crusade) {
            $this->layout = newRenderer::ONE_UP;
        } else {
            $this->layout = newRenderer::TWO_UP;
        }
        $this->crusade          = $crusade;
        $this->bigBoys          = $bigBoys;
        $this->skipDuplicates   = $skipDuplicates ? true : false;
        $this->outFile          = $outFile;

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

        // model/weapon stat block:
        if(count($unit['model_stat'])) {
            $this->renderTable($unit['model_stat'], array(), $this->maxX);
            $this->renderLine();
        }

        if(count($unit['weapon_stat'])) {
            $this->renderTable($unit['weapon_stat'], array(), $this->maxX);
        }

        // spells, if any:
        if(count($unit['powers']) > 0) {
            // jam smite in there. not sure this is even required anymore
            $needs_smite = true;
            foreach($unit['powers'] as $power) {
                if(strpos(strtolower($power['Psychic Power']), 'smite') > -1) {
                    $needs_smite = false;
                }
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

        // rules and abilities:
        if(count($unit['abilities']) > 0) {
            $this->renderAbilities('Abilities', $unit['abilities']);
            $this->currentY -= 10;
        }
        if(count($unit['rules']) > 0) {
            $this->renderKeywords('Rules', $unit['rules'], true);
        }

        //  keywords 
        $this->renderLine();
        $this->renderKeywords('Factions', $unit['factions'], true);
        $this->renderKeywords('Keywords', $unit['keywords'], true);
        $this->renderKeywords('Models',   $unit['roster'], false);

        // damage track/explodes table
        if(count($unit['wound_track']) > 0) {
            $this->renderLine();
            $this->renderTable($unit['wound_track']);
        }

        if(count($unit['explode_table']) > 0) {
            $this->renderLine();
            $this->renderTable($unit['explode_table']);
        }

        # wound tracker:
        $hasTracks = false;
        foreach($unit['model_stat'] as $type) {
            if($type['W'] > 1) { $hasTracks = true; }
        }
        if($hasTracks) {
            $this->renderLine();
            $this->renderText($this->currentX + $this->margin + 5, $this->currentY + 20, 'WOUNDS:', 400, $this->getFontSize());
            $this->currentX += 60;
            $this->renderWoundBoxes($unit, true);
            $this->currentX -= 60;
        }

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

        if($unit['slot'] != 'NA') {
            $this->logoWrapper($x + 3, $y + 3);
            $gon = new Imagick();
            $gon->readImage('../assets/icon_'.$unit['slot'].'.png');
            $gon->resizeimage(35, 35, \Imagick::FILTER_LANCZOS, 1);
            $this->image->compositeImage($gon, Imagick::COMPOSITE_DEFAULT, $x + 8, $y + 7);
        }

        $draw->setFont('../assets/title_font.otf');

        # points and power:
        $draw->setFontSize(30);
        $this->logoWrapper($x + 50, $y + 3);
        $check  = $this->image->queryFontMetrics($draw, strtoupper($unit['power']));
        $offset = $x + 50 + 22 - ($check['textWidth'] / 2);
        $draw->setFillColor('#FFFFFF');
        $this->image->annotateImage($draw, $offset, $y + 36, 0, $unit['power']);

        $draw->setFontSize(24);
        $this->logoWrapper($x + 97, $y + 3);
        $check  = $this->image->queryFontMetrics($draw, strtoupper($unit['points']));
        $offset = $x + 97 + 22 - ($check['textWidth'] / 2);
        $draw->setFillColor('#FFFFFF');
        $this->image->annotateImage($draw, $offset, $y + 34, 0, $unit['points']);

        # unit name:
        $draw->setFillColor('#000000');
        $iters = 0;
        $title = $unit['customName'] ? $unit['customName'] : $unit['title'];
        $title_size = 32;
        $draw->setFontSize($title_size);
        $draw->setFont('../assets/title_font.otf');
        $check = $this->image->queryFontMetrics($draw, strtoupper($title));
        $maxNameWidth = 420;
        while($iters < 6 && $check['textWidth'] > $maxNameWidth) {
            $iters      += 1;
            $title_size -= 2;
            $draw->setFontSize($title_size);
            $check = $this->image->queryFontMetrics($draw, strtoupper($unit['title']));
        }
        $title_x =  $x + 160;
        $this->image->annotateImage($draw, $title_x, $y + 40, 0, strtoupper($title));
        $this->currentY += 50;
        $this->renderLine();
    }
}
