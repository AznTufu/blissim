<?php

namespace App\Service;

use App\Exception\ProductApiException;
use JsonException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function is_array;
use function is_int;
use function is_string;

class ProductService
{
    private const API_DOWN_KEY = 'fake_store_api_down';
    private const API_DOWN_TTL = 500;

    public function __construct(
        private HttpClientInterface $fakeStoreClient,
        private Packages $packages,
        private LoggerInterface $logger,
        private CacheItemPoolInterface $productApiCache,
        #[Autowire('%kernel.project_dir%/data/products.json')]
        private string $fallbackFile,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getProducts(): array
    {
        try {
            $products = $this->fetch('/products');

            if (!is_array($products) || !array_is_list($products)) {
                throw new ProductApiException();
            }
        } catch (ProductApiException $e) {
            $products = $this->fallbackProducts($e);
        }

        return array_values(array_filter($products, $this->isValidProduct(...)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProduct(int $id): ?array
    {
        try {
            $product = $this->fetch('/products/'.$id);

            if (null !== $product && !$this->isValidProduct($product)) {
                throw new ProductApiException();
            }

            return $product;
        } catch (ProductApiException $e) {
            foreach ($this->fallbackProducts($e) as $product) {
                if ($id === ($product['id'] ?? null)) {
                    return $product;
                }
            }

            return null;
        }
    }

    private function fetch(string $path): ?array
    {
        if ($this->productApiCache->hasItem(self::API_DOWN_KEY)) {
            throw new ProductApiException();
        }

        try {
            $response = $this->fakeStoreClient->request('GET', $path);

            if (404 === $response->getStatusCode() || '' === trim($response->getContent())) {
                return null;
            }

            return $response->toArray();
        } catch (ExceptionInterface $e) {
            $this->productApiCache->save(
                $this->productApiCache->getItem(self::API_DOWN_KEY)->set(true)->expiresAfter(self::API_DOWN_TTL),
            );

            throw new ProductApiException($e);
        }
    }

    /**
     * @return list<array<string, mixed>>
     *
     * @throws ProductApiException
     */
    private function fallbackProducts(ProductApiException $apiError): array
    {
        $this->logger->warning('API produits indisponible, utilisation du catalogue de secours.', ['exception' => $apiError]);

        if (!is_file($this->fallbackFile)) {
            throw $apiError;
        }

        try {
            $products = json_decode(file_get_contents($this->fallbackFile), true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw $apiError;
        }

        if (!is_array($products) || !array_is_list($products)) {
            throw $apiError;
        }

        return array_map(function (mixed $product) {
            if (is_array($product) && is_string($product['image'] ?? null)) {
                $product['image'] = $this->packages->getUrl($product['image']);
            }

            return $product;
        }, $products);
    }

    private function isValidProduct(mixed $product): bool
    {
        return is_array($product)
            && is_int($product['id'] ?? null)
            && is_string($product['title'] ?? null)
            && is_string($product['image'] ?? null)
            && is_numeric($product['price'] ?? null);
    }
}
