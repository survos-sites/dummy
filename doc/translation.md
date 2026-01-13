# doc/translation.md

This page is intentionally separate from the main tutorial. The tutorial proves:

- Babel indexing
- TermSets
- STR / STR_TR stubs
- inspection commands

Translation introduces external dependencies and should be added only after the above works.

We use **SurvosTranslatorBundle** here (not Lingua) and show a DeepL setup option.

---

## Option A — dumb demo translator (no APIs)

This is useful for demos and test pipelines: it proves that “something happened” without any external service.

Create a listener that “translates” by modifying text (accent + suffix).

```bash
mkdir -p src/EventListener

cat > src/EventListener/DumbTranslateListener.php <<'PHP'
<?php
declare(strict_types=1);

namespace App\EventListener;

use Survos\BabelBundle\Event\TranslateStringEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final class DumbTranslateListener
{
    public function __invoke(TranslateStringEvent $event): void
    {
        // Only demonstrate for Spanish + French in this tutorial
        if (!in_array($event->targetLocale, ['es', 'fr'], true)) {
            return;
        }

        // If already translated, do nothing
        if (is_string($event->translated) && $event->translated !== '') {
            return;
        }

        $src = (string) $event->source;

        // Very dumb "translation": add a marker and tweak a character
        $tweaked = str_replace(['a','e','i','o','u'], ['á','é','í','ó','ú'], $src);

        $suffix = $event->targetLocale === 'es' ? ' (es)' : ' (fr)';
        $event->translated = $tweaked . $suffix;
    }
}
PHP
```

After adding the listener, run whatever command/workflow you use to populate `str_tr.text`.
If you are using `babel:translate` locally, keep it out of the main tutorial until it is fully stable.

Sanity check:

```bash
bin/console debug:event-dispatcher TranslateStringEvent
```

You should see at least one listener registered.

---

## Option B — DeepL via SurvosTranslatorBundle (recommended “real” setup)

DeepL has a low-friction setup and is fast to validate.

### 1) Configure credentials

Set an environment variable (example):

```bash
export DEEPL_API_KEY="your-key-here"
```

If you want to persist it for local dev, put it in `.env.local`.

### 2) Configure SurvosTranslatorBundle

Add a minimal config file such as:

```bash
mkdir -p config/packages

cat > config/packages/survos_translator.yaml <<'YAML'
survos_translator:
  default_engine: deepl
  engines:
    deepl:
      api_key: '%env(DEEPL_API_KEY)%'
YAML
```

### 3) Verify the translator service works

Use whatever command(s) SurvosTranslatorBundle provides in your stack to do a one-off translation.

If you don’t have a CLI command yet, the quickest verification is:

- ensure your container compiles
- confirm the engine service is autowired
- then use a one-line controller/command to call the engine translate method

This page stays provider-focused; Babel remains provider-agnostic.

---

## Recommended practice (Babel + translator)

- Babel stores **source strings** and **translation stubs**
- Your translation layer fills `str_tr.text`
- Babel resolves translated values at runtime

Keep the workflow strict:

1) Load content → STR rows exist
2) `babel:ensure` → STR_TR stubs exist per locale
3) Translation step → fill missing text (event-driven or translator engine)
4) Validate with:
    - `babel:tr --missing`
    - `babel:stats`

---

## Minimal validation commands

```bash
bin/console babel:tr --missing --limit=10
bin/console babel:stats
```
