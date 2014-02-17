function diagram_display_manager(container){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	container.style.position = "relative";
	//TEMP
	container.style.border = "1px solid";
	t.table = document.createElement("table");
	
	t.nodes = [];//If first node, means start node
		//if last node, means ending node
	
	t.createStartNode = function(title, content, id){
		//Add at the begining
		var nodes = t.nodes;
		t.nodes = [];
		t.nodes[0] = {id:id,title:title,content:content};
		for( var i = 0; i < nodes.length; i++){
			t.nodes[i+1] = nodes[i];
		} 
	};
	
	t.createEndNode = function(title, content, id){
		//Add at the end
		t.createChildNode(title, content, id);
	};
	
	t.createChildNode = function(title, content, id){
		if(t.nodes.length == 1 ||t.nodes.length == 0)//just push at the end
			t.nodes[t.nodes.length] = {id:id,title:title,content:content};
		else {
			var end = t.nodes[t.nodes.length -1];
			var nodes = t.nodes;
			t.nodes = [];
			for(var i = 0; i < nodes.length-1; i++)
				t.nodes[i] = nodes[i];
			t.nodes.push({id:id,title:title,content:content});
			t.nodes.push(end);
		}
		
		
	};
	
	t.removeChildNode = function(id){
		var node = document.getElementById("diagram_node"+id);
		container.removeChild(node);
	};
	
	t._resetLayout = function(){
		while(container.firstChild)
			container.removeChild(container.fisrtChild);
		t._setLayout();
	};
	
	t.show = function(){
		t._setLayout();
		container.appendChild(t.table);
	};
	
	t._setLayout = function(){
		if(t.nodes.length == 1){
			//So starting = ending node
			//TODO
		} else {
			for(var i = 0; i < t.nodes.length-1; i++){
				var tr = document.createElement("tr");
				var td2 = document.createElement('td');
				if(i == 0){
					var td1 = document.createElement('td');
					var td3 = document.createElement('td');
					var first = t._createNode(t.nodes[0].title, t.nodes[0].id, t.nodes[0].content);
					var end = t._createNode(t.nodes[t.nodes.length-1].title, t.nodes[t.nodes.length-1].id, t.nodes[t.nodes.length-1].content);
					td1.appendChild(first);
					td3.appendChild(end);
					td1.rowSpan = t.nodes.length-1;
					td3.rowSpan = t.nodes.length-1;
					tr.appendChild(td1);
					tr.appendChild(td2);
					tr.appendChild(td3);
				} else {
					td2.appendChild(t._createNode(t.nodes[i].title, t.nodes[i].id, t.nodes[i].content));
					tr.appendChild(td2);
				}				
				t.table.appendChild(tr);
			}
//			for(var i = 0; i < t.nodes.length; i++){
//				if(i != t.nodes.length-1){
//					var tr = document.createElement("tr");
//					t.table.appendChild(tr);
//				}
//				if(i == 0){
//					//First Node
//					var node = t._createNode(t.nodes[i].title, t.nodes[i].id, t.nodes[i].content);
//					var td1 = document.createElement("td");
//					td1.appendChild(node);
//					td1.rowSpan = t.nodes.length-2;
//					tr.id = "first_row_table";
//					tr.appendChild(td1);
//				}
//				else if(i == t.nodes.length -1){
//					//Last node
//					var node = t._createNode(t.nodes[i].title, t.nodes[i].id, t.nodes[i].content);
//					var td3 = document.createElement("td");
//					td3.appendChild(node);
//					td3.rowSpan = t.nodes.length-2;
//					tr = document.getElementById('first_row_table');
//					tr.appendChild(td3);
//
//				} else {
//					//Child node
//					var td2 = document.createElement("td");
//					var node = t._createNode(t.nodes[i].title, t.nodes[i].id, t.nodes[i].content);
//					td2.appendChild(node);
//					tr.appendChild(td2);
//				}
//			}
		}
	};
	
	t._createNode = function(title, id, content){
		var div = document.createElement("div");
		var table = document.createElement("table");
		//Add the title
		var tr_title = document.createElement("tr");
		var td_title = document.createElement("td");
		td_title.innerHTML = title;
		tr_title.appendChild(td_title);
		table.appendChild(tr_title);
		//Add the body
		var tr_body = document.createElement("tr");
		var td_body = document.createElement('td');
		if(typeof content == "string"){
			text = document.createTextNode(content);
			td_body.appendChild(text);
		} else {
			td_body.appendChild(content);
		}
		tr_body.appendChild(td_body);
		table.appendChild(tr_body);
		t._setNodeStyle(table, tr_body, tr_title);
//		table.style.position = "absolute";
		div.appendChild(table);
//		div.style.position = "absolute";
		div.id = "diagram_node_"+id; //The id is updated just for internal DOM use, to be sure that it is realy unique
		return div;
	};
	
	t._setNodeStyle = function(table_node,tr_body,tr_title){
		//TODO
		table_node.style.border = "1px solid";
	};
}