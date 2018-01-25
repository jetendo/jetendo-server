Jetendo Server Installation Documentation
OS: Ubuntu Server 14.04 LTS 

This readme is for users that downloaded the pre-configured virtual machine from https://www.jetendo.com/

If you'd like to install the OS yourself from scratch, please use README.txt that should be included in the Jetendo Server or you can download it from https://github.com/jetendo/jetendo-server/

To use this image with virtualbox, there are a few steps to customize it so that you receive the email alerts and that it works with your projects.

Download Jetendo Server 
	If you're reading this readme and haven't cloned or downloaded a release of the Jetendo Server project to your host system, please do so now.
	
	You can grab the latest development version from https://github.com/jetendo/jetendo-server/ or a release version from https://www.jetendo.com/
	
	The Jetendo Server project holds most of the configuration required by the virtual machine.

	You must create and configure jetendo-server/config/server-mac-mapping.php and the related configuration files for the virtual machine within subdirectories of the config directory.  Documentation for this step is currently unavailable.

	Copy jetendo-server/system/php/jetendo.ini.default to jetendo-server/system/php/jetendo.ini
	You must create and configure jetendo-server/system/php/jetendo.ini before booting the virtual machine the first time.  It defines the global variables used for the php scripts.

	The following empty directories must be created in the jetendo-server directory because they are excluded from the git respository since they contain your data:
		nginx
		mysql
		coldfusion
		system
		lucee
		php
		apache
		jetendo
		custom-secure-scripts
		virtual-machines
		logs
		backup


Configure Virtualbox

Virtualbox initial setup
	Make sure you are running the latest version of Virtualbox available for download at https://www.virtualbox.org/

	You should be able to double click the jetendo-server.vbox file to load it into VirtualBox.
	
	Click settings and review/modify the configuration as desired.   These are the recommended default options:
		Ubuntu Linux x64
		Minimum requirements: 2048mb ram, 5gb hard drive, 1gb 2nd hard drive for swap, 1 NAT network adapter
		NAT Advanced Settings -> Port forwarding
			Name: SSH, Host Ip: 127.0.0.2: Host Port: 22, Guest Ip: 10.0.2.15, Guest Port: 22
		Setup Shared Folders - The following names must point to the directory with the same name on your host system.  By default, they are a subdirectory of this project, however, you may relocate the paths if you wish for more space or performance.
			mysql
			coldfusion
			system
			php
			apache
			jetendo
			custom-secure-scripts
			luceevhosts
			zserver
			config
			backup
		Hard Disk 1: jetendo-os.vdi
		Hard Disk 2: jetendo-swap.vdi

After virtual machine has booted:

	The first time it starts, you'll need to login to the console with root and a blank password.

	SSH has to be manually started if you are unable to connect with SSH on the next step - this may happen if you failed to configure jetendo.ini before starting the machine:  
		service ssh start


Verify the VirtualBox shared folders are working:
	Open an SSH connection to the guest machine.
		Reminder, the SSH connection information is as follows:
			Host: 127.0.0.2
			Port: 22
			Username: root
		Note: No password is required to login.
		
		Note: if SSH login fails, you may have a different NAT ip for your guest virtual machine.  In the VirtualBox guest console window, login as root, and then type "ifconfig" to find the ip address for the eth0 interface.   Then update your virtualbox port forwarding to use that ip instead of 10.0.2.15 and try to login to SSH again.
			
	Configure SSH Tunneling on your SSH client for the services you want to use:
		Nginx, Host Ip: 127.0.0.2: Host Port: 80, Guest Ip: 127.0.0.2, Guest Port: 80
		Nginx SSL, Host Ip: 127.0.0.2: Host Port: 443, Guest Ip: 127.0.0.2, Guest Port: 443
		Apache, Host Ip: 127.0.0.3: Host Port: 80, Guest Ip: 127.0.0.3, Guest Port: 80
		Apache SSL, Host Ip: 127.0.0.3: Host Port: 443, Guest Ip: 127.0.0.3, Guest Port: 443
		Lucee, Host Ip: 127.0.0.2: Host Port: 8888, Guest Ip: 127.0.0.1, Guest Port: 8888
		Monit, Host Ip: 127.0.0.2: Host Port: 2812, Guest Ip: 127.0.0.1, Guest Port: 2812
		Coldfusion, Host Ip: 127.0.0.2: Host Port: 8500, Guest Ip: 127.0.0.1, Guest Port: 8500
		MySQL/MariaDB, Host Ip: 127.0.0.2: Host Port: 3306, Guest Ip: 127.0.0.1, Guest Port: 3306
		
	Run this command and verify that the directory isn't empty.
		ls -al /var/jetendo-server/system
	
	If the directory was empty, you most likely need to update the VirtualBox Guest Additions because you are running a different version then our version.  
	
	Follow these steps to upgrade the VirtualBox Guest Additions:

		1. In the VirtualBox Host GUI, click on Devices -> Insert Guest Addition CD Image
		2. Open an SSH connection to the guest machine
		3. Type: mount /dev/cdrom /media/cdrom
		4. Type: /media/cdrom/VBoxLinuxAdditions.run
		5. Type: reboot and then login to SSH again when system is done rebooting.
		6. Verify the shared folder is working by typing: ls -al /var/jetendo-server/system
		7. If you still have trouble, make sure the path is correct on the host system in the shared folders configuration and that you have a complete copy of the Jetendo Server project available at https://github.com/jetendo/jetendo-server

