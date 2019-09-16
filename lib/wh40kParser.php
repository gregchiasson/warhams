<?php

class wh40kParser {
    public $units = array();
    public $doc   = null;

    public $SLOTS = array(
        'HQ'                  => 'HQ',
        'Troops'              => 'TR',
        'Elites'              => 'EL',
        'Fast Attack'         => 'FA',
        'Heavy Support'       => 'HS',
        'Flyer'               => 'FL',
        'Dedicated Transport' => 'DT',
        'Fortification'       => 'FT',
        'Lord of War'         => 'LW'
    );
 
    public function __construct($file) {
        $this->loadFile($file);
        $this->findUnitsToParse();
    }

    protected function createUnit($d) {
        $template = array(
            'slot'        => null,        # FOC slot
            'power'       => 0,           # PL, points to come later
            'points'      => 0,           # its later now
            'title'       => 'unit name', # tactical squad
            'model_stat'  => array(),     # name M WS BS etc
            'weapon_stat' => array(),     # name range type etc
            'wound_track' => array(),     # remaining stat1 stat2 etc
            'explode_table' => array(),   # some vehicles apparently use this?
            'powers'      => array(),     # MUSCLE WIZARDS ONLY
            'abilities'   => array(),     # Deep Strike, etc
            'rules'       => array(),     # ATSKNF, etc
            'factions'    => array(),     # IMPERIUM, etc
            'roster'      => array(),     # 7 marines, 1 heavy weapon, sarge, etc
            'keywords'    => array()      # INFANTRY, TANK, etc
        );

        $unit = $this->populateUnit($d, $template);

        if($unit['slot'] && ($unit['points'] || $unit['power'])) { 
            ksort($unit);
            sort($unit['keywords']);
            sort($unit['factions']);
            return $unit;
        } else {
            return null;
        }
    }

    protected function findUnitsToParse() {
        return $this->units;
    }

    protected function populateUnit($d, $clean) {
        return $clean;
    }

    protected function binKeyword($keyword, $clean) {
        $k = trim($keyword);
        if(strpos($k, 'Faction: ') === 0) {
            $clean['factions'][] = str_replace('Faction: ', '', $k);
        } else {
            if(array_key_exists($k, $this->SLOTS)) {
                $clean['slot'] = $this->SLOTS[$k];
            } else {
                $clean['keywords'][] = $k;
            }
        }
        return $clean;
    }

    protected function statBlock($stats, $rows) {
        reset($stats);
        $skip  = key($stats);
        $block = array();

        foreach($rows as $r) {
            $c = $r->childNodes;
            if($c[0]->textContent == $skip) {
                $index = 0;
                foreach($r->childNodes as $cell) {
                    $stat = trim($cell->textContent);
                    if(array_key_exists($stat, $stats)) {
                        $stats[$stat] = $index;
                    }
                    $index += 1;
                }
            } else {
                $index = 0;
                $stat_block = array();
                foreach($r->childNodes as $cell) {
                    $val = trim($cell->textContent);
                    if(in_array($index, $stats)) {
                        $stat_block[array_search($index, $stats)] = $val;
                    }
                    $index += 1;
                }
                $block[] = $stat_block;
            }
        }
        return $block;
    }

    protected function deDupe($items, $col) {
        $newItems = array();
        $seen = array();
        foreach($items as $item) {
            if(!in_array($item[$col], $seen)) {
                $newItems[] = $item;
                $seen[]     = $item[$col];
            }
        }

        return $newItems;
    }

    protected function readSelectionCosts($d, $clean) {
        if($d->costs->cost) {
            foreach($d->costs->cost as $cost) {
                if((string) $cost['name'] == 'pts') {
                    $clean['points'] += (integer) $cost['value'];
                } else if((string) $cost['name'] == ' PL') {
                    $clean['power'] += (integer) $cost['value'];
                }
            }
        }
        return $clean;
    }

    # match multiple types on a startsWith basis
    protected function checkProfileTypes($p, $types=array()) {
        $matches = false;
        foreach($types as $type) {
            $len = strlen($type);

            $toMatch = (string) $p['profileTypeName'];
            if(!$toMatch) { 
                $toMatch = (string) $p['typeName'];
            }

            strtoupper($type);
            strtoupper($toMatch);

            if(substr($toMatch, 0, $len) == $type) {
                $matches = true;
            }
        }
        return $matches;
    }

    protected function checkProfileType($p, $type) {
        if((string) $p['profileTypeName'] == $type || (string) $p['typeName'] == $type) {
            return true;
        } else {
            return false; 
        }
    }

    protected function readSelectionAbilities($d, $stats, $key='Abilities') {
        if($d->profiles->profile) {
            foreach($d->profiles->profile as $p) {
                if($this->checkProfileType($p, $key)) {
                    if($p->characteristics->characteristic) {
                        foreach($p->characteristics->characteristic as $c) {
                            if((string) $c['name'] == 'Description') {
                                $kkey  = (string) $p['name'];
                                $value = (string) $c['value'];
                                if(!$value) {
                                    $value = (string) $c;
                                }
                                $stats[$kkey] = $value;
                            }
                        }
                    }
                }
            }
        }
        return $stats;
    }

    protected function readSelectionChars($d, $stats, $type, $check) {
        if($d->profiles->profile) {
            foreach($d->profiles->profile as $p) {
                if($this->checkProfileType($p, $type)) {
                    $model = array();
                    $model[$type] = (string) $p['name'];
                    foreach($p->characteristics->characteristic as $c) {
                        $key   = (string) $c['name'];
                        $value = (string) $c['value'];
                        if(!$value) {
                            $value = (string) $c;
                        }
                        if(in_array($key, $check)) {
                            $model[$key] = $value;
                        }
                    }
                    $stats[] = $model;
                }
            }
        }
        return $stats;
    }
}
