<?php
/*
To run this script manually, run this command:
	/usr/bin/php /var/jetendo-server/system/jetendo-start.php

*/
echo "jetendo-server starting: ".date(DATE_RFC2822)."\n";
require_once("library.php");


$currentDir=dirname(__FILE__);

$vimPath=$configPath.'vimrc';
if(file_exists($vimPath)){
	$cmd="/bin/cp $vimPath /root/.vimrc";
	$r=`$cmd`;
	echo $r."\n";
}

$cmd="/bin/echo '".$hostname."' > /etc/hostname";
$r=`$cmd`;
echo $r."\n";
$cmd="/bin/hostname '".$hostname."'";
$r=`$cmd`;
echo $r."\n";

$cmd="/bin/mount -a";
$r=`$cmd`;
echo $r."\n";

// mount partitions - via mount instead of fstab to avoid trouble booting due to kernel upgrades / virtualbox upgrades
for($i=0;$i<count($arrMount);$i++){
	$mount=$arrMount[$i];
	if(!is_dir($mount['path'])){
		echo "Creating directory: ".$mount['path']."\n";
		$cmd="/bin/mkdir ".$mount['path'];
		$r=`$cmd`;
		echo $r."\n";
	}
	if($environment == "production"){
		echo "Mounting 9p virtio directory: ".$mount['path']."\n";
		$cmd="/bin/mount -t 9p -o ".$mount['options']." ".$mount['mountName']." ".$mount['path']." 2>&1";
		echo $cmd."\n";
		$result=`$cmd`;
		if($result != "" && strpos($result,  "is already mounted") === false){
			echo $cmd."\n";
			dieWithError("Jetendo can't start because the above mount command returned the following information: ".$result."\n");
		}
	}else{
		echo "Mounting vboxsf directory: ".$mount['path']."\n";
		$cmd="/bin/mount -t vboxsf -o ".$mount['options']." ".$mount['mountName']." ".$mount['path']." 2>&1";
		echo $cmd."\n";
		$result=`$cmd`;
		if($result != "" && strpos($result,  "is already mounted") === false){
			echo $cmd."\n";
			dieWithError("Jetendo can't start because the above mount command returned the following information: ".$result."\n");
		}
	}
}

if($updateVarDirectory){
	echo "Updating Var Directory\n";
	$cmd="/usr/bin/rsync -av --itemize-changes --ignore-existing --exclude='jetendo-server/' /var/jetendo-server/varcopy/ /var/";
	echo $cmd."\n";
	$result=`$cmd`;
	echo $result."\n";
}

// copy all config files
for($i=0;$i<count($arrCommand);$i++){
	$cmd=$arrCommand[$i];
	echo "Running command: ".$cmd."\n";
	$r=`$cmd 2>&1`;
	echo $r."\n";
}

// load apparmor profiles
echo "Install all apparmor profiles\n";
if(!is_dir($configPath."/apparmor.d/")){
	echo "Installing shared apparmor profiles\n";
	$cmd="/bin/cp -rf ".$currentDir."/apparmor.d/".$environment."/* /etc/apparmor.d/";
	$r=`$cmd 2>&1`;
	echo $r."\n";
}
$r=`/sbin/apparmor_parser -r /etc/apparmor.d/`;
echo $r."\n";

// apply sysctl and other files and make sure they are processed
echo "Update sysctl\n";
$r=`/sbin/sysctl -p /etc/sysctl.conf`;
echo $r."\n";


