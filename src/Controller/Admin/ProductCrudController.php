<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use JoliCode\MediaBundle\Bridge\EasyAdmin\Field\MediaChoiceField;
use Survos\EzBundle\Controller\BaseCrudController;

class ProductCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {

        yield TextField::new('sku')
            ->setHelp(sprintf(
                'Request locale=%s, default=%s',
                $this->getContext()?->getRequest()?->getLocale() ?? 'n/a',
                $this->getContext()?->getRequest()?->getDefaultLocale() ?? 'n/a',
            ));


        yield MediaChoiceField::new('thumb')
            ->setRequired(false)
            ->setHelp('Thumbnail image')
            ->setFolder('thumbs')
        ;

        // Visual priority order - most important first
//        yield AvatarField::new('thumbnailUrl')->setHeight(36);  // Visual thumbnail first

//        dd($this->generateUrl('admin_app_product_show', ['productId' => 1]));
        yield TextField::new('snippet')->onlyOnIndex();
        yield IntegerField::new('rating');

        yield TextField::new('title');
        yield TextField::new('title')
            ->formatValue(function ($value, Product $entity) {
                return sprintf(
                    '<a href="%s">%s</a>',
                    $this->generateUrl('admin_app_product_show', ['productId' => $entity->sku]),
                    $value
                );
            });

        yield TextField::new('marking');                     // Status/workflow state


    }


    public function SemiAutomaticConfigureFields(string $pageName): iterable
    {
        $thumb = MediaChoiceField::new('thumb')
            ->setRequired(false)
            ->setHelp('Attach an image to this post')
            ->setFolder('articles');
        yield $thumb;
        foreach (parent::configureFields($pageName) as $field) {
            $dto = $field->getAsDto();
            $field = match ($dto->getProperty()) {
                'thumb' => null,
                default => $field,
            };
            if ($field) {
                yield $field;
            }
        }
    }


}
