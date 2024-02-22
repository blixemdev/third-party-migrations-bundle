<?php

namespace Blixem\ThirdPartyMigrations;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MigrationsCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        $configurationDefinition = $container->getDefinition('doctrine.migrations.configuration');
        $taggedServices = $container->findTaggedServiceIds('third_party_migrations.migrations_provider');

        // Loop through all extensions implementing MigrationsProviderInterface
        foreach ($taggedServices as $serviceId => $_)
        {
            $providerClass = $container->getDefinition($serviceId)->getClass();
            if ($providerClass === null || !is_subclass_of($providerClass, MigrationsProviderInterface::class))
            {
                continue;
            }

            // Call addMigrationsDirectory($namespace, $path) on the configuration definition for each extension
            $configurationDefinition->addMethodCall('addMigrationsDirectory', [
                $providerClass::getMigrationsNamespace(),
                realpath($providerClass::getMigrationsPath())
            ]);
        }
    }

}
