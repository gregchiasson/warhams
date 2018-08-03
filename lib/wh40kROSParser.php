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
        return $this->units;
    }

/* TODO:
[powers]

[wound_track]
*/
    public function populateUnit($d, $clean) {
        // title
        $clean['title']  = (string) $d['name'];

        // keywords, factions, slot
        foreach($d->categories->category as $c) {
            $clean = $this->binKeyword((string) $c['name'], $clean);
        }

        // model_stat
        $cols = array('M', 'WS', 'BS', 'S', 'T', 'W', 'A', 'Ld', 'Save');
        $clean['model_stat'] = $this->readSelectionChars($d, $clean['model_stat'], 'Unit', $cols);
        foreach($d->selections->selection as $dd) {
            $clean['model_stat'] = $this->readSelectionChars($dd, $clean['model_stat'], 'Unit', $cols);
        }
        $clean['model_stat'] = $this->deDupe($clean['model_stat'], 'Unit');

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
        // TODO: "Cast X, Deny Y" is an ability
        // $clean['abilities']['Psyker'] = "This model can attempt to manifest $cast in each friendly Psychic phase, and attempt to deny $deny in each enemy Psychic phase.";


        $clean['abilities'] = $this->readSelectionAbilities($d, $clean['abilities']);
        foreach($d->selections->selection as $dd) {
            $clean['abilities'] = $this->readSelectionAbilities($dd, $clean['abilities']);
            foreach($dd->selections->selection as $ddd) {
                $clean['abilities'] = $this->readSelectionAbilities($ddd, $clean['abilities']);
            }
        }
        ksort($clean['abilities']);

        foreach($d->rules->rule as $r) {
            $clean['rules'][] = (string) $r['name'];
        } 
        sort($clean['rules']);

        // weapon_stat
        $cols = array('Range', 'Type', 'S', 'AP', 'D', 'Abilities');
        $clean['weapon_stat'] = $this->readSelectionChars($d, $clean['weapon_stat'], 'Weapon', $cols);
        foreach($d->selections->selection as $dd) {
            $clean['weapon_stat'] = $this->readSelectionChars($dd, $clean['weapon_stat'], 'Weapon', $cols);
            foreach($dd->selections->selection as $ddd) {
                $clean['weapon_stat'] = $this->readSelectionChars($ddd, $clean['weapon_stat'], 'Weapon', $cols);
            }
        }
        $clean['weapon_stat'] = $this->deDupe($clean['weapon_stat'], 'Weapon');

        // rules
        foreach($d->rules->rule as $r) {
            $clean['rules'][] = (string) $r['name'];
        } 

        // roster
        if((string) $d['type'] == 'model') {
            $clean['roster'][] = (string) $d['number'].' '.(string) $d['name'];
        }
        foreach($d->selections->selection as $dd) {
            if((string) $dd['type'] == 'model') {
                $clean['roster'][] = (string) $dd['number'].' '.(string) $dd['name'];
                foreach($dd->selections->selection as $ddd) {
                    if((string) $ddd['type'] == 'model') {
                        $clean['roster'][] = (string) $ddd['number'].' '.(string) $ddd['name'];
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
        $clean = $this->readSelectionCosts($d, $clean);
        foreach($d->selections->selection as $dd) {
            $clean = $this->readSelectionCosts($dd, $clean);
            foreach($dd->selections->selection as $ddd) {
                $clean = $this->readSelectionCosts($ddd, $clean);
            }
        }

        return $clean;
    }

    private function readSelectionCosts($d, $clean) {
        foreach($d->costs->cost as $cost) {
            if((string) $cost['name'] == 'pts') {
                $clean['points'] += (integer) $cost['value'];
            } else if((string) $cost['name'] == ' PL') {
                $clean['power'] += (integer) $cost['value'];
            }
        }
        return $clean;
    }

    private function readSelectionAbilities($d, $stats) {
        foreach($d->profiles->profile as $p) {
            if((string) $p['profileTypeName'] == 'Abilities') {
                foreach($p->characteristics->characteristic as $c) {
                    if((string) $c['name'] == 'Description') {
                        $stats[(string) $p['name']] = (string) $c['value'];
                    }
                }
            }
        }
        return $stats;
    }

    private function readSelectionChars($d, $stats, $type, $check) {
        foreach($d->profiles->profile as $p) {
            if((string) $p['profileTypeName'] == $type) {
                $model = array();
                $model[$type] = (string) $p['name'];
                foreach($p->characteristics->characteristic as $c) {
                    $key   = (string) $c['name'];
                    $value = (string) $c['value'];
                    if(in_array($key, $check)) {
                        $model[$key] = $value;
                    }
                }
                $stats[] = $model;
            }
        }
        return $stats;
    }
}
