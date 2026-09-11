<?php

namespace App\Modules\Presenters;

use App\Localization\CatalogTranslator;
use App\Localization\SupportedLocale;
use Exception;
use Nette;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use Nette\Forms\Container;
use Nette\Forms\Controls;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    #[Inject]
    public CatalogTranslator $translator;

    public function beforeRender(): void
    {
        parent::beforeRender();

        $translator = $this->getTranslator();
        $this->getTemplate()->setTranslator($translator, $translator->getLocale());
        $this->getTemplate()->locale = $translator->getLocale();
    }

    protected function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn() && !in_array($this->getName(), ['Authentication'], true)) {
            $this->redirect('Authentication:login', ['backlink' => $this->storeRequest()]);
        }
    }

    protected function formToBootstrap3($form): void
    {
        $form->setTranslator($this->getTranslator());
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-9';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-3 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';

        // make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-horizontal');
        foreach ($form->getControls() as $control) {
            if ($control instanceof Controls\Button) {
                $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
                $usedPrimary = true;
            } elseif (
                $control instanceof Controls\TextBase
                || $control instanceof Controls\SelectBox
                || $control instanceof Controls\MultiSelectBox
            ) {
                $control->getControlPrototype()->addClass('form-control');
            } elseif (
                $control instanceof Controls\Checkbox
                || $control instanceof Controls\CheckboxList
                || $control instanceof Controls\RadioList
            ) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }
    }

    protected function formToBootstrapInline3($form): void
    {
        $form->setTranslator($this->getTranslator());
        $renderer = $form->getRenderer();
        $renderer->wrappers['controls']['container'] = null;
        $renderer->wrappers['pair']['container'] = 'div class=form-group';
        $renderer->wrappers['pair']['.error'] = 'has-error';
        $renderer->wrappers['control']['container'] = 'div class=col-sm-7';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 control-label"';
        $renderer->wrappers['control']['description'] = 'span class=help-block';
        $renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';

        // make form and controls compatible with Twitter Bootstrap
        $form->getElementPrototype()->class('form-inline');
        foreach ($form->getControls() as $control) {
            if ($control instanceof Controls\Button) {
                $control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
                $usedPrimary = true;
            } elseif (
                $control instanceof Controls\TextBase
                || $control instanceof Controls\SelectBox
                || $control instanceof Controls\MultiSelectBox
            ) {
                $control->getControlPrototype()->addClass('form-control');
            } elseif (
                $control instanceof Controls\Checkbox
                || $control instanceof Controls\CheckboxList
                || $control instanceof Controls\RadioList
            ) {
                $control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
            }
        }
    }

    protected function createForm(): Form
    {
        $form = new Form();
        $form->setTranslator($this->getTranslator());

        return $form;
    }

    private function getTranslator(): CatalogTranslator
    {
        if (!isset($this->translator)) {
            $this->translator = new CatalogTranslator(SupportedLocale::Czech->value);
        }

        return $this->translator;
    }
}
