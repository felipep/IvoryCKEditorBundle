<?php
/*
 * FILE:        descheck.php
 * AUTHOR:      Macron Software (www.macronsoftware.com); mafin
 * DATE:        2011-07-20
 * DESCRPTION:  DBPT - checking settings
 * VERSION:     1.0
 *
 *
 * Copyright (C) 2011 - Bibliographisches Institut GmbH, Mannheim, Germany.
 * All rights reserved.
 *
 */
require_once 'config.php';

if (empty($_POST['lang']))
	return false;

$error = des_check($_POST['lang']);

if ($error)
{
	header('Content-Type: text/json');
	die('{"status":"error", "msg":"' . addslashes($error) . '"}');
}
else
{
	header('Content-Type: text/json');
	die('{"status":"ok"}');
}

function des_check($lang)
{

	$username = $password = null;

//php settings
	$exts = get_loaded_extensions();
	$code = "error";

	if (!in_array('dom', $exts) && !in_array('domxml', $exts))
	{
		//There is no DOM PHP extension!		
		return get_error_code('1' . $lang, $lang);
	}

	if (!@fopen('NuSOAP/nusoap.php', 'r', true))
	{
		//There is no PHP class "NuSOAP"	
		return get_error_code('2' . $lang, $lang);
	}

	if (!@fopen('Net/DIME.php', 'r', true))
	{
		//There is no PEAR extension "Net_DIME"	
		return get_error_code('3' . $lang, $lang);
	}

//dict folder access
	$dic_dir = dirname(__FILE__) . '/dict/';

	if (!is_dir($dic_dir) || !is_readable($dic_dir) || !is_writeable($dic_dir))
	{
		//Can't access to dictionary folder	
		return get_error_code('4' . $lang, $lang);
	}

//DES access	
	if (defined('DPFWS_USERNAME'))
	{
		$username = DPFWS_USERNAME;
		$password = DPFWS_PASSWORD;
	}

	require_once 'dpfws.php';
	$dpfws = new DpfWrapper();

	$auth = array('username' => $username, 'password' => $password);
	$param = array('auth' => $auth, 'options' => array());

	$result = $dpfws->call('startSession', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);

	if ($dpfws->fault)
	{
		return get_error_code('5' . $lang, $lang);
	}
	else
	{
		if ($dpfws->getError())
		{
			return get_error_code('6' . $lang, $lang);
		}
		else
		{
			$param = array('sessionid' => $result['sessionid']);
			$result = $dpfws->call('stopSession', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
			return false;
		}
	}
}

function get_error_code($code, $lang)
{
	$msg = "";
	$xmlPath = dirname(__FILE__) . "/xml/";
	$phpver = 0 + substr(PHP_VERSION, 0, strpos(PHP_VERSION, '.'));
	$query = "//error[@code=\"$code\"]/message[@msglang=\"$lang\"]/expert/longmsg";
	if ($phpver > 4)
	{
		$dom = DOMDocument::load($xmlPath . 'errors.xml');
		$xpath = new DOMXPath($dom);
		$xpresult = $xpath->query($query);
		if ($xpresult->length > 0)
		{
			$xnode = $xpresult->item(0);
			if ($xnode)
			{
				$msg = $xnode->nodeValue;
			}
		}
	}
	else
	{
		$dom = domxml_open_file($xmlPath . 'errors.xml');
		$xpath = xpath_new_context($dom);
		$xpresult = xpath_eval($xpath, $query);
		if ($xpresult)
		{
			$msg = $xpresult->nodeset[0]->get_content();
		}
	}
	return $msg;
}
?>