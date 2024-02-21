<?php

namespace Blixem\ThirdPartyMigrationsBundle;

/**
 * Interface that informs the migrations compiler pass that an extension defines Doctrine migrations.
 */
interface MigrationsExtensionInterface
{

    /**
     * Returns the path to the directory containing the migrations.
     */
    public function getMigrationsPath(): string;

    /**
     * Returns the namespace of the migrations.
     */
    public function getMigrationsNamespace(): string;

}