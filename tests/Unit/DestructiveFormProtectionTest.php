<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Admin\Presenters\UsersPresenter;
use App\Modules\Presenters\ConcertsPresenter;
use App\Modules\Presenters\SongsPresenter;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class DestructiveFormProtectionTest extends TestCase
{
    /** @return iterable<string, array{class-string, string}> */
    public static function deleteFormProvider(): iterable
    {
        yield 'song' => [SongsPresenter::class, 'createComponentDeleteForm'];
        yield 'song file' => [SongsPresenter::class, 'createComponentFileDeleteForm'];
        yield 'concert' => [ConcertsPresenter::class, 'createComponentDeleteForm'];
        yield 'user' => [UsersPresenter::class, 'createComponentDeleteForm'];
    }

    #[DataProvider('deleteFormProvider')]
    public function testDeleteFormIsPostOnlyAndCsrfProtected(string $presenterClass, string $factoryMethod): void
    {
        $presenter = (new ReflectionClass($presenterClass))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($presenterClass, $factoryMethod);

        /** @var Multiplier $multiplier */
        $multiplier = $method->invoke($presenter);
        /** @var Form $form */
        $form = $multiplier->getComponent('42');

        self::assertSame(Form::POST, $form->getMethod());
        self::assertArrayHasKey('_token_', $form->getComponents());
        self::assertArrayHasKey('send', $form->getComponents());
    }

    public function testLegacyGetDeleteSignalsNoLongerExist(): void
    {
        self::assertFalse(method_exists(SongsPresenter::class, 'handleDelete'));
        self::assertFalse(method_exists(SongsPresenter::class, 'handleFileDelete'));
        self::assertFalse(method_exists(ConcertsPresenter::class, 'handleDelete'));
        self::assertFalse(method_exists(UsersPresenter::class, 'handleDelete'));
    }
}
