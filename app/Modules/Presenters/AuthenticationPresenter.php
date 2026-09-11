<?php

declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Forms\AuthenticationFormFactory;
use Nette;
use Nette\Application\Attributes\Persistent;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;

class AuthenticationPresenter extends BasePresenter
{
    #[Inject]
    public AuthenticationFormFactory $authenticationFormFactory;
    /** @persistent */
    #[Persistent]
    public string $backlink = '';

    /**
     * Login form factory.
     */
    protected function createComponentLoginForm(): Form
    {
        $form = $this->authenticationFormFactory->create();
        $form->onSuccess[] = function (Form $form): void {
            $this->restoreRequest($this->backlink);
            $form->getPresenter()->redirect('Dashboard:');
        };

        $this->formToBootstrap3($form);

        return $form;
    }

    public function actionLogout(): void
    {
        $this->getUser()->logout();
    }
}
