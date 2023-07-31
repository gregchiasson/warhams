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
      const listJson = buttParse.xmlToJson(xmlContent);
      $('#output-label').show();
      $('#output').html(buttRender.jsonToHTML(listJson));
      buttRender.HTMLtoPDF('output');
    }
    reader.readAsText(file);
  });
}