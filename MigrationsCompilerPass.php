<?php

namespace Blixem\ThirdPartyMigrationsBundle;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MigrationsCompilerPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container): void
    {
        $configurationDefinition = $container->getDefinition('doctrine.migrations.configuration');

        // Loop through all extensions implementing migrationsExtensionInterface
        foreach ($container->getExtensions() as $extension)
        {
            if (!$extension instanceof MigrationsExtensionInterface)
            {
                continue;
            }

            // Call addMigrationsDirectory($namespace, $path) on the configuration definition for each extension
            $configurationDefinition->addMethodCall('addMigrationsDirectory', [
                $extension->getMigrationsNamespace(),
                realpath($extension->getMigrationsPath())
            ]);
        }
    }

}
