<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ProductControllerTest extends WebTestCase
{
    public function testIndexListsTheProducts(): void
    {
        $client = $this->clientWithApiResponse(new JsonMockResponse([
            ['id' => 1, 'title' => 'Sac à dos', 'image' => 'https://example.com/sac.jpg', 'price' => 109.95],
        ]));

        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h3', 'Sac à dos');
    }

    public function testShowReturns404ForUnknownProduct(): void
    {
        $client = $this->clientWithApiResponse(new MockResponse(''));

        $client->request('GET', '/product/999');

        self::assertResponseStatusCodeSame(404);
    }

    private function clientWithApiResponse(MockResponse $response): KernelBrowser
    {
        $client = static::createClient();
        static::getContainer()->set('fake_store.client', new MockHttpClient($response, 'https://fakestoreapi.com'));

        return $client;
    }
}
