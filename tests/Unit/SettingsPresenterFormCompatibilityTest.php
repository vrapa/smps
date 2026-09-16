<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Localization\CatalogTranslator;
use App\Modules\Presenters\SettingsPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SettingsPresenterFormCompatibilityTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Zobrazované jméno:', 'E-mailová upozornění jsou aktivní', 'Nové heslo:'];
        yield 'English' => ['en', 'Display name:', 'Email notifications are enabled', 'New password:'];
        yield 'German' => ['de', 'Anzeigename:', 'E-Mail-Benachrichtigungen sind aktiviert', 'Neues Passwort:'];
        yield 'Dutch' => ['nl', 'Weergavenaam:', 'E-mailmeldingen zijn ingeschakeld', 'Nieuw wachtwoord:'];
    }

    #[DataProvider('localeProvider')]
    public function testSettingsFormsAreTranslatedAndKeepStableFields(
        string $locale,
        string $displayName,
        string $notifications,
        string $newPassword,
    ): void {
        $form = $this->createForm('createComponentProfileForm', $locale);

        self::assertArrayHasKey('displayName', $form->getComponents());
        self::assertArrayHasKey('notificationsEnabled', $form->getComponents());
        self::assertArrayNotHasKey('nazev', $form->getComponents());
        self::assertArrayNotHasKey('notifikace', $form->getComponents());
        self::assertSame($displayName, $form['displayName']->getLabel()?->getText());
        self::assertSame($notifications, $form['notificationsEnabled']->getLabelPart()->getText());

        $form = $this->createForm('createComponentPasswordForm', $locale);

        self::assertArrayHasKey('password', $form->getComponents());
        self::assertArrayHasKey('passwordVerify', $form->getComponents());
        self::assertSame($newPassword, $form['password']->getLabel()?->getText());

        $form->setValues(['password' => 'abcdef', 'passwordVerify' => 'ghijkl']);
        $form->validate();
        self::assertContains(
            (new CatalogTranslator($locale))->translate('users.form.password_mismatch'),
            $form->getErrors(),
        );
    }

    private function createForm(string $factoryMethod, string $locale): Form
    {
        $presenter = new SettingsPresenter(
            new Passwords(),
            $this->createStub(EntityManagerInterface::class),
        );
        $presenter->translator = new CatalogTranslator($locale, true);
        $method = new ReflectionMethod(SettingsPresenter::class, $factoryMethod);

        /** @var Form $form */
        $form = $method->invoke($presenter);

        return $form;
    }
}
