<?php

/*
 * FILE:        spell.php
 * AUTHOR:      Macron Software (www.macronsoftware.com); rasvo
 * DATE:        2009-01-28
 * DESCRPTION:  DBPT - backend functionality
 * VERSION:     2.01
 *
 *
 * Copyright (C) 2011 - Bibliographisches Institut GmbH, Mannheim, Germany.
 * All rights reserved.
 *
 */

require_once('cpaint/cpaint2.inc.php'); // AJAX library file
require_once('dpfws.php'); // DPFWS wrapper
require_once('config.php');

session_start();

$cp = new cpaint();
$cp->register('dpfws_get_guid');
$cp->register('dpfws_get_last_error_message');
$cp->register('dpfws_get_error_message_by_code');
$cp->register('dpfws_start_session');
$cp->register('dpfws_stop_session');
$cp->register('dpfws_check');
$cp->register('dpfws_stop_check');
$cp->register('dpfws_load_user_dict');
$cp->register('dpfws_save_user_dict');
$cp->register('dpfws_load_except_dict');
$cp->register('dpfws_save_except_dict');
$cp->register('dpfws_add_to_dict');
$cp->register('dpfws_set_paragraph_elements');
$cp->register('dpfws_post_statistics');
$cp->start();
$cp->return_data();

function save_to_file($string)
{
	$filename = dirname(__FILE__) . "/dict/temp_" . date("Y-m-d_H.i.s") . ".txt";
	if (file_exists($filename))
	{
		for ($i = 0; $i < 10; $i++)
		{
			$filename = dirname(__FILE__) . "/dict/temp_" . date("Y-m-d_H.i.s") . "_" . $i . ".txt";
			if (!file_exists($filename))
				break;
		}
	}
	$handle = fopen($filename, "w");
	if ($handle)
	{
		fwrite($handle, $string);
		fwrite($handle, "\r\n");
		fclose($handle);
	}
}

function flag_to_string($flag)
{
	if ($flag == 0)
		return 'disable';
	else
		return 'enable';
}

function dpfws_get_guid()
{
	global $cp;

	$retval = !empty($_COOKIE['dpfws_guid']) ? $_COOKIE['dpfws_guid'] : null;
	$cp->set_data($retval);
	return $retval;
}

function dpfws_get_last_error_message()
{
	global $cp;
	$retval = $_SESSION['dpfwserr'];
	$cp->set_data($retval);
}

// get error message
// aCode	error code
// aLang	language of error message
// return error message
function dpfws_get_error_message_by_code($spellid, $aCode, $aLang, $errors = false)
{
	global $cp;
	$retval = $spellid . ";";
	$msgText = "";
	$xmlPath = dirname(__FILE__) . "/xml/";
	$phpver = 0 + substr(PHP_VERSION, 0, strpos(PHP_VERSION, '.'));
	$query = "//error[@code=\"$aCode\"]/message/expert/longmsg";
	if ($phpver > 4)
	{
		$dom = DOMDocument::load($xmlPath . (($errors) ? 'errors.xml' : "messages.xml"));
		$xpath = new DOMXPath($dom);
		$xpresult = $xpath->query($query);
		if ($xpresult->length > 0)
		{
			$xnode = $xpresult->item(0);
			if ($xnode)
			{
				$msgText = $xnode->nodeValue;
			}
		}
	}
	else
	{
		$dom = domxml_open_file($xmlPath . (($errors) ? 'errors.xml' : "messages.xml"));
		$xpath = xpath_new_context($dom);
		$xpresult = xpath_eval($xpath, $query);
		if ($xpresult)
		{
			$msgText = $xpresult->nodeset[0]->get_content();
		}
	}
	$retval .= $msgText;
	$cp->set_data($retval);
}

function dpfws_start_session_on_error($dpfws, $retval)
{
	global $cp;
	$_SESSION['dpfwserr'] = $dpfws->getErrorMessage();
	$cp->set_data($retval);
}

