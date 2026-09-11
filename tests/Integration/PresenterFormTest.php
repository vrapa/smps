<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Localization\CatalogTranslator;
use App\Model\entities\Concert;
use App\Model\entities\Song;
use App\Model\entities\User as UserEntity;
use App\Services\ConcertSongService;
use App\Services\UserService;
use DateTimeImmutable;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Security\Passwords;
use Nette\Security\User;

final class PresenterFormTest extends DatabaseTestCase
{
    public function testAuthenticatedEditFlowsLoadDatabaseBackedFormDefaults(): void
    {
        $passwords = new Passwords();
        $admin = new UserEntity();
        $admin->setUsername('form-admin');
        $admin->setEmail('form-admin@example.test');
        $admin->setPassword($passwords->hash('correct-password'));
        $admin->setName('Form');
        $admin->setSurname('Admin');
        $admin->setDisplayName('Form Admin');
        (new UserService($this->entityManager))->saveUser($admin, [1]);

        $song = new Song();
        $song->setTitle('Form Song');
        $song->setAuthor('Form Composer');
        $song->setActive(true);
        $song->setCreatedAt(new DateTimeImmutable('2026-08-21 10:00:00'));
        $song->setCreatedBy($admin);
        $this->entityManager->persist($song);
        $this->entityManager->flush();

        $concert = new Concert();
        $concert->setTitle('Form Concert');
        $concert->setScheduledAt(new DateTimeImmutable('2026-10-15 19:30:00'));
        $concert->setNote('Form integration note');
        $concert->setCreatedAt(new DateTimeImmutable('2026-08-21 10:00:00'));
        $concert->setCreatedBy($admin);
        (new ConcertSongService($this->entityManager))->saveConcert($concert, [$song->getId()]);

        $securityUser = $this->container->getByType(User::class);
        $securityUser->login('form-admin', 'correct-password');
        self::assertTrue($securityUser->isInRole('admin'));

        $songPresenter = $this->runPresenter('Songs', 'edit', $song->getId());
        $songForm = $this->getForm($songPresenter, 'editForm');
        self::assertInstanceOf(CatalogTranslator::class, $songForm->getTranslator());
        self::assertSame('Form Song', $songForm['title']->getValue());
        self::assertSame('Form Composer', $songForm['author']->getValue());
        self::assertTrue($songForm['active']->getValue());

        $concertPresenter = $this->runPresenter('Concerts', 'edit', $concert->getId());
        $concertForm = $this->getForm($concertPresenter, 'editForm');
        self::assertSame('Form Concert', $concertForm['title']->getValue());
        self::assertSame('2026-10-15T19:30', $concertForm['scheduledAt']->getValue());
        self::assertSame([$song->getId()], $concertForm['songIds']->getValue());
        self::assertSame([$song->getId() => 'Form Song'], $concertForm['songIds']->getItems());
        self::assertSame('Form integration note', $concertForm['note']->getValue());

        $userPresenter = $this->runPresenter('Admin:Users', 'edit', $admin->getId());
        $userForm = $this->getForm($userPresenter, 'editForm');
        self::assertSame('form-admin', $userForm['username']->getValue());
        self::assertSame('Form Admin', $userForm['displayName']->getValue());
        self::assertSame([1], $userForm['roleIds']->getValue());
        self::assertSame([
            1 => 'admin',
            3 => 'guest',
            2 => 'user',
        ], $userForm['roleIds']->getItems());

        $loginPresenter = $this->runPresenter('Authentication', 'login');
        $loginForm = $this->getForm($loginPresenter, 'loginForm');
        self::assertSame(
            ['_token_', 'username', 'password', 'remember', 'send'],
            array_keys($loginForm->getComponents()),
        );
        self::assertFalse($loginForm['remember']->getValue());
    }

    private function runPresenter(string $name, string $action, ?int $id = null): Presenter
    {
        $parameters = ['action' => $action];
        if ($id !== null) {
            $parameters['id'] = $id;
        }

        $factory = $this->container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter($name);
        self::assertInstanceOf(Presenter::class, $presenter);
        $presenter->autoCanonicalize = false;
        self::assertInstanceOf(
            TextResponse::class,
            $presenter->run(new Request($name, 'GET', $parameters)),
        );

        return $presenter;
    }

    private function getForm(Presenter $presenter, string $name): Form
    {
        $component = $presenter->getComponent($name);
        self::assertInstanceOf(Form::class, $component);

        return $component;
    }
}
