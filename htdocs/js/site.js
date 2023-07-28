// https://github.com/eKoopmans/html2pdf.js
// page 1: all rules mentioned on all sheets (distinct), faction/detachment rules too
// cards: show name of rules but name/description of abilities
// sort units by epic > character > other, and alphabetically within

$(document).ready(bind);

function bind() {
  $('#list').change((e) => {
    const file   = e.target.files[0];
    const reader = new FileReader();
    reader.onload = async function(event) {
      var xmlContent = '';
      // try the unzip first, then fall back to assuming its not a zip
      // cant use the file extension because bscribe mobile doesnt always include it.
      // because it sucks.
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
      $('#output').html(jsonToHTML(listJson));
      var element = document.getElementById('output');
      html2pdf(element);
    }
    reader.readAsText(file);
  });
}

function xmlToJson(xml) {
  const obj = $.xml2json(xml);
  var army = [];
  
  // soup
  const forces = forceArray(obj.roster.forces.force);
  forces.forEach((force) => {
    var list = {'cost': 0, 'rules': {}, 'cheat': {}, 'units': [], 'faction': 'Unknown', 'detachment': 'Unknown'};
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

function jsonToHTML(json) {
  var html;
  json.forEach((force) => {
    html += renderCover(force);
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
  return `<div id="coverPage" class="page">
  <div class="header">
    <h2>${force.faction} army - ${force.cost}</h2>
    <hr/>
    <h3>${force.detachment} Detachment</h3>
  </div>
  <h4>Faction/Detachment Rules</h4>
  <div class="rules">${hashToLi(force.rules)}</div>
  <hr/>
  </div>`;
}

function renderUnit(unit) {
  return `<div class="page" style="border:1px solid black; margin:10px; padding 10px;">
    <div class="row">
    <div class="col-md-11 header"><h3>${unit.sheet} - ${unit.points} points</h3></div>
    <div class="col-md-7">
    ${makeTable(unit.profiles)}
    <h4>Ranged Weapons</h4>
    ${makeTable(unit.weapons['ranged'])}
    <h4>Melee Weapons</h4>
    ${makeTable(unit.weapons['melee'])}
    <h4>Wargear</h4>
    <div class="rules">${hashToLi(unit.wargear)}</div>
    </div>
    <div class="col-md-4">
      <h4>Models</h4>
      <ul><li>${unit.models.join('</li><li>')}</li></ul>
      <h4>Abilities</h4>
      <div class="rules">${hashToLi(unit.abilities)}</div>
      <h4>Rules</h4>
      ${Object.keys(unit.rules).join(', ')}
      </div>
    <div class="col-md-11">Keywords: ${unit.keywords.join(', ')}</div>
  </div></div>
  `;
}

function makeTable(data) {
  var content = '<table>';
  var first = true;
  Object.keys(data).forEach((row) => {
    if(first == true) {
      content += '<tr><td></td>';
      Object.keys(data[row]).forEach((col) => {
        content += `<th>${col}</th>`;
      });
      content += '</tr>';  
      first = false;
    }
    content += `<tr><th>${row}</th>`;
    Object.keys(data[row]).forEach((col) => {
      content += `<td>${data[row][col]}</td>`;
    });
    content += '</tr>';
  });
  content += '</table>'
  return content;
}

function hashToLi(items) {
  var content = '<ul>';
  Object.keys(items).forEach((item) => {
    content += `<li><strong>${item}:</strong> ${items[item]}</li>`
  });
  content += '</ul>'
  return content;
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