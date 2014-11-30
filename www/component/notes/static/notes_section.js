if (typeof theme != 'undefined') theme.css("notes.css");

function notes_section(attached_table, attached_key, sub_model, sub_model_instance, editable) {
	this.attached_table = attached_table;
	this.attached_key = attached_key;
	this.sub_model = sub_model;
	this.sub_model_instance;
	this.editable = editable;
}
notes_section.prototype = {
	createInsideSection: function(container, max_width, max_height, collapsable, css, collapsed) {
		var t=this;
		require("section.js", function() {
			var content = document.createElement("DIV");
			var sec = new section("/static/notes/notes_16.png", "Notes/Comments", content, collapsable, false, css, collapsed);
			if (max_width) {
				sec.element.style.maxWidth = max_width;
				sec.element.style.overflow = "auto";
			}
			if (max_height) {
				sec.element.style.maxHeight = max_height;
				sec.element.style.overflow = "auto";
			}
			if (t.editable) {
				var button = document.createElement("BUTTON");
				button.innerHTML = "<img src='"+theme.icons_16.pen+"'/> New";
				sec.addToolRight(button);
				button.onclick = function() { t.writeNew(); };
			}
			container.appendChild(sec.element);
			t.create(content);
		});
	},
	create: function(container) {
		theme.css("notes.css");
		this.container = container;
		if (this.attached_key > 0) {
			var loading = document.createElement("IMG");
			loading.src = theme.icons_16.loading;
			container.appendChild(loading);
			layout.changed(container);
			var t=this;
			service.json("notes","get",{table:this.attached_table,key:this.attached_key,sub_model:this.sub_model,sub_model_instance:this.sub_model_instance},function(res) {
				if (!res) {
					loading.src = theme.icons_16.error;
					return;
				}
				container.removeChild(loading);
				for (var i = 0; i < res.notes.length; ++i)
					t._addNote(res.notes[i]);
			});
		}
	},
	writeNew: function() {
		var note = {id:-1,title:"",text:"",author:window.top.my_people,timestamp:Math.floor(new Date().getTime()/1000)};
		this._addEditNote(note,this.container.firstChild,true);
	},
	_addNote: function(note,before) {
		var div = document.createElement("DIV");
		div.className = "notes";
		var header = document.createElement("DIV");
		header.className = "header";
		div.appendChild(header);
		var title = document.createElement("DIV");
		title.className = "title";
		title.appendChild(document.createTextNode(note.title));
		header.appendChild(title);
		var author_time = document.createElement("DIV");
		author_time.className = "author_time";
		header.appendChild(author_time);
		var author = document.createElement("DIV");
		author.className = "author";
		author.appendChild(document.createTextNode("Last edited by "));
		var author_link = document.createElement("A");
		author_link.appendChild(document.createTextNode(note.author.first_name+' '+note.author.last_name));
		author_link.href = "#";
		author_link.onclick = function() { window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+note.author.id,null,95,95); return false; };
		author.appendChild(author_link);
		author_time.appendChild(author);
		var time = document.createElement("DIV");
		time.className = "time";
		author_time.appendChild(time);
		var d = new Date(note.timestamp*1000);
		time.appendChild(document.createTextNode("on "+getDateString(d,true)+" at "+getTimeString(d,true)));
		var content = document.createElement("DIV");
		content.className = "content";
		content.innerHTML = note.text;
		div.appendChild(content);
		if (before) this.container.insertBefore(div, before);
		else this.container.appendChild(div);
	},
	_addEditNote: function(note,before,is_new) {
		var div = document.createElement("DIV");
		div.className = "notes_edit";
		var title_div = document.createElement("DIV");
		div.appendChild(title_div);
		title_div.appendChild(document.createTextNode("Title "));
		var title_input = document.createElement("INPUT");
		title_input.type = "text";
		title_input.maxLength = 100;
		title_input.value = note.title;
		title_input.size = 30;
		title_div.appendChild(title_input);
		var text_div = document.createElement("DIV");
		text_div.appendChild(document.createTextNode("Text:"));
		div.appendChild(text_div);
		if (!before) this.container.appendChild(div);
		else this.container.insertBefore(div, before);
		var t=this;
		require("tinymce.min.js", function() {
			var editor = document.createElement("DIV");
			editor.id = generateID();
			text_div.appendChild(editor);
			tinymce.init({
				selector: "#"+editor.id,
				theme: "modern",
				height: 60,
				content_css: "/static/theme/"+theme.name+"/style/global.css,/static/theme/"+theme.name+"/style/notes.css",
				body_class: "notes_editor",
				plugins: ["spellchecker paste textcolor emoticons"],
				menubar: false,
				statusbar: false,
				toolbar1: "bold italic underline strikethrough | bullist numlist outdent indent | forecolor backcolor | emoticons",
				toolbar2: "fontselect fontsizeselect | cut copy paste | undo redo",
				toolbar_items_size: 'small',
			    auto_focus: editor.id,
			    fontsize_formats: "8pt 9pt 10pt 12pt 14pt 18pt 24pt",
			    init_instance_callback : function(editor) {
			    	layout.changed(text_div);
			    }
			});
			var button_post = document.createElement("BUTTON");
			button_post.className = "action";
			button_post.innerHTML = "Save";
			text_div.appendChild(button_post);
			var button_cancel = document.createElement("BUTTON");
			button_cancel.className = "action red";
			button_cancel.innerHTML = "Cancel";
			text_div.appendChild(button_cancel);
			button_post.onclick = function() {
				var ed = tinymce.get(editor.id);
				note.text = ed.getContent();
				ed.remove();
				note.title = title_input.value;
				t._saveNote(note, function() {
					t._addNote(note, div);
					t.container.removeChild(div);
				});
			};
			button_cancel.onclick = function() {
				var ed = tinymce.get(editor.id);
				ed.remove();
				if (!is_new)
					t._addNote(note, div);
				t.container.removeChild(div);
			};
			layout.changed(text_div);
		});
		layout.changed(this.container);
	},
	_saveNote: function(note, onsaved) {
		// TODO
		onsaved();
	},
	save: function(attached_key) {
		if (!this.container.parentNode) return; // we have been removed already
		// TODO
	}
};