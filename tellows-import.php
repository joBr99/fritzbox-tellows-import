<?php
/**
 * FritzBox! XML Import from Tellows API
 *
 */
error_reporting(E_ALL);
setlocale(LC_ALL, 'de_DE.UTF8');

// check for the minimum php version
$php_min_version = '5.3.6';
if(version_compare(PHP_VERSION, $php_min_version) < 0)
{
  print 'ERROR: PHP version ' . $php_min_version . ' is required. Found version: ' . PHP_VERSION . PHP_EOL;
  exit(1);
}

require_once('lib/fritzbox_api_php/fritzbox_api.class.php');

if($argc == 2)
  $config_file_name = $argv[1];
else
  $config_file_name = __DIR__ . '/config.php';

// default/fallback config options
$config['tmp_dir'] = sys_get_temp_dir();
$config['fritzbox_ip'] = 'fritz.box';
$config['fritzbox_ip_ftp'] = $config['fritzbox_ip'];
$config['fritzbox_force_local_login'] = false;
$config['phonebook_number_Score7'] = '1';
$config['phonebook_number_Score89'] = '2';

if(is_file($config_file_name))
  require($config_file_name);
else
{
  print 'ERROR: No ' . $config_file_name . ' found, please take a look at config.example.php and create a ' . $config_file_name . ' file!' . PHP_EOL;
  exit(1);
}

// ---------------------------------------------
// MAIN
print "tellows-import.php  - Import Scorelists from Tellows" . PHP_EOL;


print "Updateing Scorelist 7" . PHP_EOL;


$client = new CardDAV2FB($config);

$client->build_fb_xml($config['score7_url']);

flush(); // in case this script runs by php-cgi

// upload the XML-file to the FRITZ!Box (CAUTION: this will overwrite all current entries in the phone book!!)
if($client->upload_to_fb($config['phonebook_number_Score7']))
  print 'Done.' . PHP_EOL;
else
  exit(1);

flush(); // in case this script runs by php-cgi


print "Updateing Scorelist 89" . PHP_EOL;
$client->build_fb_xml($config['score89_url']);

flush(); // in case this script runs by php-cgi

// upload the XML-file to the FRITZ!Box (CAUTION: this will overwrite all current entries in the phone book!!)
if($client->upload_to_fb($config['phonebook_number_Score89']))
    print 'Done.' . PHP_EOL;
else
    exit(1);

flush(); // in case this script runs by php-cgi



// ---------------------------------------------
// Class definition
class CardDAV2FB
{
  protected $entries = array();
  protected $fbxml = "";
  protected $config = null;
  protected $tmpdir = null;

  public function __construct($config)
  {
    $this->config = $config;

    // create a temp directory where we store photos
    $this->tmpdir = $this->mktemp($this->config['tmp_dir']);
  }

  public function __destruct()
  {
    // remote temp directory
    $this->rmtemp($this->tmpdir);
  }

  // Source: https://php.net/manual/de/function.tempnam.php#61436
  public function mktemp($dir, $prefix = '', $mode = 0700)
  {
    if(substr($dir, -1) != '/')
      $dir .= '/';

    do
    {
      $path = $dir . $prefix . mt_rand(0, 9999999);
    }
    while(!mkdir($path, $mode));

    return $path;
  }

  public function rmtemp($dir)
  {
    if(is_dir($dir))
    {
      $objects = scandir($dir);
      foreach($objects as $object)
      {
        if($object != "." && $object != "..")
        {
          if(filetype($dir . "/" . $object) == "dir")
            rrmdir($dir . "/" . $object); else unlink($dir . "/" . $object);
        }
      }
      reset($objects);
      rmdir($dir);
    }
  }

