<?php

namespace App\DataFixtures;

use App\Controller\AppController;
use App\Entity\Image;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Survos\SaisBundle\Model\AccountSetup;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AppFixtures extends Fixture
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/data/products.json')] private string $filename,
        private SaisClientService $saisClientService
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // make sure the client is registered on the sais service
        try {
            $response = $this->saisClientService->accountSetup(new AccountSetup(AppController::SAIS_CLIENT_CODE, 500));
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            echo 'Error during account setup: ' . $e->getMessage();
        }

        // wget https://dummyjson.com/products -O data/products.json
        foreach (json_decode(file_get_contents($this->filename))->products as $data) {
            foreach ($data->images as $image) {
                $image = new Image($image);
                $manager->persist($image);
            }
        }

        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
