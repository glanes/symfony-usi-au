<?php

namespace Glanes\UsiBundle;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class GlanesUsiBundle extends AbstractBundle
{
	public function getPath(): string
	{
		return \dirname(__DIR__);
	}

}
