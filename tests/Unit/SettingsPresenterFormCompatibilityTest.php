<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Presenters\SettingsPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class SettingsPresenterFormCompatibilityTest extends TestCase
{
    public function testProfileFormUsesEnglishTechnicalKeysAndPreservesCzechLabels(): void
    {
        $form = $this->createForm('createComponentProfileForm');

        self::assertArrayHasKey('displayName', $form->getComponents());
        self::assertArrayHasKey('notificationsEnabled', $form->getComponents());
        self::assertArrayNotHasKey('nazev', $form->getComponents());
        self::assertArrayNotHasKey('notifikace', $form->getComponents());
        self::assertSame('Název:', $form['displayName']->getCaption());
        self::assertSame('Notifikace mailem aktivní', $form['notificationsEnabled']->getCaption());
    }

    public function testPasswordFormKeepsExistingFields(): void
    {
        $form = $this->createForm('createComponentPasswordForm');

        self::assertArrayHasKey('password', $form->getComponents());
        self::assertArrayHasKey('passwordVerify', $form->getComponents());
    }

    private function createForm(string $factoryMethod): Form
    {
        $presenter = new SettingsPresenter(
            new Passwords(),
            $this->createStub(EntityManagerInterface::class),
        );
        $method = new ReflectionMethod(SettingsPresenter::class, $factoryMethod);

        /** @var Form $form */
        $form = $method->invoke($presenter);

        return $form;
    }
}
