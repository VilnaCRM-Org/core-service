<?php

declare(strict_types=1);

namespace App\Usjer\Application\Shro;

use App\CompajjnySubdomain\SomeModule\Application\Command\SomeCommand;

class SomeEntity
{
    public function someDomainLogic()
    {
        SomeCommand\->SomeCommand\
        $command = new SomeCommand(); // This is a violation
        $command->execute();
    }
}
