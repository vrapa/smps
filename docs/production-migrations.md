# Produkcni migrace pres docasnou WebSSH konzoli

Tento postup je urceny pro rucni spusteni a vraceni databazovych migraci na
produkcnim hostingu Webglobe. Automaticky deployment prenasi migracni soubory,
ale databazi nemeni. WebSSH konzole se v administraci Webglobe aktivuje vzdy jen
na jednu hodinu.

Postup zamerne nevytvari databazovou zalohu a nema automaticky rollback. Pred
kazdym prikazem `migrate` nebo `rollback` nejprve zkontrolujte jeho dry-run.

## 1. Otevreni konzole a kontrola release

V administraci Webglobe otevrete `Hosting -> FTP a soubory -> WebSSH`, aktivujte
docasnou konzoli a prihlaste se udaji zobrazenymi administraci. Tyto docasne
udaje neukladejte do repozitare ani do dokumentace.

V konzoli prejdete do produkcniho korene:

```sh
cd /home/html/rkcomputer.cz/public_html/_sub/smps
pwd
cat RELEASE_SHA
php8.1 -v
test -r config/local.neon && echo "local.neon: readable"
test -f config/phinx.production.example.php && echo "Phinx template: present"
```

`pwd` musi vypsat presne uvedeny adresar. `RELEASE_SHA` musi odpovidat commitu,
ktery ma GitHub vedeny jako posledni uspesne nasazeny release. Pri nesouladu
nepokracujte.

## 2. Priprava chranene Phinx konfigurace

Produkci nakonfigurujte z existujiciho `config/local.neon`; heslo se nikam
nekopiruje ani nevypisuje:

```sh
cp config/phinx.production.example.php config/phinx.php
chmod 600 config/phinx.php
```

`config/phinx.php` je ignorovany Gitem a automaticky deployment jej nemeni.

## 3. Read-only kontrola stavu

```sh
php8.1 vendor/bin/phinx status \
  --environment production \
  --configuration config/phinx.php
```

Zkontrolujte, ze pripojeni smeruje do prostredi `production`, historicke migrace
maji ocekavany stav `up` a pouze zamyslene nove migrace maji stav `down`. Pri
chybe pripojeni, neznamem prostredi nebo neocekavanem seznamu nepokracujte.

## 4. Nasazeni migraci

Nejprve nechte Phinx pouze vypsat SQL:

```sh
php8.1 vendor/bin/phinx migrate \
  --environment production \
  --configuration config/phinx.php \
  --dry-run
```

Po kontrole vystupu spustte stejnou sadu migraci bez dry-runu:

```sh
php8.1 vendor/bin/phinx migrate \
  --environment production \
  --configuration config/phinx.php \
  --no-interaction
```

Po dokonceni overte stav databaze a aplikace:

```sh
php8.1 vendor/bin/phinx status \
  --environment production \
  --configuration config/phinx.php
php8.1 bin/console orm:validate-schema
curl --fail --silent --show-error --output /dev/null \
  --write-out 'login HTTP %{http_code}\n' \
  https://smps.rkcomputer.cz/authentication/login
```

Vsechny prave nasazene migrace musi mit stav `up` a prihlasovaci stranka musi
vratit HTTP 200. Pokud `orm:validate-schema` hlasi rozdil, neprovadejte
`orm:schema-tool:update --force`. Nejprve si nechte vypsat pouze SQL:

```sh
php8.1 bin/console orm:schema-tool:update --dump-sql
```

Rozdil musi byt samostatne posouzen a pripadne resen novou migraci. Pri overeni
24. 9. 2026 bylo mapovani v poradku a vystup obsahoval pouze drive existujici
odchylky komentaru a kolaci nekolika sloupcu tabulky `users`. Neobsahoval
`users.deprecated_role` ani `skladby.active`, takze neukazoval chybu prave
provedenych prechodovych migraci.

## Reseni duplicitni historicke migrace

Prvni bezpecny produkcni prekryv zamerne nemazal nezname soubory. Proto na
serveru zustal historicky soubor
`db/migrations/202309261550_create_table_roles.php`, ktery mel stejne ID jako
verzovana konsolidovana migrace `202309261550_create_initial_schema.php`. Phinx
pak odmitl i prikaz `status` s chybou `Duplicate migration`.

Tento konkretni historicky soubor byl 24. 9. 2026 po kontrole obsahu z produkce
odstranen. Pokud se stejna chyba objevi na jine instalaci, nejprve porovnejte
oba soubory a stav `_phinxlog`; nemazte automaticky libovolnou migraci jen podle
stejneho ID.

## 5. Vraceni migraci

Rollback vzdy provadejte na explicitni cilovou verzi. Nepouzivejte obecny cil
`0` a nemenite rucne tabulku `_phinxlog`.

Nejprve zobrazte stav a dry-run pro zvolenou cilovou verzi:

```sh
php8.1 vendor/bin/phinx status \
  --environment production \
  --configuration config/phinx.php
php8.1 vendor/bin/phinx rollback \
  --environment production \
  --configuration config/phinx.php \
  --target TARGET_VERSION \
  --dry-run
```

Teprve po kontrole SQL provedte rollback:

```sh
php8.1 vendor/bin/phinx rollback \
  --environment production \
  --configuration config/phinx.php \
  --target TARGET_VERSION \
  --no-interaction
```

Pro aktualni prechodove migrace plati:

| Zamysleny stav po rollbacku | `TARGET_VERSION` |
| --- | --- |
| Vratit jen normalizaci `skladby.active`, ponechat odstraneny `deprecated_role` | `202608201200` |
| Vratit obe prechodove migrace a ponechat vsechny tri migrace z roku 2023 | `202309290938` |

Rollback migrace `DropDeprecatedRole` znovu vytvori sloupec
`users.deprecated_role`, ale jeho drive odstranena data neobnovi; vytvori jej s
prazdnou vychozi hodnotou. Bez samostatne databazove zalohy jsou puvodni hodnoty
nevratne ztracene.

Po rollbacku zopakujte prikazy `status`, `orm:validate-schema` a HTTP kontrolu z
predchozi casti. Pokud se WebSSH behem operace odpoji, po novem prihlaseni
nejprve znovu spustte `status`; nespoustejte migraci ani rollback naslepo.

## 6. Ukonceni

Zavrete WebSSH kartu. Docasna konzole se nejpozdeji po jedne hodine deaktivuje
sama. Soubor `config/phinx.php` muze na serveru zustat: neobsahuje prihlasovaci
udaje, pouze nacita chraneny `config/local.neon`, a neni dostupny z verejneho
dokumentoveho korene `www`.
