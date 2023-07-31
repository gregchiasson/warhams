$(document).ready(bind);

function bind() {
  $('#list').change((e) => {
    const file   = e.target.files[0];
    const reader = new FileReader();
    reader.onload = async function(event) {
      var xmlContent = '';
      try {
        const reader  = new zip.ZipReader(new zip.BlobReader(file));
        const entries = await reader.getEntries();
        xmlContent = await entries[0].getData(new zip.TextWriter(), {onprogress: (index, max) => {}});
        await reader.close();
      } catch(err) {
      }
      if(!xmlContent) {
        xmlContent = event.target.result;
      }
      const listJson = xmlToJson(xmlContent);
      $('#output-label').show();
      $('#output').html(jsonToHTML(listJson));
      var element = document.getElementById('output');
      html2pdf(element, {
        filename:  'your_list_sucks.pdf',
        pagebreak: { mode: 'css', after: '.page' },
        margin:    1,
        jsPDF: { format: 'letter', orientation: 'landscape' }
      });
    }
    reader.readAsText(file);
  });
}

function xmlToJson(xml) {
  const obj = $.xml2json(xml);
  var army = [];

  console.log(obj);
  
  // soup
  const forces = forceArray(obj.roster.forces.force);
  forces.forEach((force) => {
    var list = {'cost': 0, 'rules': {}, 'cheat': {}, 'units': [], 'faction': 'Unknown', 'detachment': 'Unknown'};
    list['faction'] = force['$'].catalogueName;
    list['cost'] = `${parseInt(obj.roster.costs.cost['$'].value)}${obj.roster.costs.cost['$'].name}`;

    if(force.rules) {
      const rules = forceArray(force.rules.rule);
      rules.forEach((rule) => {
        list['rules'][rule['$'].name] = rule.description;
      });  
    }

    const selections = forceArray(force.selections.selection);
    selections.forEach((selection) => {
      switch(selection['$'].type) {
        case 'model':
        case 'unit':
          list['units'].push(parseUnit(selection));
          break;
        case 'upgrade':
          if(selection['$'].name == "Detachment" || selection['$'].name == "Detachment Choice") {
            list['detachment'] = selection.selections.selection['$'].name;
            if(selection.selections.selection.rules) {
              const rules = forceArray(selection.selections.selection.rules.rule);
              rules.forEach((rule) => {
                list['rules'][rule['$'].name] = rule.description;
              });  
            }
            if(selection.selections.selection.profiles) {
              const rules = forceArray(selection.selections.selection.profiles.profile);
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
  if(selection.profiles) {
    const profiles = forceArray(selection.profiles.profile);
    profiles.forEach((profile) => {
      unit = parseProfile(unit, profile);
    });  
  }

  // weapon stat blocks
  const selections = forceArray(selection.selections.selection);
  selections.forEach((item) => {
    if(item.profiles) {
        const profiles = forceArray(item.profiles.profile);
        unit = parseGuns(unit, profiles);
    }
    if(item.selections) {
      const itemSelections = forceArray(item.selections.selection);
      itemSelections.forEach((gear) => {
        if(gear.profiles) {
          const profiles = forceArray(gear.profiles.profile);
          unit = parseGuns(unit, profiles);  
        } 
        if(gear.selections) {
          const fuckGuard = forceArray(gear.selections.selection);
          fuckGuard.forEach((fuckYou) => {
            if(fuckYou.profiles) {
              const profiles = forceArray(fuckYou.profiles.profile);
              unit = parseGuns(unit, profiles);  
            }     
          });
        }
      });  
    }
  });

  // unit points cost 
  unit['points'] = parseInt(selection.costs.cost['$'].value);
  const costSelections = forceArray(selection.selections.selection);
  costSelections.forEach((item) => {
    unit['points'] += parseInt(item.costs.cost['$'].value);
  });

  // unit rules
  if(selection.rules) {
    const rules = forceArray(selection.rules.rule);
    rules.forEach((rule) => {
      unit['rules'][rule['$'].name] = rule.description;
    });  
  }

  // unit keywords (including faction, prefixed with "Faction: ")
  const keywords = forceArray(selection.categories.category);
  keywords.forEach((keyword) => {
    unit['keywords'].push(keyword['$'].name);
  });

  unit['keywords'].sort((a, b) => {
    return a.match('Faction') ? -1 : 1;
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
    // i love how i have to keep hacking this crap in because all the codex
    // data maintainers are insane. this time, for guard infantry squads:
    if(model.selections) {
      const fuckGuard = forceArray(model.selections.selection);
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

  if(Object.keys(rawModels).length > 0) {
    Object.keys(rawModels).forEach((model) => {
      unit['models'].push(`${rawModels[model]} ${model}`)
    });  
  } else {
    unit['models'].push(`1 ${unit['sheet']}`)    
  }

  return unit;
}

function jsonToHTML(json) {
  var html = '';
  json.forEach((force) => {
    html += renderCover(force);
    html += renderCheat(force);
    html += renderRules(force);
    //html += renderArmory(force);
    html += renderUnits(force['units']);
  });
  return html;
}

function renderUnits(units) {
  var content = '';
  units.forEach((unit) => {
    content += renderUnit(unit);
  });
  return content;
}

function renderCover(force) {
  var unitRows = '';

  force.units.sort((a, b) => {
    return a.sheet.localeCompare(b.sheet);
  });

  force.units.forEach((unit) => {
    unitRows += `<tr>
    <td>${unit.sheet}</td>
    <td>${unit.points}</td>
    <td>${unit.models.join(', ')}</td>
    </tr>`
  });
  return `<div id="coverPage" class="page">
  <div class="row">
  <div class="col-md-11 header">
    <h3 class="floater">${force.detachment} Detachment</h3>
    <h2>${force.faction} army - ${force.cost}</h2>
  </div>
  <div class="col-md-12">
  <h4>Faction/Detachment Rules</h4>
  <div class="rules">${hashToLi(force.rules)}</div>
  <hr/>
  <h4>Units</h4>
  <table class="table table-striped">
    <thead><tr>
      <th>Datasheet</th>
      <th>Points</th>
      <th>Models</th>
    </tr></thead><tbody>
    ${unitRows}
  </tbody></table>
  </div></div></div>`;
}

function renderCheat(force) {
  var unitData = '';

  force.units.sort((a, b) => {
    return a.sheet.localeCompare(b.sheet);
  });

  uniqueUnits = [];
  seenUnits = [];
  force.units.forEach((unit) => {
    const hash = JSON.stringify(unit);
    if(seenUnits.indexOf(hash) == -1) {
      seenUnits.push(hash);
      uniqueUnits.push(unit);
    }
  })

  uniqueUnits.forEach((unit) => {
    var allWeapons = unit.weapons['ranged'];
    Object.keys(unit.weapons['melee']).forEach((gun) => {
      allWeapons[gun] = unit.weapons['melee'][gun];
    });
    var allRules = unit.abilities;
    Object.keys(unit.rules).forEach((rule) => {
      allRules[rule] = unit.rules[rule];
    })
    unitData += `
    <div class="col-md-6">
    <h4>${unit.sheet}</h4>
    <strong>Rules and Abilities</strong>: ${Object.keys(allRules).length ? Object.keys(allRules).sort().join(', ') : 'None'}
    ${makeTable(unit.profiles)}
    ${makeTable(allWeapons)}
    </div>`;
  });

  return `
  <div id="cheatPage" class="page"><div class="row">
    <div class="col-md-11 header"><h3>Quick Reference Sheet</h3></div>
      <div class="row">
        ${unitData}
      </div>
    </div>
  </div>`;
}

function renderRules(force) {
  var allRules = {};
  force.units.forEach((unit) => {
    Object.keys(unit.abilities).forEach((rule) => {
      allRules[rule] = unit.abilities[rule];
    })
  });
  return `
  <div id="rulesPage" class="page"><div class="row">
    <div class="col-md-11 header"><h3>Quick Reference Sheet</h3></div>
      <div class="rules">${hashToLi(allRules)}</div>
    </div>
  </div>`;

}

function renderArmory(force) {
  var allProfiles = {};
  var allRangedWeapons = {};
  var allMeleeWeapons = {};
  force.units.forEach((unit) => {
    Object.keys(unit.profiles).forEach((profile) => {
      allProfiles[profile] = unit.profiles[profile];
    })
    Object.keys(unit.weapons['ranged']).forEach((profile) => {
      const weaponName = `${unit.sheet}: ${profile}`;
      allRangedWeapons[weaponName] = unit.weapons['ranged'][profile];
    })
    Object.keys(unit.weapons['melee']).forEach((profile) => {
      const weaponName = `${unit.sheet}: ${profile}`;
      allMeleeWeapons[weaponName] = unit.weapons['melee'][profile];
    })
  });
  return `
  <div id="refPage" class="page"><div class="row">
  <div class="col-md-11 header"><h3>Rules Reference</h3></div>
  <div class="row">
  <div class="col-md-6">
    <h4>Model Profiles</h4>
    ${makeTable(allProfiles)}
    </div>
    <div class="col-md-6">
    <h4>Ranged Weapons</h4>
    ${makeTable(allRangedWeapons)}
    <h4>Melee Weapons</h4>
    ${makeTable(allMeleeWeapons)}
  </div>
  </div>
  </div>
  </div>`;
}

function renderUnit(unit) {
  return `<div class="page">
    <div class="row">
    <div class="col-md-11 header">
      <div class="floater">${unit.models.join(', ')}</div>
      <h3>${unit.sheet} - ${unit.points} points</h3>
      </div>
    <div class="col-md-7">
    ${makeTable(unit.profiles)}
    <h4>Ranged Weapons</h4>
    ${makeTable(unit.weapons['ranged'])}
    <h4>Melee Weapons</h4>
    ${makeTable(unit.weapons['melee'])}
    </div>
    <div class="col-md-5">
      <h4>Abilities</h4>
      <div class="rules">${hashToLi(unit.abilities)}</div>
      <!--
      <h4>Wargear</h4>
      <div class="rules">${hashToLi(unit.wargear)}</div>  
      -->
      <h4>Rules</h4>
      <ul><li>${Object.keys(unit.rules).length ? Object.keys(unit.rules).sort().join(', ') : 'None'}</li></ul>
      </div>
    <div class="footer col-md-12"><strong>Keywords:</strong> ${unit.keywords.join(', ')}</div>
  </div></div>
  `;
}

function makeTable(data) {
  var content = '<table class="table table-striped"><thead>';

  var first = true;
  Object.keys(data).sort().forEach((row) => {
    if(first == true) {
      content += '<tr><th></th>';
      Object.keys(data[row]).forEach((col) => {
        content += `<th>${col}</th>`;
      });
      content += '</tr></thead><tbody>';  
      first = false;
    }
    content += `<tr><th>${row}</th>`;
    Object.keys(data[row]).forEach((col) => {
      content += `<td>${data[row][col] || '-'}</td>`;
    });
    content += '</tr>';
  });
  content += '</tbody></table>'
  return content;
}

function hashToLi(items) {
  var content = '<ul>';
  if(Object.keys(items).length == 0) {
    content += '<li>None</li>'
  }
  Object.keys(items).sort().forEach((item) => {
    const formatted = items[item].replace(/\n+/g, '<br>');
    content += `<li><strong>${item}:</strong> ${formatted}</li>`
  });
  content += '</ul>'
  return content;
}

function parseGuns(unit, profiles) {
  profiles.forEach((profile) => {
    if(profile['$'].typeName == 'Ranged Weapons') {
      unit['weapons']['ranged'][profile['$'].name] = parseGun(profile);
    } else if(profile['$'].typeName == 'Melee Weapons') {
      unit['weapons']['melee'][profile['$'].name] = parseGun(profile);
    } else if(profile['$'].typeName == 'Abilities') {
      unit['wargear'][profile['$'].name] = profile.characteristics.characteristic['_'];
    }
    unit = parseProfile(unit, profile);
  });
  return unit;
}

function parseProfile(unit, profile) {
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
}

function parseGun(gun) {
  var stats = {}
  gun.characteristics.characteristic.forEach((stat) => {
    stats[stat['$'].name] = stat['_'];
  })
  return stats;
}

function forceArray(item) {
  if(!item) { 
    return [];
  }
  if(!Array.isArray(item)) {
    return [item];
  } else {
    return item;
  }
}