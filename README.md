![Package cover](./arts/package-cover.png)

# Backup Tables

[![Latest Version on Packagist](https://img.shields.io/packagist/v/watheqalshowaiter/backup-tables.svg?style=flat-square)](https://packagist.org/packages/watheqalshowaiter/backup-tables)
[![Total Downloads](https://img.shields.io/packagist/dt/watheqalshowaiter/backup-tables.svg?style=flat-square)](https://packagist.org/packages/watheqalshowaiter/backup-tables)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/watheqalshowaiter/backup-tables/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/watheqalshowaiter/backup-tables/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![GitHub Tests For Laravel Versions Action Status](https://img.shields.io/github/actions/workflow/status/watheqalshowaiter/backup-tables/tests-for-laravel-versions.yml?branch=main&label=tests-for-laravel-versions&style=flat-square)](https://github.com/watheqalshowaiter/backup-tables/actions?query=workflow%3A"tests-for-laravel-versions"+branch%3Amain)
[![GitHub Tests For Databases Action Status](https://img.shields.io/github/actions/workflow/status/watheqalshowaiter/backup-tables/tests-for-databases.yml?branch=main&label=tests-for-databases&style=flat-square)](https://github.com/watheqalshowaiter/backup-tables/actions?query=workflow%3Atests-for-databases+branch%3Amain)

Backup single or multiple database tables with ease.

> Note: if you want a full database backup with many features go for [Spatie Laravel Backup](https://github.com/spatie/laravel-backup).

## Installation

You can install the package via Composer:

```bash
composer require watheqalshowaiter/backup-tables
```

## Usage

Use the `BackupTables::generateBackup($tableToBackup)` Facade anywhere in your application and it will
generate `$tableToBackup_backup_2024_08_22_17_40_01` table in the database with all the data and structure. Note that
the datetime `2024_08_22_17_40_01` will be varied based on your datetime.

```php
use WatheqAlshowaiter\BackupTables\BackupTables; // import the facade

class ChangeSomeData
{
    public function handle()
    {
        BackupTables::generateBackup('users'); // will result: users_backup_2024_08_22_17_40_01
       
        // change some data.. 
    }
}
```

And More Customizations

- You can use an array to backup more than one table

```php
BackupTables::generateBackup(['users', 'posts']); 
// users_backup_2024_08_22_17_40_01
// posts_backup_2024_08_22_17_40_01 
```

- Or add Classes as parameters, It will backup their tables

```php
BackupTables::generateBackup(User::class); // users_backup_2024_08_22_17_40_01
// or
BackupTables::generateBackup([User::class, Post::class]); // users_backup_2024_08_22_17_40_01, posts_backup_2024_08_22_17_40_01 
 
```

- You can customize the $dataTime format to whatever you want

```php
BackupTables::generateBackup('users', 'Y_d_m_H_i'); // users_backup_2024_22_08_17_40
```

> *Note: be aware if you customize the datetime to wide datetime the package will check the backup datetime file and
> will be skipped
> the exact same datetime, so most of the time the default will be fine
> For example: if you use this `Y_d_m_H` you can not generate the same backup in the same hour

```php
BackupTables::generateBackup('users', 'Y_d_m_H'); // can not generate the same backup in the same hour
BackupTables::generateBackup('users', 'Y_d_m'); // can not generate the same backup in the same day
```

## Why?

Sometimes you want to backup some database tables before changing data for whatever reason, this package serves this
need. I used it personally before adding foreign keys for tables that required the removal of unlinked fields for parent tables.
You may find some situation where you play with table data or you're afraid of missing data so you backup these tables
beforehand.

## Features

✅ Supports Laravel versions: 11, 10, 9, 8, 7, and 6.

✅ Supports PHP versions: 8.2, 8.1, 8.0, and 7.4.

✅ Supports SQL databases: SQLite, MySQL/MariaDB, PostgreSQL, and SQL Server.

✅ Fully automated tested with PHPUnit.

✅ Full GitHub Action CI pipeline to format code and test against all Laravel and PHP versions.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

If you have any ideas or suggestions to improve it or fix bugs, your contribution is welcome. I encourage you to look at [todos](./todos.md) which are the most important features that need to be added. If you have something different, submit an issue first to discuss or report a bug, then do a pull request.

## Security Vulnerabilities

If you find any security vulnerabilities don't hesitate to contact me at `watheqalshowaiter[at]gmail[dot]com` to fix
them.

## Credits

- [Watheq Alshowaiter](https://github.com/WatheqAlshowaiter)
- [Omar Alalwi](https://github.com/omaralalwi) - This package is based on his initial code.
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
