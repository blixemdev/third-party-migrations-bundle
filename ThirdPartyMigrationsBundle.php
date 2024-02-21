<?php

namespace Blixem\ThirdPartyMigrationsBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ThirdPartyMigrationsBundle extends Bundle {

    public function build(ContainerBuilder $builder): void
    {
        parent::build($builder);
        $builder->addCompilerPass(new MigrationsCompilerPass());
    }


}