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
        $clean['title']  = (string) $d['name'];

        // model_stat
        $cols = array('M', 'WS', 'BS', 'S', 'T', 'W', 'A', 'Ld', 'Save');
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

        // weapon_stat
        $clean = $this->readWeaponStats($d, $clean);
        foreach($clean['weapon_stat'] as $gun) {
            $gun['Abilities'] = '-';
        }

        // points, power
        $clean = $this->readPointCosts($d, $clean);

        return $clean;
    }
}
