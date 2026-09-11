<?php

declare(strict_types=1);

namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;
use Nette\Utils\ArrayHash;

class AuthenticationFormFactory
{
    public function __construct(
        private FormFactory $formFactory,
        private User $user,
    ) {
    }

    public function create(): Form
    {
        $form = $this->formFactory->create();
        $form->addText('username', 'Uživatelské jméno:')
            ->setRequired('Zadej živatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadej heslo.');

        $form->addCheckbox('remember', 'Zůstat přihlášený');

        $form->addSubmit('send', 'Přihlásit');

        $form->onSuccess[] = [$this, 'formSucceeded'];
        return $form;
    }

    public function formSucceeded(Form $form, ArrayHash $values): void
    {
        if ($values->remember) {
            $this->user->setExpiration('14 days');
        } else {
            $this->user->setExpiration('20 minutes');
        }

        try {
            $this->user->login($values->username, $values->password);
        } catch (AuthenticationException) {
            $form->addError('Jméno nebo heslo jsou chybně zadané.');
        }
    }
}
