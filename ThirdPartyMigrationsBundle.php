<?php

namespace Blixem\ThirdPartyMigrations;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ThirdPartyMigrationsBundle extends Bundle {

    public function build(ContainerBuilder $builder): void
    {
        parent::build($builder);

        $builder->registerForAutoconfiguration(MigrationsProviderInterface::class)->addTag('third_party_migrations.migrations_provider');
        $builder->addCompilerPass(new MigrationsCompilerPass());
    }


}