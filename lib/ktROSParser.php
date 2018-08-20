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
                foreach($dd->selections->selection as $ddd) {
                    if(strpos((string) $ddd['name'], 'Level ') === 0) {
                        $clean['keywords'][] = (string) $dd['name'];
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
            foreach($dd->selections->selection as $ddd) {
                $clean['abilities'] = $this->readSelectionAbilities($ddd, $clean['abilities'], 'Ability');
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
