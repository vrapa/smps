<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Bootstrap;
use App\Forms\FormFactory;
use App\Localization\CatalogTranslator;
use App\Localization\SupportedLocale;
use InvalidArgumentException;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\Template;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CatalogTranslatorTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Lokalizace je připravena.', 'Vítejte, SMPS.'];
        yield 'English' => ['en', 'Localization is ready.', 'Welcome, SMPS.'];
        yield 'German' => ['de', 'Die Lokalisierung ist bereit.', 'Willkommen, SMPS.'];
        yield 'Dutch' => ['nl', 'De lokalisatie is gereed.', 'Welkom, SMPS.'];
    }

    #[DataProvider('localeProvider')]
    public function testSupportedCataloguesAndNamedInterpolation(
        string $locale,
        string $ready,
        string $greeting,
    ): void {
        $translator = new CatalogTranslator($locale);

        self::assertSame($locale, $translator->getLocale());
        self::assertSame($ready, $translator->translate('localization.ready'));
        self::assertSame($greeting, $translator->translate('localization.greeting', name: 'SMPS'));
    }

    #[DataProvider('localeProvider')]
    public function testConfiguredLocaleIsWiredThroughContainer(
        string $locale,
        string $ready,
        string $greeting,
    ): void {
        $configurator = Bootstrap::boot();
        $configurator->addStaticParameters(['locale' => $locale]);
        $container = $configurator->createContainer();

        $translator = $container->getByType(CatalogTranslator::class);
        self::assertSame($locale, $translator->getLocale());
        self::assertSame($ready, $translator->translate('localization.ready'));
        self::assertSame($greeting, $translator->translate('localization.greeting', name: 'SMPS'));

        $sharedForm = $container->getByType(FormFactory::class)->create();
        self::assertSame($translator, $sharedForm->getTranslator());

        $renderableForm = new Form();
        $renderableForm->setTranslator($translator);
        $renderableForm->addText('probe', 'localization.ready');
        self::assertStringContainsString($ready, (string) $renderableForm);

        $template = new Template(new Engine());
        $template->setTranslator($translator, $locale);
        self::assertSame(
            $greeting,
            trim($template->renderToString(
                dirname(__DIR__) . '/Fixtures/Localization/template.latte',
                ['name' => 'SMPS'],
            )),
        );
    }

    public function testSupportedLocaleValuesAreStable(): void
    {
        self::assertSame(['cs', 'en', 'de', 'nl'], SupportedLocale::values());
    }

    public function testUnsupportedLocaleIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Allowed locales: cs, en, de, nl.');

        new CatalogTranslator('fr');
    }

    public function testUnsupportedConfiguredLocaleIsRejectedByContainerService(): void
    {
        $configurator = Bootstrap::boot();
        $configurator->addStaticParameters(['locale' => 'fr']);
        $container = $configurator->createContainer();

        $this->expectException(InvalidArgumentException::class);
        $container->getByType(CatalogTranslator::class);
    }

    public function testMissingLocalTranslationUsesEnglishFallback(): void
    {
        $translator = new CatalogTranslator(
            'de',
            true,
            dirname(__DIR__) . '/Fixtures/Localization/messages',
        );

        self::assertSame('English fallback.', $translator->translate('fixture.fallback'));
        self::assertSame('Deutscher Wert.', $translator->translate('fixture.local'));
    }

    public function testStrictModeRejectsUnknownSemanticKeyWithoutExposingCataloguePath(): void
    {
        $translator = new CatalogTranslator('en', true);

        try {
            $translator->translate('missing.translation_key');
            self::fail('Strict translation was expected to reject an unknown key.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('missing.translation_key', $exception->getMessage());
            self::assertStringNotContainsString(DIRECTORY_SEPARATOR, $exception->getMessage());
        }
    }

    public function testLegacyLiteralMessagesPassThroughDuringMigration(): void
    {
        $translator = new CatalogTranslator('en', true);

        self::assertSame(
            'Délka musí být alespoň 3 znaky.',
            $translator->translate('Délka musí být alespoň %d znaky.', 3),
        );
    }

    public function testFormFactoryAttachesTranslator(): void
    {
        $translator = new CatalogTranslator('nl');
        $form = (new FormFactory($translator))->create();

        self::assertSame($translator, $form->getTranslator());
    }

    public function testLatteEscapesTranslatedPlaceholderValues(): void
    {
        $translator = new CatalogTranslator('en');
        $template = new Template(new Engine());
        $template->setTranslator($translator, 'en');

        $output = $template->renderToString(
            dirname(__DIR__) . '/Fixtures/Localization/template.latte',
            ['name' => '<script>alert(1)</script>'],
        );

        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $output);
        self::assertStringNotContainsString('<script>', $output);
    }
}
