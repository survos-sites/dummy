<?php

namespace App\Controller;

use App\Repository\ImageRepository;
use App\Repository\ProductRepository;
use App\Workflow\IImageWorkflow;
use App\Workflow\ImageWorkflow;
use Doctrine\ORM\EntityManagerInterface;
use Survos\LibreTranslateBundle\Service\TranslationClientService;
use Survos\SaisBundle\Model\AccountSetup;
use Survos\SaisBundle\Service\SaisClientService;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Notifier\Message\DesktopMessage;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppController extends AbstractController
{

    const SAIS_CLIENT_CODE='dummy-sais';
    public function __construct(
        private CacheInterface           $cache,
        private SaisClientService        $saisService,
        private UrlGeneratorInterface    $urlGenerator,
        private ImageRepository          $imageRepository,
        private EntityManagerInterface   $entityManager,
        private \Psr\Log\LoggerInterface $logger,
        private TexterInterface          $texter,
        #[Target(ImageWorkflow::WORKFLOW_NAME)] private WorkflowInterface $imageWorkflow,
        private readonly ProductRepository $productRepository,
//        private readonly ImageWorkflow $imageWorkflow,
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

    #[Route('/compress/{limit}', name: 'app_compress_images')]
    public function listFeatured(int $limit=3): Response
    {
        // we only use this to make it easier to debug
        // example of sending multiple images
        $products = $this->getDummyProducts();
        $responses = [];
        // makes sure dummy exists on sais!
        $response = $this->saisService->accountSetup(new AccountSetup(AppController::SAIS_CLIENT_CODE, 500));

        foreach ($products->products as $idx => $product) {
                $payload = new \Survos\SaisBundle\Model\ProcessPayload(
                    AppController::SAIS_CLIENT_CODE,
                    $product->images,
                    ['small'],
                    context: [
                        'productId' => $product->id
                    ],
                    mediaCallbackUrl: $this->urlGenerator->generate('app_media_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
                    thumbCallbackUrl: $this->urlGenerator->generate('app_thumb_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
                );
                $response = $this->saisService->dispatchProcess($payload);
                $responses[] = [
                    'payload' => $payload,
                    'response' => $response,
                ];
            if ($limit && count($responses) >= $limit) {
                break;
            }
        }
        return $this->render('app/index.html.twig', [
            'responses' => $responses,
        ]);
    }

    #[Route('/webhook/media', name: 'app_media_webhook')]
    public function mediaWebhook(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        //$data = $request->query->all();
//        $message = new DesktopMessage(
//            'New subscription! 🎉',
//            json_encode($request->query->all())
//        );
//       try {
//           $this->texter->send($message);
//       } catch (\Exception $e) {
//           $this->addFlash('error', 'Error sending message: ' . $e->getMessage());
//       }
//        return $this->redirectToRoute('app_homepage');
//        return $this->json($request->query->all());


        $imageId = $data['code'] ?? null;

        //check if code is in url via GET
        if (isset($_GET['code'])) {
            $imageId = $_GET['code'];
        }

        if (!$imageId) {
            return new Response('No imageId found ' . json_encode($data), Response::HTTP_BAD_REQUEST);
        }

        $image = $this->imageRepository->findOneBy(['code' => $imageId]);

        if (!$image) {
            return new Response('Image not found' . json_encode($data), Response::HTTP_NOT_FOUND);
        }

        $image->originalSize = $data['size'];

        $this->entityManager->flush();

        // we could also pass back the payload, for debugging.
        return new Response(json_encode(['msg' => 'image updated with original size']));

    }

    #[Route('/webhook/thumb', name: 'app_thumb_webhook')]
    public function thumbWebhook(Request $request): Response
    {
        // @todo: use Symfony Webhook with Sais Payload
        $data = json_decode($request->getContent(), true);
        //
        //log data
        // @todo: set the image resized data with whatever we received.
        $code = $request->query->get('code') ?? null;
        $image = $this->imageRepository->findOneBy(['code' => $code]);
        if (!$image) {
            $errorMsg = sprintf(
                'Image not found for code: %s. Route: %s. Entity: %s. Payload: %s. GET: %s',
                $code,
                $request->attributes->get('_route'),
                \App\Entity\Image::class,
                json_encode($data),
                json_encode($_GET)
            );
            $this->logger->error($errorMsg);
            return new Response($errorMsg, Response::HTTP_NOT_FOUND);
        }

        $image->addThumbData(
            $data['liipCode'],
            $data['url']
        );
        //add thumbs via the media
        $this->entityManager->flush();
        if ($this->imageWorkflow->can($image, IImageWorkflow::TRANSITION_COMPLETE)) {
            $this->imageWorkflow->apply($image, IImageWorkflow::TRANSITION_COMPLETE);
        }
        // we could also pass back the payload, for debugging.
        return new Response(json_encode(['msg' => 'resized updated']));
    }

    private function getDummyProducts()
    {
        return $this->productRepository->findAll();
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

//        if (!$libreTranslate) {
//            $libreTranslate = new LibreTranslate(httpClient: $httpClient);
//            $libreTranslate->setHttpClient($httpClient);
////        $libreTranslate->setTarget($target);
//        }


        $translations = [];
        $x = [];
        $products = $this->getDummyProducts(); // used to be json, now entities ->products;
        foreach ($products as $idx => $product) {
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
            'products' => $products,
            'translations' => $translations,
            'languages' => [], // $libreTranslate->getLanguages()
        ]);
    }
}
