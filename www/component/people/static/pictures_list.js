function pictures_list(container, peoples, default_view) {
	if (typeof container == 'string') container = document.getElementById(container);
	if (!default_view) default_view = 'thumbnail';
	var t=this;
	
	this.setView = function(type) {
		while (this.content.childNodes.length > 0) this.content.removeChild(this.content.childNodes[0]);
		add_javascript("/static/people/pictures_list_"+type+".js", function() {
			for (var i = 0; i < peoples.length; ++i)
				new window["pictures_list_"+type](t.content, peoples[i]);
		});
	};
	
	this._init = function() {
		this.header = document.createElement("DIV");
		this.content = document.createElement("DIV");
		this.content.style.overflowY = "auto";
		container.appendChild(this.header);
		container.appendChild(this.content);
		require("vertical_layout.js", function() {
			t.content.setAttribute("layout", "fill");
			new vertical_layout(container);
		});
		theme.css('header_bar.css');
		this.header.className = "header_bar_toolbar_style";
	};
	this._init();
	this.setView(default_view);
}