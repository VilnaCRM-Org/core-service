<?php

namespace App\Customer\Domain\Factory\Event;

use App\Customer\Domain\Entity\CustomerInterface;
use App\Customer\Domain\Event\CustomerCreatedEvent;

interface CustomerCreatedEventFactoryInterface
{
    public function create(
        CustomerInterface $customer,
        string $eventId
    ): CustomerCreatedEvent;
}