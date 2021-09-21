<?php

require_once('wh40kParser.php');

class wh40kROSParser extends wh40kParser {
    public function loadFile($file) {
        libxml_use_internal_errors(true);
        $this->doc = simplexml_load_file($file);
    }

    public function findUnitsToParse() {
        $forces = array();
        if(!$this->doc->forces->force) {
            return $this->units;
        }
        foreach($this->doc->forces->force as $force) {
            $forces[] = $this->parseForce($force);
            if($force->forces->force) {
                foreach($force->forces->force as $ff) {
                    $forces[] = $this->parseForce($ff);
                }
            }
        }
        array_unshift($this->units, $forces);
        return $this->units;
    }


    protected function parseForce($force) {
        $units = array();
        foreach($force->selections->selection as $d) {
            $unit = $this->createUnit($d);
            if($unit) {
                $this->units[] = $unit;

                if(array_key_exists('slot', $unit) && $unit['slot'] !== null) {
                    $slot = $unit['slot'];
                    if(!array_key_exists($slot, $units)) {
                        $units[$slot] = array();
                    }

                    $customName = null;
                    if($d['customName']) {
                        $customName = (string) $d['customName'];
                    }

                    $units[$slot][] = array(
                        'name'   => $unit['title'],
                        'customName' => $customName,
                        'slot'   => $unit['slot'],
                        'roster' => implode($unit['roster'], ', '),
                        'points' => array_key_exists('points', $unit) ? $unit['points'] : 0,
                        'power'  => $unit['power']
                    );
                }
            }
        }

        return array(
            'faction'    => (string) $force['catalogueName'],
            'detachment' => (string) $force['name'],
            'units'      => $units,
            'cp'         => $this->cp
        );
    }

