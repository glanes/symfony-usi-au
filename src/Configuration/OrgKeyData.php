<?php
namespace Glanes\UsiBundle\Configuration;

use DateTime;

class OrgKeyData
{
    public string $Id;
    public string $Code;
    public string $Name1;
    public string $Name2;
    public string $ABN;
    public string $LegalName;
    public string $PersonId;
    public string $SerialNumber;
    public DateTime $CreationDate;
    public DateTime $NotBefore;
    public DateTime $NotAfter;
    public string $Sha1fingerprint;
    public string $PublicCertificate;
    public string $ProtectedPrivateKey;
    public string $CredentialSalt;
    public string $IntegrityValue;
    public string $CredentialType;
    public string $PrivateKeyPassword;
}