function dpfws_start_session($spellid, $restart, $sessionid, $username, $password, $lang, $level, $style, $foreignwords, $colloquialwords, $dialect, $obsoletewords, $sentlength)
{
	global $cp;

	$retval = $spellid . ";";
	$_SESSION['dpfwserr'] = '';
	$bret = FALSE;
	$tmpstr = '';
	$checkMode = 'CmNone';
	$orthStd = 'OsDuden';
	$dpfws = new DpfWrapper($sessionid);
	if (!is_null($sessionid))
	{
		if ($restart)
		{
			$dpfws->stopSession();
		}
		else
		{
			if ($dpfws->isFinished() != -2)
			{
				$retval .= $sessionid;
				$cp->set_data($retval);
				return;
			}
		}
	}
	if (defined('DPFWS_USERNAME'))
	{
		$username = DPFWS_USERNAME;
		$password = DPFWS_PASSWORD;
	}
	switch ($level)
	{
		case 'spell':
			$checkMode = 'CmSpellSuggest';
			break;
		case 'spellgram':
			$checkMode = 'CmGramThorough';
			break;
		case 'spellgramstyle':
			$checkMode = 'CmGramThorough';
			break;
		case 'style':
			$checkMode = 'CmGramThorough';
			break;
	}
	switch ($lang)
	{
		case 'de': // German
		case 'ch': // Swiss
		case 'at': // Austrian
			switch ($style)
			{
				case 'pro':
					$orthStd = 'OsProgressive';
					break;
				case 'con':
					$orthStd = 'OsConservative';
					break;
				case 'duden':
					$orthStd = 'OsDuden';
					break;
				case 'ext':
					$orthStd = 'OsExtended';
					break;
				case 'press':
					$orthStd = 'OsPressAgency';
					break;
			}
			break;
	}

	if (!$dpfws->startSession($username, $password, $lang, $checkMode, $orthStd, $foreignwords, $colloquialwords, $dialect, $obsoletewords))
	{
		$retval .= "__error__startsession__";
		dpfws_start_session_on_error($dpfws, $retval);
		return;
	}

	$strAccept = '';
	$tmpstr = dpfws_get_user_dict_data($lang);
	if (strlen($tmpstr) > 0)
	{
		$keys = preg_split("/;/", $tmpstr);
		foreach ($keys as $v)
		{
			if (strlen($v) > 0)
			{
				$strAccept .= "<accept><key>" . $v . "</key></accept>";
			}
		}
	}
	$strReject = '';
	$tmpstr = dpfws_get_user_except_data($lang);
	if (strlen($tmpstr) > 0)
	{
		$keys = preg_split("/\r?\n/", $tmpstr);
		foreach ($keys as $v)
		{
			$exception = preg_split("/\t/", $v);
			if (strlen($exception[0]) > 0)
			{
				$strReject .= "<reject><key>" . $exception[0] . "</key>";
				$strReject .= "<proposal>" . $exception[1] . "</proposal></reject>";
			}
		}
	}

	if ((strlen($strAccept) > 0) || (strlen($strReject) > 0))
	{
		$dict = "<?xml version=\"1.0\" encoding=\"utf-8\"?><dictionary language=\"" . dpfws_get_dict_lang($lang) . "\">";
		$dict .= $strAccept;
		$dict .= $strReject;
		$dict .= '</dictionary>';
		$dpfws->loadAttachedDictionary($dict);
	}

	$retval .= $dpfws->getSessionId();
	$cp->set_data($retval);
}

function dpfws_stop_session($spellid, $sessionid)
{
	global $cp;
	$retval = $spellid . ";";
	$_SESSION['dpfwserr'] = '';
	$dpfws = new DpfWrapper($sessionid);
	$bret = $dpfws->stopSession();
	/*if ($bret) {
	$bret = $dpfws->logoff();//undefined function
}*/
	if ($bret)
	{
		$retval .= 'ok';
	}
	else
	{
		$_SESSION['dpfwserr'] = $dpfws->getErrorMessage();
	}
	$cp->set_data($retval);
}