Configure nginx
	By default, the example nginx configuration will only work for one specific domain.  You need to change the hostmap files and make "server" directives using include files in the /var/jetendo-server/system/nginx-conf/sites/ directory.
	The hostmap must be the full local domain name, mapped to the directory name on disk.  I.e. jetendo.your-company.com.127.0.0.2 "jetendo_your-company_com";

Configure MySQL (MariaDB):
	If you already have database data files installed at /var/jetendo-server/mysql/data through the virtualbox shared folders, skip this step.
	
	To reinstall the initial mysql tables, run this command:
		/usr/bin/mysql_install_db --user=mysql --basedir=/usr --datadir=/var/jetendo-server/mysql/data
	
	Restart mysql service:
		/usr/sbin/service mysql restart
		
	Then run:
		aa-complain mysql
		# make sure to set a password and write it down - Yes to all questions (i.e. it is ok to remove root access/anonymous users and test database):
		/usr/bin/mysql_secure_installation --defaults-file=/etc/mysql/my.cnf
		aa-enforce mysql
		
		Setup a user for mysql system maintenance that has all global privileges to all tables
			debian-sys-maint@127.0.0.1

			mysql -u root -p
			#type your password;
			# run these queries
			GRANT ALL PRIVILEGES ON *.* TO 'debian-sys-maint'@'127.0.0.1' IDENTIFIED BY 'YOUR_PASSWORD' WITH GRANT OPTION; 
			FLUSH PRIVILEGES;
		
		Update password (in plain text) in this file:
			/etc/mysql/debian.cnf
	
	Verify you can connect to the mysql server using sqlyog or another GUI in your host system where YOUR_PASSWORD is the password you provided during the execution of "mysql_secure_installation":
		host: 127.0.0.2
		port: 3306
		username: root
		password: YOUR_PASSWORD
	
Configure Postfix
	Edit /etc/aliases,  Find the line for "root" and make it "root: EMAIL_ADDRESS" where EMAIL_ADDRESS is the email address that system & security related emails should be forwarded to.

	vi /etc/postfix/main.cf
	
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
		
	Or relay mail to a google account by adding the following to /etc/postfix/main.cf
			#jetendo-custom-smtp-begin
			relayhost = [smtp.gmail.com]:587
			smtp_sasl_auth_enable = yes
			smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd
			smtp_sasl_security_options = noanonymous
			smtp_tls_CAfile = /etc/postfix/cacert.pem
			smtp_use_tls = yes
			# jetendo-custom-smtp-end
		Then continue with these commands to complete google account setup:  Replace and EMAIL and PASSWORD with your actual login information.  You may want to use a separate gmail account so your business email account password doesn't need to be stored in plain text.
			Create password file:
				vi /etc/postfix/sasl_passwd
					[smtp.gmail.com]:587    EMAIL:PASSWORD
			chmod 400 /etc/postfix/sasl_passwd
			postmap /etc/postfix/sasl_passwd
			cat /etc/ssl/certs/Thawte_Premium_Server_CA.pem | sudo tee -a /etc/postfix/cacert.pem
	
	After changing the postfix configuration, restart the service:
		service postfix reload
		
	Verify the mail service is working, by logging in to the guest machine with SSH and typing the following command:
		echo "Test email" | mailx -s "Hello world" your_email@your_company.com
		
	If the mail service isn't working, make sure you entered the right information and followed the steps correctly.
	
	If the problem persists, check the logs at /var/log/mail.log or /var/log/syslog for error messages.
	
Configure Jungledisk (Optional)
	This is a recommend solution for remote backup of production servers.
	
	Install Jungledisk
		Download 64-bit Server Edition Software from this URL:
		https://www.jungledisk.com/downloads/business/server/linux/
		
		Place in /var/jetendo-server/system/ and run this command to install it.  Make sure the file name matches the file you downloaded.
		dpkg -i /var/jetendo-server/system/junglediskserver_316-0_amd64.deb
		
		Reset the license key on your jungledisk.com account page and replace LICENSE_KEY below with the key they generated for you.
		vi /etc/jungledisk/junglediskserver-license.xml
			<?xml version="1.0" encoding="utf-8"?><configuration><LicenseConfig><licenseKey>LICENSE_KEY</licenseKey><proxyServer><enabled>0</enabled> <proxyServer></proxyServer><userName></userName><password></password></proxyServer></LicenseConfig></configuration>

		service junglediskserver restart
	Use the management client interface from https://www.jungledisk.com/downloads/business/server/linux/ to further configure what and when to backup.  It is highly recommended you enable the encrypted backup feature for best security.  Be sure not to lose your decryption password.
	
