<?php

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
use Survos\SaisBundle\Model\AccountSetup;
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
class LoadCommand
{
	public function __construct(
        #[Autowire('%kernel.project_dir%/data/products.json')] private string $filename,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly ImageRepository $imageRepository,
        private SaisClientService $saisClientService,
        private readonly Resolver $resolver,
        #[Target('filesystem.original.storage')]
        private FilesystemOperator $originalFilesystem,

        #[Target('filesystem.cache.storage')]
        private FilesystemOperator $cacheFilesystem,
        private HttpClientInterface $httpClient,
    )
	{
	}


	public function __invoke(
		SymfonyStyle $io,
        #[Argument('url')] ?string $url = null,

		#[Option('max number of records to import')] ?int $limit = null,
		#[Option('purge Products')] ?bool $purge = null,
	): int
	{
        $url ??= $this->filename;
		if ($limit) {
		    $io->writeln("Option limit: $limit");
		}
        if ($purge) {
            //$io show "Purging Products";
            $io->writeln("Purging Images and Products");
            foreach ([Image::class, Product::class] as $className) {
                $count = $this->entityManager->getRepository(Image::class)->createQueryBuilder('qb')->delete()->getQuery()->execute();
                $io->writeln("Purging $count $className");
            }
//            $this->entityManager->getRepository(Product::class)->createQueryBuilder('qb')->delete()->getQuery()->execute();
//            assert($this->entityManager->getRepository(Image::class)->count() == 0, "didnt purge");
//            $this->entityManager->flush();
        }

        // wget https://dummyjson.com/products -O data/products.json
        foreach (json_decode(file_get_contents($url))->products as $idx => $data) {
            // object Mapper?
            if (!$product = $this->productRepository->findOneBy(['sku' => $data->sku])) {
                $product = new Product(sku: $data->sku, data: (array) $data);
                $this->entityManager->persist($product);
            }
            $product->title = $data->title;
            $product->description = $data->description;
            $product->brand = $data->brand??null;
            $product->tags = $data->tags??null;
            $product->category = $data->category;
            if ($thumbUrl = $data->thumbnail) {
                $code = hash('xxh3', $thumbUrl);
                $ext = pathinfo(parse_url($thumbUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                $thumbPath = "thumbs/$code.$ext";
                // this downloads to the flysystem storage, e.g. s3, by way of a temporary file
                $this->downloadImage($thumbUrl, $thumbPath);
                $media = $this->resolver->resolve($thumbPath);
                $product->thumb = $media;
            }

            if (0)
            foreach ($data->images as $imageUrl) {

                $code = hash('xxh3', $imageUrl);
                $ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
                $imagePath = "images/$code.$ext";

                $this->downloadImage($imageUrl, $imagePath);
                $media = $this->resolver->resolve($imagePath);

                if (!$image = $this->imageRepository->findOneBy([
                    'product' => $product,
                    'code' => SaisClientService::calculateCode($imageUrl, AppController::SAIS_CLIENT_CODE),
                ])) {
                    $image = new Image($product, $imageUrl);
                    $this->entityManager->persist($image);
                }
                $image->media = $media;
            }

            if ($limit && ($idx >= $limit - 1)) {
                break;
            }

        }
        $this->entityManager->flush();

        $io->success(self::class . " success. " . $this->productRepository->count());
		return Command::SUCCESS;
	}

    public function downloadImage(string $imageUrl, string $path): string
    {

        if (!$this->originalFilesystem->fileExists($path)) {
            // Download to temp file
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            $response = $this->httpClient->request('GET', $imageUrl);
            file_put_contents($tempFile, $response->getContent());

            // Upload to Flysystem (works with local or S3)
            $stream = fopen($tempFile, 'rb');
            $this->originalFilesystem->writeStream($path, $stream);
            fclose($stream);

            // Clean up temp file
            unlink($tempFile);
        }

        return $path;
    }
}
