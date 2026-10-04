<?php

namespace App\Tests\Service;

use App\Service\ProductService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

use function array_column;

final class ProductServiceTest extends TestCase
{
    private const API = 'https://fakestoreapi.com';
    private const PRODUCT = ['id' => 1, 'title' => 'Sac', 'image' => 'https://example.com/sac.jpg', 'price' => 109.95];

    public function testGetProductsSkipsIncompleteProducts(): void
    {
        $service = $this->service(new MockHttpClient(new JsonMockResponse([self::PRODUCT, ['id' => 2, 'title' => 'Sans prix ni image']]), self::API));

        self::assertSame([self::PRODUCT], $service->getProducts());
    }

    public function testGetProductReturnsNullForUnknownProduct(): void
    {
        $service = $this->service(new MockHttpClient(new MockResponse('', ['http_code' => 404]), self::API));

        self::assertNull($service->getProduct(999));
    }

    public function testFallsBackToTheLocalCatalogAndDoesNotCallTheApiAgain(): void
    {
        $client = new MockHttpClient([
            new MockResponse('Internal error', ['http_code' => 500]),
            new JsonMockResponse([self::PRODUCT]),
        ], self::API);
        $service = $this->service($client);

        $service->getProducts();
        $products = $service->getProducts();

        self::assertSame(1, $client->getRequestsCount());
        self::assertSame([1, 2, 3, 4, 5], array_column($products, 'id'));
    }

    private function service(MockHttpClient $client): ProductService
    {
        return new ProductService(
            $client,
            new Packages(new Package(new EmptyVersionStrategy())),
            new NullLogger(),
            new ArrayAdapter(),
            __DIR__.'/../../data/products.json',
        );
    }
}
