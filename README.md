# Dummy — Babel + TermSets + Products (copy/paste tutorial)

This project is a minimal, working tutorial for:

- SurvosBabelBundle
- PHP 8.4 property-hook translations
- TermSets (category + tags)
- inspecting state using `babel:*` commands

Translation is intentionally separated into `doc/translation.md` and uses **SurvosTranslatorBundle** for the example (not Lingua).

---

## 0) Prerequisites

- PHP 8.4+
- Symfony CLI
- `wget`
- SQLite or PostgreSQL

---

## 1) Create the project

```bash
symfony new dummy --webapp
cd dummy
```

---

## 2) Install bundles

Babel:

```bash
composer require survos/babel-bundle
```

Translation example bundle (used in `doc/translation.md`):

```bash
composer require survos/translator-bundle
```

---

## 3) Configure locales (English source, Spanish target)

Create `config/packages/translation.yaml`:

```bash
mkdir -p config/packages

cat > config/packages/translation.yaml <<'YAML'
framework:
  default_locale: en
  translator:
    enabled_locales: ['en', 'es']
YAML
```

---

## 4) Create the Product entity (with Repository)

Create the entity skeleton (repository class included):

```bash
bin/console make:entity Product -n
```

Now replace `src/Entity/Product.php` with a canonical Babel + TermSet example:

```bash
cat > src/Entity/Product.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;
use Survos\BabelBundle\Attribute\BabelStorage;
use Survos\BabelBundle\Attribute\Translatable;
use Survos\BabelBundle\Contract\BabelHooksInterface;
use Survos\BabelBundle\Entity\Traits\BabelHooksTrait;
use Survos\BabelBundle\Attribute\BabelTermSet;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[BabelStorage]
class Product implements BabelHooksInterface
{
    use BabelHooksTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(unique: true)]
    public string $sku;

    #[ORM\Column(nullable: true)]
    public ?string $titleBacking = null;

    #[Translatable]
    public ?string $title {
        get => $this->resolveTranslatable('title', $this->titleBacking);
        set => $this->titleBacking = $value;
    }

    #[ORM\Column(nullable: true)]
    public ?string $descriptionBacking = null;

    #[Translatable]
    public ?string $description {
        get => $this->resolveTranslatable('description', $this->descriptionBacking);
        set => $this->descriptionBacking = $value;
    }

    // TERMSETS: store codes; labels are translated via STR/STR_TR using contexts like term:<set>:<code>:label
    #[BabelTermSet('category')]
    public ?string $category = null;

    /** @var list<string> */
    #[BabelTermSet('tag')]
    public array $tags = [];

    public function __construct(string $sku)
    {
        $this->sku = $sku;
    }
}
PHP
```

Update schema:

```bash
bin/console doctrine:schema:update --force
```

---

## 5) Download sample data once (local file)

```bash
mkdir -p data
wget -O data/products.json https://dummyjson.com/products
```

From now on, the loader reads locally.

---

## 6) Add a trivial loader command

Create `src/Command/LoadProductsCommand.php`:

```bash
mkdir -p src/Command

cat > src/Command/LoadProductsCommand.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Survos\BabelBundle\Service\TermRegistry;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:load', 'Load products from a local JSON file')]
final class LoadProductsCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProductRepository $products,
        private readonly TermRegistry $termRegistry,
    ) {}

    public function __invoke(
        SymfonyStyle $io,

        #[Argument('Local JSON file path')]
        string $file = 'data/products.json',

        #[Option('Purge existing products before loading')]
        bool $purge = false,

        #[Option('Max records to import')]
        ?int $limit = null,
    ): int {
        if ($purge) {
            $this->em->createQuery('DELETE FROM App\Entity\Product p')->execute();
        }

        $json = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        $rows = $json['products'] ?? [];

        // Keep this explicit in the tutorial: it makes the system deterministic.
        // Once you're confident TermSet discovery is fully automatic, you can remove these.
        $this->termRegistry->ensureTermSet('category', 'Category');
        $this->termRegistry->ensureTermSet('tag', 'Tag');

        $count = 0;

        foreach ($rows as $i => $row) {
            $sku = (string) ($row['sku'] ?? '');
            if ($sku === '') {
                continue;
            }

            $product = $this->products->findOneBy(['sku' => $sku]) ?? new Product($sku);

            $product->title = $row['title'] ?? null;
            $product->description = $row['description'] ?? null;

            $category = (string) ($row['category'] ?? '');
            if ($category !== '') {
                $product->category = $category;
                $this->termRegistry->ensureTerm('category', $category, $category);
            }

            $tags = [];
            foreach (($row['tags'] ?? []) as $tag) {
                $tag = (string) $tag;
                if ($tag === '') {
                    continue;
                }
                $tags[] = $tag;
                $this->termRegistry->ensureTerm('tag', $tag, $tag);
            }
            $product->tags = $tags;

            $this->em->persist($product);
            $count++;

            if ($limit && $count >= $limit) {
                break;
            }
        }

        $this->em->flush();

        $io->success(sprintf('Loaded %d products from %s', $count, $file));
        return Command::SUCCESS;
    }
}
PHP
```

Run the loader:

```bash
bin/console app:load --purge --limit=20
```

---

## 7) Inspect Babel state (this is the learning loop)

### Overall status

```bash
bin/console babel:debug
```

### Flags (recommended)

```bash
bin/console babel:debug --carriers
bin/console babel:debug --scan
bin/console babel:debug --index
```

---

## 8) Preview vs browse (both are important)

Entity-only (property hooks, no terms):

```bash
bin/console babel:preview App\\Entity\\Product --limit=2
```

Entity + termsets (category/tags):

```bash
bin/console babel:browse App\\Entity\\Product --limit=2
```

---

## 9) Inspect TermSets directly

```bash
bin/console babel:termset
bin/console babel:termset category --limit=3
```

Inspect underlying STR rows for term labels:

```bash
bin/console babel:str --context=term:category --limit=3
```

---

## 10) Inspect normalized storage

```bash
bin/console babel:str --limit=3
bin/console babel:tr --limit=3
```

Translation coverage (Spanish):

```bash
bin/console babel:stats
```

At this stage, you should see STR rows and STR_TR stubs for `es`, but no text yet.

---

## 11) Add French at the end, ensure stubs

Add `fr` to enabled locales:

```bash
cat > config/packages/translation.yaml <<'YAML'
framework:
  default_locale: en
  translator:
    enabled_locales: ['en', 'es', 'fr']
YAML
```

Clear cache:

```bash
bin/console cache:clear
```

Ensure translation stub rows exist for French:

```bash
bin/console babel:ensure --locale=fr
```

Verify:

```bash
bin/console babel:stats
```

You should now see coverage rows for both `es` and `fr`.

---

## 12) Translation (separate page)

See `doc/translation.md` for:
- a “dumb translator” listener (for demos)
- SurvosTranslatorBundle + DeepL setup
- how to translate STR_TR stubs safely
