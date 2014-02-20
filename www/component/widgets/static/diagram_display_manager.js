/**
 * Create a diagram with blocs starting from A, with several blocks in the middle column, and ending to the point B<br/>
 * The blocks in the middle are all linked to the starting (A) and ending (B) blocs by a line
 * @param {String|HTMLElement}container
 * @param {Null|number} start_width the width to set to the start node
 * @param {Null|number} middle_width the width to set to all the middle nodes
 * @param {Null|number} end_width the width to set to the end node
 * Note: all the layout calculations are done considering that the container has no margin neither border
 */
function diagram_display_manager(container,start_width,middle_width,end_width){
	var t = this;
	if(typeof container == "string")
		container = document.getElementById(container);
	container.style.position = "relative";
	container.style.height = "100%";
	container.style.width = "100%";
	
	t.nodes = [];//If first node, means start node
		//if last node, means ending node
	
	/**
	 * Create a start node. Insert the given node into the t.nodes array, at the first position<br/>
	 * If a start node already existed, it is not removed, only pushed to the middle nodes
	 * @param {String} title of the node when displayed
	 * @param {String|HTMLElement} content the content of the node
	 * @param {String} id of the node, must be unique
	 */
	t.createStartNode = function(title, content, id){
		//Add at the begining
		var nodes = t.nodes;
		t.nodes = [];
		t.nodes[0] = {id:id,title:title,content:content};
		for( var i = 0; i < nodes.length; i++){
			t.nodes[i+1] = nodes[i];
		} 
		t._resetLayout();
	};
	
	/**
	 * Create a ending node. Insert the given node into the t.nodes array, at the last position<br/>
	 * If a ending node already existed, it is not removed, only pushed to the middle nodes
	 * @param {String} title of the node when displayed
	 * @param {String|HTMLElement} content the content of the node
	 * @param {String} id of the node, must be unique
	 */
	t.createEndNode = function(title, content, id){
		//Add at the end
		t.nodes.push({id:id,title:title,content:content});
		t._resetLayout();
	};
	
	/**
	 * Create a middle node. Insert the given node into the t.nodes array, as the last middle node<br/>
	 * If no ending node, this node becomes the ending one
	 * @param {String} title of the node when displayed
	 * @param {String|HTMLElement} content the content of the node
	 * @param {String} id of the node, must be unique
	 */
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
		t._resetLayout();
	};
	
	/**
	 * Remove a node from the diagram<br/>
	 * The layout is reseted after removing
	 * @param {String} id the id of the node to be removed
	 */
	t.removeNode = function(id){
		//Find the node into the t.nodes array
		var index = null;
		for(var i = 0; i < t.nodes.length; i++){
			if(t.nodes[i].id == id){
				index = i;
				break;
			}
		}
		if(index != null){
			t.nodes.splice(index,1);
			t._resetLayout();
		}
	};
	
	/**
	 * Reset the diagram layout<br/>
	 * Remove all the elements from the diagram<br/>
	 * If the diagram was displayed before, call the t.show method
	 */
	t._resetLayout = function(){
		t.close();
		if (t._shown) t.show();
	};
	
	/**
	 * Remove all the elements from the diagram
	 */
	t.close = function(){
		while(container.firstChild)
			container.removeChild(container.firstChild);
	};

	t._shown = false;
	/**
	 * Start the layout process, and fill up the container
	 */
	t.show = function(){
		t._shown = true;
		//First insert all the nodes into the container
		for(var i = 0; i < t.nodes.length; i++)
			container.appendChild(t._createNode(t.nodes[i].title,t.nodes[i].id,t.nodes[i].content));
		t.layout();
		t._drawLines();
	};
	
	/**
	 * Set the layout of the diagram<br/>
	 * Each node is a table with an absolute position, manually fixed<br/>
	 * This method is added to the layout custom events
	 */
	t.layout = function(){
		//Then set the layout
		if(t.nodes.length < 2){
			if(t.nodes.length == 1){
				var node = document.getElementById(t._getComputedId(t.nodes[0].id));
				node.style.position = "absolute";
				node.style.width = t._getWidth(0)+"px";
				//Display it into the center
				node.style.top = t._getTopForEndAndFirstNodes(node); // center the top
				//Center the left
				var w = getWidth(container);
				var element_width = getWidth(node);
				node.style.left = w*0.5 - element_width*0.5;
			} else if(t.nodes.length == 2){
				var node1 = document.getElementById(t._getComputedId(t.nodes[0].id));
				node1.style.position = "absolute";
				var node2 = document.getElementById(t._getComputedId(t.nodes[1].id));
				node2.style.position = "absolute";
				//center the top
				node1.style.top = t._getTopForEndAndFirstNodes(node1);
				node2.style.top = t._getTopForEndAndFirstNodes(node2);
				//center the left
				var w = getWidth(container);
				var remaining = w - getWidth(node1) - getWidth(node2);
				if(remaining > 0){
					var ratio = remaining / 3;
					node1.style.left = ratio;
					node2.style.left = 2*ratio + getWidth(node1);
				}
			}
		} else {
			//Set the width attributes
			for(var i = 0; i < t.nodes.length; i++){
				var node = document.getElementById(t._getComputedId(t.nodes[i].id));
				node.style.width = t._getWidth(i)+"px";
				node.style.position = "absolute";
				//node.style.left = t._getLeft(i)+"px";
			}
			//Set the left and also top attributes after the width because the height may have been updated
			for(var i = 0; i < t.nodes.length; i++){
				var node = document.getElementById(t._getComputedId(t.nodes[i].id));
				node.style.left = t._getLeft(i)+"px";
				if(i != 0 && i != t.nodes.length -1){//only for the middle nodes
					node.style.top = t._getTopForMiddleNode(i)+"px";
				} else {
					node.style.top = t._getTopForEndAndFirstNodes(node)+"px";
				}
			}
		}
	};
	addLayoutEvent(container, function() { t.layout(); });
	
	/**
	 * Create a node element
	 * @param {String} title of the node when displayed
	 * @param {String|HTMLElement} content the content of the node
	 * @param {String} id of the node, must be unique
	 * @returns {HTMLElement} the node table
	 */
	t._createNode = function(title, id, content){
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
		table.id = t._getComputedId(id);
		return table;
	};
	
	/**
	 * Set the style of a node
	 * @param {HTMLElement} table_node the node element
	 */
	t._setNodeStyle = function(table_node,tr_body,tr_title){
		//TODO
		table_node.style.border = "1px solid";
		table_node.style.backgroundColor = "#FFFFFF";
	};
	
	/**
	 * Draw the lines between the nodes
	 */
	t._drawLines = function(){
		if(t.nodes.length  > 1){ //Else nothing to do
			require("drawing.js",function(){
				var first = document.getElementById(t._getComputedId(t.nodes[0].id));
				var end = document.getElementById(t._getComputedId(t.nodes[t.nodes.length-1].id));
				for(var i = 1; i < t.nodes.length-1; i++){
					drawing.horizontal_connector(first, document.getElementById(t._getComputedId(t.nodes[i].id)));
					if(t.nodes.length > 2)
						drawing.horizontal_connector(document.getElementById(t._getComputedId(t.nodes[i].id)), end);
				}
				if(t.nodes.length == 2){
					//link the first and the last node
					drawing.horizontal_connector(first,end);
				}
			});
		}
	};

	/**
	 * Get the width to be computed for a given element<br/>
	 * The values are manually chosen / can be set by the user
	 * @param {Number} index the index of the node into t.nodes array
	 * @returns {Number} the width value 
	 */
	t._getWidth = function(index){
		var w = getWidth(container);
		if(index == 0){
			if(start_width)
				return start_width;
			return w*0.25;
		} else if(index == t.nodes.length -1) {
			if(end_width)
				return end_width;
			return w*0.25;
		} else {
			if(middle_width)
				return middle_width;
			return w*0.25;
		}
		
	};

	/**
	 * Get the left position to be computed for a given element<br/>
	 * The values are manually chosen
	 * @param {Number} index the index of the node into t.nodes array
	 * @returns {Number} the width value 
	 */
	t._getLeft = function(index){
		var max = getWidth(container);
		//Get the remaining space, knowing that all the width have been fixed
		var remaining = max - t._getWidth(0)-t._getWidth(t.nodes.length -1)-t._getWidth();
		if(remaining < 0)
			return 0;
		var ratio = remaining / 8; //Set the left attribute arbitrarily
		if(index == 0)
			return ratio*1;
		else if (index == t.nodes.length-1)
			return (ratio*7 + t._getWidth(0) + t._getWidth());
		else {
			return (ratio*4 + t._getWidth(0));
		}
	};

	/**
	 * Get the container height
	 * @returns {Number} height of the container
	 */
	t._getContainerHeight = function(){
		return getHeight(container);
	};

	/**
	 * Get the interstice between the nodes of the middle column<br/>
	 * Note the interstice is calculated considering it would be added at the top and at the bottom of an element<br/>
	 * The values are manually chosen
	 * @returns {Number} the interstice to set
	 */
	t._getIntersticeMiddleColumn = function(){
		var h = 0;
		for(var i = 1; i < t.nodes.length-1; i++){
			h += getHeight(document.getElementById(t._getComputedId(t.nodes[i].id)));
		}
		//Get the remaining space
		var remaining = t._getContainerHeight() - h;
		if(remaining < 0)
			return 0;
		else
			return (remaining / (2 * (t.nodes.length -2)));
	};

	/**
	 * Get the top position to be computed for a given middle node<br/>
	 * The values are manually chosen
	 * @param {Number} index the index of the node into t.nodes array
	 * @returns {Number} the top value 
	 */
	t._getTopForMiddleNode = function(index){
		var h = t._getIntersticeMiddleColumn();
		var total = 0;
		if(index == 1)
			return h;
		for(var i = 1; i < index; i++){
			total += 2*h + getHeight(document.getElementById(t._getComputedId(t.nodes[i].id)));
		}
		total += h;
		return total;
	};

	/**
	 * Get the top position to be computed for a given starting / ending node<br/>
	 * The values are set in order to center those elements
	 * @param {HTMLElement} node
	 * @returns {Number} the top value 
	 */
	t._getTopForEndAndFirstNodes = function(node){
		var h = t._getContainerHeight();
		var element_height = getHeight(node);
		return(h*0.5 - element_height*0.5);
	};

	/**
	 * All the ids are updated for internal use only, to be sure that they are unique<br/>
	 * This id must be used by the getElementById method
	 * @param {String} id
	 * @returns {String} computed id
	 */
	t._getComputedId = function(id){
		return "diagram_node_"+id;
	};
}