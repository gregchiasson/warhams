$(document).ready(bind);

/*
TODOs:
add rules
add abilities
add remove profiles
add the image upload/preview thing
big ass css changes
*/
PROFILES = {
  'guns': {},
  'fists': {},
  'dudes': {}
};

function bind() {
  $(".add-profile").click((e) => {
    const profileType = e.target.id.replace('add-profile-', ''); 
    let profile = {};
    const profileFields = {
      'dudes': ['M', 'T', 'SV', 'iSV', 'W', 'LD', 'OC'],
      'guns':  ['Range', 'A', 'BS', 'S', 'AP', 'D', 'Keywords'],
      'fists': ['Range', 'A', 'WS', 'S', 'AP', 'D', 'Keywords']
    }
    const profileName = $(`#profile_${profileType}_name`).val() || 'whatever';
    profileFields[profileType].forEach((field) => {
      profile[field] = $(`#profile_${profileType}_${field}`).val();
    });

    PROFILES[profileType][profileName] = profile;
    console.log(PROFILES);
    $(`#profiles-${profileType}`).html(JSON.stringify(PROFILES[profileType], null, 2));
  }),
  $("#custom-card").click((e) => {
    const unitJSON = {
      sheet: $('#custom_sheet').val() || 'johnny bad ass',
      abilities: {"name": 'description'}, // TODO name and description in expandable list
      keywords: ($('#custom_keywords').val() || "big, dude, hell yeah").split(','),
      factionKeywords: ($('#custom_faction').val() || 'dudes, rock').split(','),
      models: ($('#custom_models').val() || '1 model').split(','),
      points: $('#custom_points').val() || 69,
      profiles: PROFILES['dudes'],
      rules: {"lone op": "text", "leader": "sure", "somthing": ""}, // TODO text field (big) "lone op, leader, something else"
      wargear: {}, // SKIP
      weapons: {
        ranged: PROFILES['guns'],
        melee:  PROFILES['fists']
      },
      leader: $('#custom_leader').val().split(',') || null,
      specialism: $('#custom_specialism').val() || 'being a dick'
    };

    unitHTML = buttRender.renderUnitCustom(unitJSON, false);
    $('#output').html(unitHTML);
    //buttRender.HTMLtoPDF('output');
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