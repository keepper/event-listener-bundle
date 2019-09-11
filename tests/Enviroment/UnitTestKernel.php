<?php

namespace Keepper\EventListenerBundle\Tests\Enviroment;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;

class UnitTestKernel extends Kernel implements CompilerPassInterface
{
    private $deprivateServices = [];
    private $loadBundles = [FrameworkBundle::class];
    private static $parameters = [];
    public static $configDir = __DIR__.'/Resources/config/';

    public function __construct($environment, $debug)
    {
        parent::__construct($environment, $debug);

        if (array_key_exists('KERNEL_DIR', $_SERVER)) {
            $this->rootDir = $_SERVER['KERNEL_DIR'];
        } elseif (getenv('KERNEL_DIR') !== false) {
            $this->rootDir = getenv('KERNEL_DIR');
        }
        $this->name = $this->getName(false);
    }

    public function deprivate(string $serviceName)
    {
        if (array_key_exists($serviceName, $this->deprivateServices)) {
            return;
        }

        $this->deprivateServices[] = $serviceName;
    }

    public function addBundle(string $bundleClassName)
    {
        if (array_key_exists($bundleClassName, $this->loadBundles)) {
            return;
        }

        $this->loadBundles[] = $bundleClassName;
    }

    public static function setParameter(string $parameterName, $parameterValue)
    {
        self::$parameters[$parameterName] = $parameterValue;
    }

    /**
     * Returns an array of bundles to register.
     */
    public function registerBundles()
    {
        $bundles = [];
        foreach ($this->loadBundles as $bundleClassName) {
            if (!class_exists($bundleClassName)) {
                throw new \RuntimeException('Added class of bundle not find. '.$bundleClassName);
            }

            $bundles[] = new $bundleClassName();
        }

        return $bundles;
    }

    /**
     * Loads the container configuration.
     *
     * @param LoaderInterface $loader A LoaderInterface instance
     *
     * @throws \Exception
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $parameters = self::$parameters;
        $loader->load(function (ContainerBuilder $container) use (&$parameters) {
            foreach ($parameters as $parameterName => $parameterValue) {
                $container->setParameter($parameterName, $parameterValue);
            }
            $container->addCompilerPass($this);
        });

        $loader->load(self::$configDir.'test.yml');
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->deprivateServices as $serviceName) {
            $container->getDefinition($serviceName)->setPublic(true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->getProjectDir().'/cache/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getProjectDir().'/logs';
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir()
    {
        return $this->rootDir;
    }
}
