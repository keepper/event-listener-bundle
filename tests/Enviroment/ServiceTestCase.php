<?php

namespace Keepper\EventListenerBundle\Tests\Enviroment;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

class ServiceTestCase extends KernelTestCase
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected static $pathSearchDeep = 2;
    protected static $postDir = '';

    private static $bundleClassName;

    public static function setUpBeforeClass()
    {
        $kernelDir = getenv('KERNEL_DIR');
        if ($kernelDir == '' || $kernelDir == false) {
            $_SERVER['KERNEL_DIR'] = realpath(self::getRootPath().'/run');
            $kernelDir = $_SERVER['KERNEL_DIR'];
        }

        $cacheDirectory = realpath($kernelDir).'/cache/test';
        // Очищаем кэш
        exec(sprintf('rm -rf %s', escapeshellarg($cacheDirectory)));

        $possibleDirs = [
            realpath($kernelDir.'/../Resources/'),
            realpath($kernelDir.'/../src/'.self::$postDir.'/Resources/'),
        ];

        $finded = false;
        foreach ($possibleDirs as $dir) {
            if (empty($dir)) {
                continue;
            }
            if (!file_exists($dir)) {
                continue;
            }
            UnitTestKernel::$configDir = $dir.'/';
            $finded = true;
        }

        if (!$finded) {
            throw new \RuntimeException('Не смогли определить директорию с конфигурационными файлами. KernelDir: '.$kernelDir);
        }
    }

    protected static function getRootPath()
    {
        $pos = strrpos(static::class, '\\');
        $namespace = false === $pos ? '' : substr(static::class, 0, $pos);
        $names = explode('\\', $namespace);

        while (true) {
            if (count($names) == 0) {
                throw new \RuntimeException('Имя теста, не подходит под соглашение. Нет возможности настроить тестовое окружение');
            }
            $name = array_pop($names);
            if ('Bundle' != substr($name, -6)) {
                continue;
            }

            $path = implode('\\', $names).'\\'.$name;
            $className = $path.'\\'.$name;

            if (class_exists($className)) {
                self::$bundleClassName = $className;
                $reflected = new \ReflectionObject(new $className());

                $path = \dirname($reflected->getFileName());
                $postDir = '';
                $pathParts = explode('/', $path);
                for ($i = 0; $i < static::$pathSearchDeep; ++$i) {
                    $pathPart = array_pop($pathParts);

                    if ($pathPart != 'src') {
                        if ($postDir == '') {
                            $postDir = $pathPart;
                        } else {
                            $postDir = $pathPart.'/'.$postDir;
                        }

                        continue;
                    }

                    self::$postDir = $postDir;

                    return implode('/', $pathParts);
                }
            }
        }
    }

    protected function setUp()
    {
        $this->logger = new Logger('TestEnv');
        parent::setUp();
    }

    public function bundleClassesToRegister(array $addTo = []): array
    {
        if (count($addTo) == 0) {
            return [];
        }

        return array_merge([self::$bundleClassName], $addTo);
    }

    public function servicesToUnprivate(array $addTo = []): array
    {
        if (count($addTo) == 0) {
            return [];
        }

        return array_merge([], $addTo);
    }

    public function baseParameters(array $addTo = []): array
    {
        if (count($addTo) == 0) {
            return [];
        }

        return array_merge([], $addTo);
    }

    protected function getService(string $serviceName)
    {
        $container = static::$kernel->getContainer();

        return $container->get($serviceName);
    }

	protected function hasService(string $serviceName)
	{
		$container = static::$kernel->getContainer();

		if ($container->has('test.service_container')) {
			$container = $container->get('test.service_container');
		}

		return $container->has($serviceName);
	}
}
