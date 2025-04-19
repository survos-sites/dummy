<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use Survos\LibreTranslateBundle\Service\TranslationClientService;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppController extends AbstractController
{

    const SAIS_CLIENT_CODE='dummy-sais';
    public function __construct(
        private CacheInterface $cache,
        private SaisClientService $saisService,
        private UrlGeneratorInterface $urlGenerator,
        private ImageRepository $imageRepository,
    )
    {

    }

    #[Route('/batch-translate.{_format}', name: 'app_batch_translate')]
    public function batchTranslate(
        TranslationClientService $translationClientService,
        string $_format = 'json',
        #[MapQueryParameter] int $limit = 5
    ): Response
    {
        $products = $this->getDummyProducts();
        foreach ($products->products as $idx => $product) {
            $text[] = $product->title;
            $text[] = $product->description;
            if ($idx >= $limit) {
                break;
            }
        }

        $response = $translationClientService->requestTranslations('en',
            ['es', 'fr', 'hu', 'de','da','uk'], $text);
        return $_format === 'json' ? $this->json($response): $this->render('app/index.html.twig', [
            'response' => $response,
        ]);
    }

    #[Route('/compress', name: 'app_compress_images')]
    public function listFeatured(): Response
    {
        // example of sending multiple images
        $products = $this->getDummyProducts();
        $responses = [];
        foreach ($products->products as $product) {
            $payload = new \Survos\SaisBundle\Model\ProcessPayload(
                $product->images,
                ['small'],
                context: [
                    'productId' => $product->id
                ],
                mediaCallbackUrl: $this->urlGenerator->generate('app_webhook')
            );
            $response = $this->saisService->dispatchProcess($payload);
            $responses[] = [
                'payload' => $payload,
                'response' => $response,
            ];
        }
        dd($responses);
        return $this->render('app/index.html.twig', [
            'responses' => $responses,
        ]);
    }

    #[Route('/webhook', name: 'app_webhook')]
    public function webHook(Request $request): Response
    {
        $data = $request->request->all();

    }

    private function getDummyProducts()
    {
        $url = 'https://dummyjson.com/products?limit=100';
//        dd($url);
        $data = $this->cache->get(md5($url), fn(CacheItem $item) => json_decode(file_get_contents($url)));
        return $data;

    }

    #[Route('/images', name: 'app_images')]
    #[Template('app/images.html.twig')]
    public function images(
        HttpClientInterface $httpClient,
        string         $target = 'es'): Response|array
    {
        return [
            'images' => $this->imageRepository->findBy([], [], 10),
        ];
    }


    #[Route('/{target}', name: 'app_homepage')]
    public function home(
        HttpClientInterface $httpClient,
        ?LibreTranslate $libreTranslate=null,
        string         $target = 'es'): Response
    {

        if ($libreTranslate) {
            $libreTranslate = new LibreTranslate(httpClient: $httpClient);
            $libreTranslate->setHttpClient($httpClient);
//        $libreTranslate->setTarget($target);
        }


        $translations = [];
        $x = [];
        $data = $this->getDummyProducts();
        foreach ($data->products as $idx => $product) {
            $x[] = $product->title;
            if ($libreTranslate) {
                $z[] = $libreTranslate->translate($product->title, target: $target);
                $translations[] = $this->cache->get(md5($product->title).$target,
                    fn(CacheItem $cacheItem) =>
                    $libreTranslate->translate($product->title, target: $target)
                );
                if ($idx > 0) break;

            }
        }
        // argh.  Proof that bulk translations don't work well.
//        $xx = [
//            "My name is Robert",
//            "Where is the bathroom?",
//            "Eyeshadow Palette with Mirror"
//        ];
//        $x = array_merge($x, $xx);
//        foreach ($xx as $xxx) {
//            $individual[$xxx] = $libreTranslate->translate($xxx, target: $target);
//        }
//        $bulk= $libreTranslate->translate($xx, 'en', target: $target);
//        dd(original: $xx, bulk: $bulk, individual: $individual);


//        dd($translations);
        return $this->render('app/index.html.twig', [
            'products' => $data->products,
            'translations' => $translations,
            'languages' => [], // $libreTranslate->getLanguages()
        ]);
    }
}
