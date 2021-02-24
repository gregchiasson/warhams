# -*- mode: ruby -*-
# vi: set ft=ruby :
php_app = "hsnopi"
ip_address = "192.168.33.69"

script = <<SCRIPT
apt-get update -y
echo "INstalling php php-xml php-mbstring php-zip libapache2-mod-php php-imagick apache2--------"
apt-get install -y php php-xml php-mbstring php-zip libapache2-mod-php php-imagick apache2 -y > /dev/null

echo "Installs Done enable rewrite---------------------------------------------------------"
a2enmod rewrite
echo "backup---------------------------------------------------------------------------"
mv  /etc/apache2/sites-available/000-default.conf /home/vagrant/#{php_app}/000-default.conf

sed -i 's/www-data/vagrant/g;' /etc/apache2/envvars
echo "Install CONF----------------------------------------------------------------------"
cat <<EOF > /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
  TimeOut 600
  DocumentRoot /home/vagrant/#{php_app}/public

  <Directory /home/vagrant/#{php_app}/public>
    AllowOverride All
    Require all granted
  </Directory>

  ServerAdmin hsnopi@hsnopi.net

  ErrorLog /home/vagrant/#{php_app}/apache_error.log
  CustomLog /home/vagrant/#{php_app}/apache_access.log combined
</VirtualHost>
EOF
echo "ServerName localhost" | sudo tee -a /etc/apache2/apache2.conf
echo "Restart apache------------------------------------------------------------------"
apache2ctl configtest
sudo sed -i 's#<policy domain="coder" rights="none" pattern="PDF" />#<policy domain="coder" rights="read|write" pattern="PDF" />#' /etc/ImageMagick-6/policy.xml

service apache2 restart


SCRIPT

# Vagrantfile API/syntax version. Don't touch unless you know what you're doing!
VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|

  config.vm.box = "ubuntu/xenial64"
  config.vm.box_url = "https://app.vagrantup.com/ubuntu/boxes/xenial64/versions/20210211.0.0/providers/virtualbox.box"

  config.vm.network :private_network, ip: (ip_address)

  config.vm.provider :virtualbox do |vb|
    vb.customize ["modifyvm", :id, "--memory", "4096"]
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]
    vb.customize ["modifyvm", :id, "--cpus", 4]
  end

  config.vm.synced_folder ".", "/home/vagrant/#{php_app}", type: "virtualbox"

  #SSH Credentials
  config.vm.provision "file", source: "~/.ssh/id_rsa", destination: "/home/vagrant/.ssh/id_rsa"
  config.vm.provision "file", source: "~/.ssh/id_rsa.pub", destination: "/home/vagrant/.ssh/id_rsa.pub"
  config.vm.hostname = "localhost"

  config.vm.provision "shell", inline: script, args: php_app

end
