<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Presenters\SongsPresenter;
use Nette\Application\UI\Form;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class SongPresenterFormCompatibilityTest extends TestCase
{
    public function testSongFormUsesEnglishKeysAndKeepsCzechLabels(): void
    {
        $form = $this->invokeFormFactory('createComponentCreateForm');

        self::assertSame(
            ['_token_', 'title', 'author', 'active', 'send'],
            array_keys($form->getComponents()),
        );
        self::assertSame('Název:', $form['title']->getCaption());
        self::assertSame('Autor:', $form['author']->getCaption());
        self::assertSame('Aktivní:', $form['active']->getCaption());
    }

    /** @return iterable<string, string> */
    public static function uploadFormProvider(): iterable
    {
        yield 'choir sheet music' => ['createComponentChoirSheetMusicUploadForm', 'noty-sbor'];
        yield 'orchestra sheet music' => ['createComponentOrchestraSheetMusicUploadForm', 'noty-orchestr'];
        yield 'recordings' => ['createComponentRecordingsUploadForm', 'nahravky'];
    }

    /** @dataProvider uploadFormProvider */
    public function testUploadFormsUseEnglishKeysAndPreserveStoredCategory(
        string $factoryMethod,
        string $category,
    ): void {
        $form = $this->invokeFormFactory($factoryMethod);

        self::assertSame(
            ['_token_', 'fileUpload', 'description', 'category', 'send'],
            array_keys($form->getComponents()),
        );
        self::assertSame('Soubor:', $form['fileUpload']->getCaption());
        self::assertSame('Popis souboru:', $form['description']->getCaption());
        self::assertSame($category, $form['category']->getValue());
    }

    private function invokeFormFactory(string $methodName): Form
    {
        $presenter = (new ReflectionClass(SongsPresenter::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(SongsPresenter::class, $methodName);

        /** @var Form $form */
        $form = $method->invoke($presenter);

        return $form;
    }
}
