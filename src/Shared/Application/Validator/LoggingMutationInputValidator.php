<?php

declare(strict_types=1);

namespace App\Shared\Application\Validator;

use ApiPlatform\Validator\Exception\ValidationException;
use App\Shared\Application\GraphQL\MutationInput;
use Psr\Log\LoggerInterface;

final readonly class LoggingMutationInputValidator implements MutationInputValidatorInterface
{
    public function __construct(
        private MutationInputValidatorInterface $inner,
        private LoggerInterface $logger,
    ) {
    }

    #[Override]
    public function validate(MutationInput $input): void
    {
        try {
            $this->inner->validate($input);
        } catch (ValidationException $exception) {
            $this->logger->notice('Mutation input validation failed', [
                'input_class' => $input::class,
                'violations_count' => $exception->getConstraintViolationList()->count(),
            ]);

            throw $exception;
        }
    }
}
