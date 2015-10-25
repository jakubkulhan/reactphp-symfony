<?php
namespace App;

use Skrz\Bundle\AutowiringBundle\SkrzAutowiringBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class AppKernel extends Kernel
{

	public function getRootDir()
	{
		return __DIR__ . "/../..";
	}

	public function getLogDir()
	{
		return $this->getRootDir() . "/log";
	}

	public function registerBundles()
	{
		return [
			new SkrzAutowiringBundle(),
		];
	}

	public function registerContainerConfiguration(LoaderInterface $loader)
	{
		$loader->load($this->getRootDir() . "/conf/services_" . $this->getEnvironment() . ".yml");
	}

}
