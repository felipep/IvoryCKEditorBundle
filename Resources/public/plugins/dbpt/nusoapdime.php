<?php
require_once('NuSOAP/nusoap.php');
/* PEAR Net_DIME library */
require_once('Net/DIME.php');

/**
*
* soapclientdime
* client supporting <a href="http://search.ietf.org/internet-drafts/draft-nielsen-dime-soap-00.txt">DIME attachments</a>.
*
* @author
* @version $Id: nusoapDIME.php,v 1.4 2004/04/13 12:04:27 snichol Exp $
* @access public
*/
class nusoap_client_dime extends nusoap_client
{
	var $requestAttachments = array();
	var $responseAttachments;
	var $dimeContentType = 'application/dime';

	// TODO: change this (See class nusoap_base)
	var $namespaces = array( 'SOAP-ENV' => 'http://schemas.xmlsoap.org/soap/envelope/');

	function create()
	{
		return parent::create();
	}

	/**
	* adds a DIME attachment to the current request.
	*
	* If the $data parameter contains an empty string, this method will read
	* the contents of the file named by the $filename parameter.
	*
	* If the $cid parameter is false, this method will generate the cid.
	*
	* @param string $data The data of the attachment
	* @param string $filename The filename of the attachment (default is empty string)
	* @param string $contenttype The DIME Content-Type of the attachment (default is application/octet-stream)
	* @param string $cid The content-id (cid) of the attachment (default is false)
	* @return string The content-id (cid) of the attachment
	* @access public
	*/
	function addAttachment($data, $filename = '', $contenttype = 'application/octet-stream', $cid = false)
	{
		if (! $cid)
		{
			$cid = md5(uniqid(time()));
		}

		$info['data'] = $data;
		$info['filename'] = $filename;
		$info['contenttype'] = $contenttype;
		$info['cid'] = $cid;

		$this->requestAttachments[] = $info;

		return $cid;
	}

	//This code is taken from PEAR:SOAP
	function &_makeDIMEMessage(&$xml)
	{
		// encode any attachments
		// see http://search.ietf.org/internet-drafts/draft-nielsen-dime-soap-00.txt
		// now we have to DIME encode the message
		$dime =& new Net_DIME_Message();
		$msg = &$dime->encodeData($xml,$this->namespaces['SOAP-ENV'],NULL,NET_DIME_TYPE_URI);

		// add the attachements
		$c = count($this->requestAttachments);
		$this->debug('Found '. $c .' attachments');
		for ($i=0; $i < $c; $i++)
		{
			$attachment =& $this->requestAttachments[$i];
			if ($attachment['data'] == '' && $attachment['filename'] <> '')
			{
				if ($fd = fopen($attachment['filename'], 'rb'))
				{
					$data = fread($fd,
					filesize($attachment['filename']));
					fclose($fd);
				}
				else
				{
					$data = '';
				}
				$attachment['data'] = $data;
			}
			$msg .= $dime->encodeData($attachment['data'],$attachment['contenttype'],$attachment['cid'],NET_DIME_TYPE_MEDIA);
		}
		$msg .= $dime->endMessage();
		return $msg;
	}

	/**
	*
	* $headers is array()
	* $attachments is array()
	*/
	//This code is taken from PEAR:SOAP
	function _decodeDIMEMessage(&$data, &$headers, &$attachments)
	{
		// XXX this SHOULD be moved to the transport layer, e.g. PHP itself
		// should handle parsing DIME ;)
		$dime =& new Net_DIME_Message();
		$err = $dime->decodeData($data);
		if ( PEAR::isError($err) )
		{
			$this->_raiseSoapFault('Failed to decode the DIME message!','','','Server');
			$this->debug('Failed to decode the DIME message!');
			$this->setError('Failed to decode the DIME message!');
			return;
		}
		if (strcasecmp($dime->parts[0]['type'],$this->namespaces['SOAP-ENV']) != 0)
		{
			$this->_raiseSoapFault('DIME record 1 is not a SOAP envelop!','','','Server');
			$this->debug('DIME record 1 is not a SOAP envelop!');
			$this->setError('DIME record 1 is not a SOAP envelop!');
			return;
		}

		// return by reference
		$data = $dime->parts[0]['data'];
		$headers['content-type'] = 'text/xml'; // fake it for now
		$c = count($dime->parts);
		for ($i = 0; $i < $c; $i++)
		{
			$part =& $dime->parts[$i];
			// XXX we need to handle URI's better
			$id = strncmp( $part['id'], 'cid:', 4 ) ? 'cid:'.$part['id'] :
			$part['id'];
			$attachments[$id] = $part['data'];
			$this->debug('Attachment Type:' . $part['type'] . 'id/uri: '. $id );
		}
	}

	/**
	* clears the DIME attachments for the current request.
	*
	* @access public
	*/
	function clearAttachments()
	{
		$this->requestAttachments = array();
	}

	/**
	* gets the DIME attachments from the current response.
	*
	* Each array element in the return is an associative array with keys
	* data, filename, contenttype, cid. These keys correspond to the
	parameters
	* for addAttachment.
	*
	* @return array The attachments.
	* @access public
	*/
	function getAttachments()
	{
		return $this->responseAttachments;
	}

	/**
	* gets the HTTP body for the current request.
	*
	* @param string $soapmsg The SOAP payload
	* @return string The HTTP body, which includes the SOAP payload
	* @access protected
	*/
	function getHTTPBody($soapmsg)
	{
		if (count($this->requestAttachments) > 0)
		{
			$soapmsg =& $this->_makeDIMEMessage($soapmsg);
		}
		//For testing only
		//$headers = array('content-type' => 'application/dime');
		//$this->parseResponse( $headers, $soapmsg);
		return parent::getHTTPBody($soapmsg);
	}

	/**
	* gets the HTTP content type for the current request.
	*
	* Note: getHTTPBody must be called before this.
	*
	* @return string the HTTP content type for the current request.
	* @access protected
	*/
	function getHTTPContentType()
	{
		if (count($this->requestAttachments) > 0)
		{
			return $this->dimeContentType;
		}
		return parent::getHTTPContentType();
	}

	/**
	* gets the HTTP content type charset for the current request.
	* returns false for non-text content types.
	*
	* Note: getHTTPBody must be called before this.
	*
	* @return string the HTTP content type charset for the current request.
	* @access protected
	*/
	function getHTTPContentTypeCharset()
	{
		if (count($this->requestAttachments) > 0)
		{
			return false;
		}
		return parent::getHTTPContentTypeCharset();
	}

	/**
	* processes SOAP message returned from server
	*
	* @param array $headers The HTTP headers
	* @param string $data unprocessed response data from
	server
	* @return mixed value of the message, decoded into a PHP type
	* @access protected
	*/
	function parseResponse($headers, $data)
	{
		$this->debug('Entering parseResponse() for payload of length ' . strlen($data) . ' and type of ' . $headers['content-type']);
		$this->responseAttachments = array();
		if (strstr($headers['content-type'], $this->dimeContentType))
		{
			$this->debug('Decode application/dime');
			$this->_decodeDIMEMessage($data, $headers, $this->responseAttachments);
			$this->debug('Decode DIME message successful, process SOAP as usual');
			return parent::parseResponse($headers, $data);
		}
		$this->debug('Not multipart/related');
		return parent::parseResponse($headers, $data);
	}
}
?>