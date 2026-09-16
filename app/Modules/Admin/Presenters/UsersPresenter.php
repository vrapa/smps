<?php

declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Model\entities\User;
use App\Model\entities\Role;
use App\Model\repositories\RoleRepository;
use App\Model\repositories\UserRepository;
use App\Modules\Presenters\BasePresenter;
use App\Services\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;
use Nette\Utils\Paginator;

class UsersPresenter extends BasePresenter
{
    private const PER_PAGE = 20;
    private Passwords $passwords;
    private string $pageName;
    private string $showPageName;
    private UserService $userService;

    public function __construct(
        Passwords $passwords,
        UserService $userService,
        private EntityManagerInterface $entityManager,
    ) {
        $this->passwords = $passwords;
        $this->userService = $userService;
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->getTemplate()->pageName = $this->pageName;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->pageName = $this->translator->translate('users.page.list');
        $this->showPageName = $this->translator->translate('users.page.show');

        if (!$this->getUser()->isInRole('admin')) {
            $this->flashMessage($this->translator->translate('users.admin_only'), 'alert-danger');
            $this->redirect('Authentication:login', ['backlink' => $this->storeRequest()]);
        }
    }

    public function renderDefault(int $page = 1): void
    {
        $paginator = new Paginator();
        $paginator->setItemsPerPage(self::PER_PAGE); // the number of records on page
        $paginator->setPage($page);
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        $paginator->setItemCount($userRepository->countAll());

        $this->getTemplate()->paginator = $paginator;


        $this->getTemplate()->users = $userRepository->findPage($paginator->itemsPerPage, $paginator->offset);
    }

    public function actionEdit(int $id): void
    {
        $user = $this->getUserEntity($id);
        $this['editForm']->setDefaults([
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'surname' => $user->getSurname(),
            'displayName' => $user->getDisplayName(),
            'email' => $user->getEmail(),
            'roleIds' => $this->userService->getRoleIdsByUser($id),
        ]);
    }

    public function renderCreate(): void
    {
        $this->getTemplate()->pageName = $this->translator->translate('users.page.create');
    }

    public function renderEdit(int $id): void
    {

        $this->getTemplate()->pageName = $this->translator->translate('users.page.edit');

        $this->getTemplate()->editedUser = $this->getUserEntity($id);
    }

    public function renderShow(int $id): void
    {

        $this->getTemplate()->pageName = $this->showPageName;

        $this->getTemplate()->displayedUser = $this->getUserEntity($id);
    }

    protected function createComponentEditForm(): Form
    {
        $form = $this->getFormBase();

        $form->addSubmit('send', 'users.form.save');
        $form->addProtection('form.csrf_expired');
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        $this->formToBootstrap3($form);

        return $form;
    }

    public function editFormSucceeded(Form $form, ArrayHash $values): void
    {
        $user = $this->getUserEntity((int) $this->getParameter('id'));
        $user->setName($values->name);
        $user->setSurname($values->surname);
        $user->setUsername($values->username);
        $user->setDisplayName($values->displayName);
        $user->setEmail($values->email);

        $this->userService->saveUser($user, $values->roleIds);

        $this->flashMessage($this->translator->translate('users.flash.updated'), 'alert-success');
    }

    protected function createComponentCreateForm(): Form
    {
        $form = $this->getFormBase();

        $form->addPassword('password', 'users.form.password')
            ->setRequired('users.form.password_required')
            ->addRule(Form::MIN_LENGTH, 'users.form.password_min_length', 6);

        $form->addPassword('passwordVerify', 'users.form.password_verify')
            ->setRequired('users.form.password_verify_required')
            ->addRule(Form::EQUAL, 'users.form.password_mismatch', $form['password']);

        $form->addSubmit('send', 'users.form.save');
        $form->addProtection('form.csrf_expired');
        $form->onSuccess[] = [$this, 'createFormSucceeded'];

        $this->formToBootstrap3($form);

        return $form;
    }

    public function createFormSucceeded(Form $form, ArrayHash $values): void
    {
        $user = new User();
        $user->setName($values->name);
        $user->setSurname($values->surname);
        $user->setUsername($values->username);
        $user->setDisplayName($values->displayName);
        $user->setEmail($values->email);
        $user->setPassword($this->passwords->hash($values->password));

        $this->userService->saveUser($user, $values->roleIds);

        $this->flashMessage($this->translator->translate('users.flash.created'), 'alert-success');
        $this->redirect('default');
    }


    protected function createComponentDeleteForm(): Multiplier
    {
        return new Multiplier(function (string $id): Form {
            $form = $this->createForm();
            $form->addSubmit('send', 'common.delete');
            $form->addProtection('form.csrf_expired');
            $form->onSuccess[] = function () use ($id): void {
                $userId = (int) $id;
                if (!$this->getUser()->isInRole('admin')) {
                    throw new \Exception($this->translator->translate('common.permission_denied'));
                }
                if ((int) $this->getUser()->getId() === $userId) {
                    $this->flashMessage($this->translator->translate('users.delete_self'), 'alert-danger');
                    $this->redirect('this');
                }

                $this->userService->deleteUser($this->getUserEntity($userId));
                $this->flashMessage($this->translator->translate('users.flash.deleted'), 'alert-success');
                $this->redirect('this');
            };

            return $form;
        });
    }

    private function getFormBase(): Form
    {
        $form = $this->createForm();

        $form->addText('username', 'users.form.username')
            ->setRequired('users.form.username_required')
            ->addRule(Form::MIN_LENGTH, 'users.form.username_min_length', 3)
            ->addRule(Form::MAX_LENGTH, 'users.form.username_max_length', 20);

        $form->addText('name', 'users.form.name')
            ->setRequired('users.form.name_required')
            ->addRule(Form::MAX_LENGTH, 'users.form.name_max_length', 30);

        $form->addText('surname', 'users.form.surname')
            ->setRequired('users.form.surname_required')
            ->addRule(Form::MAX_LENGTH, 'users.form.surname_max_length', 30);

        $form->addText('displayName', 'users.form.display_name')
            ->setRequired('users.form.display_name_required')
            ->addRule(Form::MAX_LENGTH, 'users.form.display_name_max_length', 30);

        $form->addText('email', 'users.form.email')
            ->addRule(Form::FILLED, 'users.form.email_required')
            ->addRule(Form::EMAIL, 'users.form.email_invalid');

        /** @var RoleRepository $roleRepository */
        $roleRepository = $this->entityManager->getRepository(Role::class);

        $form->addCheckboxList(
            'roleIds',
            'users.form.roles',
            $this->translateRoleChoices($roleRepository->findChoices()),
        )->setRequired('users.form.roles_required');

        return $form;
    }

    private function getUserEntity(int $id): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if ($user === null) {
            $this->error($this->translator->translate('common.record_not_found'));
        }

        return $user;
    }

    /**
     * @param array<int, string> $choices
     * @return array<int, string>
     */
    private function translateRoleChoices(array $choices): array
    {
        foreach ($choices as $id => $code) {
            $choices[$id] = match ($code) {
                'admin' => $this->translator->translate('roles.admin'),
                'user' => $this->translator->translate('roles.user'),
                'guest' => $this->translator->translate('roles.guest'),
                default => $code,
            };
        }

        return $choices;
    }
}