  public function build_fb_xml($url)
  {
      if (!$data = file_get_contents($url)) {
          $error = error_get_last();
          echo "HTTP request failed. Error was: " . $error['message'];
          exit(1);
      }
      $file_len = strlen ($data);
      print " Downloaded $file_len bytes successfully." . PHP_EOL;

      $root = new SimpleXMLElement($data);

      if(array_key_exists('add_date', $this->config) && $this->config['add_date']){
        // Add Date and Time
        $phonebook_name = $root->phonebook['name'];
        $root->phonebook['name'] = "$phonebook_name " . date("m.d.y H:i:s");
      }

      if($root->asXML() !== false)
        $this->fbxml = $root->asXML();
      else
      {
        print "  ERROR: created XML data isn't well-formed." . PHP_EOL;
        exit(1);
      }
  }

  public function _convert_text($text)
  {
    $text = htmlspecialchars($text);
    return $text;
  }

  public function _concat($text1, $text2)
  {
    if($text1 == '')
      return $text2;
    elseif($text2 == '')
      return $text1;
    else
      return $text1 . ", " . $text2;
  }

  public function _parse_fb_result($text)
  {
    if(preg_match("/\<h2\>([^\<]+)\<\/h2\>/", $text, $matches) == 1 && !empty($matches))
      return $matches[1];
    else
      return "Error while uploading xml to fritzbox";
  }

  public function upload_to_fb($phonebookid)
  {
    // if the user wants to save the xml to a separate file, we do so now
    if(array_key_exists('output_file', $this->config))
    {
      // build md5 hash of previous stored xml without <mod_time> Elements
      $oldphonebhash = md5(preg_replace("/<mod_time>(\\d{10})/","",file_get_contents($this->config['output_file'],'r'),-1,$debugoldtsreplace));
      $output = fopen($this->config['output_file'], 'w');
      if($output)
      {
        fwrite($output, $this->fbxml);
        fclose($output);
        print " Saved to file " . $this->config['output_file'] . PHP_EOL;
      }
	  if (array_key_exists('output_and_upload', $this->config) and $this->config['output_and_upload'])
	  {
	  	$newphonebhash = md5(preg_replace("/<mod_time>(\\d{10})/","",file_get_contents($this->config['output_file'],'r'),-1,$debugnewtsreplace));
	  	print " INFO: Compare old and new phonebook file versions." . PHP_EOL . " INFO: old version: " . $oldphonebhash . PHP_EOL . " INFO: new version: " . $newphonebhash . PHP_EOL;
	  	if($oldphonebhash === $newphonebhash)
      	{
      	print " INFO: Same versions ==> No changes in phonebook or images" . PHP_EOL . " EXIT: No need to upload phonebook to the FRITZ!Box.". PHP_EOL;
      	return 0;
      	}
      	else
      	print " INFO: Different versions ==> Changes in phonebook." . PHP_EOL . " INFO: Changes dedected! Continue with upload." . PHP_EOL;
      }
	  else
      return 0;  
    }

    // lets post the phonebook xml to the FRITZ!Box
    print " Uploading Phonebook XML to " . $this->config['fritzbox_ip'] . PHP_EOL;
    try
    {
      $fritz = new fritzbox_api($this->config['fritzbox_pw'],
        $this->config['fritzbox_user'],
        $this->config['fritzbox_ip'],
        $this->config['fritzbox_force_local_login']);

      $formfields = array(
        //'PhonebookId' => $this->config['phonebook_number_Score7']
        'PhonebookId' => $phonebookid
      );

      $filefileds = array('PhonebookImportFile' => array(
       'type' => 'text/xml',
       'filename' => 'updatepb.xml',
       'content' => $this->fbxml,
       )
      );

      $raw_result = $fritz->doPostFile($formfields, $filefileds); // send the command
      $msg = $this->_parse_fb_result($raw_result);
      unset($fritz); // destroy the object to log out

      print "  FRITZ!Box returned message: '" . $msg . "'" . PHP_EOL;
    }
    catch(Exception $e)
    {
      print "  ERROR: " . $e->getMessage() . PHP_EOL; // show the error message in anything failed
      return false;
    }
    return true;
  }
}
