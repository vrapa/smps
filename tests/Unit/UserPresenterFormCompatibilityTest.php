<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Model\entities\Role;
use App\Model\repositories\RoleRepository;
use App\Modules\Admin\Presenters\UsersPresenter;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class UserPresenterFormCompatibilityTest extends TestCase
{
    public function testUserFormUsesEnglishKeysAndKeepsCzechLabels(): void
    {
        $roleRepository = $this->createMock(RoleRepository::class);
        $roleRepository->method('findChoices')->willReturn([1 => 'admin', 2 => 'member']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(Role::class)->willReturn($roleRepository);

        $presenter = new UsersPresenter(
            new Passwords(),
            $this->createStub(UserService::class),
            $entityManager,
        );
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
        self::assertSame('Uživatel:', $form['username']->getCaption());
        self::assertSame('Název:', $form['displayName']->getCaption());
        self::assertSame('Výběr rolí:', $form['roleIds']->getCaption());
        self::assertSame([1 => 'admin', 2 => 'member'], $form['roleIds']->getItems());
        self::assertSame('Heslo:', $form['password']->getCaption());
    }
}
