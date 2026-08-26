# Smart Job Finder

TYPO3-Extension für Stellenanzeigen. Backend mit TCA, IRRE, Slug und Kategorien. Frontend mit Live-Filter und schema.org-JobPosting. Beim Veröffentlichen feuert ein PSR-14-Event Mock-Mail und optional einen Slack-Webhook.

Kompatibel mit **TYPO3 12.4, 13.4 und 14**.

## Architektur

```mermaid
flowchart LR
  BE[Backend TCA / IRRE] -->|DataHandler datamap live| Hook[JobPublishDataHandlerHook]
  Hook -->|cmdmap snapshot| Snap[LiveRecordSnapshot]
  WS[EXT:workspaces Publish] -->|AfterRecordPublishedEvent| WsL[WorkspaceJobPublishedListener]
  Snap --> WsL
  Hook -->|sichtbar| Event[JobPublishedEvent]
  Hook -->|unsichtbar| Unpub[JobUnpublishedEvent]
  WsL -->|sichtbar| Event
  WsL -->|unsichtbar| Unpub
  Event --> Listener[JobPublishedNotificationListener]
  Event --> Cache[FlushJobCacheListener]
  Unpub --> Cache
  Listener --> Mail[FluidEmail / Mock]
  Listener --> Slack[SlackWebhookNotifier]
  Listener --> Log[Notification-Log im BE-Modul]
  Hook -->|Slug-Wechsel| Redirects[EXT:redirects 301]
  WsL -->|Slug-Wechsel| Redirects
  FE[Plugin JobList] --> Filter[Live Filter AJAX]
  FE --> JsonLd[JobPosting JSON-LD]
  FE --> Seo[PageTitle + Open Graph]
  API[PSR-15 /api/jobs] --> JSON[JSON Feed]
  SEO[EXT:seo] --> Sitemap[Job XML-Sitemap]
```

Der DataHandler hat in TYPO3 12–14 kein generisches „Record published“-PSR-14-Event. Der Hook ist deshalb nur die Brücke für **Live**-Änderungen. Workspace-Entwürfe bleiben still; erst `AfterRecordPublishedEvent` (EXT:workspaces, ab TYPO3 12.2) dispatched `JobPublishedEvent` oder `JobUnpublishedEvent`. Mail und Slack hängen nur am Publish-Event. Cache-Flush hängt an beiden — sonst bleibt eine versteckte Stelle in der gecachten Liste.

## Installation

Composer-Projekt:

```bash
composer require agentur/smart-job-finder
```

Pfad-Repository (dieses Portfolio):

```json
{
  "repositories": [
    { "type": "path", "url": "smart-job-finder" }
  ]
}
```

Danach:

1. Extension im Extension Manager / `typo3 extension:setup` aktivieren
2. Datenbank-Analyse ausführen (Tabellen `tx_smartjobfinder_*`)
3. TypoScript einbinden:
   - **TYPO3 12:** Static Include „Smart Job Finder“
   - **TYPO3 13/14:** Site Set `agentur/smart-job-finder` **oder** dasselbe Static Include
4. Sysordner anlegen, Firmen und Stellen erfassen, Kategorien zuordnen
5. Inhaltselement **Smart Job Finder** auf der Listenseite platzieren, Storage-PID im FlexForm setzen

## Backend

| Thema | Umsetzung |
| --- | --- |
| TCA | Moderne Feldtypen (`slug`, `category`, `datetime`, `link`, `email`, `file`, `inline`) |
| IRRE | Anforderungen und Benefits als sortierbare Inline-Kinder der Stelle |
| Slug | Automatisch aus dem Titel, `uniqueInSite` |
| Kategorien | `sys_category` über TCA `type: category` |
| Firma | Wiederverwendbarer Select (kein IRRE), Logo als FAL |
| Modul | Web → Job Finder: Kennzahlen, Google-Jobs-Score, Notification-Log |
| Preview | Page-Modul zeigt Stellenzahl und Live-Filter-Status |

