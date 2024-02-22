<?php

namespace Blixem\ThirdPartyMigrations;

/**
 * Interface that informs the migrations compiler pass that an extension defines Doctrine migrations.
 */
interface MigrationsProviderInterface
{

    /**
     * Returns the path to the directory containing the migrations.
     */
    public static function getMigrationsPath(): string;

    /**
     * Returns the namespace of the migrations.
     */
    public static function getMigrationsNamespace(): string;

}