<?php

require_once('wh40kRenderer.php');

class ktRenderer extends wh40kRenderer {
    protected function renderUnit ($unit, $xOffset, $yOffset) {
        $this->maxX = 144 * 4.75;
        $this->maxY = 144 * 2.75;
        $this->currentX = $xOffset;
        $this->currentY = $yOffset;

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

    public function renderToOutFile() {
        // print roster sheet first:
        $this->image->newImage($this->res * 8.5, $this->res * 11, new ImagickPixel('white'), 'pdf');
        $this->image->setResolution($this->res, $this->res);
        $this->image->setColorspace(Imagick::COLORSPACE_RGB);

        $units = array();
        foreach($this->units as $unit) {
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
        $this->renderTable($units);
        $this->image->writeImages($this->outFile, true);

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
