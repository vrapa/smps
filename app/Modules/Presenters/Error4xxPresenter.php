<?php

declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Configuration\PublicSettings;
use App\Localization\CatalogTranslator;
use Nette;
use Nette\DI\Attributes\Inject;

final class Error4xxPresenter extends Nette\Application\UI\Presenter
{
    #[Inject]
    public CatalogTranslator $translator;

    #[Inject]
    public PublicSettings $publicSettings;

    public function startup(): void
    {
        parent::startup();
        if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
            $this->error();
        }
    }

    public function renderDefault(Nette\Application\BadRequestException $exception): void
    {
        $this->getTemplate()->setTranslator($this->translator, $this->translator->getLocale());
        $this->getTemplate()->locale = $this->translator->getLocale();
        $this->getTemplate()->applicationName = $this->publicSettings->getApplicationName();

        // load template 403.latte or 404.latte or ... 4xx.latte
        $templatesDirectory = dirname(__DIR__) . '/templates/Error';
        $file = $templatesDirectory . "/{$exception->getCode()}.latte";
        $this->template->setFile(is_file($file) ? $file : $templatesDirectory . '/4xx.latte');
    }
}
