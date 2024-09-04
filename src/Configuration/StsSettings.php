<?php
namespace Glanes\UsiBundle\Configuration;

class StsSettings
{
    public readonly string $IssuerUrl;
    public readonly string $AppliesTo;

    public function __construct(string $issuerUrl, string $appliesTo)
    {
        $this->IssuerUrl = $issuerUrl;
        $this->AppliesTo = $appliesTo;
    }
}
