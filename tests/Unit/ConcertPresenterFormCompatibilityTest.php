<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Localization\CatalogTranslator;
use App\Localization\LocalizedDateTimeFormatter;
use App\Model\entities\Song;
use App\Model\repositories\SongRepository;
use App\Modules\Presenters\ConcertsPresenter;
use App\Services\ConcertSongService;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ConcertPresenterFormCompatibilityTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Název:', 'Datum a čas:', 'Skladby:', 'Poznámka:'];
        yield 'English' => ['en', 'Title:', 'Date and time:', 'Songs:', 'Notes:'];
        yield 'German' => ['de', 'Titel:', 'Datum und Uhrzeit:', 'Musikstücke:', 'Notizen:'];
        yield 'Dutch' => ['nl', 'Titel:', 'Datum en tijd:', 'Muziekstukken:', 'Notities:'];
    }

    #[DataProvider('localeProvider')]
    public function testConcertFormIsTranslatedAndKeepsStableFields(
        string $locale,
        string $title,
        string $scheduledAt,
        string $songs,
        string $note,
    ): void {
        $songRepository = $this->createMock(SongRepository::class);
        $songRepository->method('findChoices')->willReturn([2 => 'Ave Maria', 5 => 'Hallelujah']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Song::class)->willReturn($songRepository);

        $presenter = new ConcertsPresenter(
            $this->createStub(ConcertSongService::class),
            $entityManager,
            new LocalizedDateTimeFormatter($locale, 'Europe/Prague'),
        );
        $presenter->translator = new CatalogTranslator($locale, true);
        $method = new ReflectionMethod(ConcertsPresenter::class, 'createComponentCreateForm');

        /** @var Form $form */
        $form = $method->invoke($presenter);

        self::assertSame(
            ['_token_', 'title', 'scheduledAt', 'songIds', 'note', 'send'],
            array_keys($form->getComponents()),
        );
        self::assertSame($title, $form['title']->getLabel()?->getText());
        self::assertSame($scheduledAt, $form['scheduledAt']->getLabel()?->getText());
        self::assertSame($songs, $form['songIds']->getLabel()?->getText());
        self::assertSame([2 => 'Ave Maria', 5 => 'Hallelujah'], $form['songIds']->getItems());
        self::assertSame($note, $form['note']->getLabel()?->getText());
    }
}
