<?php

$allowOutput=false;
if(!isset($configPath)){
	require_once("library.php");
	$allowOutput=true;
}
if($jetendoAdminDomain==""){
	// verify if services are running

	// for now just test the file existance
	if(file_exists("/var/jetendo-server/logs/jetendo_server_down")){
		echo "0";
	}else{
		echo "1";
	}
}else{
	$verifyURL=$jetendoAdminDomain.":8888/zcorerootmapping/index.cfm?_zsa3_path=/&zcoreRunFirstInit=1";
	if($allowOutput){
		echo "Running URL to check jetendo status:\n".$verifyURL."\nPlease wait...\n";
	}
	$contents=file_get_contents($verifyURL);
}
if($allowOutput){
//	echo "Response: ".$contents."\n";
	if($contents === FALSE){
		echo "jetendo-server is NOT running.\n";
	}else{
		echo "jetendo-server is running.\n";
	}
}else{
	if($contents === FALSE){
		echo "0";
	}else{
		echo "1";
	}
}
?>