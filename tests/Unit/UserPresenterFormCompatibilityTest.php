<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Localization\CatalogTranslator;
use App\Model\entities\Role;
use App\Model\repositories\RoleRepository;
use App\Modules\Admin\Presenters\UsersPresenter;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class UserPresenterFormCompatibilityTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string, array<int, string>>} */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Uživatelské jméno:', 'Zobrazované jméno:', 'Role:', [1 => 'Administrátor', 2 => 'Uživatel', 3 => 'Čtenář', 4 => 'custom']];
        yield 'English' => ['en', 'Username:', 'Display name:', 'Roles:', [1 => 'Administrator', 2 => 'Member', 3 => 'Read-only guest', 4 => 'custom']];
        yield 'German' => ['de', 'Benutzername:', 'Anzeigename:', 'Rollen:', [1 => 'Administrator/in', 2 => 'Mitglied', 3 => 'Gast mit Leserechten', 4 => 'custom']];
        yield 'Dutch' => ['nl', 'Gebruikersnaam:', 'Weergavenaam:', 'Rollen:', [1 => 'Beheerder', 2 => 'Lid', 3 => 'Gast met alleen-lezenrechten', 4 => 'custom']];
    }

    /** @param array<int, string> $roleLabels */
    #[DataProvider('localeProvider')]
    public function testUserFormIsTranslatedAndKeepsStableRoleIds(
        string $locale,
        string $username,
        string $displayName,
        string $roles,
        array $roleLabels,
    ): void {
        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->method('findChoices')->willReturn([
            1 => 'admin',
            2 => 'user',
            3 => 'guest',
            4 => 'custom',
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Role::class)->willReturn($roleRepository);

        $presenter = new UsersPresenter(
            new Passwords(),
            $this->createStub(UserService::class),
            $entityManager,
        );
        $presenter->translator = new CatalogTranslator($locale, true);
        $method = new ReflectionMethod(UsersPresenter::class, 'createComponentCreateForm');

        /** @var Form $form */
        $form = $method->invoke($presenter);

        self::assertSame(
            [
                '_token_',
                'username',
                'name',
                'surname',
                'displayName',
                'email',
                'roleIds',
                'password',
                'passwordVerify',
                'send',
            ],
            array_keys($form->getComponents()),
        );
        self::assertSame($username, $form['username']->getLabel()?->getText());
        self::assertSame($displayName, $form['displayName']->getLabel()?->getText());
        self::assertSame($roles, $form['roleIds']->getLabel()?->getText());
        self::assertSame($roleLabels, $form['roleIds']->getItems());
        self::assertSame(
            (new CatalogTranslator($locale))->translate('users.form.password'),
            $form['password']->getLabel()?->getText(),
        );

        $form->setValues([
            'username' => 'x',
            'name' => 'Test',
            'surname' => 'User',
            'displayName' => 'Test User',
            'email' => 'test@example.test',
            'roleIds' => [],
            'password' => '123',
            'passwordVerify' => '123',
        ]);
        $form->validate();
        $translator = new CatalogTranslator($locale);
        self::assertContains($translator->translate('users.form.username_min_length'), $form->getErrors());
        self::assertContains($translator->translate('users.form.roles_required'), $form->getErrors());
        self::assertContains($translator->translate('users.form.password_min_length'), $form->getErrors());
    }

    public function testAuthorizationAndSelfDeleteProtectionUseStableValues(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/app/Modules/Admin/Presenters/UsersPresenter.php',
        );
        self::assertIsString($source);
        self::assertStringContainsString("isInRole('admin')", $source);
        self::assertStringContainsString('(int) $this->getUser()->getId() === $userId', $source);
        self::assertStringContainsString("translate('users.delete_self')", $source);
    }
}