Eine Stelle gilt als **veröffentlicht**, wenn sie im Live-Workspace neu und FE-sichtbar wird, `hidden` von 1 auf 0 wechselt (Glühbirne), oder wenn ein Workspace-Entwurf nach Live **published** wird (`EXT:workspaces`). Reine Workspace-Saves lösen keine Mail/Slack aus.

Verstecken, Löschen oder ein Workspace-Delete-Placeholder nach Live dispatched `JobUnpublishedEvent` und leert dieselben Cache-Tags. Mail/Slack bleiben still.

## Frontend

- Plugin als eigener **CType** `smartjobfinder_joblist` (kein deprecated `list_type`, damit TYPO3 14 läuft)
- Live-Filter (Suche, Ort, Anstellungsart, Arbeitsmodell) per `fetch`, ohne JavaScript als GET-Formular
- Detailseite mit schema.org **JobPosting** JSON-LD, **PageTitleProvider** und Open-Graph-Meta
- Listenansicht zusätzlich mit **ItemList** JSON-LD
- Cache-Tags `tx_smartjobfinder` / `tx_smartjobfinder_job_{uid}` — Flush bei Publish **und** Unpublish
- Featured-Stellen stehen oben
- `EXT:seo`: Sitemap `jobs` (öffentliche Stellen, ohne abgelaufenes `valid_through`). `storagePid` und `detailPid` im TypoScript setzen

Route Enhancer (in die Site-Config übernehmen): siehe [`Documentation/route-enhancer.yaml`](Documentation/route-enhancer.yaml). Danach z. B. `/jobs/typo3-integrator`.

Slug-Wechsel schreibt bei geladenem `EXT:redirects` einen 301 von `/jobs/alter-slug` → `/jobs/neuer-slug` (Prefix über Extension Configuration).

Interne Bewerbungen sind **nicht** Teil dieser Extension. Dafür gibt es `agentur/smart-job-apply`: ist sie geladen, erscheint das Formular auf der Detailseite. `application_url` bleibt der Link nach draußen.

## JSON-API

`GET /api/jobs` (auch unter `/de/api/jobs`) liefert einen JSON-Feed, **bevor** TYPO3 die Seite auflöst (PSR-15 Middleware).

Ohne gesetztes `apiStoragePid` antwortet die API mit **403** — sie listet nicht alle Jobs der Instanz. Abgelaufene `valid_through`-Stellen erscheinen weder in Liste, Detail (404), API noch Sitemap, auch wenn der Scheduler noch nicht gelaufen ist.

Übersetzungen: die API overlayed wie TYPO3 (Übersetzung gewinnt, Default als Fallback). Site-Language `strict` liefert nur vorhandene Übersetzungen.

Slug-Wechsel schreibt den Redirect **und** baut den Redirect-Cache von `EXT:redirects` neu. Ein reines `INSERT` würde sonst erst nach Cache-Flush greifen.

## PSR-14 Notifications

Einstellungen unter **Admin Tools → Settings → Extension Configuration → smart_job_finder**:

| Option | Default | Bedeutung |
| --- | --- | --- |
| `notificationsEnabled` | an | Listener aktiv |
| `mockMode` | an | Kein echter Versand: Payload im Log + BE-Modul |
| `mailTo` / `mailFrom` | Beispieladressen | Wird nur ohne Mock-Modus genutzt (FluidEmail) |
| `slackWebhookUrl` | leer | Incoming Webhook; nur `https://hooks.slack.com/…`, nur 2xx gilt als Erfolg |
| `jobPathPrefix` | `/jobs` | Prefix für Slug-Redirects |
| `apiStoragePid` | `0` | Pflicht für `/api/jobs` (sonst 403, kein Instanz-Leak) |
| `apiCorsOrigin` | leer | CORS aus; `*` oder eine Origin wie `https://app.example` |
| `apiRateLimit` | `60` | Requests pro IP / Minute auf `/api/jobs` (`0` = aus). Kein WAF. |

Demo ohne Backend-Klick:

```bash
vendor/bin/typo3 smart-job-finder:notify-test
vendor/bin/typo3 smart-job-finder:expire
```

