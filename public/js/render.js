const buttRender = {
  skippables() {
    // probably a better way to handle this...
    return [
      'Leader', 'Feel No Pain', 'Kindred Sorcery', 'Reanimation Protocols', 'Deep Strike',
      'Eye of the Ancestors', 'Ruthless Efficiency', 'Strands of Fate',
      'Deadly Demise 1', 'Deadly Demise 3', 'Deadly Demise D3', 'Deadly Demise D6', 'Deadly Demise 6',
      'Firing Deck 1', 'Firing Deck 2', 'Firing Deck 5', 'Firing Deck 11', 'Firing Deck 12',
      'Cabal of Sorcerers 1', 'Cabal of Sorcerers 2', 'Cabal of Sorcerers 3', 'Cabal of Sorcerers 4'
    ] 
  },
  jsonToHTML(json, format) {
    var html = '';
    json.forEach((force) => {
      html += buttRender.renderCover(force);
      html += buttRender.renderCheat(force);
      html += buttRender.renderRules(force);
      //html += buttRender.renderArmory(force);
      html += buttRender.renderUnits(force, format == 'crusade' ? true : false);
    });
    return html;
  },
  HTMLtoPDF(targetElement) {
    var element = document.getElementById(targetElement);
    html2pdf(element, {
      filename:  'your_list_sucks.pdf',
      pagebreak: { mode: 'css', after: '.page' },
      margin:    0,
      jsPDF: { format: 'letter', orientation: 'portrait' }
    });
  },
  renderUnits(force, useCrusade=false) {
    var content = '';
    force['units'].forEach((unit) => {
      content += buttRender.renderUnitCustom(unit, force['rules'], useCrusade);
    });
    return content;
  },
  renderCover(force) {
    var unitRows = '';

    force.units.sort((a, b) => {
      return a.sheet.localeCompare(b.sheet);
    });

    force.units.forEach((unit) => {
      unitRows += `<tr>
      <td>${unit.sheet}</td>
      <td>${unit.points}</td>
      <td>${unit.models.join(', ')}</td>
      </tr>`;
    });

    unitRows += `<tr>
    <td><strong>Total Points</strong></td>
    <td>${force.cost}</td>
    <td>&nbsp;</td>
    </tr>`;

    return `<div id="coverPage" class="page">
    <div class="row">
    <div class="col-md-11 header">
      <h3 class="floater">${force.detachment} Detachment</h3>
      <h2>${force.faction} army - ${force.cost} points</h2>
    </div>
    <div class="col-md-12">
    <h4>Faction/Detachment Rules</h4>
    <div class="rules">${buttRender.hashToLi(force.rules)}</div>
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
  },
  renderCheat(force) {
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
      var allWeapons = {};
      Object.keys(unit.weapons['ranged']).forEach((gun) => {
        allWeapons[gun] = unit.weapons['ranged'][gun];
      });
      Object.keys(unit.weapons['melee']).forEach((gun) => {
        allWeapons[gun] = unit.weapons['melee'][gun];
      });
      var allRules = unit.abilities;
      Object.keys(unit.rules).forEach((rule) => {
        allRules[rule] = unit.rules[rule];
      })
      unitData += `
      <div class="col-md-6 unitSummary">
      <h4>${unit.sheet}</h4>
      <strong>Rules and Abilities</strong>: ${Object.keys(allRules).length ? Object.keys(allRules).sort().join(', ') : 'None'}
      ${buttRender.makeTable(unit.profiles)}
      ${buttRender.makeGunTable(allWeapons)}
      </div>`;
    });

    return `
    <div id="cheatPage" class="page"><div class="row">
      <div class="col-md-11 header"><h2>Unit Reference</h2></div>
        <div class="row">
          ${unitData}
        </div>
      </div>
    </div>`;
  },
  renderRules(force) {
    var allRules = {};
    force.units.forEach((unit) => {
      Object.keys(unit.abilities).forEach((rule) => {
        allRules[rule] = unit.abilities[rule];
      })
    });
    return `
    <div id="rulesPage" class="page"><div class="row">
      <div class="col-md-11 header"><h2>Rules Reference</h2></div>
        <div class="rules">${buttRender.hashToLi(allRules)}</div>
      </div>
    </div>`;

  },
  renderArmory(force) {
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
    <div class="col-md-11 header"><h2>Rules Reference</h2></div>
    <div class="row">
    <div class="col-md-6">
      <h4>Model Profiles</h4>
      ${buttRender.makeTable(allProfiles)}
      </div>
      <div class="col-md-6">
      <h4>Ranged Weapons</h4>
      ${buttRender.makeGunTable(allRangedWeapons)}
      <h4>Melee Weapons</h4>
      ${buttRender.makeGunTable(allMeleeWeapons)}
    </div>
    </div>
    </div>
    </div>`;
  },
  renderLeader(leader) {
    return leader && leader[0] ? `<h4>Leader</h4><p>This model can be attached to the following units: <span class="keyword">${leader.join(', ').toUpperCase()}</span>` : '';
  },
  renderSpecialism(specialism) {
    return specialism ? `<h4>Specialisms</h4><p><span class="keyword">${specialism.toUpperCase()}</span>` : '';
  },
  renderGunTable(title, data) {
    if(data && Object.keys(data).length > 0) {
      return `<h4>${title}</h4>${buttRender.makeGunTable(data || {})}`;
    } else {
      return '';
    }
  },
  makeGunTable(guns) {
    var newGuns = {};
    Object.keys(guns).forEach((gun) => {
      const oldGun = guns[gun];
      var newName = gun;
      if(oldGun.Keywords && oldGun.Keywords != '-') {
        newName += ` <span class="keyword">[${oldGun['Keywords'].toUpperCase()}]</span>`;
      }   
      newGuns[newName] = guns[gun];
      delete newGuns[newName].Keywords;
    });
   
    return buttRender.makeTable(newGuns, 'guns');
  },
  renderUnitCustom(unit) {
    var abilities = {};
    Object.keys(unit['abilities'] || []).forEach((ruleName) => {
      if(!unit.rules[ruleName]) {
        abilities[ruleName] = unit.abilities[ruleName];
      }
    });

    Object.keys(unit.profiles).forEach((profile) => {
      if(!unit.profiles[profile].iSV) {
        delete unit.profiles[profile].iSV;
      }
    });

    Object.keys(unit.rules).forEach((rule) => {
      if(!rule) {
        delete unit.rules[rule];
      }
    });

    const rules = Object.keys(unit.rules).length ? Object.keys(unit.rules).sort().join(', ') : 'None';
    abilities['<span class="keyword">CORE/FACTION</span>'] = rules;

    return `<div id="custom_unit_card" class="page">
      <div class="row header">
        <div class="col-md-8">
          <h2>${unit.sheet}</h2>
          <p>${unit.models.join(' and ')}.........${unit.points} points</p>
          ${buttRender.makeTable(unit.profiles || [])}
        </div>
        <div class="col-md-4" style="text-align:center">
          ${unit.imageUrl ? `<img src="${unit.imageUrl}" height="170px; vertical-align:bottom">` : ''}
        </div>
      </div>
      <div class="row">
        <div class="col-md-8">
          ${buttRender.renderGunTable('Ranged Weapons', unit.weapons['ranged'] || {})}
          ${buttRender.renderGunTable('Melee Weapons', unit.weapons['melee'] || {})}
          ${buttRender.renderLeader(unit.leader) }
          <h4>Unit Composition</h4>
          <strong>${unit.models?.join(', ')}</strong>
        </div>
        <div class="col-md-4" style="border-left: 2px dotted grey">
          <h4>Abilities</h4>
          <div class="rules">${buttRender.hashToLi(abilities)}</div>
          ${buttRender.renderSpecialism(unit.specialism)}
        </div>
      </div>
      <div class="row footer">
        <div class="col-md-8">
          <strong>KEYWORDS:</strong> <span class="keyword">${unit.keywords.join(', ')}</span>
        </div>
        <div class="col-md-4">
          <strong>FACTION KEYWORDS:</strong> <span class="keyword">${unit.factionKeywords?.join(', ')}</span>
        </div>
      </div>
    </div>
  </div>`;
  },
  renderUnit(unit, skipRules, useCrusade) {
    var abilities = {};
    // filter out a few of the more obvious USRs. 
    // these still show up under rules, just not as abilities
    buttRender.skippables().forEach((skip) => {
      skipRules[skip] = 'Nope.';
    });
    Object.keys(unit['rules'] || []).forEach((ruleName) => {
      skipRules[ruleName] = 'Nope.';
    });
    // anything that's in the army-wide rules also can be skipped
    // these still show as rules, and the full text is available
    // on the summary page, but they're too dang long
    Object.keys(unit['abilities'] || []).forEach((ruleName) => {
      if(!skipRules[ruleName]) {
        abilities[ruleName] = unit['abilities'][ruleName];
      }
    });
    return `<div class="page">
      <div class="row">
      <div class="col-md-11 header">
        <div class="floater">${unit.models?.join(', ')}</div>
        <h2>${unit.sheet} - ${unit.points} points</h2>
        </div>
      <div class="col-md-7">
      ${buttRender.makeTable(unit.profiles)}
      <h4>Ranged Weapons</h4>
      ${buttRender.makeTable(unit.weapons['ranged'])}
      <h4>Melee Weapons</h4>
      ${buttRender.makeTable(unit.weapons['melee'])}
      </div>
      <div class="col-md-5">
        <h4>Abilities</h4>
        <div class="rules">${buttRender.hashToLi(abilities)}</div>
        <!--
        <h4>Wargear</h4>
        <div class="rules">${buttRender.hashToLi(unit.wargear)}</div>  
        -->
        <h4>Rules</h4>
        <ul><li>${Object.keys(unit.rules).length ? Object.keys(unit.rules).sort().join(', ') : 'None'}</li></ul>
        </div>
      <div class="footer col-md-12"><strong>Keywords:</strong> ${unit.keywords.join(', ')}</div>
    </div>
    ${useCrusade ? buttRender.crusadeForm() : ''}
  </div>`;
  },
  crusadeForm() {
    return `
    <div class="row">
      <div class="col-md-11 header">
        <h2>Crusade</h2>
      </div>
    </div>
    `;
  },
  makeTable(data, className) {
    var content = `<table class="table table-striped ${className}"><thead>`;

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
  },
  hashToLi(items) {
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
}