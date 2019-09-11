<?php
namespace Keepper\EventListenerBundle;

use Keepper\EventListenerBundle\DependencyInjection\ListenerInterfaceRegistrationPass;
use Keepper\EventListenerBundle\DependencyInjection\ListenerRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EventListenerBundle extends Bundle {

	public function build(ContainerBuilder $container)
	{
		$container->addCompilerPass(new ListenerInterfaceRegistrationPass());
		$container->addCompilerPass(new ListenerRegistrationPass());
	}
}