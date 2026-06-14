<?php

declare(strict_types=1);

namespace App\Core\Customer\Domain\Repository;

use App\Core\Customer\Domain\Entity\Customer;

interface CustomerStreamRepositoryInterface extends CustomerRepositoryInterface
{
    /**
     * Stream every persisted customer lazily.
     *
     * Backed by a database cursor so bulk admin scans (e.g. the email
     * backfill command) keep memory bounded instead of materialising the
     * whole collection in an array.
     *
     * @return iterable<Customer>
     */
    public function findAllIterable(): iterable;
}
