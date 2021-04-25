<?php

require_once('wh40kROSParser.php');

class apocParser extends wh40kROSParser {
    protected function createUnit($d) {
        $template = array(
            'slot'        => null,        # FOC slot
            'power'       => 0,           # PL, points to come later
            'title'       => 'unit name', # tactical squad
            'model_stat'  => array(),     # name M WS BS etc
            'weapon_stat' => array(),     # name range type etc
            'abilities'   => array(),     # Deep Strike, etc
            'rules'       => array(),     # ATSKNF, etc
            'factions'    => array(),     # IMPERIUM, etc
            'roster'      => array(),     # 7 marines, 1 heavy weapon, sarge, etc
            'keywords'    => array()      # INFANTRY, TANK, etc
        );

        $unit = $this->populateUnit($d, $template);

        if($unit['slot'] == null) {
            $unit['slot'] = 'NA';
        }

        if($unit['slot'] && $unit['power']) {
            ksort($unit);
            sort($unit['keywords']);
            sort($unit['factions']);
            return $unit;
        }
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
        $cols = array('M', 'WS', 'BS', 'A', 'W', 'Ld', 'Sv');
        $clean['model_stat'] = $this->readSelectionChars($d, $clean['model_stat'], 'Unit', $cols);
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean['model_stat'] = $this->readSelectionChars($dd, $clean['model_stat'], 'Unit', $cols);
            }
        }
        $clean['model_stat'] = $this->deDupe($clean['model_stat'], 'Unit');

        if($d->profiles->profile) {
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
                $clean['roster'][] = $model['Unit'];
            }
        }

        // points, power
        $clean = $this->readPointCosts($d, $clean);

        return $clean;
    }

    // TODO recurse
    protected function readWeaponStats($d, $clean) {
        $cols = array('Type', 'Range', 'A', 'SAP', 'SAT', 'Abilities');
        $clean['weapon_stat'] = $this->readSelectionChars($d, $clean['weapon_stat'], 'Weapons', $cols);
        if($d->selections->selection) {
            foreach($d->selections->selection as $dd) {
                $clean['weapon_stat'] = $this->readSelectionChars($dd, $clean['weapon_stat'], 'Weapons', $cols);
                if($dd->selections->selection) {
                    foreach($dd->selections->selection as $ddd) {
                        $clean['weapon_stat'] = $this->readSelectionChars($ddd, $clean['weapon_stat'], 'Weapons', $cols);

                        if($ddd->selections->selection) {
                            foreach($ddd->selections->selection as $dddd) {
                                $clean['weapon_stat'] = $this->readSelectionChars($dddd, $clean['weapon_stat'], 'Weapons', $cols);
                            }
                        }
                    }
                }
            }
        }
        $clean['weapon_stat'] = $this->deDupe($clean['weapon_stat'], 'Weapons');

        return $clean;
    }
}
