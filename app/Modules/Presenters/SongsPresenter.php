<?php

declare(strict_types=1);

namespace App\Modules\Presenters;

use App\Model\entities\Song;
use App\Model\entities\SongFile;
use App\Model\entities\User;
use App\Model\repositories\SongRepository;
use App\Modules\Presenters\BasePresenter;
use App\Services\SongFileStorage;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\Response;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;
use Nette\Utils\ArrayHash;
use Nette\Utils\Paginator;
use UnexpectedValueException;

class SongsPresenter extends BasePresenter
{
    private const PER_PAGE = 20;
    private string $pageName;
    private string $showPageName;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SongFileStorage $songFileStorage,
    ) {
    }

    public function beforeRender(): void
    {
        parent::beforeRender();

        $this->getTemplate()->pageName = $this->pageName;
    }

    protected function startup(): void
    {
        parent::startup();
        $this->pageName = 'Seznam skladeb';
        $this->showPageName = 'Skladba';
    }

    public function renderDefault(int $page = 1): void
    {
        $paginator = new Paginator();
        $paginator->setItemsPerPage(self::PER_PAGE); // the number of records on page
        $paginator->setPage($page);

        /** @var SongRepository $songRepository */
        $songRepository = $this->entityManager->getRepository(Song::class);
        $paginator->setItemCount($songRepository->countAll());

        $this->getTemplate()->paginator = $paginator;

        $this->getTemplate()->songs = $songRepository->findPage(self::PER_PAGE, $paginator->offset);
    }

    public function actionEdit(int $id): void
    {
        $song = $this->getSong($id);
        $this['editForm']->setDefaults([
            'title' => $song->getTitle(),
            'author' => $song->getAuthor(),
            'active' => $song->isActive(),
        ]);
    }

    public function renderEdit(int $id): void
    {

        $this->getTemplate()->pageName = $this->showPageName . ' - úpravy';

        $this->getTemplate()->song = $this->getSong($id);
    }

    public function renderShow(int $id): void
    {

        $this->getTemplate()->pageName = $this->showPageName . ' - zobrazení';

        $song = $this->getSong($id);

        $repository = $this->entityManager->getRepository(SongFile::class);
        $this->getTemplate()->choirSheetMusic = $repository->findBy(
            ['song' => $song, 'category' => SongFileStorage::CATEGORY_CHOIR_SHEET_MUSIC],
            ['sortOrder' => 'ASC'],
        );
        $this->getTemplate()->orchestraSheetMusic = $repository->findBy(
            ['song' => $song, 'category' => SongFileStorage::CATEGORY_ORCHESTRA_SHEET_MUSIC],
            ['sortOrder' => 'ASC'],
        );
        $this->getTemplate()->recordings = $repository->findBy(
            ['song' => $song, 'category' => SongFileStorage::CATEGORY_RECORDINGS],
            ['sortOrder' => 'ASC'],
        );


        $this->getTemplate()->song = $song;
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
        $song = $this->getSong((int) $this->getParameter('id'));
        $song->setTitle($values->title);
        $song->setAuthor($values->author);
        $song->setActive($values->active);
        $this->entityManager->flush();

        $this->flashMessage("Záznam byl úspěšně aktualizován.", 'alert-success');
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
            throw new \Exception("Nemáte potřebná oprávnění!");
        }

        $song = new Song();
        $song->setTitle($values->title);
        $song->setAuthor($values->author);
        $song->setActive($values->active);
        $song->setCreatedAt(new DateTimeImmutable());
        $song->setCreatedBy($this->getCurrentUserEntity());

        $this->entityManager->persist($song);
        $this->entityManager->flush();
        $this->flashMessage('Záznam byl vložen.', 'alert-success');
        $this->redirect('default');
    }


    protected function createComponentDeleteForm(): Multiplier
    {
        return new Multiplier(function (string $id): Form {
            $form = new Form();
            $form->addSubmit('send', 'Smazat');
            $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
            $form->onSuccess[] = function () use ($id): void {
                $this->assertAdmin();
                $this->entityManager->remove($this->getSong((int) $id));
                $this->entityManager->flush();

                $this->flashMessage('Zvolený záznam byl smazán.', 'alert-success');
                $this->redirect('this');
            };

            return $form;
        });
    }

    private function getFormBase(): Form
    {
        $form = new Form();

        $form->addText('title', 'Název:')
            ->setRequired('Název musíte zadat !')
            ->addRule(Form::MIN_LENGTH, 'Délka musí být alespoň 3 znaky !', 3)
            ->addRule(Form::MAX_LENGTH, 'Délka může být maximálně 20 znaků !', 40);

        $form->addText('author', 'Autor:')
            ->setRequired('Autora musíte zadat !')
            ->addRule(Form::MAX_LENGTH, 'Délka nesmí překročit 30 znaků !', 40);

        $form->addCheckbox('active', 'Aktivní:')
            ->setDefaultValue(true);

        $form->addSubmit('send', 'Uložit úpravy');
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
        return $form;
    }


    protected function createComponentChoirSheetMusicUploadForm(): Form
    {
        $form = new Form();

        $this->addUploadControls($form, SongFileStorage::CATEGORY_CHOIR_SHEET_MUSIC);

        $form->addSubmit('send', 'Přidat soubor');
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
        $form->onSuccess[] = [$this, 'uploadFormSucceeded'];

        $this->formToBootstrapInline3($form);

        return $form;
    }
    protected function createComponentOrchestraSheetMusicUploadForm(): Form
    {
        $form = new Form();

        $this->addUploadControls($form, SongFileStorage::CATEGORY_ORCHESTRA_SHEET_MUSIC);
        $form->addSubmit('send', 'Přidat soubor');
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
        $form->onSuccess[] = [$this, 'uploadFormSucceeded'];

        $this->formToBootstrapInline3($form);

        return $form;
    }
    protected function createComponentRecordingsUploadForm(): Form
    {
        $form = new Form();

        $this->addUploadControls($form, SongFileStorage::CATEGORY_RECORDINGS);
        $form->addSubmit('send', 'Přidat soubor');
        $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
        $form->onSuccess[] = [$this, 'uploadFormSucceeded'];

        $this->formToBootstrapInline3($form);

        return $form;
    }

    public function uploadFormSucceeded(Form $form, ArrayHash $values): void
    {
        if (!$this->user->isInRole('admin')) {
            throw new \Exception("Nemáte potřebná oprávnění!");
        }

        try {
            $this->songFileStorage->store(
                $values->fileUpload,
                $this->getSong((int) $this->getParameter('id')),
                $this->getCurrentUserEntity(),
                $values->category,
                $values->description,
            );
            $this->flashMessage('Záznam byl vložen.', 'alert-success');
            $this->redirect('this');
        } catch (InvalidArgumentException $exception) {
            $form->addError($exception->getMessage());
        }
    }

    /**
     * @throws BadRequestException
     * @throws AbortException
     */
    public function handleFileDownload(int $songFileId): Response
    {
        $songFile = $this->getSongFile($songFileId);
        $filePath = $this->songFileStorage->getFilePath($songFile);
        if (!is_file($filePath)) {
            $this->error('Soubor nebyl nalezen');
        }
        $this->sendResponse(new FileResponse($filePath));
    }

    protected function createComponentFileDeleteForm(): Multiplier
    {
        return new Multiplier(function (string $id): Form {
            $form = new Form();
            $form->addSubmit('send', 'Smazat');
            $form->addProtection('Platnost formuláře vypršela. Odešlete jej prosím znovu.');
            $form->onSuccess[] = function () use ($id): void {
                $this->assertAdmin();
                $this->songFileStorage->delete($this->getSongFile((int) $id));
                $this->flashMessage('Soubor byl smazán!', 'alert-warning');

                $this->redirect('this');
            };

            return $form;
        });
    }

    private function getSong(int $id): Song
    {
        $song = $this->entityManager->getRepository(Song::class)->find($id);
        if ($song === null) {
            $this->error('Záznam nebyl nalezen');
        }

        return $song;
    }

    private function addUploadControls(Form $form, string $category): void
    {
        $form->addUpload('fileUpload', 'Soubor:')
            ->setRequired('Soubor musíte vybrat.')
            ->addRule(Form::MAX_FILE_SIZE, 'Soubor je příliš velký.', SongFileStorage::MAX_UPLOAD_SIZE);
        $form->addText('description', 'Popis souboru:')
            ->setRequired()
            ->addRule(Form::MAX_LENGTH, 'Popis je příliš dlouhý.', 150);
        $form->addHidden('category', $category);
    }

    private function getSongFile(int $id): SongFile
    {
        $songFile = $this->entityManager->getRepository(SongFile::class)->find($id);
        if ($songFile === null) {
            $this->error('Soubor nebyl nalezen');
        }

        return $songFile;
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->entityManager->getRepository(User::class)->find((int) $this->getUser()->getId());
        if ($user === null) {
            throw new UnexpectedValueException('Authenticated user entity was not found.');
        }

        return $user;
    }

    private function assertAdmin(): void
    {
        if (!$this->getUser()->isInRole('admin')) {
            throw new \Exception("Nemáte potřebná oprávnění!");
        }
    }
}
