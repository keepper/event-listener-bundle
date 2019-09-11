<?php
namespace Keepper\EventListenerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ListenerInterfaceRegistrationPass extends AbstractPass implements CompilerPassInterface {

	const TAG_NAME = 'listener.interface';

	public function process(ContainerBuilder $container) {

		$taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

		if (count($taggedServices) == 0) {
			return;
		}

		foreach ($taggedServices as $serviceId => $tags) {
			foreach ($tags as $tagArguments) {

				if (!array_key_exists('interface', $tagArguments)) {
					throw new \InvalidArgumentException(
						sprintf(
							'Сервис "%s" отмеченый тегом "%s" не содержит аргумента тега с именем интерфейса.',
							$serviceId, self::TAG_NAME));
				}

				if ( !is_array($tagArguments['interface']) ) {
					$tagArguments['interface'] = [$tagArguments['interface']];
				}

				foreach ($tagArguments['interface'] as $interfaceName) {

					if (!interface_exists($interfaceName)) {
						throw new \InvalidArgumentException(
							sprintf(
								'Сервис "%s" отмеченый тегом "%s" содержит имя не известного интерфейса. "%s"',
								$serviceId, self::TAG_NAME, $interfaceName));
					}

					if (array_key_exists('manager', $tagArguments)) {
						if (!$container->hasDefinition($tagArguments['manager'])) {
							throw new \InvalidArgumentException(
								sprintf(
									'Сервис "%s" отмеченый тегом "%s" содержит имя не известного сервиса "%s" в секции manager.',
									$serviceId, self::TAG_NAME, $tagArguments['manager']));
						}

						$managerDefinition = $container->findDefinition($tagArguments['manager']);
						$className = $managerDefinition->getClass();
						$reflection = new \ReflectionClass($className);

						if (!$reflection->hasMethod('addListenerInterface')) {
							throw new \InvalidArgumentException(
								sprintf(
									'Сервис "%s" отмеченый тегом "%s" содержит не корректный сервис "%s" в секции manager.',
									$serviceId, self::TAG_NAME, $tagArguments['manager']));
						}
					} else {
						$managerDefinition = $this->getDefaultListenerManager($container);
					}

					$managerDefinition->addMethodCall('addListenerInterface', [$interfaceName]);
				}
			}
		}
	}
}