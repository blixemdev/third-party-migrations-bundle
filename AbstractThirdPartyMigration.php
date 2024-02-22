<?php

declare(strict_types=1);

namespace Blixem\ThirdPartyMigrations;

use Composer\InstalledVersions;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Base class for third party migrations.
 *
 * Using ordinary Doctrine migrations for third party packages is a bit of a hassle. This class
 * makes it easier, by allowing you to define to what version of the package the migration belongs.
 * When installing a package for the first time, all migrations from before the current version are
 * ignored, and only a base migration with version = NULL is executed. Migrations for subsequent
 * updates to the package are executed.
 *
 * The current verison of the database schema is stored in the `composer_package_schema_version` table.
 * The version is a normalized variant of the version of the composer package (e.g. 1.x-dev => 1.0.0).
 *
 * To execute migrations in the correct order, it is recommended to give an installation migration a
 * name like Version0000_Install, and subsequent migrations a name like Version0001_<package version>.
 */
abstract class AbstractThirdPartyMigration extends AbstractMigration
{

    /**
     * Composer package name.
     */
    abstract static protected function getComposerPackage(): string;

    /**
     * Composer package version this migration targets.
     *
     * NULL if its an installation/initial migration.
     */
    abstract static protected function getVersion(): ?string;

    /**
     * Returns the version of the package the schema returns to when reversing the migration.
     *
     * Returns NULL if no previous version is specified by the developer.
     */
    static protected function getPreviousVersion(): ?string
    {
        return null;
    }

    /**
     * Internal variable to skip the migration.
     *
     * Used if an installation migration was run for a higher version than the version this migration targes.
     */
    private bool $skip = false;

    /**
     * Sets up the composer_package_schema_version table if it doesn't exist.
     */
    private function setupVersionTable(): void
    {
        $this->connection->executeQuery('CREATE TABLE IF NOT EXISTS `composer_package_schema_version` (
            `composer_package` VARCHAR(100) NOT NULL,
            `version` VARCHAR(10) NOT NULL,
            PRIMARY KEY (`composer_package`)) ENGINE = InnoDB
        ');
    }

    /**
     * Returns the package version database schema for this package.
     *
     * Returns NULL if no migration for this package has run before.
     */
    private function getCurrentVersion(): ?string
    {
        $this->setupVersionTable();

        $version = $this->connection->executeQuery('SELECT `version` FROM `composer_package_schema_version` WHERE composer_package = :package', [
            'package' => $this->getComposerPackage()
        ])->fetchOne();

        return $version === false ? null : self::normalizeVersion($version);
    }

    /**
     * Updates the package version in the schema version table.
     */
    private function setCurrentVersion(?string $version): void
    {
        $this->setupVersionTable();

        $composer_package = $this->getComposerPackage();

        // Delete existing row first, if it exists
        $this->connection->executeQuery('DELETE FROM `composer_package_schema_version` WHERE composer_package = :composer_package', [
            'composer_package' => $composer_package
        ]);

        // Insert new row with updated version
        if ($version !== null)
        {
            $this->connection->executeQuery('INSERT INTO `composer_package_schema_version` (composer_package, `version`) VALUES (:composer_package, :version)', [
                'composer_package' => $composer_package,
                'version' => $version
            ]);
        }
    }

    public function preUp(Schema $schema): void
    {
        $currentVersion = $this->getCurrentVersion();

        // If static::getVersion() returns NULL, this is an initial migration or installation migration.
        // It should only be run if no other migrations have been run for this package before, i.e.
        // when $currentVersion is NULL.
        $version = static::getVersion();
        if ($version === null)
        {
            if ($currentVersion !== null)
            {
                $this->skip = true;
            }

            return;
        }

        // If this is not an initial migration, it should only be executed if the current version
        // is less than the version of the migration.
        if (version_compare($currentVersion, $version, '<'))
        {
            return;
        }

        $this->skip = true;
    }

    public function getSql(): array
    {
        if (!$this->skip)
        {
            return parent::getSql();
        }

        return [];
    }

    protected static function getPackageVersion(): string
    {
        return self::normalizeVersion(InstalledVersions::getPrettyVersion(static::getComposerPackage()));
    }

    private static function normalizeVersion(string $version): string
    {
        // Remove -dev, -alpha, etc.
        $version = explode('-', $version)[0];

        return ltrim(str_replace('.x', '.99999', $version), 'v');
    }

    public function postUp(Schema $schema): void
    {
        if (!$this->skip)
        {
            $this->setCurrentVersion(static::getVersion() ?? self::getPackageVersion());
        }
    }

    public function preDown(Schema $schema): void
    {
        // If this is not the installation migration and no previous migration was specified, the migration is irreversible,
        // because we cannot update the version in the schema version table.
        if (static::getVersion() !== null && static::getPreviousVersion() === null)
        {
            $this->throwIrreversibleMigrationException();
        }
    }

    public function postDown(Schema $schema): void
    {
        // If the migration was run, we need to update the version in the database.
        $previousMigration = static::getPreviousVersion();
        $version = $previousMigration === null ? null : $previousMigration::getVersion();
        $this->setCurrentVersion($version);
    }

}
