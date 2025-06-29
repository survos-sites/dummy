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
use Survos\CoreBundle\Entity\RouteParametersInterface;
use Survos\CoreBundle\Entity\RouteParametersTrait;
use Survos\MeiliAdminBundle\Api\Filter\FacetsFieldSearchFilter;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

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

#[ApiFilter(OrderFilter::class, properties: ['title','price','stock','rating'])]

#[ApiFilter(SearchFilter::class, properties: ['title'=>'partial'])]
//#[ApiFilter(MultiFieldSearchFilter::class, properties: ['title', 'description'])]

#[ApiFilter(FacetsFieldSearchFilter::class,
    properties: ['category', 'tags', 'rating', 'stock', 'price'],
    arguments: [ "searchParameterName" => "facet_filter"]
)]
#[ApiFilter(RangeFilter::class, properties: ['rating','stock', 'price'])]

class Product implements RouteParametersInterface
{
    use RouteParametersTrait;
    public const UNIQUE_PARAMETERS = ['productId'=>'sku'];
    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        #[ORM\Id]
        #[Groups(['product.read'])]
        public ?string $sku,
        #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
        #[Groups(['product.details'])]
        private(set) object|array $data {
            set(object|array $data) => $this->data = (object)$data;
        }
    )
    {
        $this->images = new ArrayCollection();
        $this->stock = $this->data->stock??0;
        $this->rating = round($this->data->rating??0);
    }

    #[ORM\Column(length: 255)]
    public ?string $name = null;

    // virtual property
    #[Groups(['product.read'])]
    #[ApiProperty("category from extra, virtual but needs index")]
    public ?string $category {
        get => $this->data['category']??null;
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

    #[Groups(['product.read'])]
    #[ApiProperty("array of tags")]
    public array $tags {
        get => $this->data['tags']??[];
    }

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $images {
        get {
            return $this->images;
        }
    }

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
            // set the owning side to null (unless already changed)
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
    }

}
