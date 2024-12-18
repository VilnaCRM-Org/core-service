<?php

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\Factory\DeleteCustomerCommandFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

class DeleteCustomerProcessor implements ProcessorInterface
{
    public function __construct(private DeleteCustomerCommandFactoryInterface $factory) {

    }
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
       $this->factory->create($uriVariables['id']);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

}