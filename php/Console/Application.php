<?php

declare(strict_types=1);

namespace PeclInfo\Console;

use Symfony\Component\Console\Application as ConsoleApplication;

class Application extends ConsoleApplication
{
    public function __construct()
    {
        parent::__construct('pecl-info');
        $this->add(new Command\Update());
    }
}