`expire` ist schedulable: versteckt abgelaufene `valid_through`-Stellen, **kündigt Stellen an, deren `starttime` fällig ist** (`JobPublishedEvent`, einmalig via `notified_at`) und flusht den Listen-Cache. Wird `starttime` wieder in die Zukunft gesetzt, geht `notified_at` auf 0 — der Scheduler darf erneut feuern.

Dashboard-Widget (optional `EXT:dashboard`): offene Stellen, Ø Google-Jobs-Score, neue Bewerbungen aus Apply.

Workspace-Publish (`EXT:workspaces`, optional): `JobPublishedEvent` bekommt `source=workspace` und die Workspace-ID. IRRE-Kinder (Requirements/Benefits) lösen das Event nicht aus — nur die Job-Tabelle. Ohne Workspaces startet die Extension unverändert.

Eigene Reaktion auf Veröffentlichung:

```php
use Agentur\SmartJobFinder\Event\JobPublishedEvent;
use Agentur\SmartJobFinder\Event\JobUnpublishedEvent;

public function __invoke(JobPublishedEvent|JobUnpublishedEvent $event): void
{
    // $event->getUid(), getTitle(), getRecord() …
}
```

## TYPO3 12 / 13 / 14

- Plugin-Registrierung als CType; fünfter `configurePlugin`-Parameter nur, wenn die Core-API ihn noch kennt
- Event-Listener per Services.yaml-Tag (nicht nur `#[AsEventListener]`, das reicht erst ab v13)
- Workspace-Publish über `AfterRecordPublishedEvent` (Core ab 12.2); Delete-Placeholder wird zum Unpublish; ohne `EXT:workspaces` startet die Extension trotzdem
- XML-Sitemap nur mit `EXT:seo` (TypoScript-Config ist sonst wirkungslos)
- Dashboard-Widget nur mit `EXT:dashboard` (Services.php registriert es bedingt)
- Cache-Tags: `AddCacheTagEvent` ab v13, sonst `TSFE->addCacheTags()`
- `ext_tables.sql` bleibt für v12 die Schema-Quelle; v13/14 generieren ergänzend aus TCA
- Site Set für v13/14, Static TypoScript für v12
- PHP 8.1+, keine v14-only-APIs

## Performance

Die Standard-Liste (`list`) ist **page-cacheable**. Live-Filter trifft die uncached `filter`-Action. Beim Publish werden Cache-Tags und der eigene `smart_job_finder`-Cache geleert.

Weitere Entscheidungen:

| Thema | Umsetzung |
| --- | --- |
| Pagination | `itemsPerPage` (Default 12), kein unbegrenztes `findAll` |
| N+1 | Firmennamen per einem JOIN, Kategorien nicht in der Liste |
| Dropdown-Orte | `SELECT DISTINCT`, keine hydrierten Models |
| Suche | immer `LIKE`, zusätzlich MySQL `FULLTEXT` in BOOLEAN MODE; 0-Treffer fällt nicht mehr ins Leere. Tippfehler im Titel (z. B. Develoer / developer) über Levenshtein |
| `/api/jobs` | getaggter Cache 60s, Limit 100, Language-Overlay + Enable Fields |
| Notification-Log | `expire` löscht Einträge älter als 90 Tage |

`FULLTEXT` in `ext_tables.sql` ist MySQL/MariaDB. PostgreSQL: Index weglassen, der Code fällt auf LIKE zurück.

## Tests

```bash
composer test
composer phpstan
```

Oder ohne Composer-Vendor der Extension (CI macht das so):

```bash
phpunit -c phpunit.xml.dist
phpstan analyse -c phpstan.neon --memory-limit=512M
```

Die Unit-Tests für Event, Overlay, Visibility, Slack-URL und Google-Jobs-Score brauchen kein vollständiges TYPO3. Der Slack-Payload-Test wird übersprungen, wenn Core-Klassen fehlen. GitHub Actions läuft PHP 8.1 und 8.3.
