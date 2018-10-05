<?php

// CONFIG

// hostname or IP address of Fritz!Box
// Note: Some Boxes are configured to reject login via IP.
//       If that's the case with your box, then you have to specify the hostname.
$config['fritzbox_ip'] = 'fritz.box';

// user name/password to access Fritz!Box
$config['fritzbox_user'] = 'fbuser';
$config['fritzbox_pw'] = 'fbuserpass';
//$config['fritzbox_force_local_login'] = true;

// number of the internal phone book and its name
// 0    - main phone book
// 1..n - additional phone books
$config['phonebook_number_Score7'] = '1';
$config['phonebook_number_Score89'] = '2';

// download links form tellows api pdf
$config['score7_url'] = 'tellows Download LINK';
$config['score89_url'] = 'tellows Download LINK';

// Just download Tellows file
// $config['output_file'] = '/tmp/tellows_file.xml';

// Add Date to phonebook name
$config['add_date'] = true;