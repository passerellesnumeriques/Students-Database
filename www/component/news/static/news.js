function NewsObject(id, section, category, html, people, user, timestamp, update_timestamp) {
	this.id = id;
	this.section = section;
	this.category = category;
	this.html = html;
	this.people = people;
	this.user = user;
	this.timestamp = timestamp;
	this.update_timestamp;
}

if (typeof require != 'undefined') require("animation.js");
if (typeof theme != 'undefined') theme.css("news.css");

function news(container, sections, exclude_sections, onready, onrefreshing) {
	if (typeof container == 'string') container = document.getElementById(container);
	var t=this;
	this._main_news = [];
	this._latests = [];
	this._latests_timestamp = 0;
	this._olders = [];
	this._olders_timestamp = 0;
	this._refreshing = 0;
	this._replies_to_load = [];
	this.more = function(ondone) {
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		service.json("news", "get_more", {olders:t._olders,olders_timestamp:t._olders_timestamp,sections:t._selected_sections,nb:10}, function(res) {
			if (--t._refreshing == 0 && onrefreshing) onrefreshing(false);
			if (!res) { if (ondone) ondone(false); return; }
			if (res.length == 0) { if (ondone) ondone(false); return; }
			if (t._latests.length == 0) {
				// first time we have news
				t._latests = [res[0].id];
				t._latests_timestamp = res[0].update_timestamp;
				t._olders = [res[res.length-1].id];
				t._olders_timestamp = res[res.length-1].update_timestamp;
				for (var i = 1; i < res.length; ++i)
					if (res[i].update_timestamp == t._latests_timestamp) {
						t._latests.push(res[i].id);
						if (i == res.length - 1) {
							// all have latest, we should show more
							if (t.refresh_timeout) clearTimeout(t.refresh_timeout);
							t.refresh_timeout = setTimeout(t.refresh, 1000);
						}
					} else
						break;
				for (var i = res.length-2; i >= 0; --i)
					if (res[i].update_timestamp == t._olders_timestamp)
						t._olders.push(res[i].id);
					else
						break;
				t.current_interval = 0;
				for (var i = res.length-1; i >= 0; --i)
					t._createNews(res[i]);
			} else {
				if (res[res.length-1].update_timestamp < t._olders_timestamp) {
					t._olders = [res[res.length-1].id];
					t._olders_timestamp = res[res.length-1].update_timestamp;
				} else if (res[res.length-1].update_timestamp == t._olders_timestamp) {
					t._olders.push(res[res.length-1].id);
				}
				for (var i = res.length-2; i >= 0; --i)
					if (res[i].update_timestamp == t._olders_timestamp)
						t._olders.push(res[i].id);
					else
						break;
				for (var i = 0; i < res.length; ++i) {
					if (res[i].update_timestamp == t._latests_timestamp && !t._latests.contains(res[i].id))
						t._latests.push(res[i].id);
					if (res[i].update_timestamp == t._olders_timestamp && !t._olders.contains(res[i].id))
						t._olders.push(res[i].id);
					t._createNews(res[i]);
				}
			}
			t._launchRepliesLoading();
			if (ondone) ondone(res.length == 10);
		});
	};
	this.refresh = function() {
		if (t._latests.length == 0) { t.more(); return; }
		if (t.refresh_timeout) clearTimeout(t.refresh_timeout);
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		++t._refreshing;
		service.json("news", "get_latests", {latests:t._latests,latests_timestamp:t._latests_timestamp,sections:t._selected_sections}, function(res) {
			if (--t._refreshing == 0 && onrefreshing) onrefreshing(false);
			t.refresh_timout = setTimeout(t.refresh, 30000);
			if (!res) return;
			if (res.length == 0) return;
			if (res[0].update_timestamp > t._latests_timestamp) {
				t._latests = [res[0].id];
				t._latests_timestamp = res[0].update_timestamp;
			} else if (res[0].update_timestamp == t._latests_timestamp) {
				t._latests.push(res[0].id);
			}
			for (var i = 1; i < res.length; ++i)
				if (res[i].update_timestamp == t._latests_timestamp) {
					t._latests.push(res[i].id);
					if (i == res.length-1) {
						// all have the same, we should retrieve more
						clearTimeout(t.refresh_timeout);
						t.refresh_timeout = setTimeout(t.refresh, 1000);
					}
				} else
					break;
			t.current_interval = 0;
			for (var i = res.length-1; i >= 0; --i) {
				if (res[i].update_timestamp == t._olders_timestamp && !t._olders.contains(res[i].id))
					t._olders.push(res[i].id);
				t._createNews(res[i]);
			}
			t._launchRepliesLoading();
		});
		t._refreshReplies();
	};
	this._launchRepliesLoading = function() {
		if (t._replies_to_load.length == 0) return;
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		var list = t._replies_to_load;
		t._replies_to_load = [];
		var ids = [];
		for (var i = 0; i < list.length; ++i) ids.push(list[i].id);
		service.json("news", "get_replies", {ids:ids}, function(res) {
			if (--t._refreshing == 0 && onrefreshing) onrefreshing(false);
			if (!res) return;
			if (res.length == 0) return;
			for (var i = 0; i < res.length; ++i)
				t._createReply(res[i]);
		});		
	};
	this._refreshReplies = function() {
		var to_refresh = [];
		for (var i = 0; i < t._main_news.length; ++i) {
			var div = t._main_news[i];
			to_refresh.push({id:div.news.id,latest:div.latest_reply});
		}
		service.json("news", "get_latests_replies", {to_refresh:to_refresh}, function(res) {
			if (--t._refreshing == 0 && onrefreshing) onrefreshing(false);
			if (!res) return;
			if (res.length == 0) return;
			for (var i = 0; i < res.length; ++i)
				t._createReply(res[i]);
		});
	};
	this.more_news = function(initial_internal_call) {
		while (t._more_container.childNodes.length > 0) t._more_container.removeChild(t._more_container.childNodes[0]);
		if (!initial_internal_call) {
			var loading = document.createElement("IMG");
			loading.src = "/static/news/loading.gif";
			t._more_container.appendChild(loading);
		}
		t.more(function(has_more) {
			while (t._more_container.childNodes.length > 0) t._more_container.removeChild(t._more_container.childNodes[0]);
			if (has_more) {
				var button = document.createElement("SPAN");
				button.className = "button";
				button.appendChild(document.createTextNode("Show More"));
				t._more_container.appendChild(button);
				button.onclick = function() {
					t.more_news(false);
				};
			}
		});
	};
	
	this._createNews = function(n) {
		for (var i = 0; i < this._main_news.length; ++i)
			if (this._main_news[i].news.id == n.id) {
				container.removeChild(this._main_news[i]);
				this._main_news.splice(i,1);
				break;
			}
		div = this._createDiv(n, true);
		this._main_news.push(div);
		for (var i = 0; i < container.childNodes.length; ++i) {
			var d = container.childNodes[i];
			if (!d.news) {
				container.insertBefore(div, d);
				break;
			}
			if (d.news.update_timestamp < n.update_timestamp) {
				container.insertBefore(div, d);
				break;
			}
			if (d.news.update_timestamp == n.update_timestamp && d.news.id > n.id) {
				container.insertBefore(div, d);
				break;
			}
		}
		this._replies_to_load.push(n);
	};
	this._createReply = function(n) {
		var main = null;
		for (var i = 0; i < this._main_news.length; ++i)
			if (this._main_news[i].news.id == n.reply_to) { main = this._main_news[i]; break; }
		if (main == null) return;
		main.latest_reply = n.timestamp;
		var div = this._createDiv(n, false);
		main.reply_div.appendChild(div);
	};
	this._createDiv = function(n, main) {
		var div = document.createElement("DIV");
		div.news = n;
		div.latest_reply = 0;
		div.className = "news"+(main ? " main": " reply");
		var table = document.createElement("TABLE"); div.appendChild(table);
		table.style.width = "100%";
		table.style.borderCollapse = 'collapse';
		table.style.borderSpacing = '0px';
		var tr = document.createElement("TR"); table.appendChild(tr);
		var td = document.createElement("TD"); tr.appendChild(td);
		td.className = "picture_container";
		var td_pc = td;
		require("profile_picture.js", function() {
			new profile_picture(td_pc, null, n.user.domain, n.user.username, 30, 40, "center", "top");
		});
		var content = document.createElement("TD");
		content.className = "content";
		tr.appendChild(content);

		var header = document.createElement("DIV"); content.appendChild(header);
		var people_name = document.createElement("SPAN"); header.appendChild(people_name);
		people_name.className = "author";
		people_name.appendChild(document.createTextNode(n.people.first_name+" "+n.people.last_name));
		people_name.style.cursor = "pointer";
		people_name.onclick = function() { location.href = "/dynamic/people/page/profile?people="+n.people.id+"&domain="+n.user.domain; };
		var timing = document.createElement("SPAN"); header.appendChild(timing);
		timing.className = "time";
		timing.appendChild(div.timing_text = document.createTextNode(t._getTimingString(n.timestamp)));
		if (main) {
			var can_reply = null;
			for (var i = 0; i < t.sections.length; ++i) {
				if (t.sections[i].name == n.section) {
					can_reply = t.sections[i].can_write;
					break;
				}
			}
			if (can_reply) {
				var img = document.createElement("IMG");
				img.src = "/static/news/reply.png";
				img.className = "button_verysoft";
				img.style.marginLeft = "5px";
				img.style.verticalAlign = "bottom";
				header.appendChild(img);
				img.title = "Reply to this message";
				img.onclick = function() {
					require("tinymce.min.js", function() {
						var editor = document.createElement("DIV");
						editor.id = generateID();
						div.appendChild(editor);
						tinymce.init({
							selector: "#"+editor.id,
							theme: "modern",
							height: 60,
							content_css: "/static/theme/"+theme.name+"/style/global.css,/static/theme/"+theme.name+"/style/news.css",
							body_class: "news_editor",
							plugins: ["spellchecker paste textcolor"],
							menubar: false,
							statusbar: false,
							toolbar1: "bold italic underline strikethrough | bullist numlist outdent indent | forecolor backcolor",
							toolbar2: "fontselect fontsizeselect | cut copy paste | undo redo",
							toolbar_items_size: 'small',
						    auto_focus: editor.id,
						    fontsize_formats: "8pt 9pt 10pt 12pt 14pt 18pt 24pt"
						});
						var button = document.createElement("DIV");
						button.className = "button";
						button.innerHTML = "Post Reply";
						div.appendChild(button);
						button.onclick = function() {
							var ed = tinymce.get(editor.id);
							var message = ed.getContent();
							ed.remove();
							div.removeChild(editor);
							div.removeChild(button);
							var sending = document.createElement("IMG");
							sending.src = "/static/news/loading.gif";
							div.appendChild(sending);
							service.json("news", "post_reply", {id:n.id,message:message}, function(res) {
								div.removeChild(sending);
								t._refreshReplies();
							});
						};
					});
				};
			}
		}
		
		var msg = document.createElement("DIV"); content.appendChild(msg);
		msg.className = "message";
		msg.innerHTML = n.html;

		if (main) {
			var has_only_one_section = sections.length == 1 && t._selected_sections.length == 1;
			var has_only_one_category = has_only_one_section && t._selected_sections[0].categories.length < 2;
			if (!has_only_one_category) {
				td = document.createElement("TD"); tr.appendChild(td);
				td.style.textAlign = "right";
				td.style.verticalAlign = "top";
				td.style.padding = '0px';
				var comp_div = document.createElement("DIV"); td.appendChild(comp_div);
				comp_div.style.textAlign = "right";
				comp_div.style.whiteSpace = "nowrap";
				comp_div.style.display = "inline-block";
				var s = "";
				for (var i = 0; i < t.sections.length; ++i) {
					if (t.sections[i].name == n.section) {
						s += "<table style='border-collapse:collapse;border-spacing:0px;margin-right:1px'><tr>";
						s += "<td valign=top style='padding:0px'>";
						if (!has_only_one_section) s += "<b>"+t.sections[i].display_name+"</b>";
						if (n.category) {
							for (var j = 0; j < t.sections[i].categories.length; ++j) {
								if (t.sections[i].categories[j].name == n.category) {
									s += "<br/>";
									if (t.sections[i].categories[j].icon)
										s += "<img src='"+t.sections[i].categories[j].icon+"' style='vertical-align:bottom'/> ";
									s += "<i>"+t.sections[i].categories[j].display_name+"</i>";
									break;
								}
							}
						}
						s += "</td>";
						if (t.sections[i].icon && !has_only_one_section) s += "<td valign=top style='padding:0px'><img width='32px' height='32px' src='"+t.sections[i].icon+"'/></td>";
						s += "</tr></table>";
						break;
					}
				}
				comp_div.innerHTML = s;
			}
			
			div.reply_div = document.createElement("DIV");
			content.appendChild(div.reply_div);
			// TODO add reply button/section
		}

		if (typeof animation != 'undefined') animation.fadeIn(div, 1500);
		layout.invalidate(container);
		return div;
	};
	
	this._getTimingString = function(timestamp) {
		var d = new Date(timestamp*1000);
		var now = new Date();
		var seconds = (now.getTime()-d.getTime())/1000;
		
		var i;
		if (seconds < 60) i = 500;
		else if (seconds < 60*60) i = 10000;
		else i = 60000;
		if (t.current_interval == 0 || t.current_interval > i) {
			t.current_interval = i;
			if (t.interval) clearInterval(t.interval);
			t.interval = setInterval(t._refreshTimings, i);
		}
		
		if (seconds < 2) return "one second ago";
		if (seconds < 60) return Math.floor(seconds)+" seconds ago";
		var minutes = seconds/60;
		if (minutes < 2) return "one minute ago";
		if (minutes < 60) return Math.floor(minutes)+" minutes ago";
		var hours = minutes / 60;
		if (hours < 2) return "one hour ago";
		if (hours < 24) return Math.floor(hours)+" hours ago (at "+d.toLocaleTimeString()+")";
		var days = hours/24;
		if (days < 2) return "yesterday at "+d.toLocaleTimeString();
		return d.toLocaleString();
	};
	
	this._refreshTimings = function() {
		for (var i = 0; i < t._main_news.length; ++i) {
			t._refreshTiming(t._main_news[i]);
			for (var j = 0; j < t._main_news[i].reply_div.childNodes.length; ++j)
				t._refreshTiming(t._main_news[i].reply_div.childNodes[j]);
		}
	};
	this._refreshTiming = function(d) {
		d.timing_text.nodeValue = t._getTimingString(d.news.timestamp);
	};
	
	this._init = function() {
		t._more_container = document.createElement("DIV");
		container.appendChild(t._more_container);
		t._more_container.style.textAlign = "center";
		t.current_interval = 0;
		t.interval = null;
		var s = [];
		for (var i = 0; i < sections.length; ++i) {
			s.push({name:sections[i].name,categories:sections[i].categories});
		}
		service.json('news', 'get_infos', {sections:s,exclude:exclude_sections}, function(res) {
			if (!res) return;
			t.sections = res;
			t._selected_sections = [];
			for (var i = 0; i < t.sections.length; ++i) {
				var s = {name:t.sections[i].name,categories:[]};
				for (var j = 0; j < sections.length; ++j)
					if (sections[j].name == s.name) { s.tags = sections[i].tags; break; }
				for (var j = 0; j < t.sections[i].categories.length; ++j)
					s.categories.push(t.sections[i].categories[j].name);
				t._selected_sections.push(s);
			}
			t.more_news(true);
			t.refresh_timeout = setTimeout(t.refresh, 30000);
			if (onready) onready(t);
		});
	};
	
	this._init();
}