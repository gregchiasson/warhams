const buttParse = {
  xmlToJson(xml) {
    const obj = $.xml2json(xml);
    var army = [];
    
    // soup
    const forces = buttParse.forceArray(obj.roster.forces.force);
    forces.forEach((force) => {
      var list = {'cost': 0, 'rules': {}, 'cheat': {}, 'units': [], 'faction': 'Unknown', 'detachment': 'Unknown'};
      list['faction'] = force['$'].catalogueName;
      list['cost'] = parseInt(obj.roster.costs.cost['$'].value);
      if(force.rules) {
        const rules = buttParse.forceArray(force.rules.rule);
        rules.forEach((rule) => {
          list['rules'][rule['$'].name] = rule.description;
        });  
      }

      const selections = buttParse.forceArray(force.selections.selection);
      selections.forEach((selection) => {
        switch(selection['$'].type) {
          case 'model':
          case 'unit':
            list['units'].push(buttParse.parseUnit(selection));
            break;
          case 'upgrade':
            if(selection['$'].name == "Detachment" || selection['$'].name == "Detachment Choice") {
              list['detachment'] = selection.selections.selection['$'].name;
              if(selection.selections.selection.rules) {
                const rules = buttParse.forceArray(selection.selections.selection.rules.rule);
                rules.forEach((rule) => {
                  list['rules'][rule['$'].name] = rule.description;
                });  
              }
              if(selection.selections.selection.profiles) {
                const rules = buttParse.forceArray(selection.selections.selection.profiles.profile);
                rules.forEach((rule) => {
                  list['rules'][rule['$'].name] = rule.characteristics.characteristic['_'];
                });  
              }

            }
            break;
          case '':
            break;
        }
      });

      list['units'].forEach((unit) => {
        Object.keys(unit['rules']).forEach((rule) => {
          list['cheat'][rule] = unit['rules'][rule];
        })
      });
      army.push(list);
    });

    return army;
  },
  parseUnit(selection) {
    var unit = {
      'sheet':    'unknown',
      'points':    0,
      'profiles':  {},
      'abilities': {},
      'rules':     {},
      'keywords':  [],
      'models':    [],
      'wargear':   {},
      'weapons':   {'ranged': {}, 'melee': {}}
    };    
    unit.sheet = selection['$'].name;
    unit['models'] = buttParse.parseModels(selection);
    const costObj = Array.isArray(selection.costs.cost) ? selection.costs.cost[0] : selection.costs.cost;
    unit['points'] = parseInt(costObj['$'].value);

    if(selection.rules) {
      const rules = buttParse.forceArray(selection.rules.rule);
      rules.forEach((rule) => {
        unit['rules'][rule['$'].name] = rule.description;
      });  
    }

    if(selection.categories) {
      const keywords = buttParse.forceArray(selection.categories.category);
      keywords.forEach((keyword) => {
        unit['keywords'].push(keyword['$'].name);
      });
  
      unit['keywords'].sort((a, b) => {
        return a.match('Faction') ? -1 : 1;
      });  
    }

    if(selection.profiles) {
      const profiles = buttParse.forceArray(selection.profiles.profile);
      unit = buttParse.parseGuns(unit, profiles);
      profiles.forEach((profile) => {
        unit = buttParse.parseProfile(unit, profile);
      });  
    }

    if(selection.selections) {      
      const selections = buttParse.forceArray(selection.selections.selection);

      selections.forEach((item) => {
        console.log(item.costs.cost);
        const costObj = Array.isArray(item.costs.cost) ? item.costs.cost[0] : item.costs.cost;
        unit['points'] += parseInt(costObj['$'].value);
      });

      selections.forEach((item) => {
        if(item.profiles) {
            const profiles = buttParse.forceArray(item.profiles.profile);
            unit = buttParse.parseGuns(unit, profiles);
        }
        if(item.selections) {
          const itemSelections = buttParse.forceArray(item.selections.selection);
          itemSelections.forEach((gear) => {
            if(gear.profiles) {
              const profiles = buttParse.forceArray(gear.profiles.profile);
              unit = buttParse.parseGuns(unit, profiles);  
            } 
            if(gear.selections) {
              const fuckGuard = buttParse.forceArray(gear.selections.selection);
              fuckGuard.forEach((fuckYou) => {
                if(fuckYou.profiles) {
                  const profiles = buttParse.forceArray(fuckYou.profiles.profile);
                  unit = buttParse.parseGuns(unit, profiles);  
                }     
              });
            }
          });  
        }
      });        
    }

    return unit;
  }, 
  parseGuns(unit, profiles) {
    profiles.forEach((profile) => {
      if(profile['$'].typeName == 'Ranged Weapons') {
        unit['weapons']['ranged'][profile['$'].name] = buttParse.parseGun(profile);
      } else if(profile['$'].typeName == 'Melee Weapons') {
        unit['weapons']['melee'][profile['$'].name] = buttParse.parseGun(profile);
      } else if(profile['$'].typeName == 'Abilities') {
        unit['wargear'][profile['$'].name] = profile.characteristics.characteristic['_'];
      }
      unit = buttParse.parseProfile(unit, profile);
    });
    return unit;
  },
  parseProfile(unit, profile) {
    if(profile['$'].typeName == 'Unit') {
      const stats = profile.characteristics.characteristic;
      var statblock = {};
      stats.forEach((stat) => {
        statblock[stat['$'].name] = stat['_'];
      });
      unit['profiles'][profile['$'].name] = statblock;
    } else if(profile['$'].typeName == 'Abilities') {
      unit['abilities'][profile['$'].name] = profile.characteristics.characteristic['_']; 
    }
    return unit;
  },
  parseGun(gun) {
    var stats = {}
    gun.characteristics.characteristic.forEach((stat) => {
      stats[stat['$'].name] = stat['_'];
    })
    return stats;
  },
  parseModels(unit) {
    var allModels = [];
    var rawModels = {};
    // unit roster/model count
    if(unit.selections) {
      const models = buttParse.forceArray(unit.selections.selection);
      models.forEach((model) => {
        if(model['$'].type == 'model') {
          const modelName  = model['$'].name;
          const modelCount = model['$'].number;
          if(!rawModels[modelName]) {
            rawModels[modelName] = 0;
          }
          rawModels[modelName] += parseInt(modelCount);  
        }
        // i love how i have to keep hacking this crap in because all the codex
        // data maintainers are insane. this time, for guard infantry squads:
        if(model.selections) {
          const fuckGuard = buttParse.forceArray(model.selections.selection);
          fuckGuard.forEach((fuckYou) => {
            if(fuckYou['$'].type == 'model') {
              const modelName  = fuckYou['$'].name;
              const modelCount = fuckYou['$'].number;
              if(!rawModels[modelName]) {
                rawModels[modelName] = 0;
              }
              rawModels[modelName] += parseInt(modelCount);  
            }
          });    
        }
      });  
    }

    if(Object.keys(rawModels).length > 0) {
      Object.keys(rawModels).forEach((model) => {
        allModels.push(`${rawModels[model]} ${model}`);
      });  
    } else {
      allModels.push(`1 ${unit['$'].name}`);
    }
    return allModels;
  },
  forceArray(item) {
    if(!item) { 
      return [];
    }
    if(!Array.isArray(item)) {
      return [item];
    } else {
      return item;
    }
  }
};