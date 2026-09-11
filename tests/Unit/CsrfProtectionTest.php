<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Forms\FormFactory;
use App\Modules\Presenters\SettingsPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class CsrfProtectionTest extends TestCase
{
    public function testSharedFormFactoryAddsCsrfProtection(): void
    {
        self::assertArrayHasKey('_token_', (new FormFactory())->create()->getComponents());
    }

    /** @return iterable<string, array{string}> */
    public static function settingsFormProvider(): iterable
    {
        yield 'profile edit' => ['createComponentProfileForm'];
        yield 'password change' => ['createComponentPasswordForm'];
    }

    /** @dataProvider settingsFormProvider */
    public function testSettingsFormsAddCsrfProtection(string $factoryMethod): void
    {
        $presenter = new SettingsPresenter(
            new Passwords(),
            $this->createStub(EntityManagerInterface::class),
        );
        $method = new ReflectionMethod(SettingsPresenter::class, $factoryMethod);

        /** @var Form $form */
        $form = $method->invoke($presenter);

        self::assertArrayHasKey('_token_', $form->getComponents());
    }
}
