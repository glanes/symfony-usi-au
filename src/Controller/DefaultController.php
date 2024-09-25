<?php

namespace Glanes\UsiBundle\Controller;

use Glanes\UsiBundle\Service\UsiClient;
use Glanes\UsiBundle\Configuration\ConfigurationManager;
use Glanes\UsiBundle\Service\UsiServiceClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
	private $configurator;

	public function __construct(ConfigurationManager $configurator)
	{
		$this->configurator = $configurator;
	}

	#[Route(path: '/verify-usi', name: 'verify-symfony-usi-au')]
	public function verifyUsi()
	{

		$configuration = $this->configurator->getConfiguration("prod");
		$organization = $configuration->getOrgKeyData($configuration->DefaultOrgCode);

		$usiClient = new UsiClient($configuration, $organization);
		//dev
		//$verifyUsi = $usiClient->verifyUSI("VA1803", "BNGH7C75FN", "Maryam", "Fredrick", "1997-09-16");
		//prod
		$verifyUsi = $usiClient->verifyUSI("31254", "7XARGYADMQ", "Dominic", "Kannan", "1983-06-13");
		dd($verifyUsi);


	}

	#[Route(path: '/locate-usi', name: 'locate-symfony-usi-au')]
	public function locateUsi()
	{
		$configuration = $this->configurator->getConfiguration("prod");
		$organization = $configuration->getOrgKeyData($configuration->DefaultOrgCode);

		$usiClient = new UsiClient($configuration, $organization);
		$locateUsi = $usiClient->locateUSI("31254", "F", "NIKITA", "MCDONALD", "1995-05-12");
		dd($locateUsi);
	}
}
