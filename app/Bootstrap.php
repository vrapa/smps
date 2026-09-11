<?php

declare(strict_types=1);

namespace App;

use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\Application;
use Nette\Bootstrap\Configurator;
use Symfony\Component\Console\Application as SymfonyApplication;

class Bootstrap
{
    public static function boot(): Configurator
    {
        $configurator = new Configurator();
        $appDir = dirname(__DIR__);
        $configurator->setDebugMode(self::isDebugModeRequested(getenv('SMPS_DEBUG')));
        $configurator->enableTracy($appDir . '/log');
        $configurator->setTempDirectory($appDir . '/temp');

        $configurator->createRobotLoader()
            ->addDirectory(__DIR__)
            ->register();

        $configurator->addConfig($appDir . '/config/common.neon');
        $configurator->addConfig($appDir . '/config/services.neon');
        $configurator->addConfig(
            $appDir . '/config/' . self::localConfigName(getenv('SMPS_ENV')),
        );

        return $configurator;
    }

    public static function isDebugModeRequested(string|false $environmentValue): bool
    {
        return filter_var($environmentValue ?: '0', FILTER_VALIDATE_BOOLEAN);
    }

    public static function localConfigName(string|false $environmentValue): string
    {
        return $environmentValue === 'test' ? 'test.neon' : 'local.neon';
    }

    public static function runCli(): void
    {
        $configurator = self::boot();
        $configurator->addStaticParameters([
            'scope' => 'cli',
        ]);
        $container = $configurator->createContainer();
        $application = $container->getByType(SymfonyApplication::class);

        $em = $container->getByType(EntityManagerInterface::class);
        // get currently used platform
        $dbPlatform = $em->getConnection()->getDatabasePlatform();

        // interpret BIT as boolean
        $dbPlatform->registerDoctrineTypeMapping('bit', 'boolean');

        $application->run();
    }
}
