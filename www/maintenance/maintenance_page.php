<html>
<head>
	<title>Maintenance in progress</title>
<style type='text/css'>
html, body {
	width: 100%;
	height: 100%;
	margin: 0px;
	padding: 0px;
}
html, body, table {
	font-family: Verdana;
}
#container {
	width: 100%;
	height: 100%;
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	background-color: #D0D0D0;
}
#box {
	border: 1px solid black;
	border-radius: 5px;
	box-shadow: 5px 5px 5px 0px #808080;
}
#title {
	border-bottom: 1px solid black;
	font-weight: bold;
	text-align: center;
	font-size: 14pt;
	background-color: #D0C090;
	border-top-left-radius: 5px;
	border-top-right-radius: 5px;
	padding: 3px;
}
#message {
	padding: 10px;
}
#message, #message table {
	font-size: 12pt;
	background-color: white;
	border-bottom-left-radius: 5px;
	border-bottom-right-radius: 5px;
}
#message tr>td:nth-child(2) {
	padding: 20px;
}
</style>
</head>
<body>
<div id='container'>
	<div id='box'>
		<div id='title'>Students Management Software - Under Maintenance</div>
		<div id='message'>
			<table>
				<tr>
					<td valign=top>
						<img src='/maintenance/maintenance.jpg' height="250px"/>
					</td>
					<td valign=middle>
						The software is currently under maintenance and cannot be accessed.<br/>
						<br/>
						Please wait few minutes, or contact your administrator for more information.
					</td>
			</table>
		</div>
	</div>
</div>
<script type='text/javascript'>
if (window != window.top) window.top.location.assign("/");
else setTimeout(function() { window.top.location.reload(); }, 30000);
</script>
</body>
</html>