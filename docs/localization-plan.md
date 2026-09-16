# Localization implementation plan

The coordinating [completion plan](completion-plan.md) defines the final
GitHub-only workflow, Webglobe deployment, and release gates. This document
retains the detailed translation checklist.

This plan introduces a multilingual user interface for Czech (`cs`), English
(`en`), German (`de`), and Dutch (`nl`, Nederlands). Each installation selects
one language in its configuration; all users of that installation see the same
language.

The scope is application UI copy only. User-created song titles, concert notes,
file descriptions, names, and uploaded documents remain in the language entered
by their authors.

Each numbered stage is a separate reviewable commit. After a stage is verified,
update its checklist and use the protected public GitHub pull-request flow.
The current private/public transfer process ends at milestone 2 of the
completion plan; subsequent development is GitHub-only. Production
configuration and deployment remain separately approved operations.

## Decisions

- Supported locale codes are exactly `cs`, `en`, `de`, and `nl`.
- Configure the installation-wide locale as `parameters.locale`, with `cs` as
  the safe default in versioned configuration and an optional override in the
  ignored `config/local.neon`.
- Changing the locale requires a configuration change and cache clear or
  deployment. There is no runtime language switcher, browser-language
  detection, session preference, cookie, or per-user database field.
- Existing installations remain Czech until their administrator deliberately
  changes the configuration. No database migration is required.
- English is the catalogue fallback for a missing translation. Automated
  catalogue checks should normally make fallback unnecessary.
- Use semantic keys such as `songs.form.title`, never a Czech or English
  sentence as the key.
- Keep catalogues as versioned PHP arrays under `app/Localization/messages`.
  Implement `Nette\Localization\Translator` locally; no translation framework
  dependency is needed for the current application.
- Use named placeholders, for example `{count}` or `{name}`, and verify that
  every locale has the same placeholders for a key.
- Use `ext-intl` for locale-aware dates and future plural handling. Confirm it
  on development, CI, staging, and production before adding it as a Composer
  platform requirement.
- Do not translate persisted role codes, presenter/action names, database
  identifiers, or existing URL slugs. Display labels may be translated while
  stable identifiers remain unchanged.
- Keep existing routes, including `koncerty` and `skladby`, in the first
  release. Translated URLs can be designed later with redirects and backward-
  compatibility tests.
- Validate the configured locale against the allowlist when the localization
  service is created, before any application page is rendered.
  Never build a catalogue path directly from unchecked input.
- Translation remains escaped by Latte like other UI text; catalogue values
  must not be treated as trusted HTML.

## Configuration contract

Versioned configuration provides a working Czech default:

```neon
parameters:
	locale: cs
	applicationName: SMPS Bruntál
```

An operator may select another supported language in ignored local
configuration:

```neon
parameters:
	locale: nl
```

`config/local.example.neon` documents all four values. An unsupported or empty
value must fail localization service creation with a clear configuration error
instead of silently selecting an unexpected language. The resolved locale is
supplied to the translator, date formatter, every Latte template, and every
Nette form.
The layout renders it in `<html lang="…">`.

## Initial inventory (before implementation)

At the start of this plan the application had no localization service. Czech
UI copy was embedded in 26 PHP, Latte, and PHTML files. The inventory included:

- navigation, page titles, buttons, tables, accessibility labels, and flash
  messages;
- authentication, settings, users, songs, concerts, file uploads, pagination,
  and error pages;
- form labels, CSRF messages, validation rules, and authentication errors;
- exceptions from upload validation that are currently shown to users;
- fixed `j. n. Y H:i` concert date formatting;
- database role codes displayed directly in the administration form;
- instance-specific `SMPS Bruntál` branding in the shared layout.

Database comments and legacy column names are not UI copy and do not need
translation. The currently empty `MailService` adds no email templates to this
scope. Any mail content added later should use the installation locale unless
a per-recipient language feature is designed separately.

## 1. Translation and configuration infrastructure

- [x] Add a supported-locale value object/enum and the `parameters.locale`
  configuration contract with Czech as the default.
- [x] Document `cs`, `en`, `de`, and `nl` in `config/local.example.neon` and
  deployment documentation without changing protected production config.
- [x] Add `CatalogTranslator` implementing `Nette\Localization\Translator`.
- [x] Add catalogue loading, English fallback, named interpolation, and clear
  diagnostics for unknown keys in development without leaking paths in
  production.
- [x] Add initial `cs.php`, `en.php`, `de.php`, and `nl.php` catalogues.
- [x] Register the translator in Nette DI and attach it to every Latte template
  and Nette form through shared presenter/form infrastructure.
- [x] Add unit tests for all configured locales, invalid configuration, known
  keys, fallback, interpolation, and safe catalogue output.

Done when four test containers can render the same template and form key in
their configured language while the default container remains Czech.

Current status: complete. The four configuration variants build independently,
translate a shared Latte fixture and Nette form, and retain Czech as the
default. Legacy literal UI strings pass through unchanged until their vertical
slice moves them to semantic keys. No dependency or database migration was
added.

## 2. Shared UI, authentication, forms, and errors

- [x] Extract shared navigation, page headings, flash messages, buttons,
  pagination, login/logout, and accessibility text into semantic keys.
- [x] Set the configured locale on the translator before presenter actions
  create forms or flash messages.
- [x] Set the document `lang` attribute from configuration.
- [x] Translate shared and authentication form labels, required messages, CSRF
  errors, and authentication failures through the same translator.
- [x] Localize 403, 404, 405, 410, generic 4xx, 500, and 503 responses while
  retaining safe minimal rendering for fatal error paths.