Setup Git options
	Personalize git now so we know who made changes later.
	git config --global user.name "Your Name Here"
	git config --global user.email "your_email@example.com"
	git config --global core.filemode false
	
Configure Jetendo CMS

	Install the jetendo source code from git by running the php script below from the command line.
	You can edit this file to change the git repo or branch if you want to work on a fork or different branch of the project.  If you intend to contribute to the project, it would be wise to create a fork first.  You can always change your git remote origin later.
	Note: If you want to run a RELEASE version of Jetendo CMS, skip running this file.
		php /var/jetendo-server/system/install-jetendo.php

		some paths didn't exist on first boot, so we need to restart Lucee now for it to work:
			service lucee_ctl restart
		
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
		After creating "/zcorerootmapping", click the edit icon and make sure "Top level accessible" is checked and click save.
		
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

	copy /var/jetendo-server/jetendo/core/config-default.cfc to /var/jetendo-server/jetendo/core/config.cfc
	Edit the values in the following files to match the configuration of your system.
		/var/jetendo-server/jetendo/core/config.cfc
	
	If you want to run a RELEASE version of Jetendo CMS, follow these steps:
		Download the release file for the "jetendo" project, and unzip its contents to /var/jetendo-server/jetendo in the virtual machine or server.  Make sure that there is no an extra /var/jetendo-server/jetendo/jetendo directory.  The files should be in /var/jetendo-server/jetendo/
		Download the release file for the "jetendo-default-theme" project and unzip its contents to /var/jetendo-server/jetendo/themes/jetendo-default-theme in the virtual machine or server. Make sure that there is no an extra /var/jetendo-server/jetendo/themes/jetendo-default-theme/jetendo-default-theme directory.  The files should be in /var/jetendo-server/jetendo/themes/jetendo-default-theme
		
		Run this command to install it the release without forcing it to use the git repository:
			php /var/jetendo-server/jetendo/scripts/install.php disableGitIntegration
		Note: the project will not be installed as a git repository, so you will have to manually perform upgrades in the future.
		
	If you want to run the DEVELOPMENT version of Jetendo CMS, follow these steps:
		Run this command to install the Jetendo CMS cron jobs and verify the integrity of the source code.
			php /var/jetendo-server/jetendo/scripts/install.php
		Any updates since the last time you ran this installation file, will be pulled from github.com.
		Note: The project will be installed as a git respository.
		
	At the end of a successful run of install.php, you'll be told to visit a URL in the browser to complete installation.  The first time you run that URL, it will restore the database tables, and verify the integrity of the installation.  Please be patient as this process can take anywhere from 10 seconds to a couple minutes the first time depending on your environment.
	
	Troubleshooting Tip: If you have a problem during this step, you may need to drop the entire database, and restart the Lucee Server after correcting the configuration.   This is because the first time database installation may fail if you make a mistake in your configuration or if there is a bug in the install script.  Please make us aware of any problems you encountered during installation so we can improve the software.
	
	After it finishes loading, a page should appear saying "You're Almost Ready".
	
	You will be prompted to create a server administrator user and set some other information.  Make sure to remember the email and password you use for the server administrator account.  If you do lose your login, you can reset the password via email.
	
	Once that is done, you will be prompted to login and begin using Jetendo CMS.

At this point of the readme, you should have a fully working version of Jetendo CMS installed on Jetendo Server.

How to access web sites and administrative services:
	Make sure to update the domains to match the domain you specified in configuration files.
		
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
		
Visit http://xip.io/ to understand how this free service helps you create development environments with minimal re-configuration.
	Essentially it automates dns configuration, to let you create new domains instantly that point to any ip address you desire.
	http://mydomain.com.127.0.0.2.xip.io/ would attempt to connection to 127.0.0.2 with the host name mydomain.com.127.0.0.2.xip.io. 
	Jetendo has been designed to support this service by default.
	
Linux reboot and poweroff commands:
	To reboot Ubuntu linux, type the following at a shell prompt:
		reboot
	To poweroff Ubuntu linux gracefully, type the following at a shell prompt:
		poweroff
	To suddenly & forcefully poweroff the machine (Dangerous), type the following at a shell prompt:
		shutdown -h now
		
	To suddenly & forcefully reboot the machine (Dangerous), type the following at a shell prompt:
		shutdown -r now