<?php

abstract class Renderer {
    protected $outFile = null;
    protected $image   = null;
    protected $units   = array();
    protected $bigBoys = false;

    protected $margin = 50;
    protected $res    = 144;

    protected $currentX = 0;
    protected $currentY = 0;

    protected $maxX = 0;
    protected $maxY = 0;

    public function __construct($outFile, $units=array(), $bigBoys=false) {
        $this->image   = new Imagick();
        $this->units   = $units;
        $this->bigBoys = $bigBoys ? true : false;
        $this->outFile = $outFile;
    }

    public function getFontSize() {
        return $this->bigBoys ? 19 : 16;
    }

    public function getDraw() {
        $draw = new ImagickDraw();
        $draw->setFont('../assets/body_font.otf');
        $draw->setStrokeColor('#000000');
        $draw->setFillColor('#000000');
        $draw->setStrokeOpacity(0);
        $draw->setFillOpacity(1);
        $draw->setStrokeWidth(0);
        $draw->setFontSize(12);
        return $draw;
    }

    public function getDrawFont() {
        $hdraw = new ImagickDraw();
        $hdraw->setFontSize(14);
        $hdraw->setFontWeight(600);
        $hdraw->setFillColor('#000000');
        $hdraw->setFillOpacity(1);
        $hdraw->setFont('../assets/font.ttf');
        return $hdraw;
    }

    protected function renderText($x, $y, $text, $limit=50, $fontSize=12, $font=null) {
        $draw = $this->getDraw();
        if($font) {
            $draw->setFont($font);
        }
        $draw->setFontSize($fontSize);
        $text   = wordwrap($text, $limit, "\n", false);
        $lines  = substr_count($text, "\n") + 1;
        $height = ($lines * ($draw->getFontSize() + 4));

        $this->image->annotateImage($draw, $x, $y, 0, $text);
        $this->image->drawImage($draw);
        return $height;
    }

    protected function renderLine() {
        $draw = $this->getDraw();
        $draw->setStrokeWidth(2);
        $draw->setStrokeColor('#000000');
        $draw->line($this->margin + $this->currentX, $this->currentY, 
                    ($this->maxX + $this->currentX - $this->margin), $this->currentY);
        $this->image->drawImage($draw);
        $this->currentY += 2;
    }

    public abstract function renderToOutFile();

    protected abstract function renderUnit($unit, $xOffset, $yOffset);
}
