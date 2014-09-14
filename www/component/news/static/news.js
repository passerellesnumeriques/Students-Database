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

function news(container, sections, exclude_sections, news_type, onready, onrefreshing) {
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
		if (!t) return;
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		service.json("news", "get_more", {type:news_type,olders:t._olders,olders_timestamp:t._olders_timestamp,sections:t._selected_sections,nb:10}, function(res) {
			if (!t) return;
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
		if (!t) return;
		if (t._latests.length == 0) { t.more(); return; }
		if (t.refresh_timeout) clearTimeout(t.refresh_timeout);
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		++t._refreshing;
		service.json("news", "get_latests", {type:news_type,latests:t._latests,latests_timestamp:t._latests_timestamp,sections:t._selected_sections}, function(res) {
			if (!t) return;
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
		if (!t) return;
		if (t._replies_to_load.length == 0) return;
		if (++t._refreshing == 1 && onrefreshing) onrefreshing(true);
		var list = t._replies_to_load;
		t._replies_to_load = [];
		var ids = [];
		for (var i = 0; i < list.length; ++i) ids.push(list[i].id);
		service.json("news", "get_replies", {ids:ids}, function(res) {
			if (!t) return;
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
			if (!t) return;
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
				var button = document.createElement("BUTTON");
				button.className = "action";
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
			new profile_picture(td_pc, 30, 40, "center", "top").loadPeopleObject(n.people);
		});
		var content = document.createElement("TD");
		content.className = "content";
		tr.appendChild(content);

		var header = document.createElement("DIV"); content.appendChild(header);
		var people_name = document.createElement("DIV"); header.appendChild(people_name);
		people_name.style.display = "inline-block";
		people_name.className = "author";
		people_name.appendChild(document.createTextNode(n.people.first_name+" "+n.people.last_name));
		people_name.style.cursor = "pointer";
		people_name.onclick = function() {
			window.top.popup_frame("/static/people/profile_16.png","Profile","/dynamic/people/page/profile?people="+n.people.id+"&domain="+n.user.domain,null,95,95);
		};
		var timing = document.createElement("DIV"); header.appendChild(timing);
		timing.style.display = "inline-block";
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
				var img = document.createElement("BUTTON");
				img.innerHTML = "<img src='/static/news/reply.png'/>";
				img.className = "flat small_icon";
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
							plugins: ["spellchecker paste textcolor emoticons"],
							menubar: false,
							statusbar: false,
							toolbar1: "bold italic underline strikethrough | bullist numlist outdent indent | forecolor backcolor | emoticons",
							toolbar2: "fontselect fontsizeselect | cut copy paste | undo redo",
							toolbar_items_size: 'small',
						    auto_focus: editor.id,
						    fontsize_formats: "8pt 9pt 10pt 12pt 14pt 18pt 24pt"
						});
						var button_post = document.createElement("BUTTON");
						button_post.className = "action";
						button_post.innerHTML = "Post Reply";
						div.appendChild(button_post);
						var button_cancel = document.createElement("BUTTON");
						button_cancel.className = "action red";
						button_cancel.innerHTML = "Cancel";
						div.appendChild(button_cancel);
						button_post.onclick = function() {
							var ed = tinymce.get(editor.id);
							var message = ed.getContent();
							ed.remove();
							div.removeChild(editor);
							div.removeChild(button_post);
							div.removeChild(button_cancel);
							var sending = document.createElement("IMG");
							sending.src = "/static/news/loading.gif";
							div.appendChild(sending);
							service.json("news", "post_reply", {id:n.id,message:message}, function(res) {
								div.removeChild(sending);
								t._refreshReplies();
							});
						};
						button_cancel.onclick = function() {
							var ed = tinymce.get(editor.id);
							ed.remove();
							div.removeChild(editor);
							div.removeChild(button_post);
							div.removeChild(button_cancel);
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
									s += "<div style='margin-top:2px'>";
									if (t.sections[i].categories[j].icon)
										s += "<img src='"+t.sections[i].categories[j].icon+"' style='vertical-align:bottom'/> ";
									s += "<i>"+t.sections[i].categories[j].display_name+"</i>";
									s += "</div>";
									break;
								}
							}
						}
						s += "</td>";
						if (t.sections[i].icon && !has_only_one_section) s += "<td valign=top style='padding:0px;padding-left:2px;'><img width='32px' height='32px' src='"+t.sections[i].icon+"'/></td>";
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
		
		div.ondomremoved(function() {
			div.news = null;
		});

		if (typeof animation != 'undefined') animation.fadeIn(div, 1500);
		layout.changed(container);
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
		if (!t) return;
		for (var i = 0; i < t._main_news.length; ++i) {
			t._refreshTiming(t._main_news[i]);
			for (var j = 0; j < t._main_news[i].reply_div.childNodes.length; ++j)
				t._refreshTiming(t._main_news[i].reply_div.childNodes[j]);
		}
	};
	this._refreshTiming = function(d) {
		d.timing_text.nodeValue = t._getTimingString(d.news.timestamp);
	};
	
	this.post = function(sections,categories,tags,note_for_user) {
		require(["tinymce.min.js","popup_window.js","select.js"], function() {
			var div = document.createElement("DIV");
			
			var header = document.createElement("DIV");
			header.style.padding = "3px";
			header.style.backgroundColor = "white";
			header.style.verticalAlign = "middle";
			div.appendChild(header);
			if (note_for_user) {
				if (note_for_user instanceof Element)
					header.appendChild(note_for_user);
				else {
					var note = document.createElement("DIV");
					note.appendChild(document.createTextNode(note_for_user));
					header.appendChild(note);
				}
			}
			var secs = [];
			if (typeof sections == 'string') sections = [sections];
			if (!sections) {
				for (var i = 0; i < t.sections.length; ++i) secs.push(t.sections[i]);
			} else for (var i = 0; i < sections.length; ++i)
				for (var j = 0; j < t.sections.length; ++j)
					if (t.sections[j].name == sections[i]) { secs.push(t.sections[j]); break; }
			// filter sections
			for (var i = 0; i < secs.length; ++i) {
				if (secs[i].can_write) continue;
				var ok = false;
				for (var j = 0; j < secs[i].categories.length; ++j) if (secs[i].categories[j].can_write) { ok = true; break; }
				if (ok) continue;
				secs.splice(i,1);
				i--;
			}
			if (secs.length == 0) {
				error_dialog("You don't have the right to post in any available section");
				return;
			}
			var select_section = null;
			var select_cat = null;
			var span_cat = document.createElement("SPAN");
			var cats = [];
			if (secs.length > 1) {
				header.appendChild(document.createTextNode(" Section "));
				select_section = new select(header);
				select_section.getHTMLElement().style.verticalAlign = "bottom";
				for (var i = 0; i < secs.length; ++i)
					select_section.add(secs[i], "<img src='"+secs[i].icon+"' style='vertical-align:bottom;width:16px;height:16px;'/> "+secs[i].display_name);
				select_section.onchange = function() {
					cats = [];
					for (var i = 0; i < select_section.value.categories.length; ++i) if (select_section.value.categories[i].can_write) cats.push(select_section.value.categories[i]);
					if (cats.length == 0) {
						span_cat.style.display = "none";
					} else {
						span_cat.style.display = "";
						if (select_section.value.can_write)
							select_cat.add(null,"No specific category");
						for (var i = 0; i < cats.length; ++i)
							select_cat.add(cats[i].name, "<img src='"+cats[i].icon+"' style='vertical-align:bottom'/> "+cats[i].display_name);
					}
				};
			} else {
				cats = [];
				if (!categories) {
					for (var i = 0; i < secs[0].categories.length; ++i) if (secs[0].categories[i].can_write) cats.push(secs[0].categories[i]);
				} else for (var i = 0; i < categories.length; ++i)
					for (var j = 0; j < secs[0].categories.length; ++j)
						if (secs[0].categories[j].name == categories[i]) {
							if (secs[0].categories[j].can_write) cats.push(secs[0].categories[j]);
							break;
						}
			}
			if (secs.length > 1 || cats.length > 0) {
				header.appendChild(span_cat);
				span_cat.appendChild(document.createTextNode(" Category "));
				select_cat = new select(span_cat);
				select_cat.getHTMLElement().style.verticalAlign = "bottom";
				if (secs.length > 1)
					span_cat.style.display = "none";
				else {
					span_cat.style.display = "";
					if (secs[0].can_write)
						select_cat.add(null,"No specific category");
					for (var i = 0; i < cats.length; ++i)
						select_cat.add(cats[i].name, "<img src='"+cats[i].icon+"' style='vertical-align:bottom'/> "+cats[i].display_name);
				}
			}
			var tags_cb = [];
			if (tags) {
				header.appendChild(document.createElement("BR"));
				header.appendChild(document.createTextNode("Related to "));
				for (var tag in tags) {
					var cb = document.createElement("INPUT");
					cb.type = "checkbox";
					cb.checked = "checked";
					cb.tag_name = tag;
					tags_cb.push(cb);
					header.appendChild(cb);
					header.appendChild(document.createTextNode(tags[tag]));
				}
			}
			
			var editor = document.createElement("DIV");
			editor.id = generateID();
			div.appendChild(editor);
			var popup = new popup_window("Post Message","/static/news/write_16.png",div);
			popup.addIconTextButton(theme.icons_16.ok,"Post",'post',function() {
				var data = {};
				if (secs.length > 1) {
					if (!select_section.value) { alert("Please select a section where to post your message"); return; }
					data.section = select_section.value.name;
				} else
					data.section = secs[0].name;
				if (cats.length == 0 || !select_cat.value)
					data.category = null;
				else
					data.category = select_cat.value;
				data.tags = [];
				for (var i = 0; i < tags_cb.length; ++i) if (tags_cb[i].checked) data.tags.push(tags_cb[i].tag_name);
				var ed = tinymce.get(editor.id);
				data.message = ed.getContent();
				data.type = news_type;
				popup.freeze("Posting message...");
				service.json("news", "post", data, function(res) {
					t.refresh();
					popup.close();
				});				
			});
			popup.show();
			tinymce.init({
				selector: "#"+editor.id,
				theme: "modern",
				width: 400,
				height: 150,
				resize: false,
				nowrap: false,
				content_css: "/static/theme/"+theme.name+"/style/global.css,/static/theme/"+theme.name+"/style/news.css",
				body_class: "news_editor",
				plugins: ["spellchecker paste textcolor emoticons"],
				menubar: false,
				statusbar: false,
				toolbar1: "bold italic underline strikethrough | bullist numlist outdent indent | forecolor backcolor | emoticons",
				toolbar2: "fontselect fontsizeselect | cut copy paste | undo redo",
				toolbar_items_size: 'small',
			    auto_focus: editor.id,
			    fontsize_formats: "8pt 9pt 10pt 12pt 14pt 18pt 24pt",
			    init_instance_callback: function() { div.childNodes[0].style.border = "none"; layout.changed(div); }
			});
		});

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
	
	container.ondomremoved(function() {
		t._main_news = null;
		t._latests = null;
		t._olders = null;
		t._replies_to_load = null;
		t._more_container = null;
		if (t.interval) clearInterval(t.interval);
		t = null;
	});
}