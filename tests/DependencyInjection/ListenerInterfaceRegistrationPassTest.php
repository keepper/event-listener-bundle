<?php
namespace Keepper\EventListenerBundle\Tests\DependencyInjection;

use Keepper\EventListener\Manager\ListenerManager;
use Keepper\EventListener\Tests\Fixtures\SomeListenerInterface;
use Keepper\EventListenerBundle\DependencyInjection\ListenerInterfaceRegistrationPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ListenerInterfaceRegistrationPassTest extends TestCase {

	private function process(ContainerBuilder $cb) {
		(new ListenerInterfaceRegistrationPass())->process($cb);
	}

	/**
	 * @dataProvider dataProviderForRegistrateListenerInterfaceNegative
	 */
	public function testRegistrateListenerInterfaceNegative($tagArguments, $expectedExeption, $expectedExceptionMessage) {
		$cb = new ContainerBuilder();
		$cb->setDefinition('SomeExistsServace', new Definition(\stdClass::class));
		// Создаем определение сервиса тегированного тэгом "связи"
		$someService = new Definition();
		$someService->addTag(ListenerInterfaceRegistrationPass::TAG_NAME, $tagArguments);
		$cb->setDefinition('Test\SomeService', $someService);

		$this->expectException($expectedExeption);
		$this->expectExceptionMessage($expectedExceptionMessage);

		$this->process($cb);
	}

	public function dataProviderForRegistrateListenerInterfaceNegative() {
		return [
			[
				[],
				\InvalidArgumentException::class,
				'Сервис "Test\SomeService" отмеченый тегом "listener.interface" не содержит аргумента тега с именем интерфейса.'
			],
			[
				['interface' => 'UnexistsInterface'],
				\InvalidArgumentException::class,
				'Сервис "Test\SomeService" отмеченый тегом "listener.interface" содержит имя не известного интерфейса. "UnexistsInterface"'
			],
			[
				['interface' => SomeListenerInterface::class, 'manager' => 'UnexistsService'],
				\InvalidArgumentException::class,
				'Сервис "Test\SomeService" отмеченый тегом "listener.interface" содержит имя не известного сервиса "UnexistsService" в секции manager.'
			],
			[
				['interface' => SomeListenerInterface::class, 'manager' => 'SomeExistsServace'],
				\InvalidArgumentException::class,
				'Сервис "Test\SomeService" отмеченый тегом "listener.interface" содержит не корректный сервис "SomeExistsServace" в секции manager.'
			]
		];
	}

	/**
	 * @dataProvider dataPrividerForRegistrateListenerInterfacePositive
	 */
	public function testRegistrateListenerInterfacePositive($tagArguments, $searchedService) {
		$cb = new ContainerBuilder();
		$cb->setDefinition('AlternativeManager', new Definition(ListenerManager::class));

		// Создаем определение сервиса тегированного тэгом "связи"
		$someService = new Definition();
		$someService->addTag(ListenerInterfaceRegistrationPass::TAG_NAME, $tagArguments);
		$cb->setDefinition('Test\SomeService', $someService);

		$this->process($cb);

		$calls = $cb->findDefinition($searchedService)->getMethodCalls();
		$hasInjection = false;
		foreach ($calls as $call) {
			if ($call[0] != 'addListenerInterface') {
				continue;
			}

			if ($call[1] == [SomeListenerInterface::class]) {
				$hasInjection = true;
			}
		}

		$this->assertTrue($hasInjection);
	}

	public function dataPrividerForRegistrateListenerInterfacePositive() {
		return  [
			[
				['interface' => SomeListenerInterface::class],
				ListenerManager::class
			],
			[
				['interface' => SomeListenerInterface::class, 'manager' => 'AlternativeManager'],
				'AlternativeManager'
			]
		];
	}

	public function testWhenUnexistTaggedService() {
		$cb = new ContainerBuilder();
		$this->process($cb);

		$this->assertFalse($cb->has(ListenerManager::class));
	}
}