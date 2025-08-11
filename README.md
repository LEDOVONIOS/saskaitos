# Kieno numeris? – LT mobiliojo numerio tikrinimas

Lengvas PHP 8.x + MySQL projektas, kuris rodo Lietuvos mobiliuosius numerius (+3706/06), skaičiuoja peržiūras, leidžia pridėti komentarus (po patvirtinimo), turi paiešką, kontaktų formą ir paprastą administravimą.

## Diegimas (Hostinger)
1. Sukurkite duomenų bazę (MySQL/MariaDB) ir vartotoją.
2. Įkelkite visą projekto turinį į hostingą (pvz., `public` katalogą nukreipkite į domeno dokumentų šaknį arba nustatykite „Public HTML“ į `public`).
3. Redaguokite `config/config.php` su DB prisijungimo duomenimis ir svetainei tinkamu `base_url`.
4. Paleiskite migracijas:
   ```bash
   php scripts/migrate.php
   ```
5. Užsėkite administratorių:
   ```bash
   php scripts/seed_admin.php you@example.com StiprusSlaptazodis
   ```
6. Apsilankykite `/admin/login` ir prisijunkite.

## Funkcijos
- Normalizuoja +3706 ir 06 formatus, kanoninis URL: `/{e164}/` (pvz., `/37062173976/`).
- Peradresuoja į kanoninį URL iš bet kurios įvesties.
- Skaičiuoja peržiūras, saugo paskutinio tikrinimo laiką.
- Komentarai (pending kol patvirtins adminas).
- Paieška, numerių sąrašas, kontaktų forma (el. laiškas į `admin_email`).
- Adminas: moderavimas, kontaktų sąrašas, skydelis, numerio duomenų trynimas.
- SEO meta žymės, `sitemap.xml`, `robots.txt`, JSON API `/api/number/{e164}`.

## Saugumas
- CSRF, XSS escapinimas, supaprastintas rate limitas per IP.
- Slaptažodžiai su `password_hash()`.

## Pastabos
- `security.enable_recaptcha` galite palikti `false`. Veikia paprastas tokenas per `security.simple_token`.
- Logai rašomi į `app/storage/logs/`. 
