<?php

declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Model\entities\Concert;
use App\Model\entities\Song;
use App\Model\entities\User;
use App\Model\repositories\ConcertRepository;
use App\Model\repositories\SongRepository;
use App\Modules\Presenters\BasePresenter;
use App\Services\ConcertSongService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Utils\ArrayHash;
use Nette\Utils\Paginator;
use UnexpectedValueException;

class ConcertsPresenter extends BasePresenter
{
    private const PER_PAGE = 20;
    private string $pageName;
    private string $showPageName;

    private ConcertSongService $concertSongService;

    public function __construct(
        ConcertSongService $concertSongService,
        private EntityManagerInterface $entityManager,
    ) {
        $this->concertSongService = $concertSongService;
    }


    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->getTemplate()->pageName = $this->pageName;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->pageName = 'Seznam koncertů';
        $this->showPageName = 'Koncert';
    }

    public function renderDefault(int $page = 1): void
    {
        $paginator = new Paginator();
        $paginator->setItemsPerPage(self::PER_PAGE); // the number of records on page
        $paginator->setPage($page);

        /** @var ConcertRepository $concertRepository */
        $concertRepository = $this->entityManager->getRepository(Concert::class);

        $paginator->setItemCount($concertRepository->countAll());

        $this->getTemplate()->paginator = $paginator;


        $this->getTemplate()->concerts = $concertRepository->findPage(self::PER_PAGE, $paginator->offset);
    }

    public function actionEdit(int $id): void
    {
        $concert = $this->getConcert($id);
        $this['editForm']->setDefaults([
            'title' => $concert->getTitle(),
            'scheduledAt' => $concert->getScheduledAt()->format('Y-m-d\TH:i'),
            'songIds' => $this->concertSongService->getSongIds($concert),
            'note' => $concert->getNote(),
        ]);
    }

    public function renderEdit(int $id): void
    {

        $this->getTemplate()->pageName = $this->showPageName . ' - úpravy';

        $this->getTemplate()->concert = $this->getConcert($id);
    }

    public function renderShow(int $id): void
    {

        $this->getTemplate()->pageName = $this->showPageName . ' - zobrazení';

        $concert = $this->getConcert($id);

        $this->getTemplate()->concertSongs = $this->concertSongService->getConcertSongs($concert);
        $this->getTemplate()->concert = $concert;
    }

    protected function createComponentEditForm(): Form
    {
        $form = $this->getFormBase();
        $form->onSuccess[] = [$this, 'editFormSucceeded'];

        $this->formToBootstrap3($form);

        return $form;
    }

    public function editFormSucceeded(Form $form, ArrayHash $values): void
    {
        $concert = $this->getConcert((int) $this->getParameter('id'));
        $concert->setTitle($values->title);
        $concert->setScheduledAt($this->parseScheduledAt($values->scheduledAt));
        $concert->setNote($values->note);

        $this->concertSongService->saveConcert($concert, $values->songIds);

        $this->flashMessage($this->translator->translate('common.record_updated'), 'alert-success');
        $this->redirect('default');
    }

    protected function createComponentCreateForm(): Form
    {
        $form = $this->getFormBase();
        $form->onSuccess[] = [$this, 'createFormSucceeded'];

        $this->formToBootstrap3($form);

        return $form;
    }

    public function createFormSucceeded(Form $form, ArrayHash $values): void
    {
        if (!$this->user->isInRole('admin')) {
            throw new \Exception($this->translator->translate('common.permission_denied'));
        }

        $concert = new Concert();
        $concert->setTitle($values->title);
        $concert->setScheduledAt($this->parseScheduledAt($values->scheduledAt));
        $concert->setNote($values->note);
        $concert->setCreatedAt(new DateTimeImmutable());
        $concert->setCreatedBy($this->getCurrentUserEntity());

        $this->concertSongService->saveConcert($concert, $values->songIds);

        $this->flashMessage($this->translator->translate('common.record_created'), 'alert-success');
        $this->redirect('default');
    }


    protected function createComponentDeleteForm(): Multiplier
    {
        return new Multiplier(function (string $id): Form {
            $form = $this->createForm();
            $form->addSubmit('send', 'Smazat');
            $form->addProtection('form.csrf_expired');
            $form->onSuccess[] = function () use ($id): void {
                if (!$this->getUser()->isInRole('admin')) {
                    throw new \Exception($this->translator->translate('common.permission_denied'));
                }

                $this->concertSongService->deleteConcert($this->getConcert((int) $id));
                $this->flashMessage($this->translator->translate('common.record_deleted'), 'alert-success');
                $this->redirect('this');
            };

            return $form;
        });
    }

    private function getFormBase(): Form
    {
        $form = $this->createForm();

        $form->addText('title', 'Název:')
            ->setRequired('Název musíte zadat !')
            ->addRule(Form::MIN_LENGTH, 'Délka musí být alespoň 3 znaky !', 3)
            ->addRule(Form::MAX_LENGTH, 'Délka může být maximálně 20 znaků !', 40);

        $form->addText('scheduledAt', 'Kdy:')
            ->setRequired('Datum a čas musíte zadat !')
            ->setHtmlType('datetime-local')
            ->addRule(Form::MAX_LENGTH, 'Délka nesmí překročit 30 znaků !', 40);

        /** @var SongRepository $songRepository */
        $songRepository = $this->entityManager->getRepository(Song::class);
        $form->addCheckboxList('songIds', 'Skladby:', $songRepository->findChoices());

        $form->addTextArea('note', 'Poznámka:');

        $form->addSubmit('send', 'Uložit úpravy');
        $form->addProtection('form.csrf_expired');
        return $form;
    }

    private function getConcert(int $id): Concert
    {
        $concert = $this->entityManager->getRepository(Concert::class)->find($id);
        if ($concert === null) {
            $this->error($this->translator->translate('common.record_not_found'));
        }

        return $concert;
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->entityManager->getRepository(User::class)->find((int) $this->getUser()->getId());
        if ($user === null) {
            throw new UnexpectedValueException('Authenticated user entity was not found.');
        }

        return $user;
    }

    private function parseScheduledAt(string $value): DateTimeImmutable
    {
        $scheduledAt = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value);
        if ($scheduledAt === false) {
            throw new UnexpectedValueException('Concert date has an invalid format.');
        }

        return $scheduledAt;
    }
}
