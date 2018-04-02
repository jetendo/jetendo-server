Jetendo Server Installation Documentation
OS: Ubuntu Server 14.04 LTS

This readme is for users that want to install Jetendo Server and Jetendo from scratch.
If you downloaded the pre-configured virtual machine from https://www.jetendo.com/ , please use README-FOR-VIRTUAL-MACHINE.txt to get started with it.
If you don't have README-FOR-VIRTUAL-MACHINE.txt, please download it from https://github.com/jetendo/jetendo-server/ 

Download Jetendo Server 
	If you're reading this readme and haven't cloned or downloaded a release of the Jetendo Server project to your host system, please do so now.
	
	You can grab the latest development version from https://github.com/jetendo/jetendo-server/ or a release version from https://www.jetendo.com/
	
	The Jetendo Server project holds most of the configuration required by the virtual machine.

Virtualbox initial setup
	Ubuntu Linux x64
	Minimum requirements: 2048mb ram, 5gb hard drive, 1gb 2nd hard drive for swap, 1 NAT network adapter
	NAT Advanced Settings -> Port forwarding
		Name: SSH, Host Ip: 127.0.0.2: Host Port: 3222, Guest Ip: 10.0.2.15, Guest Port: 22
		Name: Nginx, Host Ip: 127.0.0.2: Host Port: 80, Guest Ip: 10.0.2.15, Guest Port: 80
		Name: Nginx SSL, Host Ip: 127.0.0.2: Host Port: 443, Guest Ip: 10.0.2.15, Guest Port: 443
		Name: Apache, Host Ip: 127.0.0.3: Host Port: 80, Guest Ip: 10.0.2.16, Guest Port: 80
		Name: Apache SSL, Host Ip: 127.0.0.3: Host Port: 443, Guest Ip: 10.0.2.16, Guest Port: 443
		Name: Lucee, Host Ip: 127.0.0.2: Host Port: 8888, Guest Ip: 10.0.2.15, Guest Port: 8888
	Setup Shared Folders - The following names must point to the directory with the same name on your host system.  By default, they are a subdirectory of this README.txt file, however, you may relocate the paths if you wish.
		nginx
		mysql
		coldfusion
		system
		lucee
		php
		apache
		jetendo
	Download and mount Ubuntu 14.04 LTS ISO to cdrom on first boot
	
	At Install Prompt, Press F4 (modes) and select Minimal Virtual Machine Install if this is virtual machine
		make / (root) partition on at least a 3gb drive.
		setup /var on a large separate drive (20gb+) and one large partition, so that the base file is as small as possible with all variable data on the second drive, which can be cloned and attached to multiple virtual machines.
		use defaults for all options - except don't encrypt your home directory.
	The username created during install will not be used later.
	Don't select any packages when prompted because this is a minimal install and they will be configured with shell afterwards.
	
	After finishing the rest of this guide, you'll be able to access:
		SSH/SFTP with:
			127.0.0.2 port 22
		Apache web sites with:
			www.your-site.com.127.0.0.3.xip.io
		Nginx web sites with:
			www.your-site.com.127.0.0.2.xip.io
		Lucee administrator:
			http://127.0.0.2:8888/lucee/admin/server.cfm
		Jetendo Administrator:
			https://jetendo.your-company.com.127.0.0.2.xip.io/z/server-manager/admin/server-home/index
			
	To run other copies of the virtual machine, just update the IP addresses to be unique.  You can use any ips on 127.x.x.x for this without reconfiguring your host system.
			
After OS is installed:		

# run this to act as root for a while:
sudo -i

# change vi default so insert mode is easier to use.  Type these commands:
	vi /root/.vimrc
	press i key twice.
	set nocompatible
	set backspace=indent,eol,start
	Press escape key
	:wq
	Now vi insert mode is easier to use by just pressing i once.

# disable bash history storage
	vi /root/.profile
		unset HISTFILE
	# and run the command once
	unset HISTFILE
	rm /root/.bash_history
	
# force grub screen to NOT wait forever for keypress on failed boot:
	vi /etc/default/grub
		GRUB_RECORDFAIL_TIMEOUT=2
	
	# force ubuntu to boot after 2 second delay on grub menu
		vi /etc/grub.d/00_header
			# put this below ALL the other "set timeout" records in make_timeout
			set timeout=2
			
	update-grub
	
# vi /etc/init/cron.conf
	change "exec cron" to "exec cron -L 0"  to stop it from filling syslog with non-error messages.
	and
#vi /etc/rsyslog.conf
	change 
		*.*;auth,authpriv.none		-/var/log/syslog
	to
		*.*;auth,authpriv.none,cron.none		-/var/log/syslog

# Initial kernel & OS update
	sudo apt-get update
	sudo apt-get upgrade
	sudo apt-get dist-upgrade
	sudo reboot
	
# setup ssh
	sudo apt-get install -qqy --force-yes openssh-server
	
