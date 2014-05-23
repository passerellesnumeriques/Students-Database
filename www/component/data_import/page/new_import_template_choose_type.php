<?php 
class page_new_import_template_choose_type extends Page {
	
	public function getRequiredRights() { return array(); }
	
	public function execute() {
?>
<table style='width:100%;height:100%;background-color:white'><tr><td valign=middle align=center>
<table>
<tr><td>
Choose how data are organized in the file:
</td></tr>
<tr><td>
<a class='button_verysoft' href='edit_import_template_multiple_by_columns?id=-1&root=<?php echo urlencode($_GET["root"]); if (isset($_GET["submodel"])) echo "&submodel=".urlencode($_GET["submodel"]);?>'>
1- Multiple entries, organized by columns
</a>
</td></tr>
</table>
</td></tr></table>
<?php 
	}
	
}
?>