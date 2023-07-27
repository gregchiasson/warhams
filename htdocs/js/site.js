// page 1: all rules mentioned on all sheets (distinct), faction/detachment rules too
// cards: show name of rules but name/description of abilities
// sort units by epic > character > other, and alphabetically within

$(document).ready(bind);

function bind() {
  $('#list').change((e) => {
    const file   = e.target.files[0];
    const reader = new FileReader();
    reader.onload = function(event) {
      // unzip file if needed, or just try it anyway because of mobile extension shit
      const listJson = xmlToJson(event.target.result);
      $('#output').html(`<pre>${JSON.stringify(listJson)}</pre>`);
      console.log(listJson);
    }
    reader.readAsText(file);
  });
}

function xmlToJson(xml) {
  var list = {'cost': 0, 'rules': {}, 'cheat': {}, 'units': [], 'faction': 'Unknown', 'detachment': 'Unknown'};
  const obj = $.xml2json(xml);

  // TODO soup
  const force = forceArray(obj.roster.forces.force)[0];
  list['faction'] = force['$'].catalogueName;
  list['cost'] = `${parseInt(obj.roster.costs.cost['$'].value)}${obj.roster.costs.cost['$'].name}`;

  const rules = forceArray(force.rules.rule);
  rules.forEach((rule) => {
    list['rules'][rule['$'].name] = rule.description;
  });

  const selections = forceArray(force.selections.selection);
  selections.forEach((selection) => {
    switch(selection['$'].type) {
      case 'unit':
        list['units'].push(parseUnit(selection));
        break;
      case 'upgrade':
        if(selection['$'].name == "Detachment") {
          list['detachment'] = selection.selections.selection['$'].name;
          selection.selections.selection.rules.rule.forEach((rule) => {
            list['rules'][rule['$'].name] = rule.description;
          });
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

  return list;
}

function parseUnit(selection) {
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

  // model stat blocks and abilities
  const profiles = forceArray(selection.profiles.profile);
  profiles.forEach((profile) => {
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
  });

  // TODO character guns???

  // weapon stat blocks
  const selections = selection.selections.selection;
  selections.forEach((item) => {
    
    if(item.profiles) {
        const profiles = forceArray(item.profiles.profile);
        profiles.forEach((profile) => {
          if(profile['$'].typeName == 'Ranged Weapons') {
            unit['weapons']['ranged'][profile['$'].name] = parseGun(profile);
          } else if(profile['$'].typeName == 'Melee Weapons') {
            unit['weapons']['melee'][profile['$'].name] = parseGun(profile);
          } else if(profile['$'].typeName == 'Abilities') {
            unit['wargear'][profile['$'].name] = profile.characteristics.characteristic['_'];
          }
        });
    }
    
    if(item.selections) {
      const itemSelections = forceArray(item.selections.selection);
      itemSelections.forEach((gear) => {
        const profiles = forceArray(gear.profiles.profile);
        profiles.forEach((profile) => {
          if(profile['$'].typeName == 'Ranged Weapons') {
            unit['weapons']['ranged'][profile['$'].name] = parseGun(profile);
          } else if(profile['$'].typeName == 'Melee Weapons') {
            unit['weapons']['melee'][profile['$'].name] = parseGun(profile);
          } else if(profile['$'].typeName == 'Abilities') {
            unit['wargear'][profile['$'].name] = profile.characteristics.characteristic['_'];
          }
        });
      });  
    }
  });

  // unit points cost 
  unit['points'] = parseInt(selection.costs.cost['$'].value);
  if(unit['points'] == 0) {
    const selections = forceArray(selection.selections.selection);
    selections.forEach((item) => {
      unit['points'] += parseInt(item.costs.cost['$'].value);
    });
  }

  // unit rules
  const rules = forceArray(selection.rules.rule);
  rules.forEach((rule) => {
    unit['rules'][rule['$'].name] = rule.description;
  });

  // unit keywords (including faction, prefixed with "Faction: ")
  const keywords = forceArray(selection.categories.category);
  keywords.forEach((keyword) => {
    unit['keywords'].push(keyword['$'].name);
  });

  // unit roster/model count
  const models = forceArray(selection.selections.selection);
  var rawModels = {};
  models.forEach((model) => {
    if(model['$'].type == 'model') {
      const modelName  = model['$'].name;
      const modelCount = model['$'].number;
      if(!rawModels[modelName]) {
        rawModels[modelName] = 0;
      }
      rawModels[modelName] += parseInt(modelCount);  
    }
  });

  if(Object.keys(rawModels).length > 0) {
    Object.keys(rawModels).forEach((model) => {
      unit['models'].push(`${rawModels[model]} ${model}`)
    });  
  } else {
    unit['models'].push(`1 ${unit['sheet']}`)    
  }

  return unit;
}

function parseGun(gun) {
  var stats = {}
  gun.characteristics.characteristic.forEach((stat) => {
    stats[stat['$'].name] = stat['_'];
  })
  return stats;
}

function forceArray(item) {
  if(!Array.isArray(item)) {
    return [item];
  } else {
    return item;
  }
}