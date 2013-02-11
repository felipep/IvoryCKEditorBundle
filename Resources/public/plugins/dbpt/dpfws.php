<?php
/*
 * FILE:        dpfws.php
 * AUTHOR:      Macron Software (www.macronsoftware.com); rasvo
 * DATE:        2008-11-27
 * DESCRPTION:  DBPT - wrapper of the Duden Proof Factory web service
 * VERSION:     2.0
 *
 *
 * Copyright (C) 2011 - Bibliographisches Institut GmbH, Mannheim, Germany.
 * All rights reserved.
 *
 */
	
require_once('nusoapdime.php');
require_once('config.php');

class DpfWrapper extends nusoap_client_dime
{
	/** Unique session id (string) */
	var $sessionid;

	/** Error message in case of an error */
	var $errorMessage;

	/** 1 for "is finished", 0 for "is running", -1 for "not yet started", -2 for error */
	var $isFinishedVar;

    function nusoap_client_dime($endpoint,$wsdl = false,$proxyhost = false,$proxyport = false,$proxyusername = false, $proxypassword = false, $timeout = 0, $response_timeout = 30, $portName = '')
	{
		return parent::nusoap_client($endpoint,$wsdl, $proxyhost,$proxyport,$proxyusername, $proxypassword, $timeout, $response_timeout, $portName);
	}

    function DpfWrapper($sid = NULL)
    {
        $this->nusoap_client_dime(DPFWS_WSDL_FILE_URL, 'wsdl');
		if ($this->getError())
		{
			$this->errorMessage = $this->getError();
		}
		if (!is_null($sid))
		{
			$this->sessionid = $sid;
		}
		$this->isFinishedVar = -1;
    }

	/**
	 *  Retrieves the current session id.
	 *
	 *  @return   string                 current session id
	 */
	function getSessionId()
	{
		return $this->sessionid;
	}

	/**
	 *  Sets the current session id.
	 *
	 *  @param    string    $newval      specifies the new session id
	 */
	function setSessionId($newval)
	{
		$this->sessionid = $newval;
	}

	/**
	 *  Retrieves the error message.
	 *
	 *  @return   string                 error message
	 */
	function getErrorMessage()
	{
		return $this->errorMessage;
	}

