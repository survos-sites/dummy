<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use JoliCode\MediaBundle\Bridge\EasyAdmin\Field\MediaChoiceField;
use Survos\EzBundle\Controller\AbstractEzCrudController;
use Survos\EzBundle\Field\LinkedTextField;
use Survos\MediaBundle\Entity\Photo;
use Survos\MediaBundle\Service\MediaRegistry;
use Survos\MediaBundle\Service\MediaUrlGenerator;

final class ProductCrudController extends AbstractEzCrudController
{
    public function __construct(
        private MediaUrlGenerator $mediaUrlGenerator,
        private MediaRegistry $mediaRegistry,
    )
    {
//        parent::__construct();
    }

    // Your app route that shows a product. Adjust if needed.
    private const SHOW_ROUTE = 'meili_admin_app_product_show';


    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    /**
     * CRUD override (highest precedence):
     * Put the most important fields first, then let EzField drive the rest,
     * then let EasyAdmin fill remaining defaults (deduped).
     */
    protected function preferredFields(string $pageName): iterable
    {
        // resolve to our local media

        // config/image_presets.yaml or a service
        yield TextField::new('sku', 'LargeImg')
            ->onlyOnIndex()
            ->renderAsHtml()
            ->formatValue(function ($value, Product $entity) {
                $thumbUrl = $entity->data['thumbnail'];
                $photo = $this->mediaRegistry->ensureMedia($thumbUrl, Photo::class);
                // if we have it, use it.
                $proxyUrl =
                    $photo->smallUrl ?:  $this->mediaUrlGenerator->resize($thumbUrl, 'large', true, client: 'dummy');
//                if (!$value) return '';
                // @todo: move to sais or core
//                $encoded = rtrim(strtr(base64_encode($thumbUrl), '+/', '-_'), '=');
//                $proxyUrl = "https://images.survos.com/{$prefix}/{$encoded}";
//                return $proxyUrl;
//                dd($proxyUrl);
                return sprintf('<a href="%s" target="_blank"><img src="%s" style="max-height:50px" /></a>', $proxyUrl, $proxyUrl);
            });

        if ($pageName !== Crud::PAGE_INDEX) {
            return [];
        }

        yield LinkedTextField::new('title', 'Title')
            ->setRoute(self::SHOW_ROUTE, 'productId', 'sku');
    }
}
