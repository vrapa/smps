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
        $this->pageName = 'Nastavení';
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

        $form->addCheckbox('notificationsEnabled', 'Notifikace mailem aktivní');

        $form->addSubmit('send', 'Uložit údaje');
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

        $this->flashMessage('Změna byla uložena.', 'alert-success');
        $this->redirect('default');
    }


    protected function createComponentPasswordForm(): Form
    {
        $form = $this->createForm();

        $form->addPassword('password', 'Nové heslo:')
            ->setRequired('Zvolte si heslo')
            ->addRule(Form::MIN_LENGTH, 'Heslo musí mít alespoň %d znaky', 6);

        $form->addPassword('passwordVerify', 'Heslo pro kontrolu:')
            ->setRequired('Zadejte prosím heslo ještě jednou pro kontrolu')
            ->addRule(Form::EQUAL, 'Hesla se neshodují', $form['password']);

        $form->addSubmit('send', 'Uložit změnu hesla');
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

        $this->flashMessage('Změna hesla byla uložena.', 'alert-success');
        $this->redirect('default');
    }
}
