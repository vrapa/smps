<?php

namespace App\Modules\Presenters;

use Exception;
use Nette;
use Nette\Forms\Container;
use Nette\Forms\Controls;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    protected function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn() && !in_array($this->getName(), ['Authentication'], true)) {
            $this->redirect('Authentication:login', ['backlink' => $this->storeRequest()]);
        }
    }

    protected function formToBootstrap3($form): void
    {

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
}