    public function populateUnit($d, $clean) {
        // title
        $clean['title']  = (string) $d['name'];
        if($d['customName']) {
            $clean['customName'] = (string) $d['customName'];
        }
        if($d->customNotes) {
            $clean['notes'] = (string) $d->customNotes;
        }

        // keywords, factions, slot
        if($d->categories->category) {
            foreach($d->categories->category as $c) {
                $clean = $this->binKeyword((string) $c['name'], $clean);
            }
        }

        // model_stat
        $cols = array('M', 'WS', 'BS', 'S', 'T', 'W', 'A', 'Ld', 'Save');
        $clean['model_stat'] = $this->readSelectionChars($d, $clean['model_stat'], 'Unit', $cols);
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean['model_stat'] = $this->readSelectionChars($dd, $clean['model_stat'], 'Unit', $cols);
        }
        }
        $clean['model_stat'] = $this->deDupe($clean['model_stat'], 'Unit');

        // powers
        $cols = array('Warp Charge', 'Range', 'Details');
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean['powers'] = $this->readSelectionChars($dd, $clean['powers'], 'Psychic Power', $cols);
            }
        }

        # TODO: this needs a re-factor
        $explodeCols = array(); # thanks, whoever maintains the AdMech repo, for doing this weird.
        $woundCols   = array();
        $explodeName = null;
        $trackNames  = array('Wound Track', 'Stat Damage');
        $trackName   = null;
        if($d->profiles->profile) {
            foreach($d->profiles->profile as $p) {
                if($this->checkProfileTypes($p, $trackNames) && empty($woundCols)) {
                    $trackName = (string) $p['profileTypeName'] ? (string) $p['profileTypeName'] : (string) $p['typeName'];
                    $headerRow = array();
                    foreach($p->characteristics->characteristic as $c) {
                        $key   = (string) $c['name'];
                        $value = (string) $c['value'];
                        $woundCols[]     = $key;
                        $headerRow[$key] = $value;
                    }
                    $clean['wound_track'] = $headerRow;
                } 
                if($this->checkProfileTypes($p, array('Explosion', 'Explode')) && empty($explodeCols)) {
                    $explodeName = (string) $p['profileTypeName'] ? (string) $p['profileTypeName'] : (string) $p['typeName'];
                    $headerRow = array();
                    foreach($p->characteristics->characteristic as $c) {
                        $key   = (string) $c['name'];
                        $value = (string) $c['value'];

                        $explodeCols[]   = $key;
                        $headerRow[$key] = $value;
                    }
                    $clean['explode_table'] = $headerRow;
                }
                if($this->checkProfileTypes($p, array('Psychic Power'))) {
                    $guff = $p->characteristics->characteristic;
                    $clean['powers'][] = array(
                        'Psychic Power' => (string) $p['name'],
                        'Warp Charge'   => (string) $guff[0],
                        'Range'         => (string) $guff[1],
                        'Details'       => (string) $guff[2]
                    );
                }
            }
            if(!empty($woundCols)) {
                $clean['wound_track'] = $this->readSelectionChars($d, $clean['weapon_stat'], $trackName, $woundCols);
            }
            if(!empty($explodeCols)) {
                $clean['explode_table'] = $this->readSelectionChars($d, $clean['weapon_stat'], $explodeName, $explodeCols);
            }

            // abilities
            // transport is an ability
            foreach($d->profiles->profile as $p) {
                if($this->checkProfileType($p, 'Transport')) {
                    foreach($p->characteristics->characteristic as $c) {
                        if((string) $c['name'] == 'Capacity') {
                            $words = (string) $c['value'];
                            if($words) {
                                $clean['abilities']['Transport'] = $words;
                            } else {
                                $clean['abilities']['Transport'] = (string) $c;
                            }
                        }
                    }
                }
            }
            foreach($d->profiles->profile as $p) {
                if($this->checkProfileType($p, 'Psyker')) {
                    $cast = 0;
                    $deny = 0;
                    foreach($p->characteristics->characteristic as $c) {
                        if((string) $c['name'] == 'Cast') {
                            $cast = (string) $c['value'];
                            if(!$cast) {
                                $cast = (string) $c;
                            }
                        }
                        if((string) $c['name'] == 'Deny') {
                            $deny = (string) $c['value'];
                            if(!$deny) {
                                $deny = (string) $c;
                            }
                        }
                    }
                    $cast .= $cast != 1 ? ' psychic powers' : ' psychic power';
                    $deny .= $deny != 1 ? ' psychic powers' : ' psychic power';
                    $clean['abilities']['Psyker'] = "This model can attempt to manifest $cast in each friendly Psychic phase, and attempt to deny $deny in each enemy Psychic phase.";
                }
            }
        }

        // TODO: recurse
        $clean['abilities'] = $this->readSelectionAbilities($d, $clean['abilities']);
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean['abilities'] = $this->readSelectionAbilities($dd, $clean['abilities']);
                if($dd->selections->selection) {
                    foreach($dd->selections->selection as $ddd) {
                        $clean['abilities'] = $this->readSelectionAbilities($ddd, $clean['abilities']);
                        if($ddd->selections->selection) {
                            foreach($ddd->selections->selection as $dddd) {
                                $clean['abilities'] = $this->readSelectionAbilities($dddd, $clean['abilities']);
                            }
                        }
                    }
                }
            }
        }
        ksort($clean['abilities']);

        // weapon_stat
        $clean = $this->readWeaponStats($d, $clean);

        // rules
        if($d->rules->rule) {
            foreach($d->rules->rule as $r) {
                $clean['rules'][] = (string) $r['name'];
            } 
        } 

        if($d->selections->selection) {
            foreach($d->selections->selection as $s) {
                if($s->rules->rule) {
                    foreach($s->rules->rule as $r) {
                        $clean['rules'][] = (string) $r['name'];
                    } 
                }
            }
        }

        $clean['rules'] = array_unique($clean['rules']);
        sort($clean['rules']);

        // roster
        if((string) $d['type'] == 'model') {
            $clean = $this->addToRoster($clean, $d);
        }
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                if((string) $dd['type'] == 'model') {
                    $clean = $this->addToRoster($clean, $dd);
                    if($dd->selections->selection) {
                        foreach($dd->selections->selection as $ddd) {
                            if((string) $ddd['type'] == 'model') {
                                $clean = $this->addToRoster($clean, $ddd);
                            }
                        }
                    }
                }
                if((string) $dd['type'] == 'upgrade') {
                    if($dd->profiles->profile) {
                        foreach($dd->profiles->profile as $p) {
                            if((string) $p['typeName'] == 'Unit' && (string) $p['name'] == (string) $dd['name']) {
                                $clean = $this->addToRoster($clean, $dd);    
                            }
                        }
                    }
                }
            }
        }

        $newRoster = array();
        foreach($clean['roster'] as $name => $num) {
            $newRoster[] = $num.' '.$name;
        }
        $clean['roster'] = $newRoster;

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

    protected function addToRoster($clean, $d) {
        $name  = (string) $d['name'];
        $quant =  (string) $d['number'];

        if(array_key_exists($name, $clean['roster'])) {
            $clean['roster'][$name] += $quant;
        } else {
            $clean['roster'][$name] = $quant;
        }

        return $clean;
    }

    // TODO recurse
    protected function readPointCosts($d, $clean) {
        $clean = $this->readSelectionCosts($d, $clean);
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean = $this->readSelectionCosts($dd, $clean);
                if($dd->selections->selection) {
                    foreach($dd->selections->selection as $ddd) {
                        $clean = $this->readSelectionCosts($ddd, $clean);
                        if($ddd->selections->selection) {
                            foreach($ddd->selections->selection as $dddd) {
                                $clean = $this->readSelectionCosts($dddd, $clean);
                            }
                        }
                    }
                }
            }
        }
        return $clean;
    }

    // TODO recurse
    protected function readWeaponStats($d, $clean) {
        $cols = array('Range', 'Type', 'S', 'AP', 'D', 'Abilities');
        $clean['weapon_stat'] = $this->readSelectionChars($d, $clean['weapon_stat'], 'Weapon', $cols);
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean['weapon_stat'] = $this->readSelectionChars($dd, $clean['weapon_stat'], 'Weapon', $cols);
                if($dd->selections->selection) {
                    foreach($dd->selections->selection as $ddd) {
                        $clean['weapon_stat'] = $this->readSelectionChars($ddd, $clean['weapon_stat'], 'Weapon', $cols);

                        if($ddd->selections->selection) {
                            foreach($ddd->selections->selection as $dddd) {
                                $clean['weapon_stat'] = $this->readSelectionChars($dddd, $clean['weapon_stat'], 'Weapon', $cols);
                            }
                        }
                    }
                }
            }
        }
        $clean['weapon_stat'] = $this->deDupe($clean['weapon_stat'], 'Weapon');

        return $clean;
    }
}
