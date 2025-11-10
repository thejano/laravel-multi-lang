---
title: Automation & CLI
---

# Automation & CLI

Laravel Multi-Lang includes first-party Artisan commands that make it easy to audit, export, and import translations. Combine them with scheduled tasks, CI pipelines, or external translation services.

---

## `multi-lang:audit`

```bash
php artisan multi-lang:audit "App\Models\Post" \
  --locales=ckb,ar \
  --fields=title,content \
  --chunk=100 \
  --detailed
```

**Options**

- `--locales=` Overrides the locales to inspect (defaults to `config('app.supported_locales')` or `[app.locale, fallback_locale]`).
- `--fields=` Restrict the audit to specific translatable fields.
- `--chunk=` Adjusts chunk size (defaults to 100).
- `--detailed` Outputs each missing record (model ID, locale, field).

**Use cases**

- Failing CI builds when new models ship without translations.
- Scheduled dashboards or Slack alerts summarising localisation coverage.

---

## `multi-lang:export`

```bash
php artisan multi-lang:export "App\Models\Post" \
  --locales=ckb,ar \
  --path=storage/app/posts.json \
  --missing \
  --chunk=200
```

**Options**

- `--ids=` Limit the export to specific model IDs.
- `--locales=` Decide which locales to include.
- `--chunk=` Process records in batches (default 100).
- `--missing` Export only the fields/locales that are currently empty—source text is included as a placeholder.
- `--path=` Destination file (JSON).

**Example scheduled snapshot**

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command(
        'multi-lang:export "App\Models\Post" --locales=ckb,ar --path=storage/app/exports/posts.json'
    )->weeklyOn(7, '23:00');
}
```

Upload the JSON to S3 or your translation vendor and you now have a weekly archive of content.

---

## `multi-lang:import`

```bash
php artisan multi-lang:import "App\Models\Post" \
  --path=storage/app/posts.json \
  --strategy=merge \
  --only-missing \
  --chunk=200
```

**Options**

- `--strategy=merge` Update provided locales/fields without deleting others (default).
- `--strategy=replace` Sync locales/fields and remove translations not present in the payload.
- `--only-missing` Apply translations only where values are currently empty (useful when merging human-vetted content).
- `--chunk=` Control memory usage for large imports.

**JSON schema**

```json
{
  "123": {
    "ckb": {
      "title": "ناونیشان",
      "content": "ناوەڕۆک"
    },
    "ar": {
      "title": "عنوان"
    }
  }
}
```

---

## Tips & patterns

- **Translation vendor loop**: export with `--missing`, send to translators, re-import with `--only-missing` to avoid overwriting reviewed content.
- **CI validation**: run `multi-lang:audit` after migrations or seeders to block deployments that introduce unlocalised fields.
- **Queue friendly**: wrap export/import commands in queued jobs when handling millions of rows—each command honours the `--chunk` setting.
- **Notifications**: dispatch events or notifications after imports to alert product teams that new UI copy is live.

Continue to [Extensibility & Integrations](/guide/extensibility.html) to see how you can plug these workflows into custom cache stores, middleware, and observers.

