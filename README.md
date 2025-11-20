# Laravel Universal Scraper

[![Tests Status Badge](https://github.com/balintpethe/laravel-universal-scraper/actions/workflows/run-tests.yml/badge.svg)](https://github.com/balintpethe/laravel-universal-scraper/actions/workflows/run-tests.yml)
[![PHPStan Status Badge](https://github.com/balintpethe/laravel-universal-scraper/actions/workflows/phpstan.yml.yml/badge.svg)](https://github.com/balintpethe/laravel-universal-scraper/actions/workflows/phpstan.yml)

Egy könnyen bővíthető, konfigurálható web scraping toolkit Laravel projektekhez. Célja, hogy deklaratív módon — profile-okon és CSS selectorokon keresztül — tudd leírni, hogyan kell egy webshop vagy tetszőleges oldal HTML-jéből strukturált adatot kinyerni.

## Fő funkciók

- Laravel-ready Composer package (auto-discovery)
- HTTP hívások Laravel `Http` kliensen keresztül (timeout, retry, default header-ek)
- `ConfigProfile`: scraper profile deklarálása config-ból
- List scraping deklaratív `fields` definícióval
- Alap típuskonverzió (ár → int/float, stb.)
- Kiterjeszthető `ScraperProfile` interfész egyedi logikához
- Facade: `UniversalScraper`

---

## Követelmények

- PHP: 8.1+
- Laravel: 10.x vagy 11.x
- Composer

---

## Telepítés

1. Telepítés composer-rel:

```bash
composer require balintpethe/laravel-universal-scraper
```

2. (Opció) Ha szükséges, publikáld a config fájlt a host alkalmazatba:

```bash
php artisan vendor:publish --provider="BalintPethe\\LaravelUniversalScraper\\LaravelUniversalScraperServiceProvider" --tag=config
```

A publikálás után a `config/universal-scraper.php` fájlban tudod a profilingokat és globális beállításokat szerkeszteni.

---

## Gyors használat

A csomag egy `UniversalScraper` facade-ot biztosít a legegyszerűbb használathoz. A leggyakoribb forgatókönyv: van egy profil (például `products`), amely leírja, hogyan találjuk meg a terméklistát és a mezőket.

Példa Controller-ből:

```php
use App\Http\Controllers\Controller;
use UniversalScraper; // facade

class ScrapeController extends Controller
{
    public function show()
    {
        $url = 'https://example.com/category?page=1';

        // A profil neve, vagy egy ConfigProfile objektum
        $result = UniversalScraper::scrape('example_product_profile', $url);

        // $result tömb formában tér vissza
        return response()->json($result);
    }
}
```

Alternatív mód: a szolgáltatás használata service container-en keresztül:

```php
$scraper = app()->make(\BalintPethe\UniversalScraper\ScraperManager::class);
$result = $scraper->scrape('example_product_profile', $url);
```

---

## Konfigurációs profil (példa)

A `config/universal-scraper.php` tipikusan tartalmaz egy `profiles` tömböt. Egy egyszerű példa profil (
figurális, tetszőleges mezőkkel):

```php
return [
    'default' => [
        'timeout' => 10,
        'retry' => 1,
    ],

    'profiles' => [
        'example_product_profile' => [
            'type' => 'list',
            'list_selector' => '.product-list .product-item',
            'fields' => [
                'title' => ['selector' => '.title', 'type' => 'string'],
                'price' => ['selector' => '.price', 'type' => 'money'],
                'url' => ['selector' => '.title a', 'attr' => 'href', 'type' => 'string'],
                'in_stock' => ['selector' => '.stock', 'type' => 'bool']
            ],
            // opcionális: egyedi post-processing class vagy closure
            //'post_processor' => MyCustomProcessor::class,
        ],
    ],
];
```

A fenti profil azt írja le, hogy a scraping egy listát ad vissza, minden elemre alkalmazzuk a `fields`-et.

---

## Részletes profil példák

Az alábbi példák bemutatják a gyakori profil-típusokat: terméklista, termék oldal (item) és paginációs profil.

1) Terméklista (list) - egyszerű webshop lista

```php
'profiles' => [
    'products_list' => [
        'type' => 'list',
        'list_selector' => '.products .product',
        'fields' => [
            'id' => ['selector' => '.product-id', 'type' => 'string'],
            'title' => ['selector' => '.title', 'type' => 'string'],
            'price' => ['selector' => '.price', 'type' => 'money'],
            'image' => ['selector' => '.thumb img', 'attr' => 'src', 'type' => 'string'],
            'url' => ['selector' => '.title a', 'attr' => 'href', 'type' => 'string'],
        ],
    ],
],
```

2) Termék oldal (item) - részletes adat egy termékoldalról

```php
'profiles' => [
    'product_item' => [
        'type' => 'item',
        'fields' => [
            'title' => ['selector' => 'h1.product-title', 'type' => 'string'],
            'price' => ['selector' => '.product-price .amount', 'type' => 'money'],
            'description' => ['selector' => '.description', 'type' => 'string'],
            'images' => ['selector' => '.gallery img', 'attr' => 'src', 'multiple' => true, 'type' => 'string'],
            'availability' => ['selector' => '.stock-status', 'type' => 'string'],
        ],
    ],
],
```

Megjegyzés: a `multiple: true` jelzi, hogy egy selector több találatot adhat vissza, és a mező értéke tömb lesz.

3) Paginalás - következő oldal kinyerése és összefűzés

```php
'profiles' => [
    'products_paginated' => [
        'type' => 'list',
        'list_selector' => '.products .product',
        'fields' => [
            'title' => ['selector' => '.title', 'type' => 'string'],
            'price' => ['selector' => '.price', 'type' => 'money'],
            'url' => ['selector' => '.title a', 'attr' => 'href', 'type' => 'string'],
        ],
        // A csomag alapból nem futtat automatikus paginációt, de megadhatsz
        // next_page_selector-t és egy post_processor-t, amely iterál az oldalak felett.
        'next_page_selector' => '.pagination .next a',
        'post_processor' => App\Scrapers\Processors\PaginatedCollector::class,
    ],
],
```

---

## Példa: `post_processor` osztály és regisztráció

A `post_processor` egy tetszőleges osztály vagy closure lehet, amely a scraping eredményein fut le, és módosítja vagy összegzi azokat. A csomag a profilban megadott `post_processor` értékét meghívja (ha osztály, akkor példányosítja és hív egy `process(array $items, array $meta = []): array` metódust), vagy ha closure, akkor meghívja a closure-t.

Példa egyszerű osztályra, amely abszolutizálja a relatív URL-eket és normalizálja az árakat:

```php
namespace App\Scrapers\Processors;

class ProductPostProcessor
{
    protected string $baseUrl;

    public function __construct(string $baseUrl = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * @param array $items
     * @param array $meta opcionális meta adatok (pl. current_url)
     * @return array
     */
    public function process(array $items, array $meta = []): array
    {
        $currentUrl = $meta['current_url'] ?? 'http://example.com';

        foreach ($items as &$item) {
            // absolutize url mező, ha relatív
            if (!empty($item['url']) && str_starts_with($item['url'], '/')) {
                $item['url'] = $this->baseUrl . $item['url'];
            }

            // price például centes formára konvertálás (ha string)
            if (isset($item['price']) && is_string($item['price'])) {
                // egyszerű példa: 49.99 -> 4999
                $normalized = preg_replace('/[^0-9.,]/', '', $item['price']);
                $normalized = str_replace(',', '.', $normalized);
                $item['price'] = (int) round((float) $normalized * 100);
            }
        }

        return $items;
    }
}
```

Regisztráció a profilban (config):

```php
'products_list' => [
    // ... egyéb beállítások ...
    'post_processor' => App\Scrapers\Processors\ProductPostProcessor::class,
],
```

Closure példa közvetlen használatra (ha a csomag támogatja):

```php
'post_processor' => function(array $items, array $meta = []) {
    // módosítsd $items-et és térj vissza vele
    return array_map(function($it) use ($meta) {
        // például hozzáadjuk az aktuális URL-t
        $it['scraped_from'] = $meta['current_url'] ?? null;
        return $it;
    }, $items);
},
```

---

## Kimenet / Mit várhatsz vissza

A `scrape()` metódus általában egy asszociatív tömböt (array) ad vissza. Két tipikus struktúra:

- list típusú profil: tömb, amelyben minden elem egy asszociatív tömb (rekord), pl. tömb termékekről
- item/objektum típus: egyetlen asszociatív tömb a lekért objektumról

Példa list kimenetre (PHP tömb / JSON):

```json
[
  {
    "title": "Awesome Sneakers",
    "price": 4999,
    "url": "https://example.com/products/1",
    "in_stock": true
  },
  {
    "title": "Running Shoes",
    "price": 6999,
    "url": "https://example.com/products/2",
    "in_stock": false
  }
]
```

Megjegyzések a kimenetről:
- A mezők típuskonverziója a profilban megadott `type` alapján történik (pl. `money` → int/float, `bool` → boolean).
- Alapértelmezés szerint a csomag tömböt ad vissza; ha JSON-t szeretnél, egyszerűen json_encode-olhatod vagy response()->json()-t használhatsz.
- Ha a profil `post_processor`-t ad meg, az módosíthatja a végső kimenetet.

---

## Implementáció (magas szint)

A csomag komponensei röviden:

- ScraperManager
  - A csomag belépési pontja, ez koordinálja a profil betöltését, a letöltést és a HTML feldolgozást.
- Contracts/ScraperProfile
  - Interfész, amelyet egyedi profilok implementálhatnak. Meghatározza a szükséges metódusokat (pl. `getType()`, `getFields()`, `parse()` stb.).
- Profiles/ConfigProfile
  - Alap implementáció, amely config fájlból épít profilt (a tipikus esetet lefedi).
- Http/Downloader
  - Egységesen kezeli a kéréseket Laravel `Http` kliensen keresztül (timeout, retry beállítások). Hibakezelést delegál tovább.
- Parsing/HtmlExtractor
  - Fej vagy body HTML-ből CSS szelektorokkal adatot kinyerő komponens.
- Facades/UniversalScraper
  - Egy egyszerű facade, amely kényelmesen hívható a legtöbb helyről.
- Exceptions/ProfileNotFoundException
  - Hibakezelés akkor, ha a kért profil nem található.

Ha szeretnél egyedi viselkedést (pl. saját parsing vagy API-kliens), implementálj egy osztályt a `ScraperProfile` interfész alapján, és regisztráld a szolgáltatáson keresztül.

---

## Hibakezelés és edge-case-ek

Gyakori hibák és hogyan kezeld őket:

- ProfileNotFoundException: a megadott profil név nem létezik a configban. Ellenőrizd a `config/universal-scraper.php` profil nevét.
- Hálózati hibák (timeout, DNS, 5xx): a `Downloader` továbbadja a Laravel `Http` hibákat. Állíts be timeout-ot és retry-t a configban.
- Üres vagy változó HTML struktúra: használj robusztusabb szelektorokat vagy post-processort a hiányzó mezők kezelése érdekében.
- Relatív URL-ek: alapértelmezésben az `href`-eket nem mindig abszolutizálja; ha szükséges, add hozzá az alap URL-t a post-processing fázisban.

Edge-case lista (amire figyelni kell):
- Többszörös találatok egy selectorra (első vs. összes eredmény)
- Dinamikus JS által generált tartalom (ez nem fog működni a szerver-side HTML parserekkel)
- Oldalak, amelyek blokkolják a scraping-et (rate-limit, bot-detection)

---

## Kiterjesztés / Custom profil példa

Rövid példa, hogyan írj saját `ScraperProfile`-t:

```php
namespace App\Scrapers;

use BalintPethe\UniversalScraper\Contracts\ScraperProfile;

class MyCustomProfile implements ScraperProfile
{
    // ...implementáld a szükséges metódusokat, pl. getType(), getFields(), parse()
}
```

Regisztrálhatod a service containerben vagy konfigurációban hivatkozhatsz rá, ha a csomag ezt a hookot támogatja.

---

## Tesztelés és helyi fejlesztés

- Használj fixture HTML fájlokat unit tesztekhez (minta HTML bemenet → várt tömb kimenet).
- Ajánlott: PHPStan / Psalm és PHPUnit konfiguráció létrehozása a csomaghoz.

---

## Gyakori használati forgatókönyvek

- Listázás (terméklista): `type: list`, `list_selector` + `fields`
- Single item (termék oldal): `type: item`, egy objektum kinyerése
- Paginalás: a profilban definiálhatod a következő oldal URL-jének logikáját, vagy a post-processor kezelheti

---

## Authentikált (loginos) scraping – AuthenticatedDownloader

Bizonyos esetekben szükség lehet arra, hogy a scraper **csak login mögötti oldalakhoz** férjen hozzá  
(pl. saját admin felület, belső ERP, partner rendszer, ahova van jogosultságod).

Ehhez a profilhoz egy `auth` blokkot adhatsz, ekkor a csomag automatikusan
`AuthenticatedDownloader`-t fog használni az adott profilhoz.

```php
'profiles' => [

    'internal_admin_orders' => [
        'base_url' => 'https://admin.my-internal-app.local',

        'class' => \Balintpethe\LaravelUniversalScraper\Profiles\ConfigProfile::class,

        'auth' => [
            'type'      => 'form',   // jelenleg: 'form' támogatott
            'login_url' => '/login',
            'method'    => 'POST',

            // A credential értékeket NEM írjuk a configba, csak env-ből olvassuk:
            'credentials' => [
                'email_env'    => 'SCRAPER_LOGIN_EMAIL',
                'password_env' => 'SCRAPER_LOGIN_PASSWORD',
            ],

            // A form mezők nevei
            'fields' => [
                'email'    => 'email',
                'password' => 'password',
            ],
        ],

        'list' => [
            'path'  => '/orders',
            'item'  => '.order-row',
            'fields' => [
                'id' => [
                    'selector' => '.order-id',
                    'attr'     => 'text',
                    'cast'     => 'int',
                ],
                'total' => [
                    'selector' => '.order-total',
                    'attr'     => 'text',
                    'cast'     => 'float',
                ],
            ],
        ],
    ],

];
```
### Env változók:

```env
SCRAPER_LOGIN_EMAIL=scraper@example.com
SCRAPER_LOGIN_PASSWORD=super-secret-password
```
### A folyamat:

- A profil tartalmaz auth blokkot → a csomag AuthenticatedDownloader-t hoz létre.

- Az első get() hívás előtt a downloader:

  - POST /login kérésben elküldi az email/jelszó párost.

  - elmenti a szerver által adott session cookie-kat.

A következő kérések ugyanezzel a sessionnel, authentikáltan futnak tovább.

⚠️ Ha a profilban nincs auth blokk, akkor továbbra is a sima, “anonim” Downloader
kerül használatra, tehát az auth egy teljesen opcionális extra.

---

## Contributing

Ha szeretnél hozzájárulni:

- Nyiss issue-t, ha hibát találsz vagy feature-t javasolsz
- Fork-olj és küldj PR-t; tartsd a kódot PSR-12 kompatibilisnek és adj hozzá teszteket

---


## Jogi / etikai megjegyzés a README-ben

### Legal / etikai megjegyzés

Ez a csomag kizárólag olyan célokra készült, ahol a scraping **jogszerű és engedélyezett**.

- Mindig tartsd be a célrendszer felhasználási feltételeit (ToS), a `robots.txt` előírásait
  és az adatvédelmi / szerzői jogi szabályokat.
- Authentikált scrapinget (login mögötti oldalakat) csak olyan fiókokkal és olyan rendszerekhez
  használj, amelyekhez hivatalos, jogszerű hozzáférésed van.
- A csomag **nem** a védelmek, paywallok, 2FA, CAPTCHA vagy egyéb biztonsági mechanizmusok
  megkerülésére készült.

A csomag készítője semmilyen felelősséget nem vállal a helytelen vagy jogsértő felhasználásért.
A felelősség kizárólag a használót terheli.

---

## License & Copyright

[MIT](https://github.com/balintpethe/laravel-universal-scraper/blob/master/LICENSE), (c) 2025-present Bálint Pethe and [contributors](https://github.com/balintpethe/laravel-universal-scraper/graphs/contributors)