function dpfws_check($spellid, $sessionid, $string)
{
	global $cp;
	$string = substr($string, 1); // B 16536
	$retval = $spellid . ";" . $sessionid . ";";
	$result = '';
	$_SESSION['dpfwserr'] = '';
	$dpfws = new DpfWrapper($sessionid);

	$string = str_replace("~~~~", "\n", $string);

	if ($dpfws->check($string))
	{
		$result = $dpfws->getResultsAll();
	}
	else
	{
		$_SESSION['dpfwserr'] = $dpfws->getErrorMessage();
	}
	$retval .= $result;

	$cp->set_data($retval);
}

function dpfws_stop_check($spellid, $sessionid)
{
	global $cp;
	$retval = $spellid . ";";
	$_SESSION['dpfwserr'] = '';
	$dpfws = new DpfWrapper($sessionid);
	$result = $dpfws->stopCheck();
	if ($result)
	{
		$dpfws->stopSession();
	}
	$cp->set_data($retval);
}

function dpfws_load_user_dict($lang)
{
	global $cp;
	$retval = '';
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid() . "/usr" . $lang . ".txt";
	$handle = fopen($filename, "rb");
	if ($handle)
	{
		$data = fread($handle, filesize($filename));
		$retval = preg_replace("/\r?\n/", "~~~~", $data);
		fclose($handle);
	}
	$cp->set_data($retval);
}

function dpfws_save_user_dict($lang, $data)
{
	global $cp;
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid();
	if (!is_dir($filename))
	{
		mkdir($filename);
	}
	$filename .= "/usr" . $lang . ".txt";
	$handle = fopen($filename, "wb");
	if ($handle)
	{
		fwrite($handle, str_replace('~~~~', PHP_EOL, $data));
		fclose($handle);
	}
	$cp->set_data($lang);
}

function dpfws_load_except_dict($lang)
{
	global $cp;
	$retval = '';
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid() . "/exc" . $lang . ".txt";
	$handle = fopen($filename, "rb");
	if ($handle)
	{
		$data = fread($handle, filesize($filename));
		$retval = preg_replace("/\r?\n/", "~~~~", $data);
		$retval = preg_replace("/\t/", "~tb~", $retval);
		fclose($handle);
	}
	$cp->set_data($retval);
}

function dpfws_save_except_dict($lang, $data)
{
	global $cp;
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid();
	if (!is_dir($filename))
	{
		mkdir($filename);
	}
	$filename .= "/exc" . $lang . ".txt";
	$handle = fopen($filename, "wb");
	if ($handle)
	{
		$data = str_replace('~~~~', PHP_EOL, $data);
		$data = str_replace('~tb~', "\t", $data);
		fwrite($handle, $data);
		fclose($handle);
	}
	$cp->set_data($lang);
}

function dpfws_add_to_dict($sessionid, $lang, $word)
{
	global $cp;
	$curdata = dpfws_get_user_dict_data($lang);
	$tmpstr = ";" . $curdata;
	$findme = ";" . $word . ";";
	if (strpos($tmpstr, $findme) === false)
	{ // B 16527
		$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid();
		if (!is_dir($filename))
		{
			mkdir($filename);
		}
		$filename .= "/usr" . $lang . ".txt";
		$handle = fopen($filename, "ab");
		if ($handle)
		{
			if (strlen($curdata) > 0)
			{
				fwrite($handle, PHP_EOL);
			}
			fwrite($handle, $word);
			fclose($handle);
		}
		$dpfws = new DpfWrapper($sessionid);
		$dpfws->addUserEntry($word);
		$cp->set_data($lang);
	}
}

function dpfws_get_user_dict_data($lang)
{
	$retval = '';
	$data = '';
	$data2 = '';
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid() . "/usrneutr.txt";
	$handle = fopen($filename, "rb");
	if ($handle != false)
	{
		$data = fread($handle, filesize($filename));
		fclose($handle);
		if ($data != false)
		{
			$data .= PHP_EOL;
		}
	}
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid() . "/usr" . $lang . ".txt";
	$handle = fopen($filename, "rb");
	if ($handle != false)
	{
		$data2 = fread($handle, filesize($filename));
		fclose($handle);
		if ($data2 != false)
		{
			$data .= $data2 . PHP_EOL;
		}
	}
	if (strlen($data) > 0)
	{
		$retval = preg_replace("/\r?\n/", ";\n", $data);
		$retval = preg_replace("/\n/", "", $retval);
	}
	return $retval;
}

