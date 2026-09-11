<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Configuration\PublicSettings;
use App\Localization\CatalogTranslator;
use InvalidArgumentException;
use Latte\Engine;
use Nette\Bridges\ApplicationLatte\Template;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SharedUiLocalizationTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Stránka nenalezena', 'Chyba serveru'];
        yield 'English' => ['en', 'Page not found', 'Server error'];
        yield 'German' => ['de', 'Seite nicht gefunden', 'Serverfehler'];
        yield 'Dutch' => ['nl', 'Pagina niet gevonden', 'Serverfout'];
    }

    #[DataProvider('localeProvider')]
    public function testErrorPagesRenderInConfiguredLocale(
        string $locale,
        string $notFoundTitle,
        string $serverTitle,
    ): void {
        $translator = new CatalogTranslator($locale, true);
        $template = new Template(new Engine());
        $template->setTranslator($translator, $locale);

        $notFound = $template->renderToString(
            dirname(__DIR__, 2) . '/app/Modules/templates/Error/404.latte',
        );
        self::assertStringContainsString($notFoundTitle, $notFound);
        self::assertStringContainsString($translator->translate('error.404.message'), $notFound);

        $serverError = $this->renderPhpErrorTemplate('500.phtml', $translator);
        self::assertStringContainsString('lang="' . $locale . '"', $serverError);
        self::assertStringContainsString($serverTitle, $serverError);

        $maintenance = $this->renderPhpErrorTemplate('503.phtml', $translator);
        self::assertStringContainsString('lang="' . $locale . '"', $maintenance);
        self::assertStringContainsString($translator->translate('error.503.message'), $maintenance);
    }

    public function testEveryCatalogueHasTheSameSharedKeysAndPlaceholders(): void
    {
        $catalogues = [];
        foreach (['cs', 'en', 'de', 'nl'] as $locale) {
            $catalogues[$locale] = require dirname(__DIR__, 2) . "/app/Localization/messages/{$locale}.php";
        }

        $expectedKeys = array_keys($catalogues['en']);
        sort($expectedKeys);
        foreach ($catalogues as $locale => $catalogue) {
            $keys = array_keys($catalogue);
            sort($keys);
            self::assertSame($expectedKeys, $keys, "Catalogue {$locale} has a different key set.");

            foreach ($catalogue as $key => $translation) {
                self::assertSame(
                    $this->placeholders($catalogues['en'][$key]),
                    $this->placeholders($translation),
                    "Catalogue {$locale} has different placeholders for {$key}.",
                );
            }
        }
    }

    public function testPublicApplicationNameMustNotBeEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PublicSettings('   ');
    }

    private function renderPhpErrorTemplate(string $filename, CatalogTranslator $translator): string
    {
        ob_start();
        require dirname(__DIR__, 2) . '/app/Modules/templates/Error/' . $filename;
        $output = ob_get_clean();

        self::assertIsString($output);
        return $output;
    }

    /** @return list<string> */
    private function placeholders(string $translation): array
    {
        preg_match_all('/\{[a-z][a-z0-9_]*\}/i', $translation, $matches);
        $placeholders = array_values(array_unique($matches[0]));
        sort($placeholders);

        return $placeholders;
    }
}
