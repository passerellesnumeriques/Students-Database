<?php include("../header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>
In order to test with an existing version, we will:<ol>
<li>Download a backup from this existing version</li>
<li>Install the version of the software of this backup</li>
<li>Install the database and files from this backup</li>
<li>Let you use this <i>test version</i>, and try to update it</li>
</ol>
<br/>
<form method='post' action='connect.php'>
<input type='hidden' name='path' value="<?php echo $_GET["path"];?>"/>
<table>
<tr>
	<td>URL of the installed software</td>
	<td><input type='text' name='url'/> (without http://)</td>
</tr>
<tr>
	<td>Password for the remote access</td>
	<td><input type='password' name='pass'/></td>
</tr>
<tr>
	<td colspan=2 align=center><input type='submit' value='Connect'/></td>
</tr>
</table>
</form>
</div>
<?php include("../footer.inc");?>