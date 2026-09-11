<?php

namespace App\Forms;

use App\Localization\CatalogTranslator;
use App\Localization\SupportedLocale;
use Nette\Application\UI\Form;
use Nette\SmartObject;

class FormFactory
{
    private CatalogTranslator $translator;

    public function __construct(?CatalogTranslator $translator = null)
    {
        $this->translator = $translator ?? new CatalogTranslator(SupportedLocale::Czech->value);
    }

    /**
     * @return Form
     */
    public function create()
    {
        $form = new Form();
        $form->setTranslator($this->translator);
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
        return $form;
    }
}