	/**
	 *  Starts the DPF session using the appropriate parameters.
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function startSession($username, $password, $lang, $checkMode, $orthStd,
					$foreignwords, $colloquialwords, $dialect, $obsoletewords)
    {
		$this->errorMessage = NULL;
		$this->isFinishedVar = -1;
		$auth = array('username' => $username, 'password' => $password);
		$options = array('checkMode' => $checkMode,
						'language' => $this->getDESLangID($lang),
						'orthStd' => $orthStd,
						'hyphenMode' => 'HyNone',
						'hyphenStd' => 'HyConservative',
						'hyphenNoStem' => 0,
						'hyphenUnAest' => 0,
						'hyphenVowel' => 0,
						'useSimlist' => 0,
						'foreign' => $foreignwords,
						'oldFashioned' => $obsoletewords,
						'regional' => $dialect,
						'colloquial' => $colloquialwords,
						'singleLine' => 0,
						'sentenceMarkup' => 1,
						'encoding' => 'EncUtf8',
						'markupMode' => 'MmXmlSimple');//MmXmlSimple
		$param = array('auth' => $auth, 'options' => $options);
		$result = $this->call('startSession', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
			else
			{
				$this->sessionid = $result['sessionid'];
			}
		}
		return true;
    }

	/**
	 *  Stops the current DPF session and deletes all belonging
	 *  resources. Stopping is necessary for changing DPF
	 *  parameters set by the setParam method.
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function stopSession()
    {
		$this->errorMessage = NULL;
		$this->isFinishedVar = 1;
		$param = array('sessionid' => $this->sessionid);
		$result = $this->call('stopSession', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
    }

	/**
	 *  Starts the spell and grammar checking.
	 *
	 *  @param    string    $text         the text that shall be checked
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function check($text)
    {
		$this->errorMessage = NULL;
		$this->isFinishedVar = -1;
		$param = array('sessionid' => $this->sessionid, 'text' => $text);
		$result = $this->call('check', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
    }

	/**
	 *  Stops the spell and grammar checking.
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function stopCheck()
    {
		$this->errorMessage = NULL;
		$this->isFinishedVar = 1;
		$param = array('sessionid' => $this->sessionid);
		$result = $this->call('stopCheck', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
    }

	/**
	 *  Fetch the result from the spell checking. If the belonging
	 *  checking process has already finished, the complete
	 *  result set is returned, otherwise only the current
	 *  available data.
	 *
	 *  @return   string                  the current corrected data chunk of text or NULL if failed
	 */
    function getResults()
    {
		$this->errorMessage = NULL;
		$this->isFinishedVar = -2;
		$param = array('sessionid' => $this->sessionid);
		$result = $this->call('getResults', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return NULL;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return NULL;
			}
		}
		if ($result['return']['expectMore'] == 0)
		{
			$this->isFinishedVar = 1;
		}
		else
		{
			$this->isFinishedVar = 0;
		}
		return $result['return']['result'];
    }

	/**
	 *  Fetch the result from the spell checking.
	 *  Waits for the complete result set.
	 *
	 *  @return   string                  the complete corrected data
	 */
	function getResultsAll()
	{
		$this->errorMessage = NULL;
		$result = $this->getResults();
		while ($this->isFinished() == 0)
		{
			usleep(200000);
			$result .= $this->getResults();
		}
		return $result;
	}

	/**
	 *  Adds a new entry to the user user dictionary. The
	 *  entry string must be composed of Latin-1 characters
	 *  only. The limit of a User Lexicon is currently set to
	 *  18,000 entries. The error code oe309
	 *  is thrown by the method if this limit is reached.
	 *
	 *  @param    string    $entry        entry to add
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function addUserEntry($entry)
    {
		$this->errorMessage = NULL;
		$userEntry = array('key' => $entry);
		$param = array('sessionid' => $this->sessionid, 'dictionaryid' => 0, 'entry' => $userEntry);
		$result = $this->call('addUserEntry', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
    }

	/**
	 *  A dictionary can be loaded into a session by sending
	 *  the content of the user dictionary as a DIME attachment
	 *  of a loadAttachedDictionary request.
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function loadAttachedDictionary($data)
    {
		$this->errorMessage = NULL;
		$this->addAttachment($data, '', 'text/xml');
		$result = $this->call('loadAttachedDictionary', array('sessionid' => $this->sessionid), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result['detail'];
			return false;
		}
		else
		{
			if ($this->getError())
			{
				echo(' error<br>');
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
    }

	/**
	 *  Deletes a single entry from the user dictionary.
	 *
	 *  @param    string    $entry        entry to delete
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function deleteUserEntry($entry)
    {
		$this->errorMessage = NULL;
		$param = array('sessionid' => $this->sessionid, 'dictionaryid' => 0, 'key' => $entry);
		$result = $this->call('deleteUserEntry', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
    }

	/**
	 *  Adds a new entry to the exception dictionary. The entry string
	 *  and the proposal string should be composed of Latin-1 characters only.
	 *  The exception entry must include a second string, the proposal
	 *  string, that represents the proposed or preferred string to use.
	 *  This proposal is used to trigger a possible error message of the
	 *  application, and is part of the error markup. The limit of a
	 *  exception dictionary is currently set to 18,000 entries. The error
	 *  code oe309 is thrown by the method if this limit is reached.
	 *
	 *  @param    string    $entry        entry to add
	 *  @param    string    $proposal     entry used as preferred replacement for an entry
	 *
	 *  @return   boolean                 TRUE if succeeded, otherwise FALSE
	 */
    function addExceptionEntry($entry, $proposal)
    {
		$this->errorMessage = NULL;
		$exceptionEntry = array('key' => $entry, 'proposals' => $proposal);
		$param = array('sessionid' => $this->sessionid, 'dictionaryid' => 0, 'entry' => $exceptionEntry);
		$result = $this->call('addExceptionEntry', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
    }

	/**
	 *  Check whether there are more results form a previous check-request.
	 *
	 *  @return   int                     1 for "is finished", 0 for "is running", -1 for "not yet started", -2 for error
	 */
    function isFinished()
    {
		return $this->isFinishedVar;
    }

	function getDESLangID($oldStyleID)
	{
		$retVal = '';

		switch($oldStyleID)
		{
		case "en": // General English
			$retVal = "LngEnglish";
			break;
		case "gb": // British English
			$retVal = "LngEnglish-GB";
			break;
  		case "us": // American English
			$retVal = "LngEnglish-USA";
			break;
		case "sp": // Spanish
			$retVal = "LngSpanish";
			break;
		case "it": // Italian
			$retVal = "LngItalian";
			break;
		case "fr": // French
			$retVal = "LngFrench";
			break;
		case "de": // German
			$retVal = "LngGerman-DE";
			break;
		case "ch": // Swiss
			$retVal = "LngGerman-CH";
			break;
		case "at": // Austrian
			$retVal = "LngGerman-AT";
			break;
		default:
			$retVal = "LngGerman-DE";
			break;
		}

		return $retVal;
	}

	function setParagraphElements()
	{
		$this->errorMessage = NULL;
		$this->isFinishedVar = 1;
		$elems = array();

		if (defined('DPFWS_PARAGRAPH_ELEMENTS'))
		{
			$elems = explode(',', DPFWS_PARAGRAPH_ELEMENTS);
		}

		$param = array('sessionid' => $this->sessionid, 'pElems' => array('item' => $elems));
		$result = $this->call('setParagraphElements', array('parameters' => $param), 'http://www.duden.de/dpf_soap', '', false, true);
		if ($this->fault)
		{
			$this->errorMessage = $result;
			return false;
		}
		else
		{
			if ($this->getError())
			{
				$this->errorMessage = $this->getError();
				return false;
			}
		}
		return true;
	}
}
?>