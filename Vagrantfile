# -*- mode: ruby -*-
# vi: set ft=ruby :
php_app = "Hams"
ip_address = "192.168.33.69"

script = <<SCRIPT
apt-get update -y

apt-get install git -y > /dev/null

apt-get install apache2 -y > /dev/null

add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y php7.1
apt-get install -y php7.1-curl
apt-get install -y php7.1-common
apt-get install -y php7.1-cli
apt-get install -y php7.1-readline
apt-get install -y php7.1-xml
apt-get install -y php7.1-zip
apt-get install -y php7.1-mbstring

apt-get install -y php7.1-fpm
apt-get install -y php7.1-intl

apt-get install -y mcrypt
apt-get install -y php7.1-mcrypt

apt-get install -y php-imagick

apt-get install debconf-utils -y > /dev/null

a2enmod rewrite

mkdir /home/vagrant/#{php_app}
mkdir /home/vagrant/#{php_app}/public
chown -R vagrant.vagrant /home/vagrant

perl -p -i -e 's/www-data/vagrant/ge;' /etc/apache2/envvars

cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
  TimeOut 600
  DocumentRoot /home/vagrant/#{php_app}/public

  <Directory /home/vagrant/#{php_app}/public>
    AllowOverride All
    Require all granted
  </Directory>

  ServerAdmin webmaster@localhost

  ErrorLog /home/vagrant/#{php_app}/var/log/apache_error.log
  CustomLog /home/vagrant/#{php_app}/var/log/apache_access.log combined
</VirtualHost>
EOF

mv /etc/supervisor/supervisord.conf /etc/supervisor/supervisord.conf.old
ln -s /home/vagrant/$1/misc/vagrantsupervisor.conf /etc/supervisor/supervisord.conf

service apache2 restart

chown -R vagrant /home/vagrant/.ssh/id_rsa*
chmod 600 /home/vagrant/.ssh/id_rsa*
SCRIPT

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "ubuntu/trusty64"
  config.vm.box_url = "https://cloud-images.ubuntu.com/vagrant/trusty/current/trusty-server-cloudimg-amd64-vagrant-disk1.box"

  config.vm.network :private_network, ip: (ip_address)

  config.vm.provider :virtualbox do |vb|
    vb.customize ["modifyvm", :id, "--memory", "4096"]
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
    vb.customize ["modifyvm", :id, "--cpus", 4]
  end

  config.vm.synced_folder ".", "/home/vagrant/#{php_app}", type: "rsync", rsync__auto: true, rsync__exclude: [
 	".git/",
 	".vagrant/",
  ]

  #SSH Credentials
  config.vm.provision "file", source: "~/.ssh/id_rsa", destination: "/home/vagrant/.ssh/id_rsa"
  config.vm.provision "file", source: "~/.ssh/id_rsa.pub", destination: "/home/vagrant/.ssh/id_rsa.pub"

  config.vm.provision "shell", inline: script, args: php_app

end