echo "Create symbolic links to configuration files\n";
# symbolic link configuration
if(array_key_exists("mysql", $arrServiceMap)){
	if(!is_dir('/var/jetendo-server/mysql')){
		mkdir('/var/jetendo-server/mysql', 0755);
	}
	if(!is_dir('/var/jetendo-server/mysql/data')){
		mkdir('/var/jetendo-server/mysql/data', 0700);
		chown('/var/jetendo-server/mysql/data', 'mysql');
		chgrp('/var/jetendo-server/mysql/data', 'mysql');
	}
	if(!is_dir('/var/jetendo-server/mysql/logs')){
		mkdir('/var/jetendo-server/mysql/logs', 0700);
		chown('/var/jetendo-server/mysql/logs', 'mysql');
		chgrp('/var/jetendo-server/mysql/logs', 'mysql');
	}
	if(file_exists($configPath."mysql/my.cnf")){
		$cmd="/bin/ln -sfn ".$configPath."mysql/my.cnf /etc/mysql/conf.d/jetendo.cnf";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/jetendo-mysql-".$environment.".cnf /etc/mysql/conf.d/jetendo.cnf";
	}
	$r=`$cmd`;
	echo $r."\n";
}
if(array_key_exists("nginx", $arrServiceMap)){

	$r=`/bin/cp /var/jetendo-server/system/jetendo-nginx-init /etc/init.d/nginx`;
	echo $r."\n";
	$cmd="/bin/sed -i 's/\r//' /etc/init.d/nginx";
	$r=`$cmd`;
	$r=`/bin/chmod 755 /etc/init.d/nginx`;
	echo $r."\n";
	$r=`/usr/sbin/update-rc.d -f nginx defaults`;
	echo $r."\n";
	$r=`mkdir client_body_temp /var/jetendo-server/nginx/fastcgi_temp /var/jetendo-server/nginx/proxy_temp /var/jetendo-server/nginx/scgi_temp /var/jetendo-server/nginx/uwsgi_temp /var/jetendo-server/nginx/ssl`;
	echo $r."\n";
	$r=`chown www-data:root /var/jetendo-server/nginx/client_body_temp /var/jetendo-server/nginx/fastcgi_temp /var/jetendo-server/nginx/proxy_temp /var/jetendo-server/nginx/scgi_temp /var/jetendo-server/nginx/uwsgi_temp`;
	echo $r."\n";
	$r=`chmod 770 /var/jetendo-server/nginx/client_body_temp /var/jetendo-server/nginx/fastcgi_temp /var/jetendo-server/nginx/proxy_temp /var/jetendo-server/nginx/scgi_temp /var/jetendo-server/nginx/uwsgi_temp`;
	echo $r."\n";
	$r=`chmod 400 /var/jetendo-server/nginx/ssl`;
	echo $r."\n";
	if(is_dir($configPath."nginx/nginx.conf")){
		$cmd="/bin/ln -sfn ".$configPath."nginx/nginx.conf /var/jetendo-server/nginx/conf/nginx.conf";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/nginx-conf/nginx-".$environment.".conf /var/jetendo-server/nginx/conf/nginx.conf";
	}
	$r=`$cmd`;
	echo $r."\n";
}
if(is_dir($configPath."sysctl.conf")){
	$cmd="/bin/ln -sfn ".$configPath."sysctl.conf /etc/sysctl.d/jetendo.conf";
}else{
	$cmd="/bin/ln -sfn /var/jetendo-server/system/jetendo-sysctl-".$environment.".conf /etc/sysctl.d/jetendo.conf";
}
$r=`$cmd`;
echo $r."\n";
if(array_key_exists("apache", $arrServiceMap)){
	if(is_dir($configPath."apache/sites-enabled")){
		$cmd="/bin/ln -sfn ".$configPath."apache/sites-enabled /etc/apache2/sites-enabled";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/apache-conf/".$environment."-sites-enabled /etc/apache2/sites-enabled";
	}
	$r=`$cmd`;
	echo $r."\n";
}
if(array_key_exists("php", $arrServiceMap)){
	if(!is_dir('/var/jetendo-server/php')){
		mkdir('/var/jetendo-server/php', 0755);
		chown('/var/jetendo-server/php', 'root');
		chgrp('/var/jetendo-server/php', 'root');
	}
	if(!is_dir('/var/jetendo-server/php/run')){
		mkdir('/var/jetendo-server/php/run', 0770);
		chown('/var/jetendo-server/php/run', 'www-data');
		chgrp('/var/jetendo-server/php/run', 'www-data');
	}
	if(!is_dir('/var/jetendo-server/php/session')){
		mkdir('/var/jetendo-server/php/session', 0770);
		chown('/var/jetendo-server/php/session', 'www-data');
		chgrp('/var/jetendo-server/php/session', 'www-data');
	}
	if(!is_dir('/var/jetendo-server/php/temp')){
		mkdir('/var/jetendo-server/php/temp', 0770);
		chown('/var/jetendo-server/php/temp', 'www-data');
		chgrp('/var/jetendo-server/php/temp', 'www-data');
	}
	if(is_dir($configPath."php/pool")){
		$cmd="/bin/ln -sfn ".$configPath."php/pool /etc/php/7.0/fpm/pool.d";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/php/".$environment."-pool /etc/php/7.0/fpm/pool.d";
	}
	$r=`$cmd`;
	echo $r."\n";
}


