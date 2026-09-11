# Security policy

## Supported versions

Projekt zatím nemá veřejné stabilní vydání. Bezpečnostní opravy se připravují
pro aktuální vývojovou větev a po zveřejnění budou podporované verze uvedené
zde. Starší commity a neoznačené deploymenty nejsou samostatně podporované.

## Hlášení zranitelnosti

Nezakládejte veřejný issue s popisem dosud neopravené zranitelnosti, tajnými
údaji, osobními údaji ani produkční konfigurací.

Po vzniku veřejného GitHub repozitáře použijte jeho soukromé hlášení
zranitelnosti v části **Security → Advisories → Report a vulnerability**. Do
té doby kontaktujte správce projektu již existujícím soukromým kanálem. Pokud
žádný soukromý kanál nemáte, zveřejněte pouze žádost o navázání bezpečného
kontaktu bez technických detailů.

V hlášení uveďte postiženou verzi nebo commit, kroky k reprodukci, dopad a
případný návrh opravy. Nepřikládejte skutečné přihlašovací údaje ani exporty
produkční databáze. Přijetí hlášení, další postup a koordinovaný termín
zveřejnění budou potvrzeny soukromě.

## Provozní bezpečnost

- Produkční tajné údaje patří pouze do chráněné lokální konfigurace nebo do
  secrets příslušného deployment prostředí.
- Migrace, rollbacky, reset databáze a změny produkčního deploymentu vyžadují
  samostatnou revizi, zálohu a výslovné schválení.
- Uživatelské uploady v `www/dokumenty`, `log`, `temp`, `config/local.neon` a
  `config/phinx.php` ani starší `config/phinx.yaml` nejsou zdrojový kód a
  nesmějí být součástí artefaktu.