# setup ufw
	sudo apt-get install ufw
	
	# allow web traffic:
	sudo ufw allow 80/tcp
	sudo ufw allow 443/tcp
	
	# allow ssh from specific ip, replace YOUR_STATIC_IP with a real IP address.
	ufw allow from YOUR_STATIC_IP to any port 22
	
	# don't have a static ip? Then allow from any IP (less secure)
	sudo ufw allow 22/tcp
	
	# disable all firewall logging, unless you have concerns
	ufw logging off
	
	# Add connection limiting on a production server
	
		add to /etc/ufw/before.rules after the "drop INVALID packets" configuration lines
		
			# Limit to 30 concurrent connections on port 80 per IP
			-A ufw-before-input -p tcp --syn --dport 80 -m connlimit --connlimit-above 30 -j REJECT
			-A ufw-before-input -p tcp --syn --dport 443 -m connlimit --connlimit-above 30 -j REJECT

			# Limit to 20 connections on port 80 per 1 seconds per IP
			-A ufw-before-input -p tcp --dport 80 -i eth0 -m state --state NEW -m recent --set
			-A ufw-before-input -p tcp --dport 80 -i eth0 -m state --state NEW -m recent --update --seconds 1 --hitcount 20 -j REJECT
			-A ufw-before-input -p tcp --dport 443 -i eth0 -m state --state NEW -m recent --set
			-A ufw-before-input -p tcp --dport 443 -i eth0 -m state --state NEW -m recent --update --seconds 1 --hitcount 20 -j REJECT
			
			
			# jetendo - block invalid tcp commands
			-A ufw-before-input -p TCP --tcp-flags ALL FIN,URG,PSH -j DROP
			-A ufw-before-input -p TCP --tcp-flags ALL SYN,RST,ACK,FIN,URG -j DROP
			-A ufw-before-input -p TCP --tcp-flags SYN,RST SYN,RST -j DROP
			-A ufw-before-input -p TCP --tcp-flags SYN,FIN SYN,FIN -j DROP
			-A ufw-before-input -p TCP --tcp-flags SYN,ACK NONE -j DROP
			-A ufw-before-input -p TCP --tcp-flags RST,FIN RST,FIN -j DROP
			-A ufw-before-input -p TCP --tcp-flags SYN,URG SYN,URG -j DROP
			-A ufw-before-input -p TCP --tcp-flags ALL SYN,PSH -j DROP
			-A ufw-before-input -p TCP --tcp-flags ALL SYN,ACK,PSH -j DROP
	service ufw restart
	
	ufw enable
	
# Enable empty password autologin for root on development server
	might also need to run
		sudo passwd -u root
	sudo passwd - enter temporary password
	vi /etc/pam.d/common-auth
		change nullok_secure to nullok
	vi /etc/ssh/sshd_config
		PermitEmptyPasswords Yes
	vi /etc/shadow
		delete the password hash for root between the 2 colons so it appears like "root::" on the first line.
	
	service ssh restart
	
Log out and login as root using ssh for the rest of the instructions.

# If server is a virtualbox virtual machine
	apt-get install build-essential module-assistant linux-headers-$(uname -r) dkms
	apt-get install virtualbox-guest-dkms virtualbox-guest-utils virtualbox-guest-x11
	m-a prepare
	#rebuild the kernel modules (at any time)
		uname -r | sudo xargs -n1 /usr/lib/dkms/dkms_autoinstaller start
	apt-get install --no-install-recommends virtualbox-guest-utils && apt-get install virtualbox-guest-dkms
	
	# force the vboxsf dkms kernel module to load before fstab runs
		vi /etc/default/rcS
			# add the following line to the bottom of the file:
			/sbin/modprobe vboxsf
	
	# verify the kernel modules are loaded:
		lsmod | grep vbox
	
			
# update hostname
	for development environment, make sure /etc/hostname matches the value used in the Jetendo configuration for the testDomain affix.  I.e. jetendo.127.0.0.2.xip.io
	

# If this is a virtual machine: Add the contents of /jetendo-server/system/jetendo-fstab.conf and copy the file to /etc/fstab, then run
	mkdir /var/jetendo-server/
	cd /var/jetendo-server/
	mkdir apache nginx mysql php system lucee coldfusion jetendo backup server config custom-secure-scripts logs virtual-machines luceevhosts
	mount -a
	mount mysql fails until it is installed because user doesn't exist yet.
	
Add Prerequisite Repositories
	apt-get install software-properties-common
	apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
	add-apt-repository 'deb http://ftp.utexas.edu/mariadb/repo/10.0/ubuntu trusty main'

	
	#best way to upgrade from mariadb 10.0 to 10.1 (10.1 fails to function - don't install 10.1)
	service monit stop
	service mysql stop
	 apt-get -f remove --auto-remove mariadb-server
	add-apt-repository --remove 'deb [arch=amd64,i386] http://mirrors.accretive-networks.net/mariadb/repo/10.1/ubuntu trusty main'
	apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
	add-apt-repository 'deb [arch=amd64,i386] http://mirrors.accretive-networks.net/mariadb/repo/10.1/ubuntu trusty main'
	apt-get update
	apt-get install mariadb-server

	apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0x4F4EA0AAE5267A6C
	LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
	add-apt-repository ppa:kirillshkrogalev/ffmpeg-next
	add-apt-repository ppa:webupd8team/java
	add-apt-repository ppa:stebbins/handbrake-releases
	add-apt-repository ppa:isage-dna/imagick
	apt-get update

Install Required Packages
	apt-get install apache2 apt-show-versions monit rsyslog ntp cifs-utils mailutils samba fail2ban libsasl2-modules postfix opendkim opendkim-tools oracle-java8-installer p7zip-full handbrake-cli dnsmasq imagemagick ffmpeg git libssl-dev build-essential  libpcre3-dev unzip apparmor-utils rng-tools php-pear mariadb-server make sshpass dnsutils
	
	# remove php 7.0
	apt purge php7.0*
	apt-get remove php7.0 php7.0-mysql php7.0-cli php7.0-fpm php7.0-gd php7.0-curl php7.0-dev php7.0-sqlite3 php7.0-mbstring curl php7.0-imap php7.0-opcache php7.0-readline

	
	apt-get install php7.2
	apt-get install php7.2-mysql php7.2-cli php7.2-fpm php7.2-gd php7.2-curl php7.2-dev php7.2-sqlite3 php7.2-mbstring curl php7.2-imap
	
	# if you want to use php with apache2, also run this:
	apt-get install libapache2-mod-php7.2
	
	# dnstools missing from ubuntu 14.04 lts now
	
	# accept defaults for all installers - when postfix installer prompts you, i.e. OK, Internet Site
	# Don't auto-configure database when the rsyslog utility app asks you.

