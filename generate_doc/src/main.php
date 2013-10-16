<?php 
$start_time = time();
echo "PN Students Database Documentation\r\n";

global $www_dir, $generated_dir;
$www_dir = dirname(__FILE__)."/../../www";
$generated_dir = dirname(__FILE__)."/../../generated_doc";

mkdir($generated_dir."/tmp");
mkdir($generated_dir."/tmp/navigation");

require_once("CommandsExecution.inc");
require_once("Components.inc");
require_once("FSUtils.inc");

$cmds = new CommandsExecution();

// launch PHP documentation
mkdir($generated_dir."/tmp/general_php");
mkdir($generated_dir."/tmp/general_php/component");
FSUtils::copy_dir_flat($www_dir,$generated_dir."/tmp/general_php");
FSUtils::copy_dir_flat($www_dir."/component",$generated_dir."/tmp/general_php/component");
@mkdir($generated_dir."/general/php");
$cmds->add_command(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/../tools/apigen/apigen.php --source ".$generated_dir."/tmp/general_php"." --destination ".$generated_dir."/general/php"." --extensions inc,php");
mkdir($generated_dir."/tmp/php");
foreach (Components::list_components() as $name) {
	FSUtils::mkdir_rec($generated_dir."/component/".$name."/php");
	FSUtils::mkdir_rec($generated_dir."/tmp/component/".$name."/php");
	$path = $www_dir."/component/".$name;
	FSUtils::copy_dir_flat($path, $generated_dir."/tmp/component/".$name."/php");
	$cmds->add_command(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/../tools/apigen/apigen.php --source ".$generated_dir."/tmp/component/".$name."/php"." --destination ".$generated_dir."/component/".$name."/php --extensions inc,php");
}

// launch javascript documentation
// TODO

// launch the general generation and components generation
$cmds->add_command(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/generate_general.php");
$cmds->add_command(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/generate_components.php");
$cmds->add_command(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/generate_uml.php");

$cmds->launch_execution("main");

// finally, we can integrate everything
$cmds = new CommandsExecution();
$cmds->add_command(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/integrate_components.php");
$cmds->add_command(getenv("PHP_PATH")."/php.exe -c ".$generated_dir."/php.ini ".dirname(__FILE__)."/generate_navigation.php");
$cmds->launch_execution("final");

echo "Generation done in ".(time()-$start_time)."s.";
?>