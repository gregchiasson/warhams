$(document).ready(bind);

/*
TODOs:
add remove profiles
add the image upload/preview thing
big ass css changes
*/
PROFILES = {
  'guns': {},
  'fists': {},
  'dudes': {},
  'abilities': {}
};

function bind() {
  $('#custom-download').hide();

  $(".add-profile").click((e) => {
    const profileType = e.target.id.replace('add-profile-', ''); 
    let profile = {};
    const profileFields = {
      'dudes': ['M', 'T', 'SV', 'iSV', 'W', 'LD', 'OC'],
      'guns':  ['Range', 'A', 'BS', 'S', 'AP', 'D', 'Keywords'],
      'fists': ['Range', 'A', 'WS', 'S', 'AP', 'D', 'Keywords'],
    }
    const profileName = $(`#profile_${profileType}_name`).val() || 'whatever';
    if(profileType == 'abilities') {
      profile = $(`#profile_${profileType}_description`).val()
    } else {
      profileFields[profileType].forEach((field) => {
        profile[field] = $(`#profile_${profileType}_${field}`).val();
      });
    }

    PROFILES[profileType][profileName] = profile;
    console.log(PROFILES);

    var html = ''
    Object.keys(PROFILES[profileType]).forEach((profile) => {
      html += `${profile} <input id="remove-profile-${profileType}" class="delete-profile" name="${profile}" type="button" value="-" />`;
    });
    $(`#profiles-${profileType}`).html(html);
  });

  $(document).on('click', '.delete-profile', (e) => {
    console.log(e.target);
    const profileType = e.target.id.replace('remove-profile-', ''); 
    const profileName = $(e.target).attr('name');
    console.log(profileType);
    console.log(profileName);
    delete PROFILES[profileType][profileName];

    var html = ''
    Object.keys(PROFILES[profileType]).forEach((profile) => {
      html += `${profile} <input id="remove-profile-${profileType}" class="delete-profile" name="${profile}" type="button" value="-" />`;
    });
    $(`#profiles-${profileType}`).html(html);
  });

  $("#custom-card").click((e) => {
    $('#custom-download').hide();

    const unitJSON = {
      sheet: $('#custom_sheet').val() || 'johnny bad ass',
      abilities: PROFILES['abilities'],
      keywords: ($('#custom_keywords').val() || "big, dude, hell yeah").split(','),
      factionKeywords: ($('#custom_faction').val() || 'dudes, rock').split(','),
      models: ($('#custom_models').val() || '1 model').split(','),
      points: $('#custom_points').val() || 69,
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
    buttRender.HTMLtoPDF('output');
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