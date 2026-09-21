<?php

declare(strict_types=1);

use Illuminate\Foundation\Application as BaseApplication;

test('bootstrap resolves the application namespace without composer namespace metadata', function () {
    $directory = sys_get_temp_dir().'/impact-namespace-'.bin2hex(random_bytes(8));
    mkdir($directory);
    file_put_contents($directory.'/composer.json', '{}');

    try {
        // Reproduce the framework failure with deployment metadata lacking autoload.
        $defaultApplication = new BaseApplication($directory);
        expect(fn () => $defaultApplication->getNamespace())
            ->toThrow(RuntimeException::class, 'Unable to detect application namespace.');

        $application = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $application->setBasePath($directory);

        expect($application->getNamespace())->toBe('App\\');
    } finally {
        BaseApplication::setInstance(null);
        unlink($directory.'/composer.json');
        rmdir($directory);
    }
});
