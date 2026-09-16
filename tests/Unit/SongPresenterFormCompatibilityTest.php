<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Localization\CatalogTranslator;
use App\Modules\Presenters\SongsPresenter;
use Nette\Application\UI\Form;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class SongPresenterFormCompatibilityTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Název:', 'Autor:', 'Aktivní:', 'Soubor:', 'Popis souboru:'];
        yield 'English' => ['en', 'Title:', 'Composer / author:', 'Active:', 'File:', 'File description:'];
        yield 'German' => ['de', 'Titel:', 'Komponist/in oder Autor/in:', 'Aktiv:', 'Datei:', 'Dateibeschreibung:'];
        yield 'Dutch' => ['nl', 'Titel:', 'Componist / auteur:', 'Actief:', 'Bestand:', 'Bestandsbeschrijving:'];
    }

    #[DataProvider('localeProvider')]
    public function testSongFormIsTranslatedAndKeepsStableFieldNames(
        string $locale,
        string $title,
        string $author,
        string $active,
        string $file,
        string $description,
    ): void {
        $form = $this->invokeFormFactory('createComponentCreateForm', $locale);

        self::assertSame(
            ['_token_', 'title', 'author', 'active', 'send'],
            array_keys($form->getComponents()),
        );
        self::assertSame($title, $form['title']->getLabel()?->getText());
        self::assertSame($author, $form['author']->getLabel()?->getText());
        self::assertSame($active, $form['active']->getLabelPart()->getText());

        $form->setValues(['title' => 'x', 'author' => '', 'active' => true]);
        $form->validate();
        self::assertContains(
            (new CatalogTranslator($locale))->translate('songs.form.title_min_length'),
            $form->getErrors(),
        );
        self::assertContains(
            (new CatalogTranslator($locale))->translate('songs.form.author_required'),
            $form->getErrors(),
        );
    }

    /** @return iterable<string, array{string, string}> */
    public static function uploadFormProvider(): iterable
    {
        yield 'choir sheet music' => ['createComponentChoirSheetMusicUploadForm', 'noty-sbor'];
        yield 'orchestra sheet music' => ['createComponentOrchestraSheetMusicUploadForm', 'noty-orchestr'];
        yield 'recordings' => ['createComponentRecordingsUploadForm', 'nahravky'];
    }

    /** @return iterable<string, array{string, string, string, string, string}> */
    public static function localizedUploadFormProvider(): iterable
    {
        foreach (self::localeProvider() as $localeName => [$locale, , , , $file, $description]) {
            foreach (self::uploadFormProvider() as $formName => [$factoryMethod, $category]) {
                yield $localeName . ' ' . $formName => [
                    $locale,
                    $factoryMethod,
                    $category,
                    $file,
                    $description,
                ];
            }
        }
    }

    #[DataProvider('localizedUploadFormProvider')]
    public function testUploadFormsAreTranslatedAndPreserveStoredCategory(
        string $locale,
        string $factoryMethod,
        string $category,
        string $file,
        string $description,
    ): void {
        $form = $this->invokeFormFactory($factoryMethod, $locale);

        self::assertSame(
            ['_token_', 'fileUpload', 'description', 'category', 'send'],
            array_keys($form->getComponents()),
        );
        self::assertSame($file, $form['fileUpload']->getLabel()?->getText());
        self::assertSame($description, $form['description']->getLabel()?->getText());
        self::assertSame($category, $form['category']->getValue());
    }

    private function invokeFormFactory(string $methodName, string $locale): Form
    {
        $presenter = (new ReflectionClass(SongsPresenter::class))->newInstanceWithoutConstructor();
        $presenter->translator = new CatalogTranslator($locale, true);
        $method = new ReflectionMethod(SongsPresenter::class, $methodName);

        /** @var Form $form */
        $form = $method->invoke($presenter);

        return $form;
    }
}
