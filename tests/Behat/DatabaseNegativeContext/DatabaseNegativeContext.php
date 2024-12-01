<?php

declare(strict_types=1);

namespace App\Tests\Behat\DatabaseNegativeContext;

use App\Tests\Behat\Factory\ConfigurableContainerFactory;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DatabaseNegativeContext extends KernelTestCase implements Context
{
    private ContainerInterface $container;
    private Response $response;

    public function __construct()
    {
        parent::__construct();
        $this->container = (new ConfigurableContainerFactory())
            ->create([__DIR__ . '/config/doctrine.yaml']);
    }

    /**
     * @When :method negative database request is sent to :path
     */
    public function sendRequestTo(string $method, string $path): void
    {
        $kernel = $this->container->get('kernel');
        $this->response = $kernel->handle(Request::create($path, $method));
    }

    /**
     * @Then negative database the response status code should be :statusCode
     */
    public function theResponseStatusCodeShouldBe(string $statusCode): void
    {
        Assert::assertEquals($statusCode, $this->response->getStatusCode());
    }

    /**
     * @Then negative database the response body should contain :text
     */
    public function theResponseBodyShouldContain(string $text): void
    {
        $responseContent = $this->response->getContent();
        Assert::assertStringContainsString(
            $text,
            $responseContent,
            "The response body does not contain the expected text: '{$text}'."
        );
    }
}
