<?php 
if (empty($_POST["version"])) die("No version specified");
if (empty($_POST["path"])) die("No path specified");
?>
<?php include("header.inc");?>
<div style='flex:none;background-color:white;padding:10px'>

Starting deployment...
<form name='deploy' method="POST" action="create_deploy_directory.php">
<input type='hidden' name='version' value='<?php echo $_POST["version"];?>'/>
<input type='hidden' name='path' value='<?php echo $_POST["path"];?>'/>
</form>

</div>
<script type='text/javascript'>
document.forms['deploy'].submit();
</script>
<?php include("footer.inc");?>