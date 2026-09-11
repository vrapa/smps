<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Forms\AuthenticationFormFactory;
use App\Forms\FormFactory;
use Nette\Security\User;
use Nette\Security\UserStorage;
use PHPUnit\Framework\TestCase;

final class AuthenticationFormFactoryTest extends TestCase
{
    public function testLoginFormPreservesFieldsLabelsAndCsrfProtection(): void
    {
        $factory = new AuthenticationFormFactory(
            new FormFactory(),
            new User($this->createStub(UserStorage::class)),
        );

        $form = $factory->create();

        self::assertArrayHasKey('username', $form->getComponents());
        self::assertArrayHasKey('password', $form->getComponents());
        self::assertArrayHasKey('remember', $form->getComponents());
        self::assertArrayHasKey('_token_', $form->getComponents());
        self::assertSame('Uživatelské jméno:', $form['username']->getCaption());
        self::assertSame('Heslo:', $form['password']->getCaption());
        self::assertSame('Zůstat přihlášený', $form['remember']->getCaption());
    }
}
