<?php

namespace App\Forms;

use Nette\Application\UI\Form;
use Nette\SmartObject;

class FormFactory
{
    /**
     * @return Form
     */
    public function create()
    {
        $form = new Form();
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
        return $form;
    }
}
