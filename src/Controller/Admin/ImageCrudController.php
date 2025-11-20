<?php

namespace App\Controller\Admin;

use App\Entity\Image;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use JoliCode\MediaBundle\Bridge\EasyAdmin\Field\MediaChoiceField;
use Survos\EzBundle\Controller\BaseCrudController;

class ImageCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return Image::class;
    }

    public function configureFields(string $pageName): iterable
    {

        yield MediaChoiceField::new('image')
            ->setRequired(false)
            ->setHelp('Thumbnail image')
            ->setFolder('original')
        ;

        yield IdField::new('code', 'hash');
        yield UrlField::new('originalUrl');
        yield TextField::new('marking');
    }
}
