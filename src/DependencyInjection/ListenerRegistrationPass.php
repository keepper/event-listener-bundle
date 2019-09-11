<?php
namespace Keepper\EventListenerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ListenerRegistrationPass extends AbstractPass implements CompilerPassInterface {

	const TAG_NAME = 'event.listener';

	public function process(ContainerBuilder $container) {

		$taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

		if (count($taggedServices) == 0) {
			return;
		}

		foreach ($taggedServices as $serviceId => $tags) {
			$serviceDefinition = $container->getDefinition($serviceId);

			if ( !$serviceDefinition->isLazy() ) {
				$serviceDefinition->setLazy(true);
			}

			foreach ($tags as $tagArguments) {

				if (array_key_exists('manager', $tagArguments)) {
					if ( !$container->hasDefinition($tagArguments['manager']) ) {
						throw new \InvalidArgumentException(
							sprintf(
								'Сервис "%s" отмеченый тегом "%s" содержит имя не известного сервиса "%s" в секции manager.',
								$serviceId, self::TAG_NAME, $tagArguments['manager']));
					}

					$managerDefinition = $container->findDefinition($tagArguments['manager']);
					$className = $managerDefinition->getClass();
					$reflection = new \ReflectionClass($className);

					if ( !$reflection->hasMethod('addListener') ) {
						throw new \InvalidArgumentException(
							sprintf(
								'Сервис "%s" отмеченый тегом "%s" содержит не корректный сервис "%s" в секции manager.',
								$serviceId, self::TAG_NAME, $tagArguments['manager']));
					}
				} else {
					$managerDefinition = $this->getDefaultListenerManager($container);
				}

				$managerDefinition->addMethodCall('addListener', [new Reference($serviceId)]);
			}
		}
	}
}