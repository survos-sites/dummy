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
use Survos\CoreBundle\Controller\BaseCrudController;

class ProductCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {

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
}
