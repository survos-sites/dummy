# Dummy SAIS

Use json dummy data to test sais-bundle and sais image server.

```bash
bin/console d:sch:update --force
bin/console d:fix:load -n
bin/console workflow:iterate App\\Entity\\Image  --marking=new --transition=dispatch -vvv
```

Someday translationes, but removed on April 12, 2025.

