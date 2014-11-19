if (window == window.top) window.top.google = {
	installed: false,
	loadGoogleMap: function(container,onready) {
		container.removeAllChildren();
		container.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> We cannot display a map from Google because Google is not installed. Please contact your administrator to install it.";
	}
};