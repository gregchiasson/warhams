<?php

require_once('wh40kParser.php');

class wh40kHTMLParser extends wh40kParser {
    public function loadFile($file) {
        libxml_use_internal_errors(true);
        $this->doc = new DOMDocument();
        $this->doc->loadHTMLFile($file);
    }

    public function findUnitsToParse() {
        $ds = $this->doc->getElementsByTagName('li');

        foreach($ds as $d) {
            if($d->getAttribute('class') == 'rootselection') {
                $unit = $this->createUnit($d);
                if($unit) {
                    $this->units[] = $unit;
                }
            }
        }
    }

    public function populateUnit($d, $clean) {
        foreach($d->childNodes as $c) {
            if($c->nodeName == 'h4') {
                # TITLE, POWER, POINTS:
                $m       = null;
                preg_match('/(.*)\s+\[(\d+) PL, (\d+)pts\]$/', $c->textContent, $m);
                if($m) {
                    $clean['title']  = $m[1];
                    $clean['power']  = $m[2];
                    $clean['points'] = $m[3];
                }
            } else if($c->nodeName == 'p' && $c->getAttribute('class') == 'category-names') {
                # KEYWORDS, FACTIONS, FOC SLOT:
                $keywords = explode(',', $c->textContent);
                $keywords[0] = trim($keywords[0]);
                $keywords[0] = str_replace('Categories: ', '', $keywords[0]);
                foreach($keywords as $k) {
                    $clean = $this->binKeyword($k, $clean);
                }
            } else if($c->nodeName == 'p' && $c->getAttribute('class') == 'rule-names') {
                # AYSKNF, etc:
                $keywords = explode(',', $c->textContent);
                $keywords[0] = trim($keywords[0]);
                $keywords[0] = str_replace('Rules: ', '', $keywords[0]);
                foreach($keywords as $k) {
                    $k = trim($k);
                    $clean['rules'][] = $k;
                }
                sort($clean['rules']);
            } else if($c->nodeName == 'table') {
                # ABILITIES, STATS, WEAPONS:
                $rows  = $c->childNodes;
                $hr    = $rows[0];
                $type  = $hr->childNodes[0]->textContent;
                $type  = preg_replace('/^(.+)\s\(.*$/', '$1', $type); # eg: Wound Track (Knights)
                switch($type) {
                    case 'Abilities':
                        foreach($rows as $r) {
                            $c = $r->childNodes;
                            if($c[0]->textContent != 'Abilities') {
                                $clean['abilities'][$c[0]->textContent] = $c[2]->textContent;
                            }
                        }
                        ksort($clean['abilities']);
                        break;
                    case 'Psyker':
                        $cast = $rows[1]->childNodes[2]->textContent;
                        $deny = $rows[1]->childNodes[3]->textContent;
                        $cast .= $cast != 1 ? ' psychic powers' : ' psychic power';
                        $deny .= $deny != 1 ? ' psychic powers' : ' psychic power';
                        $clean['abilities']['Psyker'] = "This model can attempt to manifest $cast in each friendly Psychic phase, and attempt to deny $deny in each enemy Psychic phase.";
                        break;
                    case 'Transport':
                        $clean['abilities']['Transport'] = $rows[1]->childNodes[2]->textContent;
                        break;
                    case 'Unit':
                        $stats = array('Unit' => 0, 'M'  => 0, 'WS'   => 0, 'BS'   => 0,
                                       'S'    => 0, 'T'  => 0, 'W'    => 0,
                                       'A'    => 0, 'Ld' => 0, 'Save' => 0);
                        $clean['model_stat'] = $this->statBlock($stats, $rows);
                        break;
                    case 'Weapon':
                        $stats = array('Weapon' => 0, 'Range'  => 0, 'Type' => 0,
                                       'S'      => 0, 'AP'     => 0, 'D'    => 0, 'Abilities' => 0);
                        $clean['weapon_stat'] = $this->statBlock($stats, $rows);
                        break;
                    case 'Psychic Power':
                        $stats = array('Psychic Power' => 0, 'Warp Charge' => 0,
                                        'Range'        => 0, 'Details'     => 0);
                        $clean['powers'] = $this->statBlock($stats, $rows);
                        break;
                    case 'Wound Track':
                        $stats = array();
                        foreach($rows[0]->childNodes as $header) {
                            $header = trim($header->textContent);
                            if(strlen($header) && $header != 'Ref') {
                                $stats[$header] = 0;
                            }
                        }
                        $clean['wound_track'] = $this->statBlock($stats, $rows);
                        break;
                }
            }
        }
        return $clean;
    }
}
