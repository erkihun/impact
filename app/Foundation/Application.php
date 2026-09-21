<?php

declare(strict_types=1);

namespace App\Foundation;

use Illuminate\Foundation\Application as BaseApplication;

class Application extends BaseApplication
{
    /**
     * The application namespace is fixed, regardless of deployment metadata.
     *
     * @var string
     */
    protected $namespace = 'App\\';
}