function dpfws_get_user_except_data($lang)
{
	$retval = '';
	$data = '';
	$data2 = '';
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid() . "/excneutr.txt";
	$handle = fopen($filename, "rb");
	if ($handle)
	{
		$data = fread($handle, filesize($filename));
		fclose($handle);
		if (strlen($data) > 0)
		{
			$data .= PHP_EOL;
		}
	}
	$filename = dirname(__FILE__) . "/dict/" . dpfws_get_guid() . "/exc" . $lang . ".txt";
	$handle = fopen($filename, "rb");
	if ($handle)
	{
		$data2 = fread($handle, filesize($filename));
		fclose($handle);
		if (strlen($data2) > 0)
		{
			$data .= $data2 . PHP_EOL;
		}
	}
	$retval = $data;
	return $retval;
}

function dpfws_get_dict_lang($lang)
{
	switch ($lang)
	{
		case "en": // General English
			$retVal = "english";
			break;
		case "gb": // British English
			$retVal = "english";
			break;
		case "us": // American English
			$retVal = "english";
			break;
		case "sp": // Spanish
			$retVal = "spanish";
			break;
		case "it": // Italian
			$retVal = "italian";
			break;
		case "fr": // French
			$retVal = "french";
			break;
		case "de": // German
			$retVal = "german";
			break;
		case "ch": // Swiss
			$retVal = "german";
			break;
		case "at": // Austrian
			$retVal = "german";
			break;
		default:
			$retVal = "any";
			break;
	}
	return $retVal;
}

function dpfws_set_paragraph_elements($sessionid)
{
	global $cp;
	$retval = '';
	$dpfws = new DpfWrapper($sessionid);
	$bret = $dpfws->setParagraphElements();
	if ($bret) $retval .= 'ok;';
	$cp->set_data($retval);
}

function dpfws_post_statistics($sessionid)
{
	global $cp;
	$retval = '';
	if (!defined('DPFWS_RESELLERURL') || !DPFWS_RESELLERURL)
	{
		$retval .= 'empty url;';
		$cp->set_data($retval);
	}
	else
	{
		$data = array(
			'sid' => $sessionid,
			'rid' => defined('DPFWS_RESELLERID') ? DPFWS_RESELLERID : null
		);
		$agent = defined('DPFWS_RESELLERAGENT') ? DPFWS_RESELLERAGENT : null;
		$result = post_request(DPFWS_RESELLERURL, $data, $agent);
		if ($result['status'] == 'ok') $retval .= 'ok;';
		else $retval .= 'error: ' . $result['error'] . ';';

		$cp->set_data($retval);
	}
}

function post_request($url, $data, $agent = null)
{
	if (trim($url) == '')
	{
		return array(
			'status' => 'err',
			'error' => "POST URL is undefined!"
		);
	}

	if (!is_array($data))
	{
		return array(
			'status' => 'err',
			'error' => "Posted data is not an array!"
		);
	}

	$data = http_build_query($data);
	$url = parse_url($url);

	if ($url['scheme'] != 'http')
	{
		return array(
			'status' => 'err',
			'error' => "Error: Only HTTP request are supported!"
		);
	}

	$host = $url['host'];
	$path = $url['path'];
	$port = $url['port'];
	if (!$port) $port = 80;

	$fp = fsockopen($host, $port, $errno, $errstr, 5);
	if ($fp)
	{
		fputs($fp, "POST $path HTTP/1.1\r\n");
		fputs($fp, "Host: $host\r\n");
		fputs($fp, "User-Agent: $agent\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: " . strlen($data) . "\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);
		$result = '';
		while (!feof($fp))
		{
			$result .= fgets($fp, 128);
		}
	}
	else
	{
		return array(
			'status' => 'err',
			'error' => "$errstr ($errno)"
		);
	}

	fclose($fp);

	$result = explode("\r\n\r\n", $result, 2);
	$header = isset($result[0]) ? $result[0] : '';
	$content = isset($result[1]) ? $result[1] : '';

	return array(
		'status' => 'ok', 'header' => $header, 'content' => $content
	);
}

?>