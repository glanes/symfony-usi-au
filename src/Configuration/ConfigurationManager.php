<?php
namespace Glanes\UsiBundle\Configuration;

use DOMDocument;
use DOMXPath;
use DateTime;
use App\Configuration\ConfigurationCollection;
use App\Configuration\Configuration;
use App\Configuration\OrgKeyData;
use App\Configuration\OrgKeyDataCollection;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;


class ConfigurationManager
{
	public ConfigurationCollection $Configurations;
	private $appKernel;
	private $parameterBag;
	private $env;

	public function __construct(ParameterBagInterface $parameterBag, KernelInterface $appKernel)
	{
		$this->env = $parameterBag->get("kernel.environment");
		$this->appKernel = $appKernel;
	}

	public function getConfiguration(string $env = null): Configuration
	{
		$this->initialize();
		if($env) {
			$this->env = $env;
		}
		foreach ($this->Configurations as $configuration) {
			if (strcasecmp($configuration->Environment, $this->env) === 0) {
				return $configuration;
			}
		}
	}


    private function initialize(): void
    {
        if (isset($this->Configurations)) {
            return;
        }
		$baseDirectory = sprintf("%s/config/packages/usi", $this->appKernel->getProjectDir());
        $this->Configurations = new ConfigurationCollection();
        $counter = 0;
        $environmentPaths = glob(sprintf("%s/*", $baseDirectory), GLOB_ONLYDIR);
        foreach ($environmentPaths as $environmentPath) {
            preg_match("/[^\/]+$/", $environmentPath, $environment);
            [$environmentDomXPath, $keyStoreDomXPath] = $this->getEnvironmentDomXPath($environmentPath);
            $this->Configurations[$counter] = $this->getEnvironment($environment[0], $environmentDomXPath, $keyStoreDomXPath);
            $counter++;
        }
    }

    private function getEnvironment(string $environment, DomXPath $environmentDomXPath, DomXPath $keyStoreDomXPath): Configuration
    {
        $envirommentElement = $environmentDomXPath->query("//usi:environment")->item(0);
        $configuration = new Configuration(
            $environment,
            $environmentDomXPath->evaluate("string(@url)", $envirommentElement),
            $environmentDomXPath->evaluate("string(@defaultOrgCode)", $envirommentElement),
            $this->getStsSettings($environmentDomXPath),
			$this->getKeyStore($keyStoreDomXPath, $environmentDomXPath),
			$this->appKernel->getProjectDir()
        );

        return $configuration;
    }

    private function getStsSettings(DomXPath $environmentDomXPath): StsSettings
    {
        $stsElement = $environmentDomXPath->query("//usi:environment/usi:sts")->item(0);
        $stsSettings = new StsSettings(
            $environmentDomXPath->evaluate("string(@url)", $stsElement),
            $environmentDomXPath->evaluate("string(@uri)", $stsElement)
        );

        return $stsSettings;
    }

    private function getKeyStore(DomXPath $keyStoreDomXPath, DomXPath $environmentDomXPath): KeyStore
    {
        $keyStore = new KeyStore();
        $keyStore->Salt = $keyStoreDomXPath->evaluate("string(//ato:credentialStore/ato:salt)");
        $keyStore->Credentials = new OrgKeyDataCollection();
        $counter = 0;
        $elements = $keyStoreDomXPath->query("//ato:credential");
        foreach ($elements as $element) {
            $orgKeyData = new OrgKeyData();
            $orgKeyData->ABN = $keyStoreDomXPath->evaluate("string(ato:abn)", $element);
            $mappingElement = $environmentDomXPath->query(sprintf("//usi:environment/usi:sts/usi:keyStoreMapping/usi:add[@abn='%s']", $orgKeyData->ABN))->item(0);
            $orgKeyData->Id = $keyStoreDomXPath->evaluate("string(@id)", $element);
            $orgKeyData->IntegrityValue = $keyStoreDomXPath->evaluate("string(@integrityValue)", $element);
            $orgKeyData->CredentialSalt = $keyStoreDomXPath->evaluate("string(@credentialSalt)", $element);
            $orgKeyData->CredentialType = $keyStoreDomXPath->evaluate("string(@credentialType)", $element);
            $orgKeyData->Name1 = $keyStoreDomXPath->evaluate("string(ato:name1)", $element);
            $orgKeyData->Name2 = $keyStoreDomXPath->evaluate("string(ato:name2)", $element);
            $orgKeyData->Code = $environmentDomXPath->evaluate("string(@code)", $mappingElement);
            $orgKeyData->LegalName = $keyStoreDomXPath->evaluate("string(ato:legalName)", $element);
            $orgKeyData->PersonId = $keyStoreDomXPath->evaluate("string(ato:personId)", $element);
            $orgKeyData->SerialNumber = $keyStoreDomXPath->evaluate("string(ato:serialNumber)", $element);
            $orgKeyData->CreationDate = new DateTime($keyStoreDomXPath->evaluate("string(ato:creationDate)", $element));
            $orgKeyData->NotBefore = new DateTime($keyStoreDomXPath->evaluate("string(ato:notBefore)", $element));
            $orgKeyData->NotAfter = new DateTime($keyStoreDomXPath->evaluate("string(ato:notAfter)", $element));
            $orgKeyData->Sha1fingerprint = $keyStoreDomXPath->evaluate("string(ato:sha1fingerprint)", $element);
            $orgKeyData->PublicCertificate = $keyStoreDomXPath->evaluate("string(ato:publicCertificate)", $element);
            $orgKeyData->ProtectedPrivateKey = $keyStoreDomXPath->evaluate("string(ato:protectedPrivateKey)", $element);
            $orgKeyData->PrivateKeyPassword = $environmentDomXPath->evaluate("string(@privateKeyPassword)", $mappingElement);
            $keyStore->Credentials[$counter] = $orgKeyData;
            $counter++;
        }

        return $keyStore;
    }

    private function getEnvironmentDomXPath(string $containerPath): array
    {
        $environmentData = new DOMDocument();
        $environmentData->load(sprintf("%s/environment.xml", $containerPath));
        $environmentDomXPath = new DOMXPath($environmentData);
        $environmentDomXPath->registerNamespace("usi", "http://usi.gov.au/ws");

        $keyStoreData = new DOMDocument();
        $keyStoreData->load(sprintf("%s/keystore-usi.xml", $containerPath));
        $keyStoreDomXPath = new DOMXPath($keyStoreData);
        $keyStoreDomXPath->registerNamespace("ato", "http://auth.abr.gov.au/credential/xsd/SBRCredentialStore");

        return [$environmentDomXPath, $keyStoreDomXPath];
    }
}
