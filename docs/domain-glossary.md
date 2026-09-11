# SMPS domain glossary

This is the canonical vocabulary for converting developer-facing source code
to English. Czech database identifiers and stored values remain mapped in
Doctrine, and Czech user-facing text remains unchanged.

## Naming rules

- Entity classes and relation entities use singular nouns.
- Repositories use the singular entity name plus `Repository`.
- Services use the domain noun plus a responsibility, avoiding generic plural
  class names where a narrower name is possible.
- New PHP properties and methods use English `camelCase`; constants use English
  `UPPER_SNAKE_CASE`.
- Existing table and column names stay unchanged during the code rename and are
  expressed explicitly in Doctrine mapping attributes.
- Existing Czech presenter routes remain available through aliases or redirects
  when presenter classes are renamed. Bookmarked URLs must not break.
- Form control names and template variables are renamed together with the PHP
  handler that produces or consumes them. Visible Czech labels and validation
  messages are not translated.

## Entities and persistence

| Current technical name | Canonical English name | Existing database identifier |
| --- | --- | --- |
| `Users` | `User` | `users` |
| `Roles` | `Role` | `roles` |
| `Roles2users` | `UserRole` | `roles2users` |
| `Skladby` | `Song` | `skladby` |
| `Koncerty` | `Concert` | `koncerty` |
| `Skladby2koncerty` | `ConcertSong` | `skladby2koncerty` |
| `Soubory2skladby` | `SongFile` | `soubory2skladby` |

The corresponding repositories are `UserRepository`, `RoleRepository`,
`UserRoleRepository`, `SongRepository`, `ConcertRepository`,
`ConcertSongRepository`, and `SongFileRepository`.

## Fields and relationships

| Current name | Canonical English name | Context |
| --- | --- | --- |
| `nazev` | `title` | Song and concert |
| `nazev` | `displayName` | User; current login identity display value |
| `autor` | `author` | Song |
| `kdy` | `scheduledAt` | Concert |
| `poznamka` | `note` | Concert |
| `createdAt`, `createdat` | `createdAt` | Song, concert, and song file |
| `createdBy`, `createdby` | `createdBy` | User relationship |
| `active` | `active` / `isActive()` | Song |
| `priority`, `priorita` | `sortOrder` | Concert-song and song-file ordering |
| `kategorie` | `category` | Song file |
| `filename` | `filename` | Song file |
| `popis` | `description` | Role and song file |
| `kod` | `code` | Role |
| `skladby` | `song` or `songs` | Singular relation or collection |
| `koncerty` | `concert` or `concerts` | Singular relation or collection |
| `roles` | `role` or `roles` | Singular relation or collection |
| `users` | `user` or `users` | Singular relation or collection |
| `opravneni` | `permissions` | User |
| `notifikace` | `notificationsEnabled` | User |
| `notifyDaysBefore` | `notificationLeadDays` | User |
| `ulice` | `street` | User address |
| `cisloPopisne` | `streetNumber` | User address |
| `mesto` | `city` | User address |
| `psc` | `postalCode` | User address |
| `telefon` | `phone` | User |
| `ico` | `companyRegistrationNumber` | Czech company identifier |
| `dico` | `vatIdentificationNumber` | Czech VAT identifier |

Database columns such as `createdAt`, `createdBy`, `nazev`, and `priorita` are
not renamed merely to match PHP style.

## Application components

| Current name | Canonical English name |
| --- | --- |
| `MyAuthenticator` | `UserAuthenticator` |
| `KoncertyService` | `ConcertSongService` while it manages concert-song links |
| `SkladbyPresenter` | `SongsPresenter` |
| `KoncertyPresenter` | `ConcertsPresenter` |
| `SettingPresenter` | `SettingsPresenter` |
| `SignPresenter` | `AuthenticationPresenter` |
| `SignFormFactory` | `AuthenticationFormFactory` |
| `notySbor` | `choirSheetMusic` |
| `notyOrchestr` | `orchestraSheetMusic` |
| `nahravky` | `recordings` |
| `seznamSouboru`, `soubory` | `files` |
| `heslo`, `hesla`, `loginHesla` | `password`, `passwords`, `loginPasswords` |
| `uzivatel` | `user` |
| `skladba` | `song` |
| `koncert` | `concert` |

Keep current stored song-file category values (`nahravky`, `noty-orchestr`, and
`noty-sbor`) until an explicitly migrated data change is justified. Code
constants become `RECORDINGS`, `ORCHESTRA_SHEET_MUSIC`, and
`CHOIR_SHEET_MUSIC`, mapped to those existing values.

## Route compatibility

The current public presenter names `Skladby`, `Koncerty`, `Setting`, `Sign`, and
`Admin:Users` are compatibility routes. When English presenters are introduced,
the old routes must resolve to the same actions and parameters or redirect to
their English equivalents. Action names such as `default`, `show`, `create`,
`edit`, and signal parameters such as `id` may stay unchanged because they are
already English or language-neutral.

## Czech UI vocabulary to preserve

Visible terms including **Skladby**, **Koncerty**, **Uživatelé**, **Název**,
**Autor**, **Poznámka**, **Noty sbor**, **Noty orchestr**, **Nahrávky**,
validation messages, flash messages, and navigation labels remain Czech. This
glossary governs implementation identifiers, not the language shown to choir
members.
