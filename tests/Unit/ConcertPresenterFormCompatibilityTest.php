<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Song;
use App\Model\repositories\SongRepository;
use App\Modules\Presenters\ConcertsPresenter;
use App\Services\ConcertSongService;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class ConcertPresenterFormCompatibilityTest extends TestCase
{
    public function testConcertFormUsesEnglishKeysAndKeepsCzechLabels(): void
    {
        $songRepository = $this->createMock(SongRepository::class);
        $songRepository->method('findChoices')->willReturn([2 => 'Ave Maria', 5 => 'Hallelujah']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Song::class)->willReturn($songRepository);

        $presenter = new ConcertsPresenter(
            $this->createStub(ConcertSongService::class),
            $entityManager,
        );
        $method = new ReflectionMethod(ConcertsPresenter::class, 'createComponentCreateForm');

        /** @var Form $form */
        $form = $method->invoke($presenter);

        self::assertSame(
            ['_token_', 'title', 'scheduledAt', 'songIds', 'note', 'send'],
            array_keys($form->getComponents()),
        );
        self::assertSame('Název:', $form['title']->getCaption());
        self::assertSame('Kdy:', $form['scheduledAt']->getCaption());
        self::assertSame('Skladby:', $form['songIds']->getCaption());
        self::assertSame([2 => 'Ave Maria', 5 => 'Hallelujah'], $form['songIds']->getItems());
        self::assertSame('Poznámka:', $form['note']->getCaption());
    }
}
