<?php
namespace Keepper\EventListenerBundle\Tests\DependencyInjection;

use Keepper\EventListener\Manager\ListenerManager;
use Keepper\EventListenerBundle\DependencyInjection\ListenerInterfaceRegistrationPass;
use Keepper\EventListenerBundle\DependencyInjection\ListenerRegistrationPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ListenerRegistrationPassTest extends TestCase {

	private function process(ContainerBuilder $cb) {
		(new ListenerInterfaceRegistrationPass())->process($cb);
		(new ListenerRegistrationPass())->process($cb);
	}

	public function testWhenUnexistTaggedService() {
		$cb = new ContainerBuilder();
		$this->process($cb);

		$this->assertFalse($cb->has(ListenerManager::class));
	}

	public function testWithIncorrectManagerServaceName() {
		$cb = new ContainerBuilder();
		$cb->setDefinition('SomeService', new Definition(\stdClass::class))
			->addTag(ListenerRegistrationPass::TAG_NAME, ['manager' => 'SameUnexistsService']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Сервис "SomeService" отмеченый тегом "'.ListenerRegistrationPass::TAG_NAME.'" содержит имя не известного сервиса "SameUnexistsService" в секции manager.');

		$this->process($cb);
	}

	public function testWithIncorectManagerService() {
		$cb = new ContainerBuilder();
		$cb->setDefinition('SomeManagerService', new Definition(\stdClass::class));
		$cb->setDefinition('SomeService', new Definition(\stdClass::class))
			->addTag(ListenerRegistrationPass::TAG_NAME, ['manager' => 'SomeManagerService']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Сервис "SomeService" отмеченый тегом "'.ListenerRegistrationPass::TAG_NAME.'" содержит не корректный сервис "SomeManagerService" в секции manager.');

		$this->process($cb);
	}
}