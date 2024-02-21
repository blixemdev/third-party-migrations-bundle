# ThirdPartyMigrationsBundle

ThirdPartyMigrationsBundle enables third party Symfony bundles to easily register their own migrations.

## Installation

```
$ composer require blixem/third-party-migrations-bundle
```

## Usage

```php
<?php

/**
 * The installation migration sets up the database from scratch.
 * It contains the most up-to-date version of the database schema.
 */
class Version0000_Install
{

    public function getComposerPackage(): string { return 'name/package'; }
    public function getVersion(): ?string { return null; }

    public function up(): void
    {
        $this->addSql('CREATE TABLE ...');
    }

    public function down(): void
    {
        $this->addSql('DELETE TABLE ...');
    }

}
```

```php
<?php

/**
 * This migration updates the schema from version 1.0.1 to 1.1.0
 */
class Version0001_1_1_0
{

    public function getComposerPackage(): string { return 'name/package'; }
    public function getVersion(): ?string { return '1.1.0'; }
    public function getPreviousVersion(): ?string { return '1.0.1'; }

    public function up(): void
    {
        $this->addSql('ALTER TABLE ... ADD ...');
    }

    public function down(): void
    {
        $this->addSql('ALTER TABLE ... DROP COLUMN ...');
    }

}
```
