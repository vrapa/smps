<?php

declare(strict_types=1);

namespace App\Localization;

use InvalidArgumentException;
use LogicException;
use Nette\Localization\Translator;
use Stringable;
use UnexpectedValueException;

final class CatalogTranslator implements Translator
{
    private const FALLBACK_LOCALE = SupportedLocale::English;

    /** @var array<string, string> */
    private array $catalogue;

    /** @var array<string, string> */
    private array $fallbackCatalogue;

    private SupportedLocale $locale;

    public function __construct(
        string $locale,
        private bool $strict = false,
        ?string $messagesDirectory = null,
    ) {
        $this->locale = SupportedLocale::fromConfiguration($locale);
        $messagesDirectory ??= __DIR__ . '/messages';
        $this->fallbackCatalogue = $this->loadCatalogue($messagesDirectory, self::FALLBACK_LOCALE);
        $this->catalogue = $this->locale === self::FALLBACK_LOCALE
            ? $this->fallbackCatalogue
            : $this->loadCatalogue($messagesDirectory, $this->locale);
    }

    public function getLocale(): string
    {
        return $this->locale->value;
    }

    public function translate(string|Stringable $message, mixed ...$parameters): string
    {
        $key = (string) $message;
        $translation = $this->catalogue[$key] ?? $this->fallbackCatalogue[$key] ?? null;

        if ($translation === null) {
            if ($this->strict && $this->looksLikeTranslationKey($key)) {
                throw new LogicException(sprintf(
                    'Missing translation key "%s" for locale "%s".',
                    $key,
                    $this->locale->value,
                ));
            }

            $translation = $key;
        }

        return $this->interpolate($translation, $parameters);
    }

    /** @return array<string, string> */
    private function loadCatalogue(string $messagesDirectory, SupportedLocale $locale): array
    {
        $path = rtrim($messagesDirectory, '/\\') . DIRECTORY_SEPARATOR . $locale->value . '.php';
        if (!is_file($path)) {
            throw new UnexpectedValueException(sprintf(
                'Translation catalogue for locale "%s" is unavailable.',
                $locale->value,
            ));
        }

        $catalogue = require $path;
        if (!is_array($catalogue)) {
            throw new UnexpectedValueException(sprintf(
                'Translation catalogue for locale "%s" must return an array.',
                $locale->value,
            ));
        }

        foreach ($catalogue as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                throw new UnexpectedValueException(sprintf(
                    'Translation catalogue for locale "%s" must contain only string keys and values.',
                    $locale->value,
                ));
            }
        }

        /** @var array<string, string> $catalogue */
        return $catalogue;
    }

    /** @param array<int|string, mixed> $parameters */
    private function interpolate(string $translation, array $parameters): string
    {
        if (count($parameters) === 1 && isset($parameters[0]) && is_array($parameters[0])) {
            $parameters = $parameters[0];
        }

        if ($parameters === []) {
            return $translation;
        }

        if (array_is_list($parameters)) {
            return vsprintf($translation, $parameters);
        }

        $replace = [];
        foreach ($parameters as $name => $value) {
            if (!is_string($name) || (!is_scalar($value) && !$value instanceof Stringable)) {
                throw new InvalidArgumentException('Translation placeholders require named scalar values.');
            }

            $replace['{' . $name . '}'] = (string) $value;
        }

        return strtr($translation, $replace);
    }

    private function looksLikeTranslationKey(string $message): bool
    {
        return preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+$/D', $message) === 1;
    }
}
