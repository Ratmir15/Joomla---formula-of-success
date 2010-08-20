tinyMCEPopup.requireLangPack();

var PasteTextDialog = {
	init : function() {
		this.resize();
		
		document.getElementById('linebreaks').checked = tinyMCEPopup.getParam('paste_keep_linebreaks', true);
	},

	insert : function() {
		var h = tinyMCEPopup.dom.encode(document.getElementById('content').value), lines;

		// Convert linebreaks into paragraphs
		if (document.getElementById('linebreaks').checked) {
			lines = h.split(/\r?\n/);
			if (lines.length > 1) {
				h = '';
				tinymce.each(lines, function(row) {
					if(tinyMCEPopup.getParam('force_p_newlines')){
						h += '<p>' + row + '</p>';
					}else{
						h += row + '<br />';
					}
				});
			}
		}

		tinyMCEPopup.editor.execCommand('mceInsertClipboardContent', false, h);
		tinyMCEPopup.close();
	},

	resize : function() {
		var vp = tinyMCEPopup.dom.getViewPort(window), el;

		el = document.getElementById('content');

		el.style.width  = (vp.w - 20) + 'px';
		el.style.height = (vp.h - 90) + 'px';
	}
};

tinyMCEPopup.onInit.add(PasteTextDialog.init, PasteTextDialog);
