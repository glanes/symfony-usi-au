<?php

namespace Glanes\UsiBundle\Service;

use Glanes\UsiBundle\Configuration\Configuration;
use Glanes\UsiBundle\Configuration\OrgKeyData;
use DOMDocument;
use SimpleXMLElement;

class UsiClient extends UsiServiceClient
{
	private $orgCode;
	private $usi;
	private $first;
	private $family;
	private $dob;
	private $configuration;
	private $orgKeyData;

	public function __construct(Configuration $configuration, OrgKeyData $orgKeyData)
	{
		$this->configuration = $configuration;
		$this->orgKeyData = $orgKeyData;
	}


	public function verifyUSI($orgCode, $usi, $first, $family, $dob)
	{
		/* The original version had this in the constructor */
		$this->orgCode = $orgCode;
		$this->usi = $usi;
		$this->first = $first;
		$this->family = $family;
		$this->dob = $dob;

		$xml = file_get_contents(sprintf("%s/src/Resources/operations/verifyUSI.xml", $this->configuration->ProjectDir));

		$document = new DOMDocument();
		$document->loadXML($xml);

		$document->getElementsByTagName('OrgCode')->item(0)->nodeValue = $this->orgCode;
		$document->getElementsByTagName('USI')->item(0)->nodeValue = $this->usi;
		$document->getElementsByTagName('FirstName')->item(0)->nodeValue = $this->first;
		$document->getElementsByTagName('FamilyName')->item(0)->nodeValue =$this->family;
		$document->getElementsByTagName('DateOfBirth')->item(0)->nodeValue = $this->dob;

		$usiServiceClient = new UsiServiceClient($this->configuration, $this->orgKeyData);
		$response = $usiServiceClient->invoke(sprintf("http://usi.gov.au/2022/ws/%s", "VerifyUSI"), $document->saveXML());

		$xml = new SimpleXMLElement($rawResponse = end($response));
		$xml->registerXPathNamespace("ws", "http://usi.gov.au/2022/ws");


		$response = $xml->xpath("//ws:VerifyUSIResponse");
		if (count($response)>0) {

			$response = $response[0];
			/* Convert result into an array instead of simpleXMLElement */
			$output = array();
			foreach ($response as $name => $value) {
				$output[$name] = "$value";
			}
			return $output;
		} else {
			//* We have a failure
			$output = array();
			$output['_error'] = $rawResponse;
			return $output;
		}
	}
}


