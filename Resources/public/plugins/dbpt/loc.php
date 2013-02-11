<?php
header('Content-Type: text/json');

require_once 'config.php';

$xmlPath = dirname(__FILE__) . '/xml/loc.xml';

if (!is_file($xmlPath))
{
	die(json_encode(array('status' => 'error', 'data' => 'loc.xml file is missing.')));
}

if (!is_readable($xmlPath))
{
	die(json_encode(array('status' => 'error', 'data' => 'The user doesn\'t have rights to read loc.xml file.')));
}

$exts = get_loaded_extensions();
if (!in_array('dom', $exts) && !in_array('domxml', $exts))
{	
	die(json_encode(array('status' => 'error', 'data' => 'XML parser is missing. Please, check if DOM-XML PHP extension is installed')));
}

$xml = new SimpleXMLElement(file_get_contents($xmlPath));
$output = array();

$locs = $xml->xpath('//loc/message[@msglang="' . DPFWS_INTERFACE_LANGUAGE . '"]');

foreach ($locs as $loc)
{
	$parent = $loc->xpath('parent::*');
	$output[(string) $parent[0]['code']] = (string) $loc;
}

die(json_encode(array('status' => 'ok', 'data' => $output)));
?>