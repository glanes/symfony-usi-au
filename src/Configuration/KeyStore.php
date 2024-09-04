<?php
namespace Glanes\UsiBundle\Configuration;

use UsiBundle\Configuration\OrgKeyDataCollection;

class KeyStore
{
    public string $Salt;
    public OrgKeyDataCollection $Credentials;
}
