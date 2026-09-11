# Localization implementation plan

This plan introduces a multilingual user interface for Czech (`cs`), English
(`en`), German (`de`), and Dutch (`nl`, Nederlands). Each installation selects
one language in its configuration; all users of that installation see the same
language.

The scope is application UI copy only. User-created song titles, concert notes,
file descriptions, names, and uploaded documents remain in the language entered
by their authors.

Each numbered stage is a separate reviewable commit. After a stage is verified,
update its checklist and push it to the private source repository and through
the protected public GitHub pull-request flow. Production configuration and
deployment remain separately approved operations.

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
- Validate the configured locale against the allowlist during container setup.
  Never build a catalogue path directly from unchecked input.
- Translation remains escaped by Latte like other UI text; catalogue values
  must not be treated as trusted HTML.

## Configuration contract

Versioned configuration provides a working Czech default:

```neon
parameters:
	locale: cs
```

An operator may select another supported language in ignored local
configuration:

```neon
parameters:
	locale: nl
```

`config/local.example.neon` documents all four values. An unsupported or empty
value must fail container creation with a clear configuration error instead of
silently selecting an unexpected language. The resolved locale is supplied to
the translator, date formatter, every Latte template, and every Nette form.
The layout renders it in `<html lang="…">`.

## Current inventory

The application has no localization service today. Czech UI copy is embedded
in 26 PHP, Latte, and PHTML files. The inventory includes:

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

- [ ] Add a supported-locale value object/enum and the `parameters.locale`
  configuration contract with Czech as the default.
- [ ] Document `cs`, `en`, `de`, and `nl` in `config/local.example.neon` and
  deployment documentation without changing protected production config.
- [ ] Add `CatalogTranslator` implementing `Nette\Localization\Translator`.
- [ ] Add catalogue loading, English fallback, named interpolation, and clear
  diagnostics for unknown keys in development without leaking paths in
  production.
- [ ] Add initial `cs.php`, `en.php`, `de.php`, and `nl.php` catalogues.
- [ ] Register the translator in Nette DI and attach it to every Latte template
  and Nette form through shared presenter/form infrastructure.
- [ ] Add unit tests for all configured locales, invalid configuration, known
  keys, fallback, interpolation, and safe catalogue output.

Done when four test containers can render the same template and form key in
their configured language while the default container remains Czech.

## 2. Shared UI, authentication, forms, and errors

- [ ] Extract shared navigation, page headings, flash messages, buttons,
  pagination, login/logout, and accessibility text into semantic keys.
- [ ] Set the configured locale on the translator before presenter actions
  create forms or flash messages.
- [ ] Set the document `lang` attribute from configuration.
- [ ] Translate form labels, required messages, validation messages, CSRF
  errors, and authentication failures through the same translator.
- [ ] Localize 403, 404, 405, 410, generic 4xx, 500, and 503 responses while
  retaining safe minimal rendering for fatal error paths.
- [ ] Move choir/application display name to public instance configuration so
  another choir does not need to edit templates. Keep secrets out of this
  setting.
- [ ] Add presenter and response tests using each configured locale.

Done when shared UI and every authentication/error path contain no hard-coded
user-facing Czech or English sentence.

## 3. Songs and files vertical slice

- [ ] Translate song list, detail, create/edit/delete flows and all flash/error
  messages.
- [ ] Translate upload forms, file categories, size/type/category errors,
  download/delete controls, and empty states.
- [ ] Keep song titles, authors, filenames, and descriptions unchanged as user
  content.
- [ ] Add four-locale form, presenter, upload-error, and authorization tests.

Done when the complete song and file workflow is usable under every supported
configuration without translating or renaming stored content.

## 4. Concerts vertical slice

- [ ] Translate concert list, detail, create/edit/delete flows, programme
  controls, validation, and flash/error messages.
- [ ] Introduce one locale-aware date/time formatter backed by `ext-intl` and
  remove fixed date patterns from Latte.
- [ ] Keep concert titles, notes, and song ordering unchanged as user data.
- [ ] Test date rendering and the complete concert workflow in all locales and
  under the application's configured timezone.

Done when concert UI and dates are correct in Czech, English, German, and Dutch.

## 5. Users, roles, and settings vertical slice

- [ ] Translate user list/detail/create/edit/delete flows and password forms.
- [ ] Translate display labels for stable role codes without changing the codes
  stored in the database or used by authorization.
- [ ] Translate notification settings and status values.
- [ ] Verify that authorization decisions never depend on translated text.
- [ ] Add all-locale tests for administration, self-delete protection,
  validation, password changes, and settings.

Done when administrators and members can complete their workflows in the
configured language with unchanged authorization semantics.

## 6. Completeness, translation review, and release

- [ ] Add a catalogue parity test: every locale has exactly the canonical key
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
