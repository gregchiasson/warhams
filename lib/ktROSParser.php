<?php

require_once('wh40kROSParser.php');

#           +points 
# +name      +model_stat
# +weapon_stat
# +abilities
# +keywords

class ktROSParser extends wh40kROSParser {
    public function loadFile($file) {
        libxml_use_internal_errors(true);
        $this->doc = simplexml_load_file($file);
    }

    protected function createUnit($d) {
        $template = array(
            'points'      => 0,           # its later now
            'title'       => 'unit name', # tactical squad
            'model_stat'  => array(),     # name M WS BS etc
            'weapon_stat' => array(),     # name range type etc
            'powers'      => array(),     # MUSCLE WIZARDS ONLY
            'abilities'   => array(),     # Deep Strike, etc
            'rules'       => array(),     # ATSKNF, etc
            'factions'    => array(),     # IMPERIUM, etc
            'keywords'    => array()      # INFANTRY, TANK, etc
        );

        $unit = $this->populateUnit($d, $template);

        if($unit['points']) {
            ksort($unit);
            sort($unit['keywords']);
            sort($unit['factions']);
            return $unit;
        } else {
            return null;
        }
    }

    public function findUnitsToParse() {
        foreach($this->doc->forces->force as $force) {
            $units = array();
            foreach($force->selections->selection as $d) {
                $unit = $this->createUnit($d);
                if($unit) {
                    $this->units[] = $unit;
                }
            }
        }
        return $this->units;
    }

    public function populateUnit($d, $clean) {
        // is this a specialist? 
        $isSpecial = false; # brutal
        foreach($d->categories->category as $c) {
            if((string) $c['name'] == 'Specialist' || (string) $c['name'] == 'Leader') {
                $isSpecial = true;
            }
        }

        if($isSpecial) {
            foreach($d->selections->selection as $dd) {
                if($dd->selections->selection) {
                    foreach($dd->selections->selection as $ddd) {
                        if(strpos((string) $ddd['name'], 'Level ') === 0) {
                            $clean['keywords'][] = (string) $dd['name'];
                        }
                    }
                }
            }
        }


        // title
        $clean['custom_name']  = null;
        $clean['title']  = (string) $d['name'];
        if(isset($d['customName'])) {
            $clean['custom_name']  = (string) $d['customName'];
        }

        // model_stat
        $cols = array('M', 'WS', 'BS', 'S', 'T', 'W', 'A', 'Ld', 'Sv');
        $clean['model_stat'] = $this->readSelectionChars($d, $clean['model_stat'], 'Model', $cols);

        // abilities
        $clean['abilities'] = $this->readSelectionAbilities($d, $clean['abilities'], 'Ability');
        foreach($d->selections->selection as $dd) {
            $clean['abilities'] = $this->readSelectionAbilities($dd, $clean['abilities'], 'Ability');
            if($dd->selections->selection) {
                foreach($dd->selections->selection as $ddd) {
                    $clean['abilities'] = $this->readSelectionAbilities($ddd, $clean['abilities'], 'Ability');
                }
            }
        }
        ksort($clean['abilities']);
        $stuff = array();
        foreach($clean['abilities'] as $k => $v) {
            $stuff[] = $k;
        }
        $clean['abilities'] = $stuff;

        // weapon_stat
        $clean = $this->readWeaponStats($d, $clean);
        $guns  = array();
        foreach($clean['weapon_stat'] as $gun) {
            unset($gun['Abilities']);
            $guns[] = $gun;
        }
        $clean['weapon_stat'] = $guns;

        // points, power
        $clean = $this->readPointCosts($d, $clean);

        return $clean;
    }
}
