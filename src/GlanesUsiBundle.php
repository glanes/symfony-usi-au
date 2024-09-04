<?php

namespace Glanes\UsiBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class GlanesUsiBundle extends Bundle
{
	public function getPath(): string
	{
		return \dirname(__DIR__);
	}

}
