<?php
namespace Glanes\UsiBundle\Configuration;

use TypeError;

class Configuration
{
    public readonly string $Environment;
    public readonly StsSettings $Sts;
    public readonly string $UsiServiceUrl;
    public readonly string $DefaultOrgCode;
    public readonly KeyStore $KeyStore;
    public readonly string $ProjectDir;

    public function __construct(string $environment, string $usiServiceUrl, string $defaultOrgCode, StsSettings $sts, KeyStore $keyStore, string $projectDIr)
    {
        $this->Environment = $environment;
        $this->Sts = $sts;
        $this->UsiServiceUrl = $usiServiceUrl;
        $this->DefaultOrgCode = $defaultOrgCode;
        $this->KeyStore = $keyStore;
        $this->ProjectDir = $projectDIr;
    }

    public function getOrgKeyData(string $orgCode): OrgKeyData
    {
        foreach ($this->KeyStore->Credentials as $credential) {
            if (strcasecmp($credential->Code, $orgCode) === 0) {
                return $credential;
                break;
            }
        }
    }
}
