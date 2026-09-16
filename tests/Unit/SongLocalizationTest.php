<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Localization\CatalogTranslator;
use App\Modules\Presenters\SongsPresenter;
use App\Services\SongFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use Nette\Security\UserStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SongLocalizationTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Seznam skladeb', 'Noty pro sbor', 'Tento typ souboru není povolen.', 'Nemáte potřebná oprávnění.'];
        yield 'English' => ['en', 'Song list', 'Choir sheet music', 'This file type is not allowed.', 'You do not have the required permission.'];
        yield 'German' => ['de', 'Liste der Musikstücke', 'Chornoten', 'Dieser Dateityp ist nicht zulässig.', 'Sie verfügen nicht über die erforderliche Berechtigung.'];
        yield 'Dutch' => ['nl', 'Lijst met muziekstukken', 'Koorpartituren', 'Dit bestandstype is niet toegestaan.', 'U hebt niet de vereiste toestemming.'];
    }

    #[DataProvider('localeProvider')]
    public function testSongPagesUploadErrorsAndPermissionsTranslate(
        string $locale,
        string $listTitle,
        string $choirSheetMusic,
        string $typeError,
        string $permissionError,
    ): void {
        $translator = new CatalogTranslator($locale, true);

        self::assertSame($listTitle, $translator->translate('songs.page.list'));
        self::assertSame(
            $choirSheetMusic,
            $translator->translate('songs.files.category.choir_sheet_music'),
        );
        self::assertSame($typeError, $translator->translate('songs.files.error.type_not_allowed'));
        self::assertSame($permissionError, $translator->translate('common.permission_denied'));

        $presenter = $this->createPresenter($translator, ['user']);
        $method = new ReflectionMethod(SongsPresenter::class, 'assertAdmin');
        try {
            $method->invoke($presenter);
            self::fail('A non-admin user must not pass the song administration check.');
        } catch (Exception $exception) {
            self::assertSame($permissionError, $exception->getMessage());
        }
    }

    public function testSongAuthorizationUsesStableAdminRoleCode(): void
    {
        $presenter = $this->createPresenter(new CatalogTranslator('de', true), ['admin']);
        $method = new ReflectionMethod(SongsPresenter::class, 'assertAdmin');

        $method->invoke($presenter);
        self::addToAssertionCount(1);
    }

    public function testSongUiUsesSemanticKeysInsteadOfCzechLiterals(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = [
            $root . '/app/Modules/Presenters/SongsPresenter.php',
            $root . '/app/Modules/templates/Songs/default.latte',
            $root . '/app/Modules/templates/Songs/show.latte',
            $root . '/app/Modules/templates/Songs/partials/list-files.latte',
        ];
        $source = '';
        foreach ($paths as $path) {
            $contents = file_get_contents($path);
            self::assertIsString($contents);
            $source .= $contents;
        }

        foreach ([
            'Seznam skladeb',
            'Název musíte zadat',
            'Noty - sbor',
            'Nahrávky',
            'Soubor byl smazán',
            '>Stáhnout<',
        ] as $legacyText) {
            self::assertStringNotContainsString($legacyText, $source);
        }

        self::assertStringContainsString('songs.page.list', $source);
        self::assertStringContainsString('songs.files.error.size_exceeded', $source);
        self::assertStringContainsString('songs.files.empty', $source);
    }

    /** @param list<string> $roles */
    private function createPresenter(CatalogTranslator $translator, array $roles): SongsPresenter
    {
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $presenter = new SongsPresenter(
            $entityManager,
            new SongFileStorage($entityManager),
        );
        $presenter->translator = $translator;

        $userStorage = $this->createStub(UserStorage::class);
        $userStorage->method('getState')->willReturn([
            true,
            new SimpleIdentity(1, $roles),
            null,
        ]);
        $presenter->injectPrimary(
            $this->createStub(IRequest::class),
            $this->createStub(IResponse::class),
            user: new User($userStorage),
        );

        return $presenter;
    }
}
