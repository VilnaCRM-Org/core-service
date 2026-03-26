<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$legacyI18nPath = dirname(__DIR__).'/vendor/i18n.php';

if (! is_file($legacyI18nPath)) {
    $packageI18nPath = dirname(__DIR__).'/vendor/behat/gherkin/i18n.php';

    if (is_file($packageI18nPath)) {
        set_error_handler(static fn (): bool => true);

        try {
            $symlinkCreated = symlink($packageI18nPath, $legacyI18nPath);
        } finally {
            restore_error_handler();
        }

        if (! $symlinkCreated && ! is_file($legacyI18nPath)) {
            if (! @copy($packageI18nPath, $legacyI18nPath)) {
                trigger_error(
                    'Could not create vendor/i18n.php symlink or copy for Behat Gherkin.',
                    E_USER_WARNING
                );
            }
        }
    }
}
