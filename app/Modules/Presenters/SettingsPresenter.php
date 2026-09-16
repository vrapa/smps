<?php

declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Model\entities\User;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;

class SettingsPresenter extends BasePresenter
{
    private string $pageName;

    public function __construct(protected Passwords $passwords, private EntityManagerInterface $entityManager,)
    {
    }
    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->getTemplate()->pageName = $this->pageName;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->pageName = $this->translator->translate('settings.page.title');
    }

    public function actionDefault(): void
    {
        $this->getTemplate()->profileUser = $this->entityManager->getRepository(User::class)->find($this->getUser()->id);
    }

    public function actionEdit(): void
    {
        $profileUser = $this->entityManager->getRepository(User::class)->find($this->getUser()->id);
        $this['profileForm']->setDefaults([
            'name' => $profileUser->getName(),
            'surname' => $profileUser->getSurname(),
            'displayName' => $profileUser->getDisplayName(),
            'email' => $profileUser->getEmail(),
            'notificationsEnabled' => $profileUser->areNotificationsEnabled(),
        ]);

        $this['passwordForm']->setDefaults([
            'password' => '',
            'passwordVerify' => '',
        ]);
    }

    protected function createComponentProfileForm(): Form
    {
        $form = $this->createForm();

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

        $form->addCheckbox('notificationsEnabled', 'settings.form.notifications_enabled');

        $form->addSubmit('send', 'settings.form.save_profile');
        $form->addProtection('form.csrf_expired');
        $form->onSuccess[] = [$this, 'profileFormSucceeded'];
        $this->formToBootstrap3($form);

        return $form;
    }

    public function profileFormSucceeded(Form $form, ArrayHash $values): void
    {
        $profileUser = $this->entityManager->getRepository(User::class)->find($this->getUser()->id);
        $profileUser->setName($values['name']);
        $profileUser->setSurname($values['surname']);
        $profileUser->setDisplayName($values['displayName']);
        $profileUser->setEmail($values['email']);
        $profileUser->setNotificationsEnabled($values['notificationsEnabled']);
        $this->entityManager->persist($profileUser);
        $this->entityManager->flush();

        $this->flashMessage($this->translator->translate('settings.flash.profile_saved'), 'alert-success');
        $this->redirect('default');
    }


    protected function createComponentPasswordForm(): Form
    {
        $form = $this->createForm();

        $form->addPassword('password', 'settings.form.new_password')
            ->setRequired('users.form.password_required')
            ->addRule(Form::MIN_LENGTH, 'users.form.password_min_length', 6);

        $form->addPassword('passwordVerify', 'users.form.password_verify')
            ->setRequired('users.form.password_verify_required')
            ->addRule(Form::EQUAL, 'users.form.password_mismatch', $form['password']);

        $form->addSubmit('send', 'settings.form.save_password');
        $form->addProtection('form.csrf_expired');
        $form->onSuccess[] = [$this, 'passwordFormSucceeded'];

        $this->formToBootstrap3($form);

        return $form;
    }

    public function passwordFormSucceeded(Form $form, ArrayHash $values): void
    {
        $profileUser = $this->entityManager->getRepository(User::class)->find($this->getUser()->id);
        $profileUser->setPassword($this->passwords->hash($values['password']));

        $this->entityManager->persist($profileUser);
        $this->entityManager->flush();

        $this->flashMessage($this->translator->translate('settings.flash.password_saved'), 'alert-success');
        $this->redirect('default');
    }
}