// if using virtualbox
$result=`/usr/sbin/dmidecode  | /bin/grep -i product`;
if(strpos($result, "Virtualbox") !== FALSE){
	// using virtualbox - check for module being loaded
	$result=`/sbin/lsmod | /bin/grep -i vboxguest`;
	if($result == ""){
		// reinstall modules
		`/bin/mount /dev/cdrom /media/cdrom`;
		`/media/cdrom/VBoxLinuxAdditions.run`;
		
		// check again
		$result=`/sbin/lsmod | /bin/grep -i vboxguest`;
		if($result == ""){
			// fail - may require rebooting.
			dieWithError("Jetendo can't start because the virtualbox guest additions are not able to be loaded.  Make sure the guest additions ISO is mounted, install them with the following commands, and then reboot the machine:\n\n/bin/mount /dev/cdrom /media/cdrom\n/media/cdrom/VBoxLinuxAdditions.run");
		}
	}
}

// stop monit before restarting services to avoid conflicts & failures
if(array_key_exists("monit", $arrServiceMap)){
	echo "Stop monit\n";
	$r=`/usr/sbin/service monit stop`;
	echo $r."\n";
}

// start services in sequence
if(array_key_exists("ufw", $arrServiceMap)){
	echo "Start ufw\n";
	$r=`/usr/sbin/service ufw start`;
	echo $r."\n";
	//echo "Enable ufw\n";
	//$r=`/bin/echo "y" | /usr/sbin/ufw enable`;
	//echo $r."\n";
}
// service networking
if(array_key_exists("networking", $arrServiceMap)){
	echo "Start networking\n";
	$r=`/sbin/ifup lo 2>&1`;
	echo $r."\n";
	if($r == ""){
		$r=`/sbin/ifup --exclude=lo -a`;
		// no longer works in ubuntu 14+
		//$r=`/usr/sbin/service networking restart`;
		echo $r."\n";
	}
}

if(array_key_exists("fail2ban", $arrServiceMap)){
	echo "Start fail2ban\n";
	$r=`/usr/sbin/service fail2ban start`;
	echo $r."\n";
}
if(array_key_exists("ssh", $arrServiceMap)){
	echo "Start ssh\n";
	$r=`/usr/sbin/service ssh start`;
	echo $r."\n";
}
// start dnsmasq
if(array_key_exists("dnsmasq", $arrServiceMap)){
	echo "Start dnsmasq\n";
	$r=`/usr/sbin/service dnsmasq start`;
	echo $r."\n";
}
// start cron
if(array_key_exists("cron", $arrServiceMap)){
	echo "Start cron\n";
	$r=`/usr/sbin/service cron start`;
	echo $r."\n";
}
// start postfix
if(array_key_exists("postfix", $arrServiceMap)){
	echo "Start postfix\n";
	$r=`/usr/bin/newaliases`;
	echo $r."\n";
	$r=`/usr/sbin/service postfix start`;
	echo $r."\n";
}
// start php7.0-fpm
if(array_key_exists("php", $arrServiceMap)){
	echo "Start php7.0-fpm\n";
	$r=`/usr/sbin/service php7.0-fpm start`;
	echo $r."\n";
}

