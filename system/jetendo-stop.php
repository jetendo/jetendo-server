<?php
// /usr/bin/php /var/jetendo-server/system/jetendo-stop.php
echo "jetendo-server stopping: ".date(DATE_RFC2822)."\n";
require("library.php");

file_put_contents("/var/jetendo-server/logs/jetendo_server_down", "1");

if($isHostServer){
	stopHost($arrVirtualMachine);
}
// stop lucee with the memory dump request
/*
if(array_key_exists("lucee", $arrServiceMap) && $memoryDumpURL != ""){
	echo "Dumping lucee application scope:\n";
	// might want to use curl or wget with timeout instead.
	$result=trim(@file_get_contents($memoryDumpURL));
	if($result != "dump complete"){
		echo "Dump failed with the following response: ".substr($result, 0, 100)."\n";
	}else{
		echo $result;
	}
}
*/
/*
// stop railo with the memory dump request
if(array_key_exists("railo", $arrServiceMap) && $memoryDumpURL != ""){
	echo "Dumping railo application scope:\n";
	// might want to use curl or wget with timeout instead.
	$result=trim(@file_get_contents($memoryDumpURL));
	if($result != "dump complete"){
		echo "Dump failed with the following response: ".substr($result, 0, 100)."\n";
	}else{
		echo $result;
	}
}
*/
// stop monit
if(array_key_exists("monit", $arrServiceMap)){
	echo "Stop monit\n";
	$r=`/usr/sbin/service monit stop`;
	echo $r."\n";
}

// stop cron
if(array_key_exists("cron", $arrServiceMap)){
	echo "Stop cron\n";
	$r=`/usr/sbin/service cron stop`;
	echo $r."\n";
}

// stop junglediskserver
if(array_key_exists("junglediskserver", $arrServiceMap)){
	echo "Stop junglediskserver\n";
	$r=`/usr/sbin/service junglediskserver stop`;
	echo $r."\n";
}

// stop replication

// killall php
$pid=getmypid();
$r=`/bin/ps -e | /bin/grep php`;
$arrP=explode("\n", trim($r));
for($i=0;$i<count($arrP);$i++){
	$p=explode(" ", trim($arrP[$i]));
	if($p[0] != $pid){
		echo "Kill running php process id ".$p[0]."\n";
		$cmd="/bin/kill ".$p[0]."";
		$r=`$cmd`;
		echo $r."\n";
	}
}

// stop nginx
if(array_key_exists("nginx", $arrServiceMap)){
	echo "Stop nginx\n";
	$r=`/usr/sbin/service nginx stop`;
	echo $r."\n";
}
if(array_key_exists("apache", $arrServiceMap)){
	echo "Stop apache\n";
	$r=`/usr/sbin/service apache2 stop`;
	echo $r."\n";
}
/*
if(array_key_exists("railo", $arrServiceMap)){
	echo "Stop railo\n";
	$r=`/usr/sbin/service railo_ctl forcequit`;
	echo $r."\n";
}
*/
// stop coldfusion
if(array_key_exists("coldfusion", $arrServiceMap)){
	echo "Stop coldfusion\n";
	$r=`/usr/sbin/service coldfusion stop`;
	echo $r."\n";
}

// stop php7.0-fpm
if(array_key_exists("php", $arrServiceMap)){
	echo "Stop php\n";
	$r=`/usr/sbin/service php7.0-fpm stop`;
	echo $r."\n";
}

// stop mysql
if(array_key_exists("mysql", $arrServiceMap)){
	echo "Stop mysql\n";
	$r=`/usr/sbin/service mysql stop`;
	echo $r."\n";
}

if($copyVarDirectory){
	echo "Copying Var Directory\n";
	$cmd="/usr/bin/rsync -av --itemize-changes --exclude='jetendo-server/' /var/ /var/jetendo-server/varcopy/";
	echo $cmd."\n";
	$result=`$cmd`;
	echo $result."\n";
}

if(array_key_exists("postfix", $arrServiceMap)){
	echo $hostname.' has been stopped.';
	$cmd='/bin/echo "'.$hostname.' has been stopped." | /usr/bin/mailx -s "'.$hostname.' has been stopped." root@localhost';
	$r=`$cmd`;
	echo $r."\n";
}else{
	echo $hostname.' has been stopped. Can\'t send a notification email because postfix is not enabled.';
}
/*
// stop postfix
if(array_key_exists("postfix", $arrServiceMap)){
	echo "Stop postfix\n";
	$r=`/usr/sbin/service postfix stop`;
	echo $r."\n";
}
*/
/*
for($i=0;$i<count($arrMount);$i++){
	$mount=$arrMount[$i];
	echo "Unmount ".$mount['path'];
	$cmd="/bin/umount -f ".$mount['path']."";
	$r=`$cmd`;
	echo $r."\n";
}*/


echo "\n===========\n";

?>