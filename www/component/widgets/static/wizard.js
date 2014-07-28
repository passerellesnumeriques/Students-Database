if (typeof require != 'undefined')
	require("popup_window.js");
if (typeof theme != 'undefined')
	theme.css("wizard.css");

function wizard(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t = this;

	t.icon = null;
	t.title = "Wizard";
	t.pages = [];
	t.current_page = 0;
	t.element = document.createElement("TABLE");
	t.element.className = "wizard_table";
	t.element.data = t;
	t.popup = null;
	
	t.launch = function() {
		require("popup_window.js",function() {
			t.popup = new popup_window(t.title, t.icon, t.element);
			t.popup.show();
			t.showPage(0);
		});
	};
	t.createInPage = function(wizard_container) {
		wizard_container.appendChild(t.element);
	};
	t.showPage = function(index) {
		t.page_container.removeAllChildren();
		t.page_icon.src = t.pages[index].icon;
		t.page_title_td.innerHTML = t.pages[index].title;
		t.page_container.appendChild(t.pages[index].content);
		t.current_page = index;
		t.validate();
		t._refresh_buttons();
		if (t.pages[index].onshown) t.pages[index].onshow(t,t.pages[index]);
		layout.invalidate(t.page_container);
	};
	t.validate = function() {
		var p = t.pages[t.current_page];
		if (p.validate) p.validate(t,function(ok){p.valid=ok;t._refresh_buttons();});
	};
	t._refresh_buttons = function() {
		if (t.current_page == -1) return;
		var ok = t.pages[t.current_page].valid;
		t.previousButton.disabled = (t.current_page > 0 && ok ? "" : "disabled");
		t.nextButton.disabled = (t.current_page < t.pages.length-1 && ok ? "" : "disabled");
		ok = true;
		for (var i = 0; i < t.pages.length; ++i)
			ok &= t.pages[i].valid;
		t.finishButton.disabled = ok ? "" : "disabled";
	};
	
	t.previous = function() {
		if (t.current_page == 0) return;
		t.showPage(t.current_page-1);
	};
	t.next = function() {
		if (t.current_page == t.pages.length-1) return;
		t.showPage(t.current_page+1);
	};
	t.finish = function() {
		t.popup.close(t.keep_on_close);
		if (t.onfinish) t.onfinish(t);
	};
	
	if (container) {
		t.keep_on_close = true;
		if (container.data) {
			// wizard already loaded
			t.icon = container.data.icon;
			t.title = container.data.title;
			t.pages = container.data.pages;
			t.onfinish = container.data.onfinish;
		} else {
			// load from elements on page
			t.icon = container.getAttribute("icon"); container.removeAttribute("icon");
			t.title = container.getAttribute("title"); container.removeAttribute("title");
			if (container.hasAttribute("finish")) {
				t.onfinish = eval("("+container.getAttribute("finish")+")");
				container.removeAttribute("finish");
			}
			for (var i = 0; i < container.childNodes.length; ++i) {
				var e = container.childNodes[i];
				if (e.nodeType != 1) continue;
				if (e.className != "wizard_page") continue;
				var icon = e.getAttribute("icon"); e.removeAttribute("icon");
				var title = e.getAttribute("title"); e.removeAttribute("title");
				var validate = e.getAttribute("validate"); e.removeAttribute("validate");
				if (validate) validate = eval("("+validate+")");
				var page = {
					icon: icon,
					title: title,
					content: e,
					validate: validate,
					valid: false,
					_init_validation: function() {
						var th = this;
						if (!th.validate)
							th.valid = true;
						else
							th.validate(t,function(v){th.valid=v;t._refresh_buttons();});
					}
				};
				page._init_validation();
				t.pages.push(page);
			}
			container.data = t;
		}
	}
	
	t.addPage = function(page) {
		page.valid = false;
		page._init_validation = function() {
			var th = this;
			if (!th.validate)
				th.valid = true;
			else
				th.validate(t,function(v){th.valid=v;t._refresh_buttons();});
		};
		page._init_validation();
		t.pages.push(page);
		t._refresh_buttons();
	};
	t.removePagesFrom = function(index) {
		if (t.pages.length > index)
			t.pages.splice(index,t.pages.length-index);
		t.validate();
		t._refresh_buttons();
	};
	t.removePage = function(index) {
		t.pages.splice(index,1);
		if (t.current_page == index) {
			t.showPage(index < t.pages.length ? index : index-1);
		} else if (t.current_page > index) {
			t.current_page--;
		}
		t.validate();
		t._refresh_buttons();
	};
	
	t._createTable = function() {
		var tr = document.createElement("TR"); t.element.appendChild(tr);
		tr.className = "wizard_header";
		t.page_icon_td = document.createElement("TD"); tr.appendChild(t.page_icon_td);
		t.page_icon_td.style.width = "35px";
		t.page_icon = document.createElement("IMG"); t.page_icon_td.appendChild(t.page_icon);
		t.page_title_td = document.createElement("TD"); tr.appendChild(t.page_title_td);
		tr = document.createElement("TR"); t.element.appendChild(tr);
		tr.className = "wizard_content";
		t.page_container_td = document.createElement("TD"); tr.appendChild(t.page_container_td);
		t.page_container_td.colSpan = 2;
		t.page_container = document.createElement("DIV");
		t.page_container.style.overflow = "auto";
		t.page_container_td.appendChild(t.page_container);
		tr = document.createElement("TR"); t.element.appendChild(tr);
		tr.className = "wizard_buttons";
		var td = document.createElement("TD"); tr.appendChild(td);
		td.colSpan = 2;
		t.previousButton = document.createElement("BUTTON"); td.appendChild(t.previousButton);
		t.previousButton.innerHTML = "<img src='"+theme.icons_16.back+"'/> Previous";
		t.previousButton.onclick = function() { t.previous(); };
		t.nextButton = document.createElement("BUTTON"); td.appendChild(t.nextButton);
		t.nextButton.innerHTML = "<img src='"+theme.icons_16.forward+"'/> Next";
		t.nextButton.onclick = function() { t.next(); };
		t.finishButton = document.createElement("BUTTON"); td.appendChild(t.finishButton);
		t.finishButton.innerHTML = "<img src='"+theme.icons_16.ok+"'/> Ok";
		t.finishButton.onclick = function() { t.finish(); };
		t.cancelButton = document.createElement("BUTTON"); td.appendChild(t.cancelButton);
		t.cancelButton.innerHTML = "<img src='"+theme.icons_16.close+"'/> Cancel";
		t.cancelButton.onclick = function() { t.popup.close(); };
	};
	t._createTable();
}

function get_wizard_from_element(e) {
	while (e.parentNode.className != 'wizard_table') e = e.parentNode;
	return e.parentNode.data;
}
function wizard_validate(e) {
	get_wizard_from_element(e).validate();
}