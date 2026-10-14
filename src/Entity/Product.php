<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\BabelBundle\Attribute\BabelLocale;
use Survos\BabelBundle\Attribute\BabelStorage;
use Survos\BabelBundle\Attribute\BabelTerm;
use Survos\BabelBundle\Contract\BabelHooksInterface;
use Survos\FieldBundle\Attribute\RouteIdentity;
use Survos\FieldBundle\Entity\RouteParametersInterface;
use Survos\FieldBundle\Entity\RouteIdentityTrait;
use Survos\EzBundle\Attribute\Page;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Survos\EzBundle\Attribute\EzAdmin;
use Survos\EzBundle\Attribute\EzField;


use Survos\BabelBundle\Entity\Traits\BabelHooksTrait;

use Doctrine\ORM\Mapping\Column;

use Survos\BabelBundle\Attribute\Translatable;
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: [
                'groups' => ['product.read', 'product.details'],
            ]
        ),
        new GetCollection(
            normalizationContext: [
                'groups' => ['product.read'],
            ]
        )],
    normalizationContext: ['groups' => ['product.read', 'product.details','rp']],
)]
#[ApiFilter(OrderFilter::class, properties: ['price','stock','rating'])]

// @todo: sort/search on translatable properties
//    #[ApiFilter(SearchFilter::class, properties: ['title'=>'partial'])]
//#[ApiFilter(MultiFieldSearchFilter::class, properties: ['title', 'description'])]

#[ApiFilter(RangeFilter::class, properties: ['rating','stock', 'price'])]
#[BabelStorage()]
#[BabelLocale(locale: 'en', targetLocales: ['es'])]
#[EzAdmin(
    label: 'Product',
    icon: 'tabler:shopping-cart',
    indexMax: 12,
    editRoles: ['ROLE_ADMIN', 'ROLE_EDITOR'],
    readOnlyByDefault: true,
)]
#[RouteIdentity(field: 'sku', key: 'productId')]
class Product implements RouteParametersInterface, BabelHooksInterface
{
    use BabelHooksTrait;

    use RouteIdentityTrait;
    public function __construct(
        #[ORM\Column(type: Types::STRING, length: 255)]
        #[ORM\Id]
        #[Groups(['product.read'])]
        #[EzField(order: 900, label: 'SKU', showOn: [Page::DETAIL])]
        public ?string $sku,


        #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
//        #[Groups(['product.details'])]
        private(set) array $data
    )
    {
        $this->images = new ArrayCollection();
        $this->stock = $this->data['stock']??0;
        $this->rating = (int) round($this->data['rating']??0);
    }
    public string $id { get => $this->sku; }

    public function getId(): string
    {
        return $this->sku;
    }

    #[Groups(['product.read'])]
    public int $imageCount { get => $this->images->count(); }


// App\Entity\Product.php — replace just the Category + Tags section with this (and add the use statement)
// add: use Survos\BabelBundle\Attribute\BabelTerm;

    // TERM-BACKED: Category (stored as term code)
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private(set) ?string $categoryCode = null;

    #[BabelTerm(set: 'category')]
    #[Groups(['product.read'])]
    #[ApiProperty('Category term code (TermSet=category)')]
    #[EzField(index: true, order: 40, filter: true, label: 'Category')]
    public ?string $category {
        get => $this->categoryCode;
        set => $this->categoryCode = $value;
    }

    // TERM-BACKED: Tags (stored as array of term codes)
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private(set) array $tagCodes = [];

    #[BabelTerm(set: 'tag', multiple: true)]
    #[Groups(['product.read'])]
    #[ApiProperty('Tag term codes (TermSet=tag)')]
    public array $tags {
        get => $this->tagCodes;
        set => $this->tagCodes = $value;
    }

    #[Groups(['product.read'])]
    #[ApiProperty('Localized category label (resolved via Babel term hydration).')]
    public ?string $categoryLabel {
        get => $this->getResolvedTerm('category') ?? null;
    }

    #[Groups(['product.read'])]
    #[ApiProperty('Localized tag labels (resolved via Babel term hydration).')]
    public array $tagLabels {
        get => $this->getResolvedTerm('tags') ?? [];
    }


    #[Groups(['product.read'])]
    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[ApiProperty("the registered brand name")]
    #[EzField(index: true, order: 30, filter: true, label: 'Brand')]
    public ?string $brand;

    #[Groups(['product.read'])]
    #[ApiProperty("thumbnail from data (not sais)")]
    public ?string $thumbnail {
        get => $this->data['thumbnail']??null;
    }


    #[Groups(['product.read'])]
    #[ApiProperty("virtual price")]
    public ?float $price {
        get => $this->data['price']??null;
    }

    #[Groups(['product.read'])]
    #[ApiProperty("rounded rating, for range slider")]
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Range(
        min: 0,
        max: 5
    )]
    public int $rating;

    #[Groups(['product.read'])]
    #[ApiProperty("rounded rating, for range slider")]
    #[ORM\Column(type: Types::INTEGER)]
    public int $stock;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'product', orphanRemoval: true, cascade: ['persist'])]
    private(set) Collection $images;

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
    }

        // <BABEL:TRANSLATABLE:START title>
        #[Column(type: Types::TEXT, nullable: true)]
        private(set) ?string $titleBacking = null;

        #[Translatable(context: NULL)]
        #[Groups(['product.read', 'product.searchable'])]
        #[EzField(
            index: true,
            order: 10,
            label: 'Title',
//            linkRoute: 'admin_app_product_show',
//            linkParam: 'productId',
//            linkProperty: 'sku',
        )]
        public ?string $title {
            get => $this->resolveTranslatable('title', $this->titleBacking, NULL);
            set => $this->titleBacking = $value;
        }
        // <BABEL:TRANSLATABLE:END title>

        // <BABEL:TRANSLATABLE:START description>
        #[Column(type: Types::TEXT, nullable: true)]
        private ?string $descriptionBacking = null;

        #[Translatable(context: NULL)]
        #[Groups(['product.read', 'product.searchable'])]
        public ?string $description {
            get => $this->resolveTranslatable('description', $this->descriptionBacking, NULL);
            set => $this->descriptionBacking = $value;
        }
        public ?string $snippet { get => mb_substr($this->description, 0, 40); }
        // <BABEL:TRANSLATABLE:END description>
}