- [x] Move choir/application display name to public instance configuration so
  another choir does not need to edit templates. Keep secrets out of this
  setting.
- [x] Add shared form and error response rendering tests using each configured
  locale.

Done when shared UI and every authentication/error path contain no hard-coded
user-facing Czech or English sentence.

Current status: complete. Shared navigation, reusable actions and flash
messages, pagination, accessibility labels, login/logout, authentication form,
CSRF protection, and all error templates use semantic keys in the four
catalogues. The configured locale is resolved during presenter startup and is
rendered as the document language. `parameters.applicationName` supplies public
instance branding. Domain-specific headings, labels, validation, and messages
remain assigned to stages 3–5.

## 3. Songs and files vertical slice

- [x] Translate song list, detail, create/edit/delete flows and all flash/error
  messages.
- [x] Translate upload forms, file categories, size/type/category errors,
  download/delete controls, and empty states.
- [x] Keep song titles, authors, filenames, and descriptions unchanged as user
  content.
- [x] Add four-locale form, presenter, upload-error, and authorization tests.

Done when the complete song and file workflow is usable under every supported
configuration without translating or renaming stored content.

Current status: complete. Song pages, headings, actions, forms, validation,
flash messages, file categories, upload errors, download/delete controls, and
empty states use semantic keys in all four catalogues. Stored category codes
(`noty-sbor`, `noty-orchestr`, and `nahravky`) and all user-entered metadata
remain unchanged. Tests cover all locales, stable field/category identifiers,
upload validation, and the unchanged `admin` authorization role. The full local
suite passes with database-only tests skipped when no isolated test DSN is
configured; GitHub CI supplies the required database integration gate.

## 4. Concerts vertical slice

- [x] Translate concert list, detail, create/edit/delete flows, programme
  controls, validation, and flash/error messages.
- [x] Introduce one locale-aware date/time formatter backed by `ext-intl` and
  remove fixed date patterns from Latte.
- [x] Keep concert titles, notes, and song ordering unchanged as user data.
- [x] Test date rendering and the complete concert workflow in all locales and
  under the application's configured timezone.

Done when concert UI and dates are correct in Czech, English, German, and Dutch.

Current status: complete in application code and CI. Concert pages, forms,
validation, programme controls, empty states, and messages use semantic keys;
titles, notes, song identifiers, and ordering remain user data. A single Intl
formatter uses `parameters.locale` and the new configurable IANA
`parameters.timezone`, while database-compatible `datetime-local` values keep
their wall time. CI installs Intl and the four-locale tests cover localized
output, stable fields, valid timezone parsing, and invalid calendar dates.
Production Intl availability is still a hosting gate: the Composer platform
requirement remains intentionally deferred until Webglobe has been verified.

## 5. Users, roles, and settings vertical slice

- [x] Translate user list/detail/create/edit/delete flows and password forms.
- [x] Translate display labels for stable role codes without changing the codes
  stored in the database or used by authorization.
- [x] Translate notification settings and status values.
- [x] Verify that authorization decisions never depend on translated text.
- [x] Add all-locale tests for administration, self-delete protection,
  validation, password changes, and settings.

Done when administrators and members can complete their workflows in the
configured language with unchanged authorization semantics.

Current status: complete. User administration, profile settings, password
forms, validation, notification states, empty lists, and flash/permission
messages use semantic keys in all four catalogues. Database role IDs and codes
remain unchanged; only the known `admin`, `user`, and `guest` display labels
are translated, while an unknown extension role is shown by its stable code.
Authorization and self-delete protection still compare stable IDs/codes rather
than translated labels. Four-locale tests cover field names, role IDs and
labels, validation, password confirmation, and notification controls.

## 6. Completeness, translation review, and release

- [x] Add a catalogue parity test: every locale has exactly the canonical key
  set and matching placeholders.
- [ ] Add a focused static audit that rejects new raw UI sentences in
  presenters/templates while allowing documented technical strings and user
  data.
- [ ] Run PHPUnit, database integration tests, PHP syntax checks, PHPCS,
  PHPStan, Latte lint, NEON lint, Composer validation, and dependency audit.
- [ ] Review responsive layout for longer German and Dutch labels, navigation,
  forms, validation messages, and small screens.
- [ ] Have Czech, German, and Dutch catalogues reviewed by fluent speakers;
  record corrections without claiming machine-generated text is authoritative.
- [ ] Update README requirements and contributor guidance for translation keys.
  Add screenshots only if publication rights are clear.
- [ ] Verify staging separately with `cs`, `en`, `de`, and `nl`, including
  login, roles, uploads, dates, errors, and fallback behaviour.
- [ ] Change production locale only through protected configuration after an
  explicitly approved deployment; verify the chosen language and rollback.

Done when CI enforces catalogue completeness, fluent-speaker review is
recorded, and staging passes the full multilingual acceptance matrix.

## Acceptance matrix

For each configured locale (`cs`, `en`, `de`, and `nl`), verify:

- application startup, invalid-config rejection, and `<html lang>`;
- navigation, headings, buttons, tables, pagination, and accessibility labels;
- login/logout and song, file, concert, user, password, and settings workflows;
- client- and server-side validation, CSRF, authorization, and 4xx/5xx errors;
- date/time output, placeholder substitution, fallback, and long-label layout;
- no translation of user-entered content and no use of translated labels as
  database or authorization identifiers.

## Explicitly deferred

- per-user language preferences and runtime language switching;
- browser-language detection, language cookies, and locale-specific sessions;
- translating user-created song, concert, or file metadata;
- translated or locale-prefixed URL slugs;
- right-to-left layout support;
- automatic machine translation at runtime;
- per-recipient email locale until actual mail templates are implemented.
