<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Forms\AuthenticationFormFactory;
use App\Forms\FormFactory;
use App\Localization\CatalogTranslator;
use Nette\Security\AuthenticationException;
use Nette\Security\Authenticator;
use Nette\Security\User;
use Nette\Security\UserStorage;
use Nette\Utils\ArrayHash;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthenticationFormFactoryTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Uživatelské jméno:', 'Heslo:', 'Zůstat přihlášený', 'Uživatelské jméno nebo heslo není správné.'];
        yield 'English' => ['en', 'Username:', 'Password:', 'Keep me signed in', 'The username or password is incorrect.'];
        yield 'German' => ['de', 'Benutzername:', 'Passwort:', 'Angemeldet bleiben', 'Benutzername oder Passwort ist falsch.'];
        yield 'Dutch' => ['nl', 'Gebruikersnaam:', 'Wachtwoord:', 'Aangemeld blijven', 'De gebruikersnaam of het wachtwoord is onjuist.'];
    }

    #[DataProvider('localeProvider')]
    public function testLoginFormIsTranslatedAndCsrfProtected(
        string $locale,
        string $username,
        string $password,
        string $remember,
        string $invalidCredentials,
    ): void
    {
        $authenticator = $this->createMock(Authenticator::class);
        $authenticator->method('authenticate')->willThrowException(new AuthenticationException());
        $storage = $this->createStub(UserStorage::class);
        $storage->method('getState')->willReturn([false, null, null]);
        $factory = new AuthenticationFormFactory(
            new FormFactory(new CatalogTranslator($locale)),
            new User($storage, $authenticator),
        );

        $form = $factory->create();

        self::assertArrayHasKey('username', $form->getComponents());
        self::assertArrayHasKey('password', $form->getComponents());
        self::assertArrayHasKey('remember', $form->getComponents());
        self::assertArrayHasKey('_token_', $form->getComponents());
        self::assertSame($username, $form['username']->getLabel()?->getText());
        self::assertSame($password, $form['password']->getLabel()?->getText());
        self::assertSame($remember, $form['remember']->getLabelPart()->getText());

        $factory->formSucceeded($form, ArrayHash::from([
            'username' => 'invalid-user',
            'password' => 'invalid-password',
            'remember' => false,
        ]));
        self::assertContains($invalidCredentials, $form->getErrors());
    }
}
