#!/usr/bin/php -q                                                
<?php                                                            

# Script for creating vhosts and databases on Linux.
# Copyright (C) 2011  Morten Møllegård Hansen - Bellcom Udvikling ApS

# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.

# vhost hentes fra tools.bellcom.dk/vhost.txt
#                                            
# Sæt $test til true for debug mode hvor kommandoerne kun udskrives
#                                                                  
$test = false;                                                    
#$test = true;                                                       

$mysqlpasswd = "ditkodeordher";
#
# Kontrollerer om der er input fra kommando line
#                                               
if(empty($_SERVER["argv"][1])) {
   echo "Usage: " . $_SERVER["argv"][0] . " <domain.tld>\n";
   exit(0);                                                 
}                                                           
else                                                        
{                                                           
  $sitename = $_SERVER['argv'][1];                            
  $sitename = chop($sitename);                              
#  $sitenamestripped = $_SERVER[argv][2];                   
#  $sitenamestripped = chop($sitenamestripped);             
}                                                           


#
# Printer system info til brugeren - til kontrol
#                                               
print "\nSystemnavn: " . $sitename;             
print "\n\n Tryk på Enter for at oprette systemet.";
$sysName = fread(STDIN, 80); // Read up to 80 characters or a newline


#
#Sætter alle faste variable.
#                           
$sitepath = "/var/www/";    
$vhostaddress = "http://tools.bellcom.dk/vhost.txt";
$exec_genpasswd = "pwgen -N1 -s 10";                
$passwd = exec($exec_genpasswd);                    
$username = preg_replace('/\./', '_', $sitename);   
$dbname = preg_replace('/\-/', '_', $username);     
$dbusername = substr($dbname, 0, 15);
$htpasswdfile = "/var/www/.htpasswd";

#
# Opretter dirs
#              
print "\nOpretter site dir\n";
$systemcmd = "\nmkdir -p $sitepath$sitename/tmp $sitepath$sitename/logs $sitepath$sitename/sessions $sitepath$sitename/public_html";
if ($test) {                                                                                                                        
  print "Udfører(Debug): " . $systemcmd . "\n ";                                                                                    
}                                                                                                                                   
else {                                                                                                                              
  print "Udfører: " . $systemcmd . "\n ";                                                                                           
  system($systemcmd);                                                                                                               
}                                                                                                                                   

#
# Ejer og gruppe
#               
print "\nEjer og gruppe\n";
$systemcmd = "\nchown -R www-data: $sitepath$sitename";
if ($test) {                                           
  print "Udfører(Debug): " . $systemcmd . "\n ";       
}                                                      
else {                                                 
  print "Udfører: " . $systemcmd . "\n ";              
  system($systemcmd);                                  
}                                                      

#
# Rettigheder
#            
print "\nRettigheder\n";
$systemcmd = "\nchmod ug+rwX -R $sitepath$sitename";
if ($test) {                                        
  print "Udfører(Debug): " . $systemcmd . "\n ";    
}                                                   
else {                                              
  print "Udfører: " . $systemcmd . "\n ";           
  system($systemcmd);                               
}                                                   


#
# Tilretter vhost fil - kopier filen til /etc/apache2/sites-available
#                                                                    
print "\nApache2 conf\n";                                            
$systemcmd = "wget -q --output-document=/etc/apache2/sites-available/".$sitename." " . $vhostaddress . "";
if ($test) {                                                                                              
  print "Udfører(Debug): \n" . $systemcmd . "\n";                                                         
}                                                                                                         
else {                                                                                                    
  print "Udfører: \n" . $systemcmd . "\n";                                                                
  system($systemcmd);                                                                                     
}                                                                                                         

#
# Tilretter apache vhost fil
#                           
$systemcmd = "perl -pi -e 's/\[domain\]/".$sitename."/g' /etc/apache2/sites-available/$sitename";
if ($test) {                                                                                     
  print "Udfører(Debug): \n" . $systemcmd . "\n";                                                
}                                                                                                
else {                                                                                           
  print "Udfører: \n" . $systemcmd . "\n";                                                       
  system($systemcmd);                                                                            
}                                                                                                

#
# Enabler vhost
#                           
$systemcmd = "a2ensite " .$sitename."";
if ($test) {
  print "Udfører(Debug): \n" . $systemcmd . "\n";
}
else {
  print "Udfører: \n" . $systemcmd . "\n";
  system($systemcmd);
}

#
#Opretter mysql database og bruger
#
print "\nOpretter mysql database og bruger\n";
$systemcmd = "\nmysql -u root --password='$mysqlpasswd' -e 'CREATE DATABASE $dbname'";
if ($test) {
  print "Udfører(Debug): " . $systemcmd . "\n";
}
else {
  print "Udfører: " . $systemcmd . "\n";
  system($systemcmd);
}
$systemcmd = "\nmysql -u root --password='$mysqlpasswd' -e 'GRANT ALL ON $dbname.* TO $dbusername@localhost IDENTIFIED BY \"$passwd\"'";
if ($test) {
  print "Udfører(Debug): " . $systemcmd . "\n";
}
else {
  print "Udfører: " . $systemcmd . "\n";
  system($systemcmd);
}


#
# Laver htaccess adgang
#
#$systemcmd = "/usr/bin/htpasswd -b " . $htpasswdfile . " " .$sitenamestripped . " " . $passwd ;
#if ($test) {
#  print "Udfører(Debug): \n" . $systemcmd . "\n";
#}
#else {
#  print "Udfører: \n" . $systemcmd . "\n";
#  system($systemcmd);
#}

#
# Mail and print info
#
$hostname = system("hostname");
mail("mmh@bellcom.dk","New site at $hostname","DBNAME = $dbname DBUSERNAME = $dbusername DBPASS = $passwd\nSitepath: $sitepath$sitename\n");

print "\n\nHusk at reload apache med:\n";
print "/etc/init.d/apache2 reload\n";
print "Ret evt. vhost'en med flere domæner\n";
#print "\nhtaccess bruger til kunden: " . $sitenamestripped . " / " . $passwd."\n";
print "DBNAME = $dbname DBUSERNAME = $dbusername DBPASS = $passwd\n";
print "Sitepath: $sitepath$sitename\n";
?>

