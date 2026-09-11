# Contributing to SMPS

Děkujeme za pomoc s projektem. Než začnete, přečtěte si instalační a testovací
pokyny v [`README.md`](README.md) a aktuální omezení v
[`docs/public-release-plan.md`](docs/public-release-plan.md).

## Vývojový postup

1. Vytvořte krátkou větev z aktuální vývojové větve.
2. Udržujte změnu úzce zaměřenou a neměňte nesouvisející legacy kód.
3. Doplňte nebo upravte testy pro měněné chování.
4. Spusťte kontrolní sadu uvedenou v README.
5. V pull requestu popište účel, rizika, ověření a případné kroky, které nebylo
   možné lokálně provést.

Commitujte záměrné, samostatně kontrolovatelné kroky. Necommitujte vygenerovaný
`vendor`, runtime data, IDE konfiguraci ani lokální přístupové údaje.

## Jazyk a styl

- Identifikátory zdrojového kódu, komentáře a vývojářské zprávy pište anglicky.
- České texty uživatelského rozhraní a zavedené doménové názvy zachovejte,
  pokud změna výslovně neřeší copywriting.
- Dodržujte strukturu a styl sousedních souborů. Nové PHP soubory mají používat
  `declare(strict_types=1);`, pokud je to slučitelné s okolním kódem.
- Hromadné formátování nebo refaktoring nespojujte s funkční změnou.

## Databáze a runtime data

Aplikace používá Doctrine ORM a Phinx migrace. Verzovaný řetězec migrací umí
vytvořit schéma prázdné databáze; podrobnosti a pravidla bezpečného upgradu
jsou v [`docs/database-inventory.md`](docs/database-inventory.md).

Bez výslovného schválení nespouštějte produkční migrace, rollback, schema
generation ani reset databáze. Nikdy nemažte či nepřesouvejte uživatelské
uploady v `www/dokumenty` ani ignorované adresáře `log` a `temp` kvůli čistému
working tree.

## Závislosti a assety

Změny Composer závislostí musí aktualizovat `composer.lock` a projít
`composer validate --strict --no-check-publish` a `composer audit --locked`.
Při přidání distribuované knihovny nebo assetu zachovejte její licenci a
aktualizujte [`THIRD_PARTY_NOTICES.md`](THIRD_PARTY_NOTICES.md).

## Bezpečnost

Citlivé zranitelnosti nehlaste ve veřejném issue. Postupujte podle
[`SECURITY.md`](SECURITY.md). Pull request ani test nesmí obsahovat skutečné
produkční hostitele, účty, e-maily, hesla, hashe, tokeny nebo databázová data.
