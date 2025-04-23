# Dummy SAIS

Use json dummy data to test sais-bundle and sais image server.

Steps:

* Load the dummy data via fixtures
* iterate through the images marked as 'new' and dispatch a request to sais
* sais calls a webhook when the image has been resize
* the webhook update the Image record with the resized data and applies a transition

```bash
rm var/data.db -f && bin/console d:sch:update --force
bin/console d:fix:load -n
bin/console workflow:iterate App\\Entity\\Image  --marking=new --transition=dispatch -vvv
symfony open:local --path=/images
```

Someday translations, but removed on April 12, 2025.

