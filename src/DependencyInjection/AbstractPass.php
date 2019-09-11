<?php
namespace Keepper\EventListenerBundle\DependencyInjection;

use Keepper\EventListener\Manager\ListenerManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class AbstractPass {
	protected function getDefaultListenerManager(ContainerBuilder &$container) {

		if (!$container->hasDefinition(ListenerManager::class)) {
			$definition = new Definition(ListenerManager::class);
			$definition->addMethodCall('setLogger', [new Reference('logger')]);
			$definition->setPublic(true);
			$container->setDefinition(ListenerManager::class, $definition);
		}

		return $container->getDefinition(ListenerManager::class);
	}
}