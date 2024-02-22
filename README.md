# ThirdPartyMigrations

ThirdPartyMigrations enables third party Composer packages to easily register
their own migrations.

Using ordinary Doctrine migrations for third party packages is a bit of a
hassle. This package makes it easier, by allowing you to define which package
the migration belongs to, and indicate a schema version number for every
migration. ThirdPartyMigrations makes sure that only the right migrations are
executed. The current schema versions for every package is stored in the
`package_schema_version` table. Executing migrations up or down automatically
modifies this table.

You can define two types of migrations: installation migrations, containing the
full schema for the latest version of the package, and update migrations,
containing only the changes to the schema since the previous version.

To execute migrations in the correct order, it is recommended to give an
installation migration a name like Version0000_Install, and increase the number
for update migrations names, e.g. Version0001_<package version>.

## Installation

```
$ composer require blixem/third-party-migrations
```

### Symfony
ThirdPartyMigrations provides a Symfony bundle for easy integration into the
Symfony framework. When using Symfony Flex, the ThirdPartyMigrationsBundle gets
added to your bundle configuration automatically. Otherwise, add the following
line to your `bundles.php`

```php
    Blixem\ThirdPartyMigrations\ThirdPartyMigrationsBundle::class => ['all' => true],
```

## Usage

```php
<?php

namespace MyVendor\MyPackage;

use Blixem\ThirdPartyMigrations\MigrationsProviderInterface;

/**
 * Service definitions of MigrationsProviderInterface are automatically
 * picked up by the Symfony bundle
 */
class MyMigrationsProvider implements MigrationsProviderInterface {

    public static function getMigrationsPath(): string
    {
        return __DIR__ .'/../migrations';
    }

    public static function getMigrationsNamespace(): string
    {
        return 'MyVendor\\MyPackage\\Migrations';
    }

}
```

```php
<?php

namespace MyVendor\MyPackage\Migrations;

/**
 * The installation migration sets up the database from scratch.
 * It contains the most up-to-date version of the database schema.
 */
class Version0000_Install
{

    public function getPackageName(): string { return 'name/package'; }
    public function getTargetVersion(): string { return '1.1.0'; /* the latest version */ }
    public function getPreviousVersion(): ?string { return null; }

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

namespace MyVendor\MyPackage\Migrations;

/**
 * This migration updates the schema from version 1.0.0 to 1.1.0
 */
class Version0001_1_1_0
{

    public function getPackageName(): string { return 'name/package'; }
    public function getTargetVersion(): ?string { return '1.1.0'; }
    public function getPreviousVersion(): ?string { return '1.0.0'; }

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