if(array_key_exists("postfix", $arrServiceMap)){
	echo $hostname.' has been started.';
	$cmd='/bin/echo "'.$hostname.' has been started." | /usr/bin/mailx -s "'.$hostname.' has been started." root@localhost';
	echo $cmd."\n";
	$r=`$cmd`;
	echo $r."\n";
}else{
	echo $hostname.' has been started. Can\'t send a notification email because postfix is not enabled.';
}	


// start mysql
if(array_key_exists("mysql", $arrServiceMap)){
	echo "Start mysql\n";
	$r=`/usr/sbin/service mysql start`;
	echo $r."\n";
}

$jetendoStarted=false;

// start lucee
if(array_key_exists("lucee", $arrServiceMap)){
	if(!is_dir('/var/jetendo-server/luceevhosts')){
		mkdir('/var/jetendo-server/luceevhosts', 0770);
		chown('/var/jetendo-server/luceevhosts', 'www-data');
		chgrp('/var/jetendo-server/luceevhosts', 'www-data');
	}
	if(!is_dir('/var/jetendo-server/luceevhosts/server')){
		mkdir('/var/jetendo-server/luceevhosts/server', 0770);
		chown('/var/jetendo-server/luceevhosts/server', 'www-data');
		chgrp('/var/jetendo-server/luceevhosts/server', 'www-data');
		`/bin/cp -rf /var/jetendo-server/lucee/* /var/jetendo-server/luceevhosts/server/`;
		`/bin/chown -R www-data:www-data /var/jetendo-server/luceevhosts/server/`;
		`/bin/chmod -R 770 /var/jetendo-server/luceevhosts/server/`;
	}
	if(!is_dir('/var/jetendo-server/luceevhosts/tomcat-logs')){
		mkdir('/var/jetendo-server/luceevhosts/tomcat-logs', 0770);
		chown('/var/jetendo-server/luceevhosts/tomcat-logs', 'www-data');
		chgrp('/var/jetendo-server/luceevhosts/tomcat-logs', 'www-data');
	}

	if(file_exists($currentDir."/lucee_ctl")){
		$cmd="/bin/cp -f ".$currentDir."/lucee_ctl /etc/init.d/lucee_ctl";
		chmod('/var/jetendo-server/system/lucee/lucee_ctl', 0755);
		chown('/var/jetendo-server/luceevhosts/tomcat-logs', 'root');
		chgrp('/var/jetendo-server/luceevhosts/tomcat-logs', 'root');
	}
	echo "Start lucee\n";
	$r=`/usr/sbin/service lucee_ctl start`;
	echo $r."\n";
	// setup logs
	$cmd="/bin/cp -f ".$currentDir."/lucee/logrotate.txt /etc/logrotate.d/tomcat";
	$r=`$cmd`;
	echo $r."\n";
	/*
	// TODO: bring this back when live server also uses it.
	if(!file_exists("/var/jetendo-server/jetendo/core/config.cfc")){
		dieWithError("Jetendo CMS is not installed, and can't be started.");
	}else{
		echo "Start jetendo\n";
		// verify lucee's first jetendo request has completed
		ob_start();
		require_once("jetendo-status.php");
		$status=ob_get_clean();
		if($status !== "1"){
			dieWithError("Jetendo CMS status check failed.  You need to manually fix Lucee / Jetendo CMS and then re-run this script.");
		}
		$jetendoStarted=true;
	}*/
}
// start nginx
if(array_key_exists("nginx", $arrServiceMap)){
	echo "Start nginx\n";
	$r=`/usr/sbin/service nginx start`;
	echo $r."\n";
}