# speed up network failsafe
	/etc/init/failsafe.conf
		change the sleeps to 3 3 3 and then change the bottom to 10 seconds instead of 120
	
Configure MariaDB
	service mysql stop
	#make sure mysql shared folder is mounted if using virtualbox
		mount -a
	
	# to begin with a fresh database, run this command to overwrite your mysql/data folder. WARNING:  If you existing mysql data files on your host system already, don't run this command.
	mkdir /var/jetendo-server/mysql/data/
	cp -rf /var/lib/mysql/* /var/jetendo-server/mysql/data/
	chown -R mysql:mysql /var/jetendo-server/mysql/data/
	
	# disable the /root/.mysql_history file
	export MYSQL_HISTFILE=/dev/null
	
	you must get the password in /etc/mysql/debian.cnf, and create "debian-sys-maint" user with host: localhost AND 127.0.0.1 with global access to all privileges for service mysql restart to work correctly.

Configure Apache2 (Note: Jetendo CMS uses Nginx exclusive, Apache configuration is optional)
	# enable modules
		a2enmod ssl rewrite proxy proxy_html xml2enc
	Change apache2 ip binding
		vi /etc/apache2/ports.conf
			ServerName dev
			Listen 127.0.0.3:80
			Listen 127.0.0.3:443
	
	service apache2 restart
	
	If you don't need Apache, it is recommended to disable it from starting with the following command:
		update-rc.d apache2 disable
	To re-enable:
		update-rc.d apache2 enable
		service apache2 start
	
Install Required Software From Source
	Nginx
		mkdir /var/jetendo-server/system/nginx-build
		cd /var/jetendo-server/system/nginx-build
		wget http://nginx.org/download/nginx-1.13.4.tar.gz
		tar xvfz nginx-1.13.4.tar.gz
		adduser --system --no-create-home --disabled-login --disabled-password --group nginx
		
		Put "sendfile off;" in nginx.conf on test server when using virtualbox shared folders
		
		#download and unzip nginx modules
			cd /var/jetendo-server/system/nginx-build/
			wget https://github.com/simpl/ngx_devel_kit/archive/master.zip
			unzip master.zip -d /var/jetendo-server/system/nginx-build/
			rm master.zip
			wget https://github.com/agentzh/set-misc-nginx-module/archive/master.zip
			unzip master.zip -d /var/jetendo-server/system/nginx-build/
			rm master.zip
		
		#compile and install zlib library
			wget http://zlib.net/zlib-1.2.11.tar.gz
			tar -zxf zlib-1.2.11.tar.gz
			cd zlib-1.2.11
			./configure
			make
			sudo make install
			
			
		cd /var/jetendo-server/system/nginx-build/nginx-1.13.4/
		./configure --with-http_realip_module  --with-http_v2_module --prefix=/var/jetendo-server/nginx --user=nginx --group=nginx --with-http_ssl_module --with-http_gzip_static_module  --with-http_flv_module --with-http_mp4_module --with-http_stub_status_module  --add-module=/var/jetendo-server/system/nginx-build/ngx_devel_kit-master --add-module=/var/jetendo-server/system/nginx-build/set-misc-nginx-module-master
		make
		make install
		cd /var/jetendo-server/nginx
		mkdir cache client_body_temp fastcgi_temp proxy_temp scgi_temp uwsgi_temp ssl
		chown www-data:root cache client_body_temp fastcgi_temp proxy_temp scgi_temp uwsgi_temp
		chmod 770 cache client_body_temp fastcgi_temp proxy_temp scgi_temp uwsgi_temp
		chmod -R 400 ssl
		mkdir /var/jetendo-server/nginx/conf/sites/
		mkdir /var/jetendo-server/nginx/conf/sites/jetendo/
		chmod -R 770 /var/jetendo-server/nginx/conf/sites
		
		# add mysql to www-data group so lucee / mysql backups work.
		usermod -G mysql,www-data mysql
		
		# service is not running until symbolic link and reboot steps are followed below

		openssl dhparam -out /var/jetendo-server/nginx/ssl/dh2048.pem -outform PEM -2 2048
		
	add mime-types to /var/jetendo-server/nginx/conf/mime.types
		
		audio/webm weba;
		application/x-font-ttf             ttf;
		font/opentype                      otf;
		application/font-woff2            woff2;
		
	
Install lucee
	Compile and Install Apache APR Library
		mkdir /var/jetendo-server/system/apr-build/
		cd /var/jetendo-server/system/apr-build/
		# get the newest apr unix gz here: http://apr.apache.org/download.cgi
		wget http://apache.mirrors.tds.net//apr/apr-1.6.3.tar.gz
		tar -xvf apr-1.6.3.tar.gz
		cd apr-1.6.3
		./configure
		make && make install
	Compile and Install Tomcat Native Library
		JAVA_HOME=/usr/lib/jvm/java-8-oracle
		export JAVA_HOME
		cd /var/jetendo-server/system/apr-build/
		# get the newest tomcat native library source here: http://tomcat.apache.org/download-native.cgi
		wget http://apache.mirrors.ionfish.org/tomcat/tomcat-connectors/native/1.2.16/source/tomcat-native-1.2.16-src.tar.gz
		tar -xvzf tomcat-native-1.2.16-src.tar.gz
		cd tomcat-native-1.2.16-src/native/
		./configure --with-apr=/usr/local/apr/bin/ --with-ssl=/usr/include/openssl --with-java-home=/usr/lib/jvm/java-8-oracle && make && make install
		
	Install lucee from newest tomcat x64 binary release on www.lucee.org
		mkdir /var/jetendo-server/system/lucee/
		cd /var/jetendo-server/system/lucee/
		download lucee linux x64 tomcat from http://lucee.org/ and upload to /var/jetendo-server/system/lucee/
		
		chmod 770 the installer file.
		
		#shutdown and disable Lucee if it is installed.
		service lucee_ctl stop
		echo manual | sudo tee /etc/init/lucee_ctl.override
		
		run the installer file
		When it asks for the user to run lucee as, type in: www-data
		Installation Directory /var/jetendo-server/lucee
		Start lucee at boot time: Y
		Don't allow installation of apache connectors: n
		Remember to write down password for Tomcat/lucee administrator.
		
		Edit /etc/init.d/lucee_ctl
			Before echo "[DONE]" in the start function add these lines where company domain is one of the main domain setup in jetendo for the server administrator.
				
				Test Server:
					printf "\nLoading Jetendo Application\n"
					/usr/bin/wget -O- 'http://dev.127.0.0.2.nip.io:8888/zcorerootmapping/index.cfm?_zsa3_path=/&zcoreRunFirstInit=1'
					printf "\n[DONE]"
				Production Server:
					printf "\nLoading Jetendo Application\n"
					/usr/bin/wget -O- 'http://dev.127.0.0.1.nip.io:8888/zcorerootmapping/index.cfm?_zsa3_path=/&zcoreRunFirstInit=1'
					printf "\n[DONE]"
			
		# prevent lucee from starting on boot (requires that jetendo-start.php init script is installed and configured - documentation is incomplete for this)
		echo manual | sudo tee /etc/init/lucee_ctl.override
		
		This forces the request that loads jetendo to occur on each restart of Lucee.
		
	/var/jetendo-server/lucee/tomcat/bin/setenv.sh
	# adjust Xmx high as you can afford, but at least 512m is necessary
		CATALINA_OPTS="-server -Dsun.io.useCanonCaches=false -Xms512m -Xmx1024m -javaagent:lib/lucee-inst.jar  -Djava.library.path=/usr/local/apr/lib -XX:+OptimizeStringConcat -XX:+UseTLAB -XX:+UseBiasedLocking -Xverify:none -XX:+UseThreadPriorities  -XX:+UseFastAccessorMethods -XX:-UseLargePages -XX:+UseCompressedOops";
		
	Put newest JRE In lucee:
		service lucee_ctl stop
		mv /var/jetendo-server/lucee/jdk/jre /var/jetendo-server/lucee/jdk/jre-original
		mkdir /var/jetendo-server/lucee/jdk/jre
		/bin/cp -rf /usr/lib/jvm/java-8-oracle/jre/* /var/jetendo-server/lucee/jdk/jre
		chown -R www-data:www-data /var/jetendo-server/lucee/
		chmod -R 770 /var/jetendo-server/lucee/

	mkdir /var/jetendo-server/jetendo/
	mkdir /var/jetendo-server/jetendo/sites
	mkdir /var/jetendo-server/jetendo/share
	touch /var/jetendo-server/jetendo/share/hostmap.conf
	touch /var/jetendo-server/jetendo/share/hostmap-redirect.conf
	mkdir /var/jetendo-server/luceevhosts/
	mkdir /var/jetendo-server/luceevhosts/server/
	mkdir /var/jetendo-server/luceevhosts/tomcat-logs/
	cp -rf /var/jetendo-server/lucee/lib/* /var/jetendo-server/luceevhosts/server/
	chown -R www-data:www-data /var/jetendo-server/luceevhosts/
	chmod -R 770 /var/jetendo-server/luceevhosts/

	lucee config backup
		mkdir /var/jetendo-server/system/lucee/temp/
		cp /var/jetendo-server/lucee/tomcat/conf/server.xml /var/jetendo-server/system/lucee/temp/
		cp /var/jetendo-server/lucee/tomcat/conf/web.xml /var/jetendo-server/system/lucee/temp/
		cp /var/jetendo-server/lucee/tomcat/conf/logging.properties /var/jetendo-server/system/lucee/temp/
		cp /var/jetendo-server/lucee/tomcat/bin/setenv.sh /var/jetendo-server/system/lucee/temp/
		
	# install the server.xml for production or development
		# development
		cp /var/jetendo-server/system/lucee/server-development.xml /var/jetendo-server/lucee/tomcat/conf/server.xml
		cp /var/jetendo-server/system/lucee/web-development.xml /var/jetendo-server/lucee/tomcat/conf/web.xml
		cp /var/jetendo-server/system/lucee/logging-development.properties /var/jetendo-server/lucee/tomcat/conf/logging.properties
		cp /var/jetendo-server/system/lucee/setenv-development.sh /var/jetendo-server/lucee/tomcat/bin/setenv.sh
		# production
		cp /var/jetendo-server/system/lucee/server-production.xml /var/jetendo-server/lucee/tomcat/conf/server.xml
		cp /var/jetendo-server/system/lucee/web-production.xml /var/jetendo-server/lucee/tomcat/conf/web.xml
		cp /var/jetendo-server/system/lucee/logging-production.properties /var/jetendo-server/lucee/tomcat/conf/logging.properties
		cp /var/jetendo-server/system/lucee/setenv-production.sh /var/jetendo-server/lucee/tomcat/bin/setenv.sh
		
	
	service lucee_ctl start
	
	vi /etc/logrotate.d/tomcat
	/var/jetendo-server/lucee/tomcat/logs/catalina.out {
		copytruncate
		daily
		rotate 7
		compress
		missingok
		size 5M
	}
	
	vi /etc/logrotate.d/jetendo
	/var/jetendo-server/jetendo/share/task-log/cfml-tasks.log {
		su root www-data
		copytruncate
		daily
		rotate 7
		compress
		missingok
		size 5M
	}
	
	
	http://dev.com.127.0.0.2.xip.io:8888/lucee/admin/web.cfm?action=resources.mappings
	
	
	NO LONGER NEED THESE PATCH NOTES in newer versions of lucee: Lucee has been patched in order to fix some things.  Here are the notes to re-create the patch until there is an official fix.
			learn how to build and deploy a patched version of lucee from source before making any changes:
				https://bitbucket.org/lucee/lucee/wiki/Build_from_source
				
			Edit version to be higher then what you're using:
			/lucee-java/lucee-core/src/lucee/runtime/Info.ini 
		
			farbeyondcode multiple file upload - no micha refused my patch
				
		fix for multiple file uploads:
			C:\ServerData\lucee-build\lucee-java\lucee-core\src\lucee\runtime\type\scope\FormImpl.java
			line 1: 184: change:
				fileItems.put(item.getFieldName().toLowerCase(),
			to:
				fileItems.put(getFileName(),
			
		fix for objectload/objectsave of functions outside a cfc.
			C:\ServerData\Lucee4\lucee-java\lucee-core\src\lucee\runtime\type\UDFPropertiesImpl.java
			out.writeObject(cachedWithin); 
			cachedWithin = in.readObject();
	
		make ant in path or use the commands below in command prompt.  http://ant.apache.org/manual/index.html
			
			# where Lucee4 is the java github project for current version of Lucee
			cd C:\ServerData\Lucee4
			set JAVA_HOME=C:\Program Files\Java\jdk1.7.0_25
			"C:\ServerData\apache-ant-1.9.6\bin\ant" core
			
			then locate patch file in "dist/"
	
Install node.js 0.12.x using nodesource PPA
	apt-get install apt-transport-https
	wget -qO- https://deb.nodesource.com/setup_0.12 | bash -
	apt-get install nodejs
	apt-get install build-essential
	
	# install handlebars globally to allow template precompilation
	npm install handlebars -g
	
	node -v
	handlebars -v
	
Install Coldfusion 9.0.2 (Jetendo CMS uses Lucee exclusively, Coldfusion installation is optional)
	apt-get install libstdc++5
	download coldfusion 9 developer editing linux 64-bit from adobe: http://www.adobe.com/support/coldfusion/downloads_updates.html#cf9
	/var/jetendo-server/system/coldfusion/install/ColdFusion_9_WWEJ_linux64.bin
	http://127.0.0.2:8500/CFIDE/administrator/index.cfm
	
Download Install Newest Intel Ethernet Adapter Driver If Production Server Use Intel Device
	lspci | grep -i eth
	
Install Optional Packages If You Want Them:
	# provide KVM virtual machines on production server
		apt-get install cpu-checker qemu-kvm libvirt-bin virtinst bridge-utils ubuntu-virt-server python-vm-builder
	# provide regular ftp
		apt-get install vsftpd
	# provides ab (apachebench) benchmarking utility
		apt-get install apache2-utils
	# provides hard drive smart status utilities
		apt-get install smartmontools
	# utilities for monitoring network and hard drive performance
		apt-get install sysstat iftop
	


# dev server manually modified files
	
Configure the variables in jetendo.ini manually
	/var/jetendo-server/system/php/jetendo.ini
	
Make sure the jetendo.ini symbolic link is created:
	ln -sfn /var/jetendo-server/system/php/jetendo.ini /etc/php/7.2/mods-available/jetendo.ini
Enable the php configuration module:	
	phpenmod jetendo
	service php7.2-fpm restart
	
# development server symbolic link configuration
	ln -sfn /var/jetendo-server/system/jetendo-mysql-development.cnf /etc/mysql/conf.d/jetendo-mysql-development.cnf 
	ln -sfn /var/jetendo-server/system/nginx-conf/nginx-development.conf /var/jetendo-server/nginx/conf/nginx.conf
	ln -sfn /var/jetendo-server/system/jetendo-sysctl-development.conf /etc/sysctl.d/jetendo-sysctl-development.conf
	ln -sfn /var/jetendo-server/system/monit/jetendo-development.conf /etc/monit/conf.d/jetendo.conf
	ln -sfn /var/jetendo-server/system/apache-conf/development-sites-enabled /etc/apache2/sites-enabled
	ln -sfn /var/jetendo-server/system/php/development-pool /etc/php/7.2/fpm/pool.d
		
	replace sa.your-company.com 3 times in /var/jetendo-server/system/monit/jetendo-development.conf with the jetendo server manager domain and change to https if you are using https 
	
# production server symbolic link configuration
	ln -sfn /var/jetendo-server/system/jetendo-mysql-production.cnf /etc/mysql/conf.d/jetendo-mysql-production.cnf
	ln -sfn /var/jetendo-server/system/nginx-conf/nginx-production.conf /var/jetendo-server/nginx/conf/nginx.conf
	ln -sfn /var/jetendo-server/system/jetendo-sysctl-production.conf /etc/sysctl.d/jetendo-sysctl-production.conf
	ln -sfn /var/jetendo-server/system/monit/jetendo-production.conf /etc/monit/conf.d/jetendo.conf
	ln -sfn /var/jetendo-server/system/apache-conf/production-sites-enabled /etc/apache2/sites-enabled
	ln -sfn /var/jetendo-server/system/php/production-pool /etc/php/7.2/fpm/pool.d
	
	replace sa.your-company.com 3 times in /var/jetendo-server/system/monit/jetendo-production.conf with the jetendo server manager domain and change to https if you are using https 
	
	
cp /var/jetendo-server/system/jetendo-nginx-init /etc/init.d/nginx
chmod +x /etc/init.d/nginx
/usr/sbin/update-rc.d -f nginx defaults

# enable apparmor profiles:
	development server:
		cp -f /var/jetendo-server/system/apparmor.d/development/* /etc/apparmor.d/
		apparmor_parser -r /etc/apparmor.d/
	production server:
		cp -f /var/jetendo-server/system/apparmor.d/production/* /etc/apparmor.d/
		apparmor_parser -r /etc/apparmor.d/
	
	configure the profiles to be specific to your application by editing them in /etc/apparmor.d/ directly.
	
# generate self-signed ssl certs for development
	cd /var/jetendo-server/nginx/ssl/
	openssl genrsa -out dev.com.key 2048
	openssl rsa -in dev.com.key -out dev.com.pem
	openssl req -new -key dev.com.key -out dev.com.csr
	openssl x509 -req -days 3650 -in dev.com.csr -signkey dev.com.key -out dev.com.crt
	chmod -R 400 /var/jetendo-server/nginx/ssl/

# increase security limits
	vi /etc/security/limits.conf
		* soft nofile 32768
		* hard nofile 32768
		root soft nofile 32768
		root hard nofile 32768
		* soft memlock unlimited
		* hard memlock unlimited
		root soft memlock unlimited
		root hard memlock unlimited
		* soft as unlimited
		* hard as unlimited
		root soft as unlimited
		root hard as unlimited

# reboot system to have all changes take effect.	
reboot

Setup Git options
	git config --global user.name "Your Name Here"
	git config --global user.email "your_email@example.com"
	git config --global core.filemode false

	
Configure fail2ban:
	change max retry to 5 and ban time to 600 seconds in the /etc/fail2ban/jail.conf
	service fail2ban restart
	If you are ever blocked from ssh login, restarting the server or waiting 10 minutes will allow you back in.

Configure Postfix to use Sendgrid.net for relying mail.
	vi /etc/aliases,  Find the line for "root" and make it "root: EMAIL_ADDRESS" where EMAIL_ADDRESS is the email address that system & security related emails should be forwarded to.
	Then run "newaliases"
	
	comment out line starting with "relayhost" in /etc/postfix/main.cf
	
	Relay mail with Sendgrid.net (Optional, but recommended for production servers)
		Add this to the end /etc/postfix/main.cf where your_username and your_password are replaced with the sendgrid.net login information.
			# jetendo-custom-smtp-begin
			smtp_sasl_auth_enable = yes
			smtp_sasl_password_maps = static:your_username:your_password
			smtp_sasl_security_options = noanonymous
			smtp_tls_security_level = may
			header_size_limit = 4096000
			relayhost = [smtp.sendgrid.net]:587
			# jetendo-custom-smtp-end
		
	Or relay mail to a google account by adding the following to 
		vi /etc/postfix/main.cf
			#jetendo-custom-smtp-begin
			relayhost = [smtp.gmail.com]:587
			smtp_sasl_auth_enable = yes
			smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd
			smtp_sasl_security_options = noanonymous
			smtp_tls_CAfile = /etc/postfix/cacert.pem
			smtp_use_tls = yes
			# jetendo-custom-smtp-end
		vi /etc/postfix/sasl_passwd
			[smtp.gmail.com]:587    USERNAME@gmail.com:PASSWORD
		chmod 400 /etc/postfix/sasl_passwd
		postmap /etc/postfix/sasl_passwd
		cat /etc/ssl/certs/Thawte_Premium_Server_CA.pem | sudo tee -a /etc/postfix/cacert.pem
	
	After changing the postfix configuration, restart the service:
		service postfix reload
		
	Verify the mail service is working, by logging in to the guest machine with SSH and typing the following command:
		echo "Test email" | mailx -s "Hello world" your_email@your_company.com
		
	If the mail service isn't working, make sure you entered the right information and followed the steps correctly.
	
	If the problem persists, check the logs at /var/log/mail.log or /var/log/syslog for error messages.
	
Enable hardware random number generator on non-virtual machine.  This is not safe on a virtual machine.
	rngd -r /dev/urandom
	
	on virtual machine use this instead:
		apt-get install haveged
	
manually download the latest 64-bit stable linux version of wkhtmltopdf on the website: http://wkhtmltopdf.org/downloads.html
	apt-get install xfonts-base xfonts-75dpi
	dpkg -i /root/wkhtmltox-0.12.2.1_linux-trusty-amd64.deb
	
	
Configure Jungledisk (Optional)
	This is a recommend solution for remote backup of production servers.
	
	Install Jungledisk
		Download 64-bit Server Edition Software from your jungledisk.com account:
		cd /root/
		wget https://downloads.jungledisk.com/jungledisk/junglediskserver_316-0_amd64.deb
		
		# Run this command to install it.  Make sure the file name matches the file you downloaded.
		dpkg -i /root/junglediskserver_316-0_amd64.deb
		
		Reset the license key on your jungledisk.com account page and replace LICENSE_KEY below with the key they generated for you.
		vi /etc/jungledisk/junglediskserver-license.xml
			<?xml version="1.0" encoding="utf-8"?><configuration><LicenseConfig><licenseKey>LICENSE_KEY</licenseKey><proxyServer><enabled>0</enabled> <proxyServer></proxyServer><userName></userName><password></password></proxyServer></LicenseConfig></configuration>

			
		need to exclude these from being backed up
			/var/hotcopy   # this is for r1soft cdp
			/var/cache
			/var/jetendo-server/mysql/data/   # make sure /zbackup/mysql is backed up!
			/usr
			/sbin
			/lib
			/lib64
			/bin
			/boot
			/dev
			/proc
			
		Change cache directory to:
			/zbackup/jungledisk/cache/
			
		service junglediskserver restart
	Use the management client interface from https://www.jungledisk.com/downloads/business/server/linux/ to further configure what and when to backup.  It is highly recommended you enable the encrypted backup feature for best security.  Be sure not to lose your decryption password.

Configuring Static IPs
	vi /etc/network/interfaces
	
	# Be careful, you may accidentally take your server off the Internet if you make a mistake.  It is best to do this with KVM access or have the hosting company help you.
	
	# By default, Virtualbox is configured to use NAT, and this configuration looks like this in /etc/network/interfaces after installing ubuntu
		auto eth0
		iface eth0 inet dhcp
	
	# To replace NAT with a static IP for same interface, delete "auto eth0" and "iface eth0 inet dhcp" and use the settings below.  Make sure the IPs match what is provided by your ISP.  The DNS Nameservers should ideally be your ISP's nameservers for best performance or google public dns which is: 8.8.8.8 8.8.4.4
		auto eth0
		iface eth0 inet static
		address 192.168.0.2
		netmask 255.255.255.0
		network 192.168.0.0
		broadcast 192.168.0.255
		gateway 192.168.0.1
		dns-nameservers 192.168.0.1 192.168.0.1
	
	This is the static ip configuration for development server:
		auto eth0
		iface eth0 inet static
			address 10.0.2.15
			netmask 255.255.255.0
			network 10.0.2.0
			broadcast 10.0.2.255
			gateway 10.0.2.2
			dns-nameservers 10.0.2.2
			
		auto eth0:1
		iface eth0:1 inet static
			address 10.0.2.16
			netmask 255.255.255.0
			network 10.0.2.0
			broadcast 10.0.2.255

	# each additional ip appends to the interface name, a colon and a sequential number.  Such as p4p1:1 below.  You can add as many of these as you wish to a single interface.  It is not necessary to specify the dns-nameservers again.
	auto p4p1:1
	iface p4p1:1 inet static
		address 192.168.0.3
		netmask 255.255.255.0
		network 192.168.0.0
		broadcast 192.168.0.255

Visit http://xip.io/ to understand how this free service helps you create development environments with minimal re-configuration.
	Essentially it automates dns configuration, to let you create new domains instantly that point to any ip address you desire.
	http://mydomain.com.127.0.0.1.xip.io/ would attempt to connection to 127.0.0.1 with the host name mydomain.com.127.0.0.1.xip.io. 
	Jetendo has been designed to support this service by default.
	
	You can also use nip.io the same way.
	
By default, this is not needed.  If you want additional pools, add them like this.  one listen path for each fastcgi pool.   /etc/php/7.2/fpm/pool.d/dev.com.conf  - but lets symbolic link it to /var/jetendo-server/system/php/fpm-pool-conf/
			[dev.com]
			listen = /var/jetendo-server/php/run/fpm.dev.com.sock
			listen.owner = www-user
			listen.mode = 0600
			user = devsite1
			group = www-data
			pm = dynamic
			pm.max_children = 5
			pm.min_spare_servers = 1
			pm.max_spare_servers = 2
	useradd -g www-data devsite1
	mkdir /var/www/devsite1
	chown devsite1:www-data /var/www/devsite1
	
	for devsite1.com, nginx uses
		fastcgi_pass unix:/var/jetendo-server/php/run/fpm.devsite1.sock;

Reboot the virtual machine to ensure all services are installed and running before continuing with Jetendo CMS installation
	At a shell prompt, type: 
		reboot
	Also, to turn off the machine gracefully, you can type the following at a shell prompt:
		poweroff
	
		 
Configure Jetendo CMS

	Install the Jetendo source code from git by running the php script below from the command line.
	You can edit this file to change the git repo or branch if you want to work on a fork or different branch of the project.  If you intend to contribute to the project, it would be wise to create a fork first.  You can always change your git remote origin later.
	Note: If you want to run a RELEASE version of Jetendo CMS, skip running this file.
		php /var/jetendo-server/system/install-jetendo.php
		
		or just run these commands:
		chmod 755 /var/jetendo-server/jetendo/
		chmod 770 /var/jetendo-server/jetendo/sites
		mkdir /var/jetendo-server/jetendo/themes
		mkdir /var/jetendo-server/jetendo/sites-writable
		chmod 770 /var/jetendo-server/jetendo/sites-writable
		mkdir /var/jetendo-server/backup/
		
	Add the following mappings to the Lucee web admin for the /var/jetendo-server/jetendo/ context:
		Lucee web admin URL for VirtualBox (create a new password if it asks.)
		
		http://dev.com.127.0.0.2.xip.io:8888/lucee/admin/web.cfm?action=resources.mappings
	
		The resource path for "/zcorecachemapping" must be the sites-writable path for the adminDomain.
		For example, if request.zos.adminDomain = "http://jetendo.your-company.com";
		Then the correct configuration is:
			Virtual: /zcorecachemapping
			Resource Path: /var/jetendo-server/jetendo/sites-writable/jetendo_your-company_com/_cache
		
		Virtual: /zcorerootmapping
		Resource Path: /var/jetendo-server/jetendo/core
		After creating "/zcorerootmapping", click the edit icon and make sure "Web accessible" is checked and click save.
		
		Virtual: /jetendo-themes
		Resource Path: /var/jetendo-server/jetendo/themes
		
		Virtual: /jetendo-sites-writable
		Resource Path: /var/jetendo-server/jetendo/sites-writable
		
		Virtual: /jetendo-database-upgrade
		Resource Path: /var/jetendo-server/jetendo/database-upgrade
	
	Setup the Jetendo datasource - the database, datasource, jetendo_datasource, and request.zos.zcoreDatasource must all be the same name.
		http://dev.com.127.0.0.2.xip.io:8888/lucee/admin/web.cfm?action=services.datasource
		Add mysql datasource named "jetendo" or whatever you've configured it to be in the jetendo config files.
			host: 127.0.0.1
			Required options: 
				Blog: Check
				Clob: Check
				Use Unicode: true
				Alias handling: true
				Allow multiple queries: false
				Zero DateTime behavior: convertToNull
				Auto reconnect: false
				Throw error upon data truncation: false
				TinyInt(1) is bit: false
				Legacy Datetime Code: true

	
	Enable complete null support and set Key case to Preserve Case (fixes javascript case problems) and Template charset to UTF-8:
		http://dev.com.127.0.0.2.xip.io:8888/lucee/admin/server.cfm?action=server.compiler
		
	Enable mail server:
		http://dev.com.127.0.0.2.xip.io:8888/lucee/admin/server.cfm?action=services.mail
		
		Under Mail Servers -> Server (SMTP), type "localhost" and click update"
		
	Configure Lucee security sandbox - Warning: Upgrading Lucee has caused the security sandbox to be deleted in the past.  Be sure to review it periodically.
		http://jetendo.your-company.com.127.0.0.2.xip.io:8888/lucee/admin/server.cfm?action=security.access&sec_tab=SPECIAL
		Under Create new context, select "b180779e6dc8f3bb6a8ea14a604d83d4 (/var/jetendo-server/jetendo/sites)" and click Create
		Then click edit next to the specific web context
		On a production server, set General Access for read and write to "closed" when you don't need to access the Lucee admin.   You can re-enable it only when you need to make changes.
		Under File Access, select "Local" and enter the following directories. 
			Note: In Lucee 4.2, you have to enter one directory at a time by submitting the form with one entered, and then click edit again to enter the next one.
			/var/jetendo-server/jetendo/core
			/var/jetendo-server/jetendo/sites
			/var/jetendo-server/jetendo/share
			/var/jetendo-server/jetendo/execute
			/var/jetendo-server/jetendo/public
			/var/jetendo-server/luceevhosts/1599b2419bcff43008448d60f69f646e/
			/var/jetendo-server/jetendo/sites-writable
			/var/jetendo-server/jetendo/themes
			/var/jetendo-server/jetendo/database-upgrade
			/var/jetendo-server/backup/
			/var/jetendo-server/lucee/tomcat/lucee-server/context/
			/var/jetendo-server/lucee/tomcat/lucee-server/context/userdata
			/zbackup/backup
			/zbackup/jetendo
			
			# if you have other drives, you can do something like this too:
			/zbackup2/backup
			/zbackup2/jetendo
		Uncheck "Direct Java Access"
		Uncheck all the boxes under "Tags & Functions" - Jetendo CMS intentionally allows not using these features to be more secure.
		
	Edit the values in the following files to match the configuration of your system.
		touch /var/jetendo-server/jetendo/core/config.cfc
		chown www-data:www-data /var/jetendo-server/jetendo/core/config.cfc
		chmod 770 /var/jetendo-server/jetendo/core/config.cfc
	If you want to run a RELEASE version of Jetendo CMS, follow these steps:
		Download the release file for the "jetendo" project, and unzip its contents to /var/jetendo-server/jetendo in the virtual machine or server.  Make sure that there is no an extra /var/jetendo-server/jetendo/jetendo directory.  The files should be in /var/jetendo-server/jetendo/
		Download the release file for the "jetendo-default-theme" project and unzip its contents to /var/jetendo-server/jetendo/themes/jetendo-default-theme in the virtual machine or server. Make sure that there is no an extra /var/jetendo-server/jetendo/themes/jetendo-default-theme/jetendo-default-theme directory.  The files should be in /var/jetendo-server/jetendo/themes/jetendo-default-theme
		chmod -R 770 /var/jetendo-server/jetendo/themes
		chown -R www-data:www-data /var/jetendo-server/jetendo/themes
		
		vi /var/jetendo-server/custom-secure-scripts
		vi /var/jetendo-server/custom-secure-scripts/retsConfig.php
			<?php  
			$arrRetsConfig=array();
			?>
		
		Run this command to install the release without forcing it to use the git repository:
			php /var/jetendo-server/jetendo/scripts/install.php disableGitIntegration
			
			If it says "configPath is missing", then you haven't setup php with jetendo.ini correctly yet.
			
		Note: the project will not be installed as a git repository, so you will have to manually perform upgrades in the future.
		
	If you want to run the DEVELOPMENT version of Jetendo CMS, follow these steps:
		Run this command to install the Jetendo CMS cron jobs and verify the integrity of the source code.
			php /var/jetendo-server/jetendo/scripts/install.php
		Any updates since the last time you ran this installation file, will be pulled from github.com.
		Note: The project will be installed as a git respository.
		
	At the end of a successful run of install.php, you'll be told to visit a URL in the browser to complete installation.  The first time you run that URL, it will restore the database tables, and verify the integrity of the installation.  Please be patient as this process can take anywhere from 10 seconds to a couple minutes the first time depending on your environment.
	
	Troubleshooting Tip: If you have a problem during this step, you may need to drop the entire database, and restart the Lucee Server after correcting the configuration.   This is because the first time database installation may fail if you make a mistake in your configuration or if there is a bug in the install script.  Please make us aware of any problems you encountered during installation so we can improve the software.
	
	After it finishes loading, a page should appear saying "You're Almost Ready".
	
	You will be prompted to create a server administrator user and set some other information.
	
	Make sure you select the 127.0.0.1 as the ip on a development machine unless you know what you're doing.
	
	Make sure to remember the email and password you use for the server administrator account.  If you do lose your login, you can reset the password via email.
	
	Once that is done, you will be prompted to login to the server manager and begin using Jetendo CMS.
	
Install KernelCare.com (paid service for no-reboot kernel updates)
		wget https://downloads.kernelcare.com/kernelcare-latest.deb
		dpkg -i kernelcare-latest.deb
		# set license key (where KEY is the actual key you purchased)
		/usr/bin/kcarectl --register KEY
		# To check if patches applied: 
		/usr/bin/kcarectl --info
		# The software will automatically check for new patches every 4 hours. If you would like to run update manually: 
		/usr/bin/kcarectl --update

Preparing the virtual machine for distribution:
	Run these commands inside the virtual machine - it will automatically poweroff when complete.
		killall -9 php
		php /var/jetendo-server/system/clean-machine.php
	In host, run compact on the vdi file - Windows command line example:
		"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" modifyhd jetendo-server-os.vdi --compact
	# The VDI files should be less then 4gb afterwards.
	# Manually 7-zip the virtual machine - It takes about 10 minutes to make it 6 times smaller
	# regular zip takes 5 minutes to make it 5 times smaller
		jetendo-server-os.vdi, jetendo-server-swap.vdi and jetendo-server.vbox
	