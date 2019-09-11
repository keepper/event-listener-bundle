<?php
namespace Keepper\EventListenerBundle\Tests\Enviroment;

abstract class KernelTestCase extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
	protected static function getKernelClass()
	{
		return UnitTestKernel::class;
	}

	protected function setUp()
	{
		parent::setUp();

		/**
		 * @var UnitTestKernel
		 */
		$kernelClassName = self::getKernelClass();
		// Setting base parameters
		$kernelClassName::setParameter('kernel.secret', 'Gdasd82$adGdasfwa#Yapuk6');
		foreach ($this->baseParameters() as $parameterName => $parameterValue) {
			$kernelClassName::setParameter($parameterName, $parameterValue);
		}

		/**
		 * @var UnitTestKernel static::$kernel
		 */
		static::$kernel = self::createKernel([
			'debug' => self::isDebug(),
		]);

		// Setting bundle names for load
		$bundles = $this->bundleClassesToRegister();
		$bundles = array_reverse(array_unique(array_reverse($bundles))); // Removing duplicates from start
		foreach ($bundles as $bundleClassName) {
			static::$kernel->addBundle($bundleClassName);
		}

		// Setting service names for deprivate in test enviroments
		foreach ($this->servicesToUnprivate() as $serviceName) {
			static::$kernel->deprivate($serviceName);
		}

		static::$kernel->boot();
	}

	protected static function isDebug(): bool
	{
		return in_array('--debug', $_SERVER['argv']);
	}

	abstract public function bundleClassesToRegister(): array;

	abstract public function servicesToUnprivate(): array;

	abstract public function baseParameters(): array;
}