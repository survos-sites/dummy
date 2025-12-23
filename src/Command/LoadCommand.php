<?php
declare(strict_types=1);

namespace App\Command;

use App\Controller\AppController;
use App\Entity\Image;
use App\Entity\Product;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use Castor\Attribute\AsSymfonyTask;
use Doctrine\ORM\EntityManagerInterface;
use JoliCode\MediaBundle\Resolver\Resolver;
use League\Flysystem\FilesystemOperator;
use Survos\BabelBundle\Service\TermRegistry;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand('app:load', 'Load the Product and Image entities from dummyjson.com')]
#[AsSymfonyTask('load')]
final class LoadCommand
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/data/products.json')] private string $filename,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly ImageRepository $imageRepository,
        private readonly SaisClientService $saisClientService,
        private readonly Resolver $resolver,
        private readonly TermRegistry $termRegistry,
        #[Target('filesystem.original.storage')]
        private readonly FilesystemOperator $originalFilesystem,
        #[Target('filesystem.cache.storage')]
        private readonly FilesystemOperator $cacheFilesystem,
        private readonly HttpClientInterface $httpClient,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('url')] ?string $url = null,
        #[Option('max number of records to import')] ?int $limit = null,
        #[Option('purge Products')] ?bool $purge = null,
    ): int {
        $url ??= $this->filename;

        if ($purge) {
            $io->writeln('Purging Images and Products');
            foreach ([Image::class, Product::class] as $className) {
                $count = $this->entityManager->getRepository($className)->createQueryBuilder('qb')->delete()->getQuery()->execute();
                $io->writeln("Purged $count $className");
            }
        }

        // Ensure baseline sets exist (labels are source-locale; Lingua can fill later if you add stubs)
        $this->termRegistry->ensureTermSet('category', 'Category');
        $this->termRegistry->ensureTermSet('tag', 'Tag');

        $payload = json_decode((string) file_get_contents($url), false, 512, JSON_THROW_ON_ERROR);

        foreach ($payload->products as $idx => $data) {
            if (!$product = $this->productRepository->findOneBy(['sku' => $data->sku])) {
                $product = new Product(sku: $data->sku, data: (array) $data);
                $this->entityManager->persist($product);
            }

            $product->title = $data->title ?? null;
            $product->description = $data->description ?? null;
            $product->brand = $data->brand ?? null;

            // TERM-BACKED: category + tags (store codes; ensure terms exist)
            $categoryCode = (string) ($data->category ?? '');
            $product->category = $categoryCode !== '' ? $categoryCode : null;
            if ($categoryCode !== '') {
                $this->termRegistry->ensureTerm('category', $categoryCode, $categoryCode);
            }

            $tagCodes = [];
            foreach (($data->tags ?? []) as $tag) {
                $tag = (string) $tag;
                if ($tag === '') {
                    continue;
                }
                $tagCodes[] = $tag;
                $this->termRegistry->ensureTerm('tag', $tag, $tag);
            }
            $product->tags = $tagCodes;

            if ($thumbUrl = ($data->thumbnail ?? null)) {
                $code = hash('xxh3', (string) $thumbUrl);
                $ext = pathinfo((string) parse_url((string) $thumbUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                $thumbPath = "thumbs/$code.$ext";

                $this->downloadImage((string) $thumbUrl, $thumbPath);
                $product->thumb = $this->resolver->resolve($thumbPath);
            }

            if ($limit && ($idx >= $limit - 1)) {
                break;
            }
        }

        $this->entityManager->flush();

        $io->success(self::class . ' success. ' . $this->productRepository->count());
        return Command::SUCCESS;
    }

    public function downloadImage(string $imageUrl, string $path): string
    {
        if (!$this->originalFilesystem->fileExists($path)) {
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            $response = $this->httpClient->request('GET', $imageUrl);
            file_put_contents($tempFile, $response->getContent());

            $stream = fopen($tempFile, 'rb');
            $this->originalFilesystem->writeStream($path, $stream);
            fclose($stream);

            unlink($tempFile);
        }

        return $path;
    }
}