// start apache
if(array_key_exists("apache", $arrServiceMap)){
	echo "Start apache\n";
	$r=`/usr/sbin/service apache2 start`;
	echo $r."\n";
}

// force cron to work earlier
`/var/jetendo-server/jetendo/scripts/execute-commands.php >/dev/null 2>&1 &`;
//`/var/jetendo-server/jetendo/scripts/zqueue/queue.php >/dev/null 2>&1 &`;
//`/var/jetendo-server/jetendo/scripts/zqueue/queue-check-running.php >/dev/null 2>&1 &`;
/*
// start railo
if(array_key_exists("railo", $arrServiceMap)){
	if(!is_dir('/var/jetendo-server/railovhosts')){
		mkdir('/var/jetendo-server/railovhosts', 0770);
		chown('/var/jetendo-server/railovhosts', 'www-data');
		chgrp('/var/jetendo-server/railovhosts', 'www-data');
	}
	if(!is_dir('/var/jetendo-server/railovhosts/server')){
		mkdir('/var/jetendo-server/railovhosts/server', 0770);
		chown('/var/jetendo-server/railovhosts/server', 'www-data');
		chgrp('/var/jetendo-server/railovhosts/server', 'www-data');
		`/bin/cp -rf /var/jetendo-server/railo/* /var/jetendo-server/railovhosts/server/`;
		`/bin/chown -R www-data:www-data /var/jetendo-server/railovhosts/server/`;
		`/bin/chmod -R 770 /var/jetendo-server/railovhosts/server/`;
	}
	if(!is_dir('/var/jetendo-server/railovhosts/tomcat-logs')){
		mkdir('/var/jetendo-server/railovhosts/tomcat-logs', 0770);
		chown('/var/jetendo-server/railovhosts/tomcat-logs', 'www-data');
		chgrp('/var/jetendo-server/railovhosts/tomcat-logs', 'www-data');
	}
	echo "Start railo\n";
	$r=`/usr/sbin/service railo_ctl start`;
	echo $r."\n";
	// setup logs
	$cmd="/bin/cp -f ".$currentDir."/railo/logrotate.txt /etc/logrotate.d/tomcat";
	$r=`$cmd`;
	echo $r."\n";

	if(!file_exists("/var/jetendo-server/jetendo/core/config.cfc")){
		dieWithError("Jetendo CMS is not installed, and can't be started.");
	}else{
		echo "Start jetendo\n";
		// verify railo's first jetendo request has completed
		ob_start();
		require_once("jetendo-status.php");
		$status=ob_get_clean();
		if($status !== "1"){
			dieWithError("Jetendo CMS status check failed.  You need to manually fix Railo / Jetendo CMS and then re-run this script.");
		}
		$jetendoStarted=true;
	}
}*/
// check availability of the other servers
checkAvailableServers();

// start replication
// doesn't exist yet

// verify replication state is less then 1 minute behind before coming back online.


// update nginx configuration to match server availability

	

// start coldfusion
if(array_key_exists("coldfusion", $arrServiceMap)){
	echo "Start coldfusion\n";
	$r=`/usr/sbin/service coldfusion start`;
	echo $r."\n";
}


// start junglediskserver
if(array_key_exists("junglediskserver", $arrServiceMap)){
	if(file_exists('/etc/init.d/junglediskserver')){
		echo "Start junglediskserver\n";
		$r=`/usr/sbin/service junglediskserver start`;
		echo $r."\n";
	}else{
		echo "Tried to start junglediskserver, but it is not installed.\n";
	}
}

// start monit
if(array_key_exists("monit", $arrServiceMap)){
	echo "Start monit\n";
	$r=`/usr/sbin/service monit start`;
	echo $r."\n";
}

if($jetendoStarted && file_exists($configPath."/jetendo_server_down")){
	unlink($configPath."/jetendo_server_down");
}

if($isHostServer){
	startHost($serverPath, $arrVirtualMachine);
}

// done!
echo "\n===========\n";
?>