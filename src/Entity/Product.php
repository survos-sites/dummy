<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ApiResource]
class Product
{
    public function __construct(
        #[ORM\Column(type: 'string', length: 255)]
        #[ORM\Id]
        #[Groups(['product.read'])]
        public ?string $sku,

        #[ORM\Column(nullable: true)]
        #[Groups(['product.details'])]
        private(set) ?array $extra = null, // catch all for everything, could be excluded from API in the listing

        #[Groups(['product.read'])]
        #[ApiProperty("category from extra, virtual but needs index")]
        public ?string $category=null {
            get => $this->extra['category']??null;
        }

    )
    {
    }

    #[ORM\Column(length: 255)]
    public ?string $name = null;

}
