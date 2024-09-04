<?php

namespace Glanes\UsiBundle\Service;

use DateTime;
use DateTimeZone;
use SoapClient;
use DOMDocument;
use DOMXPath;
use Glanes\UsiBundle\Configuration\Configuration;
use Glanes\UsiBundle\Configuration\OrgKeyData;

abstract class BaseServiceClient
{
    protected readonly SoapClient $ServiceClient;
    protected readonly Configuration $Configuration;
    protected readonly string $ServiceUrl;
    protected readonly OrgKeyData $OrgData;

    protected function __construct(Configuration $configuration, string $serviceUrl, OrgKeyData $orgKeyData)
    {
        $this->Configuration = $configuration;
        $this->ServiceUrl = $serviceUrl;
        $this->OrgData = $orgKeyData;
        $wsdlUrl = $this->getWsdlUrl($serviceUrl);
        $this->ServiceClient = new SoapClient($wsdlUrl);
    }

    protected function getWsdlUrl(): string
    {
        $wsdlUrl = sprintf("%s?wsdl", $this->ServiceUrl);
        return $wsdlUrl;
    }

    protected static function getDomXPath(string $xml): array
    {
        $document = new DOMDocument();
        $document->loadXML($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace("s", "http://www.w3.org/2003/05/soap-envelope");
        $xpath->registerNamespace("a", "http://www.w3.org/2005/08/addressing");
        $xpath->registerNamespace("u", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd");
        $xpath->registerNamespace("o", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
        $xpath->registerNamespace("ds", "http://www.w3.org/2000/09/xmldsig#");
        $xpath->registerNamespace("trust", "http://docs.oasis-open.org/ws-sx/ws-trust/200512");
        $xpath->registerNamespace("wsp", "http://schemas.xmlsoap.org/ws/2004/09/policy");
        $xpath->registerNamespace("i", "http://schemas.xmlsoap.org/ws/2005/05/identity");
        $xpath->registerNamespace("wsu", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd");
        $xpath->registerNamespace("xenc", "http://www.w3.org/2001/04/xmlenc#");
        $xpath->registerNamespace("saml", "urn:oasis:names:tc:SAML:2.0:assertion");
        $xpath->registerNamespace("k", "http://docs.oasis-open.org/wss/oasis-wss-wssecurity-secext-1.1.xsd");
        $xpath->registerNamespace("v4", "http://usi.gov.au/2020/ws");
        $xpath->registerNamespace("v5", "http://usi.gov.au/2022/ws");

        return [$document, $xpath];
    }

    protected static function getDigest(string $cannonicalizedXml, string $algorithm = "sha256"): string
    {
        $hash = hash($algorithm, $cannonicalizedXml, true);
        return base64_encode($hash);
    }

    protected static function getGuidV4(): string
    {
        if (function_exists("com_create_guid")) {
            return trim(com_create_guid(), "{}");
        }

        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    protected static function GetXmlUTCDateTime(DateTime $dateTime): string
    {
        $timeZoneName = $dateTime->getTimezone()->getName();
        $utcTimeZone = new DateTimeZone("GMT");
        if (strcasecmp($timeZoneName, $utcTimeZone->getName()) !== 0) {
            $dateTime->setTimezone($utcTimeZone);
        }

        $formatted = $dateTime->format("c");
        return str_replace('+00:00', '.000Z', $formatted);
    }
}
