<?php

declare(strict_types=1);

namespace Blixem\ThirdPartyMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Base class for third party migrations.
 *
 * To execute migrations in the correct order, it is recommended to give an
 * installation migration a name like Version0000_Install, and increase the number
 * for update migrations names, e.g. Version0001_<package version>.
 */
abstract class AbstractThirdPartyMigration extends AbstractMigration
{

    /**
     * Name/identifier of the package this migration belongs to.
     */
    abstract static protected function getPackageName(): string;

    /**
     * Package schema version to which this migration updates the database.
     */
    abstract static protected function getTargetVersion(): string;

    /**
     * Returns the version the schema returns to when reversing the migration.
     *
     * NULL if this is an installation migration: reversing the installation migration
     * removes all this package's tables from the database.
     */
    abstract static protected function getPreviousVersion(): ?string;

    /**
     * Internal variable to skip the migration.
     *
     * Used if an installation migration was run for a higher version than the version this migration targes.
     */
    private bool $skip = false;

    /**
     * Sets up the package_schema_version table if it doesn't exist.
     */
    private function setupVersionTable(): void
    {
        $this->connection->executeQuery('CREATE TABLE IF NOT EXISTS `package_schema_version` (
            `package` VARCHAR(100) NOT NULL,
            `version` VARCHAR(10) NOT NULL,
            PRIMARY KEY (`package`)) ENGINE = InnoDB
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

        $version = $this->connection->executeQuery('SELECT `version` FROM `package_schema_version` WHERE package = :package', [
            'package' => $this->getPackageName()
        ])->fetchOne();

        return $version === false ? null : $version;
    }

    /**
     * Updates the package version in the schema version table.
     */
    private function setCurrentVersion(?string $version): void
    {
        $this->setupVersionTable();

        $package = $this->getPackageName();

        // Delete existing row first, if it exists
        $this->connection->executeQuery('DELETE FROM `package_schema_version` WHERE package = :package', [
            'package' => $package
        ]);

        // Insert new row with updated version
        if ($version !== null)
        {
            $this->connection->executeQuery('INSERT INTO `package_schema_version` (package, `version`) VALUES (:package, :version)', [
                'package' => $package,
                'version' => $version
            ]);
        }
    }

    public function preUp(Schema $schema): void
    {
        $currentVersion = $this->getCurrentVersion();
        $targetVersion = static::getTargetVersion();

        // A migration should only be executed if its target version is higher than the current schema version.
        if ($currentVersion === null || version_compare($currentVersion, $targetVersion, '<'))
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

    public function postUp(Schema $schema): void
    {
        if (!$this->skip)
        {
            $this->setCurrentVersion(static::getTargetVersion());
        }
    }

    public function postDown(Schema $schema): void
    {
        $this->setCurrentVersion(static::getPreviousVersion());
    }

}
