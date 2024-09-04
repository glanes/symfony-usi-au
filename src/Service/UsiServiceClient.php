<?php
namespace Glanes\UsiBundle\Service;

use DateTime;
use DateTimeZone;
use DateInterval;
use Glanes\UsiBundle\Configuration\Configuration;
use Glanes\UsiBundle\Configuration\OrgKeyData;

class UsiServiceClient extends BaseServiceClient
{
    private readonly StsServiceClient $stsServiceClient;

    public function __construct(Configuration $configuration, OrgKeyData $orgKeyData)
    {
        parent::__construct($configuration, $configuration->UsiServiceUrl, $orgKeyData);
        $this->stsServiceClient = new StsServiceClient($configuration, $orgKeyData);
    }

    public function getWsdl(): string
    {
        $wsdlUrl = $this->getWsdlUrl();
        $wsdl = file_get_contents($wsdlUrl);
        return $wsdl;
    }

    public function getOperations(): array
    {
        $functions = $this->ServiceClient->__getFunctions();
        return $functions;
    }

    public function invoke(string $soapAction, string $soapBody): array
    {
        [$stsRequest, $stsResponse] = $this->stsServiceClient->issue();
        [, $stsResponseXPath] = parent::getDomXPath($stsResponse);

        $xml = file_get_contents(sprintf("%s/../Resources/templates/usi-request-template.xml", __DIR__));
        [$usiRequestDocument, $usiRequestXPath] = parent::getDomXPath($xml);

        // header
        $header = $usiRequestXPath->query("//s:Header")->item(0);

        // <a:Action>
        $actionElement = $usiRequestXPath->query("a:Action", $header)->item(0);
        $actionElement->nodeValue = $soapAction;

        // <a:MessageID>
        $messageIdElement = $usiRequestXPath->query("a:MessageID", $header)->item(0);
        $messageIdElement->nodeValue = sprintf("urn:uuid:%s", parent::getGuidV4());

        // <a:To>
        $toElement = $usiRequestXPath->query("a:To", $header)->item(0);
        $toElement->nodeValue = $this->ServiceUrl;

        // secutity header
        $securityHeader = $usiRequestXPath->query("o:Security", $header)->item(0);

        // <u:Created> & <u:Expires>
        $timestampElement = $usiRequestXPath->query("u:Timestamp", $securityHeader)->item(0);
        $created = new DateTime("now", new DateTimeZone("GMT"));
        $createdElement = $usiRequestXPath->query("u:Created", $timestampElement)->item(0);
        $createdElement->nodeValue = parent::GetXmlUTCDateTime($created);
        $expires = $created->add(DateInterval::createFromDateString("300 seconds"));
        $expiresElement = $usiRequestXPath->query("u:Expires", $timestampElement)->item(0);
        $expiresElement->nodeValue = parent::GetXmlUTCDateTime($expires);

        // issued token data
        $requestSecurityTokenResponseElement = $stsResponseXPath->query("//s:Body/trust:RequestSecurityTokenResponseCollection/trust:RequestSecurityTokenResponse")->item(0);

        // <xenc:EncryptedData> - replace with sts token data
        $encryptedDataElement = $usiRequestXPath->query("xenc:EncryptedData", $securityHeader)->item(0);
        $issuedDataElement = $stsResponseXPath->query("trust:RequestedSecurityToken/saml:EncryptedAssertion/xenc:EncryptedData", $requestSecurityTokenResponseElement)->item(0);
        $importedElement = $usiRequestDocument->importNode($issuedDataElement, true);
        $securityHeader->replaceChild($importedElement, $encryptedDataElement);

        // <ds:Signature>
        $signatureElement = $usiRequestXPath->query("ds:Signature", $securityHeader)->item(0);

        // <ds:SignedInfo>
        $signatureInfoElement = $usiRequestXPath->query("ds:SignedInfo", $signatureElement)->item(0);

        // <ds:Reference URI="#_0"> <ds:DigestValue />
        $digestValueElement = $usiRequestXPath->query("ds:Reference[@URI='#_0']/ds:DigestValue", $signatureInfoElement)->item(0);
        $digestValueElement->nodeValue = parent::getDigest($timestampElement->C14N(true), "sha1");

        // <ds:SignatureValue>
        $signatureValueElement = $usiRequestXPath->query("ds:SignatureValue", $signatureElement)->item(0);
        $stsProofTokenKeyElement = $stsResponseXPath->query("trust:RequestedProofToken/trust:BinarySecret", $requestSecurityTokenResponseElement)->item(0);
        $stsProofToken = base64_decode($stsProofTokenKeyElement->nodeValue);
        $signatureValue = hash_hmac("sha1",  $signatureInfoElement->C14N(true), $stsProofToken, true);
        $signatureValueElement->nodeValue = base64_encode($signatureValue);

        // <ds:KeyInfo>
        $keyInfoElement = $usiRequestXPath->query("ds:KeyInfo", $signatureElement)->item(0);
        $issuedDataElement = $stsResponseXPath->query("trust:RequestedAttachedReference/o:SecurityTokenReference", $requestSecurityTokenResponseElement)->item(0);
        $importedElement = $usiRequestDocument->importNode($issuedDataElement, true);
        $keyInfoElement->appendChild($importedElement);

        // body
        $body = $usiRequestXPath->query("//s:Body")->item(0);

        // content
        [, $bodyContentXPath] = parent::getDomXPath($soapBody);
        $bodyContentNode = $bodyContentXPath->query("/*")->item(0);
        $bodyContentNode = $usiRequestDocument->importNode($bodyContentNode, true);
        $body->appendChild($bodyContentNode);

        $usiRequest = $usiRequestDocument->saveXML();
        $usiResponse = $this->ServiceClient->__doRequest($usiRequest, $this->ServiceUrl, "", SOAP_1_2);
        return [$stsRequest, $stsResponse, $usiRequest, $usiResponse];
    }
}
