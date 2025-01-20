<?php

namespace App\Controller;

use Jefs42\LibreTranslate;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppController extends AbstractController
{
    #[Route('/{target}', name: 'app_homepage')]
    public function home(
//        LibreTranslate $libreTranslate,
        CacheInterface $cache,
        HttpClientInterface $httpClient,
        string         $target = 'es'): Response
    {

        $libreTranslate = new LibreTranslate(httpClient: $httpClient);
        $libreTranslate->setHttpClient($httpClient);
//        $libreTranslate->setTarget($target);

        $url = 'https://dummyjson.com/products';
//        dd($url);
        $data = $cache->get(md5($url), fn(CacheItem $item) => json_decode(file_get_contents($url)));

        $translations = [];
        $x = [];
        foreach ($data->products as $idx => $product) {
            $x[] = $product->title;
            $z[] = $libreTranslate->translate($product->title, target: $target);
            $translations[] = $cache->get(md5($product->title).$target,
                fn(CacheItem $cacheItem) =>
                    $libreTranslate->translate($product->title, target: $target)
            );
            if ($idx > 0) break;
        }
        // argh.  Proof that bulk translations don't work well.
        $xx = [
            "My name is Robert",
            "Where is the bathroom?",
            "Eyeshadow Palette with Mirror"
        ];
        $x = array_merge($x, $xx);
        foreach ($xx as $xxx) {
            $individual[$xxx] = $libreTranslate->translate($xxx, target: $target);
        }
        $bulk= $libreTranslate->translate($xx, 'en', target: $target);
        dd(original: $xx, bulk: $bulk, individual: $individual);


//        dd($translations);
        return $this->render('app/index.html.twig', [
            'products' => $data->products,
            'translations' => $translations,
            'languages' => $libreTranslate->getLanguages()
        ]);
    }
}
