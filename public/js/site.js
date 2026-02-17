$(document).ready(bind);

PROFILES = {
  'guns': {},
  'fists': {},
  'dudes': {},
  'abilities': {}
};

previewUrl = null;

function addProfileLinks(profileType) {
  var html = '<strong>Saved Profiles: </strong>'
  Object.keys(PROFILES[profileType]).forEach((profile) => {
    html += `${profile} <input id="remove-profile-${profileType}" class="delete-profile" name="${profile}" type="button" value="-" />`;
  });
  $(`#profiles-${profileType}`).html(html);
}
function displayPreview() {
  const unitJSONdemo = {
    sheet: '[SAMPLE] Shas\'o Gun Shootman',
    imageUrl: previewUrl,
    abilities: {
      "Quad Damage": "once per battle when this unit shoots, roll 4d6 for damage.",
      "Heavy Armor": "this model has a 4+++ FnP against mortal wounds.",
      "Battlesuit Support System": "Models in this unit can shoot after falling back."
    },
    keywords: 'Broadside, Battlesuit, Monster, Character, Fly, Epic Hero'.split(','),
    factionKeywords: 'Tau Empire'.split(','),
    models: '1 Battlesuit Veteran'.split(','),
    points: 125,
    profiles: {
      'Battlesuit Veteran': {M: '5"', T: 6, SV: '2+', iSV: '4++', W: 8, LD: '5+', OC: 1}
    },
    rules: 'For the Greater Good, Deep Strike, Feel No Pain (3+)'.split(',').reduce((a,i)=> (a[i]='test',a),{}),
    wargear: {}, // SKIP
    weapons: {
      ranged: {
        'Heavy Rail Rifle':  {'Range': "36\"", 'A': 2, 'BS': '3+', 'S': 12, 'AP': '-4', 'D': 'd6+1', 'Keywords': 'heavy, devastating wounds'},
        'Smart Missile System':  {'Range': "30\"", 'A': 6, 'BS': '4+', 'S': 5, 'AP': '-1', 'D': '1', 'Keywords': 'twin-linked, indirect'},
      },
      melee:  {
        'Big Hands':  {'Range': "Melee", 'A': 3, 'WS': '5+', 'S': 6, 'AP': '0', 'D': '1', 'Keywords': ''},
      }
    },
    leader: 'Broadside battlesuits'.split(',') || null,
    specialism: '67IQ Railgun god'
  };
  unitHTML = buttRender.renderUnitCustom(unitJSONdemo, false);
  $('#output').html(unitHTML);
}

function bind() {
  $('#custom-download').hide();
  displayPreview();

  $(".add-profile").click((e) => {
    const profileType = e.target.id.replace('add-profile-', ''); 
    let profile = {};
    const profileFields = {
      'dudes': ['M', 'T', 'SV', 'iSV', 'W', 'LD', 'OC'],
      'guns':  ['Range', 'A', 'BS', 'S', 'AP', 'D', 'Keywords'],
      'fists': ['Range', 'A', 'WS', 'S', 'AP', 'D', 'Keywords'],
    }
    const profileName = $(`#profile_${profileType}_name`).val() || 'whatever';
    $(`#profile_${profileType}_name`).val(null)
    if(profileType == 'abilities') {
      profile = $(`#profile_${profileType}_description`).val()
       $(`#profile_${profileType}_description`).val(null)
    } else {
      profileFields[profileType].forEach((field) => {
        profile[field] = $(`#profile_${profileType}_${field}`).val();
        $(`#profile_${profileType}_${field}`).val(null);
      });
    }

    PROFILES[profileType][profileName] = profile;
    console.log(PROFILES);

    addProfileLinks(profileType);
  });

  $(document).on('click', '.delete-profile', (e) => {
    console.log(e.target);
    const profileType = e.target.id.replace('remove-profile-', ''); 
    const profileName = $(e.target).attr('name');
    console.log(profileType);
    console.log(profileName);
    delete PROFILES[profileType][profileName];

    addProfileLinks(profileType);
  });

  $("#custom-card").click((e) => {
    $('#custom-download').hide();

    const imageFile = $('#custom-image').prop('files')[0];
    if (imageFile && imageFile.type.match('image.*')) {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        previewUrl = URL.createObjectURL(imageFile);
    }

    const unitJSON = {
      sheet: $('#custom_sheet').val() || 'Some Guy',
      imageUrl: previewUrl,
      abilities: PROFILES['abilities'],
      keywords: ($('#custom_keywords').val() || "None").split(','),
      factionKeywords: ($('#custom_faction').val() || 'None').split(','),
      models: ($('#custom_models').val() || '1 model').split(','),
      points: $('#custom_points').val() || 0,
      profiles: PROFILES['dudes'],
      rules: ($('#custom_rules').val() || '').split(',').reduce((a,i)=> (a[i]='test',a),{}),
      wargear: {}, // SKIP
      weapons: {
        ranged: PROFILES['guns'],
        melee:  PROFILES['fists']
      },
      leader: $('#custom_leader').val().split(',') || null,
      specialism: $('#custom_specialism').val()
    };

    unitHTML = buttRender.renderUnitCustom(unitJSON, false);
    $('#output').html(unitHTML);
    $('#custom-download').show();
  });

  $("#custom-download").click((e) => {
    var contentElement = document.getElementById("output");
    var opt = {
      margin:       0,
      filename:     'your_character_sucks.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, width: 1600, height: 800 },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
    };
    html2pdf(contentElement, opt);
  });
  
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
      const listJson = buttParse.xmlToJson(xmlContent);
      $('#output-label').show();
      // check the format thing - big/crusade/normal
      const format = 'normal';
      var listHtml = '';
      switch(format) {
        case 'big':
          listHtml = buttRender.jsonToHTML(listJson, 'big');
          break;
        case 'crusade':
          listHtml = buttRender.jsonToHTML(listJson, 'crusade');
          break;
        default:
          listHtml = buttRender.jsonToHTML(listJson)
          break;
      }
      $('#output').html(listHtml);
      buttRender.HTMLtoPDF('output');
    }
    reader.readAsText(file);
  });
}