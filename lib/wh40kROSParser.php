<?php

require_once('wh40kParser.php');

class wh40kROSParser extends wh40kParser {
    public function loadFile($file) {
        libxml_use_internal_errors(true);
        $this->doc = simplexml_load_file($file);
    }

    public function findUnitsToParse() {
        foreach($this->doc->forces->force as $force) {
            foreach($force->selections->selection as $d) {
                $unit = $this->createUnit($d);
                if($unit) {
                    $this->units[] = $unit;
                }
            }
        }
#print_r($this->units); exit();
        return $this->units;
    }

    public function populateUnit($d, $clean) {
        // title
        $clean['title']  = (string) $d['name'];

        // keywords, factions, slot
        if($d->categories->category) {
            foreach($d->categories->category as $c) {
                $clean = $this->binKeyword((string) $c['name'], $clean);
            }
        }

        // model_stat
        $cols = array('M', 'WS', 'BS', 'S', 'T', 'W', 'A', 'Ld', 'Save');
        $clean['model_stat'] = $this->readSelectionChars($d, $clean['model_stat'], 'Unit', $cols);
        foreach($d->selections->selection as $dd) {
            $clean['model_stat'] = $this->readSelectionChars($dd, $clean['model_stat'], 'Unit', $cols);
        }
        $clean['model_stat'] = $this->deDupe($clean['model_stat'], 'Unit');

        // powers
        $cols = array('Warp Charge', 'Range', 'Details');
        foreach($d->selections->selection as $dd) {
            $clean['powers'] = $this->readSelectionChars($dd, $clean['powers'], 'Psychic Power', $cols);
        }

        $woundCols = array();
        foreach($d->profiles->profile as $p) {
            if((string) $p['profileTypeName'] == 'Wound Track' && empty($woundCols)) {
                $headerRow = array();
                foreach($p->characteristics->characteristic as $c) {
                    $key   = (string) $c['name'];
                    $value = (string) $c['value'];
                    $woundCols[] = $key;
                    $headerRow[$key] = $value;
                }
                $clean['wound_track'] = $headerRow;
            }
        }
        $clean['wound_track'] = $this->readSelectionChars($d, $clean['weapon_stat'], 'Wound Track', $woundCols);

        // abilities
        // transport is an ability
        foreach($d->profiles->profile as $p) {
            if((string) $p['profileTypeName'] == 'Transport') {
                foreach($p->characteristics->characteristic as $c) {
                    if((string) $c['name'] == 'Capacity') {
                        $clean['abilities']['Transport'] = (string) $c['value'];
                    }
                }
            }
        }
        foreach($d->profiles->profile as $p) {
            if((string) $p['profileTypeName'] == 'Psyker') {
                $cast = 0;
                $deny = 0;
                foreach($p->characteristics->characteristic as $c) {
                    if((string) $c['name'] == 'Cast') {
                        $cast = (string) $c['value'];
                    }
                    if((string) $c['name'] == 'Deny') {
                        $deny = (string) $c['value'];
                    }
                }
                $cast .= $cast != 1 ? ' psychic powers' : ' psychic power';
                $deny .= $deny != 1 ? ' psychic powers' : ' psychic power';
                $clean['abilities']['Psyker'] = "This model can attempt to manifest $cast in each friendly Psychic phase, and attempt to deny $deny in each enemy Psychic phase.";
            }
        }
        $clean['abilities'] = $this->readSelectionAbilities($d, $clean['abilities']);
        foreach($d->selections->selection as $dd) {
            $clean['abilities'] = $this->readSelectionAbilities($dd, $clean['abilities']);
            if($dd->selections->selection) {
                foreach($dd->selections->selection as $ddd) {
                    $clean['abilities'] = $this->readSelectionAbilities($ddd, $clean['abilities']);
                }
            }
        }
        ksort($clean['abilities']);

        // weapon_stat
        $clean = $this->readWeaponStats($d, $clean);

        // rules
        foreach($d->rules->rule as $r) {
            $clean['rules'][] = (string) $r['name'];
        } 
        $clean['rules'] = array_unique($clean['rules']);
        sort($clean['rules']);

        // roster
        if((string) $d['type'] == 'model') {
            $clean['roster'][] = (string) $d['number'].' '.(string) $d['name'];
        }
        foreach($d->selections->selection as $dd) {
            if((string) $dd['type'] == 'model') {
                $clean['roster'][] = (string) $dd['number'].' '.(string) $dd['name'];
                if($dd->selections->selection) {
                    foreach($dd->selections->selection as $ddd) {
                        if((string) $ddd['type'] == 'model') {
                            $clean['roster'][] = (string) $ddd['number'].' '.(string) $ddd['name'];
                        }
                    }
                }
            } 
        }
        foreach($clean['model_stat'] as $model) {
            $notInRoster = true;
            foreach($clean['roster'] as $rank) { 
                if(strpos($rank, $model['Unit'])) {
                    $notInRoster = false;
                }
            }
            if($notInRoster == true) {
                $clean['roster'][] = '1 '.$model['Unit'];
            }
        }

        // points, power
        $clean = $this->readPointCosts($d, $clean);

        return $clean;
    }

    protected function readPointCosts($d, $clean) {
        $clean = $this->readSelectionCosts($d, $clean);
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean = $this->readSelectionCosts($dd, $clean);
                if($dd->selections->selection) {
                    foreach($dd->selections->selection as $ddd) {
                        $clean = $this->readSelectionCosts($ddd, $clean);
                    }
                }
            }
        }
        return $clean;
    }

    protected function readWeaponStats($d, $clean) {
        $cols = array('Range', 'Type', 'S', 'AP', 'D', 'Abilities');
        $clean['weapon_stat'] = $this->readSelectionChars($d, $clean['weapon_stat'], 'Weapon', $cols);
        foreach($d->selections->selection as $dd) {
            $clean['weapon_stat'] = $this->readSelectionChars($dd, $clean['weapon_stat'], 'Weapon', $cols);
            if($dd->selections->selection) {
                foreach($dd->selections->selection as $ddd) {
                    $clean['weapon_stat'] = $this->readSelectionChars($ddd, $clean['weapon_stat'], 'Weapon', $cols);
                }
            }
        }
        $clean['weapon_stat'] = $this->deDupe($clean['weapon_stat'], 'Weapon');

        return $clean;
    }
}
