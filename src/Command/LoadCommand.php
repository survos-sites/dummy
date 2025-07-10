<?php

namespace App\Command;

use App\Controller\AppController;
use App\Entity\Image;
use App\Entity\Product;
use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Survos\SaisBundle\Model\AccountSetup;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand('app:load', 'Load the data from dummyjson.com')]
class LoadCommand
{
	public function __construct(
        #[Autowire('%kernel.project_dir%/data/products.json')] private string $filename,
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $productRepository,
        private readonly ImageRepository $imageRepository,
        private SaisClientService $saisClientService

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
            $io->writeln("Purging Products");
            $this->entityManager->getRepository(Product::class)->createQueryBuilder('qb')->delete();
        }

        try {
            $response = $this->saisClientService->accountSetup(new AccountSetup(AppController::SAIS_CLIENT_CODE, 500));
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            echo 'Error during account setup: ' . $e->getMessage();
        }


        // wget https://dummyjson.com/products -O data/products.json
        foreach (json_decode(file_get_contents($url))->products as $idx => $data) {
            // object Mapper?
            if (!$product = $this->productRepository->findOneBy(['sku' => $data->sku])) {
                $product = new Product(sku: $data->sku, title: $data->title, data: $data);
                $this->entityManager->persist($product);
            }
            $product->title = $data->title;

            foreach ($data->images as $imageUrl) {
                if (!$image = $this->imageRepository->findOneBy([
                    'product' => $product,
                    'code' => SaisClientService::calculateCode($imageUrl, AppController::SAIS_CLIENT_CODE),
                ])) {
                    $image = new Image($product, $imageUrl);
                    $this->entityManager->persist($image);
                }
            }

            if ($limit && ($idx > $limit)) {
                break;
            }

        }

        // $product = new Product();
        // $manager->persist($product);

        $this->entityManager->flush();

        $io->success(self::class . " success.");
		return Command::SUCCESS;
	}
}
