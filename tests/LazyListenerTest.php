<?php
namespace Keepper\EventListenerBundle\Tests;

use Keepper\EventListener\Manager\ListenerManager;
use Keepper\EventListener\Tests\Fixtures\SomeListenerInterface;
use Keepper\EventListenerBundle\EventListenerBundle;
use Keepper\EventListenerBundle\Tests\Enviroment\ServiceTestCase;
use Keepper\EventListenerBundle\Tests\Fixtures\SomeListener;

class LazyListenerTest extends ServiceTestCase {

	public function bundleClassesToRegister(array $addTo = []): array
	{
		return parent::bundleClassesToRegister([
			EventListenerBundle::class
		]);
	}

	public function testLazyListeners() {
		$this->assertTrue($this->hasService(ListenerManager::class), 'Ожидали наличие сервиса ListenerManager');

		/**
		 * @var ListenerManager $manager
		 */
		$manager = $this->getService(ListenerManager::class);
		$this->assertTrue($manager->hasListeners(SomeListenerInterface::class), 'Ожидали наличие слушателей события');

		$this->assertFalse(SomeListener::$inited, 'Ожидали, что класс слушателя не инициализирован');
	}
}