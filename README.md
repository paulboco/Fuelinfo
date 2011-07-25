#Fuelinfo

A class that outputs information about Fuel.

My purpose for writing this class was to teach myself to understand Fuel.
I have found it useful on occasion for 'sanity check' situations, but is probably not much help to those that understand Fuel and is therefore offered as a noob tool.

NOTE! This class if for DEVELOPMENT environments only as it outputs configuration information to the display.

##Usage
Fuelinfo::all()  - to display all sections except phpinfo.

or for a specific section...

Fuelinfo::routes();  
Fuelinfo::request();  
Fuelinfo::modules();  
Fuelinfo::packages();  
Fuelinfo::database();  
Fuelinfo::session();  
Fuelinfo::config();  
Fuelinfo::phpinfo();  
