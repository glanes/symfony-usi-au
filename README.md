# symfony-usi-au

Installation
============


Applications that use Symfony Flex
----------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute:

```console
composer update glanes/symfony-usi-au
```


### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Glanes\UsiBundle\GlanesUsiBundle::class => ['all' => true],
];

```

### Step 3: keystore and environment.xml

Add your environment.xml and keystore-usi.xml in:

config/packages/usi/dev

config/packages/usi/prod


### Step 3: Controller Example

```php

class DefaultController extends AbstractController
{
	private $configurator;
	public function __construct(ConfigurationManager $configurator) {
		$this->configurator = $configurator;
	}

	#[Route(path: '/verify-usi', name: 'verify')]
	public function verifyUsi() {

		$configuration = $this->configurator->getConfiguration("dev");
		$organization = $configuration->getOrgKeyData($configuration->DefaultOrgCode);

		$usiClient = new UsiClient($configuration, $organization);
		$verifyUsi = $usiClient->verifyUSI("VA1803", "BNGH7C75FN", "Maryam", "Fredrick", "1997-09-16");
		dd($verifyUsi);
		return null;
    }
}
```
