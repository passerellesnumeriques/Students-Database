<?php 
require_once("staff_info.inc");
$container_id = $this->generateID();
$fct = staff_info($this, $people_id, $container_id);
?>
<div style='padding:5px'>
	<div id='<?php echo $container_id;?>'></div>
</div>

<script type='text/javascript'>
new <?php echo $fct;?>();
</script>