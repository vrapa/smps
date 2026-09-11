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
        $this->pageName = 'Seznam uživatelů';
        $this->showPageName = 'Uživatel';

        if (!$this->getUser()->isInRole('admin')) {
            $this->flashMessage('Vstup do této sekce je umožněn pouze pro admina !', 'alert-success');
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

    public function renderEdit(int $id): void
    {

        $this->getTemplate()->pageName = $this->showPageName . ' - úpravy';

        $this->getTemplate()->editedUser = $this->getUserEntity($id);
    }

    public function renderShow(int $id): void
    {

        $this->getTemplate()->pageName = $this->showPageName . ' - náhled';

        $this->getTemplate()->displayedUser = $this->getUserEntity($id);
    }

    protected function createComponentEditForm(): Form
    {
        $form = $this->getFormBase();

        $form->addSubmit('send', 'Uložit úpravy');
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
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

        $this->flashMessage("Záznam byl úspěšně aktualizován.", 'alert-success');
    }

    protected function createComponentCreateForm(): Form
    {
        $form = $this->getFormBase();

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zvolte si heslo')
            ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaky', 6);

        $form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
            ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
            ->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password']);

        $form->addSubmit('send', 'Uložit úpravy');
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
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

        $this->flashMessage('Záznam byl vložen.', 'alert-success');
        $this->redirect('default');
    }


    protected function createComponentDeleteForm(): Multiplier
    {
        return new Multiplier(function (string $id): Form {
            $form = $this->createForm();
            $form->addSubmit('send', 'Smazat');
            $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
            $form->onSuccess[] = function () use ($id): void {
                $userId = (int) $id;
                if (!$this->getUser()->isInRole('admin')) {
                    throw new \Exception("Nemáte potřebná oprávnění!");
                }
                if ((int) $this->getUser()->getId() === $userId) {
                    $this->flashMessage('Nemůžete smazat sami sebe!', 'alert-danger');
                    $this->redirect('this');
                }

                $this->userService->deleteUser($this->getUserEntity($userId));
                $this->flashMessage('Zvolený záznam byl smazán.', 'alert-success');
                $this->redirect('this');
            };

            return $form;
        });
    }

    private function getFormBase(): Form
    {
        $form = $this->createForm();

        $form->addText('username', 'Uživatel:')
            ->setRequired('Uživatele musíte zadat !')
            ->addRule(Form::MIN_LENGTH, 'Délka musí být alespoň 3 znaky !', 3)
            ->addRule(Form::MAX_LENGTH, 'Délka může být maximálně 20 znaků !', 20);

        $form->addText('name', 'Jméno:')
            ->setRequired('Jméno musíte zadat !')
            ->addRule(Form::MAX_LENGTH, 'Délka nesmí překročit 30 znaků !', 30);

        $form->addText('surname', 'Příjmení:')
            ->setRequired('Příjmení musíte zadat !')
            ->addRule(Form::MAX_LENGTH, 'Délka nesmí překročit 30 znaků !', 30);

        $form->addText('displayName', 'Název:')
            ->setRequired('Název musíte zadat !')
            ->addRule(Form::MAX_LENGTH, 'Délka nesmí překročit 30 znaků !', 30);

        $form->addText('email', 'Email:')
            ->addRule(Form::FILLED, 'Zadejte email')
            ->addRule(Form::EMAIL, 'Email nemá správný formát');

        /** @var RoleRepository $roleRepository */
        $roleRepository = $this->entityManager->getRepository(Role::class);

        $form->addCheckboxList('roleIds', 'Výběr rolí:', $roleRepository->findChoices())
            ->setRequired('Musíte vybrat nějakou roli !');

        return $form;
    }

    private function getUserEntity(int $id): User
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);
        if ($user === null) {
            $this->error('Záznam nebyl nalezen');
        }

        return $user;
    }
}
