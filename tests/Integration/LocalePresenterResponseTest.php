<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Localization\CatalogTranslator;
use App\Modules\Presenters\ErrorPresenter;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\Responses\CallbackResponse;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\Bootstrap\Configurator;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Security\SimpleIdentity;
use Nette\Security\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tracy\ILogger;

final class LocalePresenterResponseTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        ini_set('session.save_path', dirname(__DIR__, 2) . '/temp');
    }

    public static function tearDownAfterClass(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /** @return iterable<string, array{string, string, string, string, array<int, string>}> */
    public static function localeProvider(): iterable
    {
        yield 'Czech' => ['cs', 'Přihlášení', 'Vítejte v aplikaci SMPS Bruntál.', 'Chyba serveru', [
            403 => 'Přístup odepřen',
            404 => 'Stránka nenalezena',
            405 => 'Metoda není povolena',
            410 => 'Stránka již není dostupná',
            418 => 'Požadavek se nezdařil',
        ]];
        yield 'English' => ['en', 'Sign in', 'Welcome to SMPS Bruntál.', 'Server error', [
            403 => 'Access denied',
            404 => 'Page not found',
            405 => 'Method not allowed',
            410 => 'Page no longer available',
            418 => 'Request failed',
        ]];
        yield 'German' => ['de', 'Anmelden', 'Willkommen bei SMPS Bruntál.', 'Serverfehler', [
            403 => 'Zugriff verweigert',
            404 => 'Seite nicht gefunden',
            405 => 'Methode nicht zulässig',
            410 => 'Seite nicht mehr verfügbar',
            418 => 'Anfrage fehlgeschlagen',
        ]];
        yield 'Dutch' => ['nl', 'Aanmelden', 'Welkom bij SMPS Bruntál.', 'Serverfout', [
            403 => 'Toegang geweigerd',
            404 => 'Pagina niet gevonden',
            405 => 'Methode niet toegestaan',
            410 => 'Pagina niet meer beschikbaar',
            418 => 'Verzoek mislukt',
        ]];
    }

    #[DataProvider('localeProvider')]
    public function testPublicShellProtectedRouteAndErrorsRenderInConfiguredLocale(
        string $locale,
        string $loginTitle,
        string $welcome,
        string $serverErrorTitle,
        array $errorTitles,
    ): void {
        $root = dirname(__DIR__, 2);
        $container = $this->createContainer($root, $locale);
        $user = $container->getByType(User::class);
        $user->logout(true);

        $loginHtml = $this->renderPresenter($container, 'Authentication', 'GET', ['action' => 'login']);
        self::assertStringContainsString('<html lang="' . $locale . '">', $loginHtml);
        self::assertStringContainsString($loginTitle, $loginHtml);
        self::assertStringContainsString('SMPS Bruntál', $loginHtml);
        self::assertStringNotContainsString('auth.login.title', $loginHtml);
        self::assertStringNotContainsString('nav.login', $loginHtml);

        $dashboard = $this->runPresenter($container, 'Dashboard', 'GET', ['action' => 'default']);
        self::assertInstanceOf(RedirectResponse::class, $dashboard);
        self::assertStringContainsString('/authentication/login', strtolower($dashboard->getUrl()));

        $user->login(new SimpleIdentity(1, ['admin'], ['name' => 'Synthetic Tester']));
        $dashboardHtml = $this->renderPresenter($container, 'Dashboard', 'GET', ['action' => 'default']);
        self::assertStringContainsString('<html lang="' . $locale . '">', $dashboardHtml);
        self::assertStringContainsString($welcome, $dashboardHtml);
        self::assertStringNotContainsString('/images/carousel/', $dashboardHtml);

        foreach ($errorTitles as $statusCode => $errorTitle) {
            $errorHtml = $this->renderPresenter(
                $container,
                'Error4xx',
                Request::FORWARD,
                [
                    'action' => 'default',
                    'exception' => new BadRequestException('Synthetic request failure', $statusCode),
                ],
            );
            self::assertStringContainsString('<html lang="' . $locale . '">', $errorHtml);
            self::assertStringContainsString($errorTitle, $errorHtml);
            $titleKey = $statusCode === 418 ? 'error.4xx.title' : "error.{$statusCode}.title";
            self::assertStringNotContainsString($titleKey, $errorHtml);
        }

        $serverErrorHtml = $this->renderServerError($container);
        self::assertStringContainsString('<html lang="' . $locale . '">', $serverErrorHtml);
        self::assertStringContainsString($serverErrorTitle, $serverErrorHtml);
        self::assertStringNotContainsString('error.500.title', $serverErrorHtml);

        $user->logout(true);
    }

    private function renderServerError(Container $container): string
    {
        $presenter = new ErrorPresenter(
            $this->createStub(ILogger::class),
            $container->getByType(CatalogTranslator::class),
        );
        $response = $presenter->run(new Request(
            'Error',
            'GET',
            ['exception' => new RuntimeException('Synthetic server failure')],
        ));
        self::assertInstanceOf(CallbackResponse::class, $response);

        $httpRequest = $this->createStub(IRequest::class);
        $httpResponse = $this->createStub(IResponse::class);
        $httpResponse->method('getHeader')
            ->with('Content-Type')
            ->willReturn('text/html');
        ob_start();
        $response->send($httpRequest, $httpResponse);
        $output = ob_get_clean();

        self::assertIsString($output);
        return $output;
    }

    /** @param array<string, mixed> $parameters */
    private function renderPresenter(
        Container $container,
        string $name,
        string $method,
        array $parameters,
    ): string {
        $response = $this->runPresenter($container, $name, $method, $parameters);
        self::assertInstanceOf(TextResponse::class, $response);
        $source = $response->getSource();
        self::assertInstanceOf(Template::class, $source);

        return $source->renderToString();
    }

    /** @param array<string, mixed> $parameters */
    private function runPresenter(
        Container $container,
        string $name,
        string $method,
        array $parameters,
    ): object {
        $factory = $container->getByType(IPresenterFactory::class);
        $presenter = $factory->createPresenter($name);
        self::assertInstanceOf(Presenter::class, $presenter);
        $presenter->autoCanonicalize = false;

        return $presenter->run(new Request($name, $method, $parameters));
    }

    private function createContainer(string $root, string $locale): Container
    {
        $configurator = new Configurator();
        $configurator->setDebugMode(true);
        $configurator->setTempDirectory($root . '/temp');
        $configurator->createRobotLoader()
            ->addDirectory($root . '/app')
            ->register();
        $configurator->addConfig($root . '/config/common.neon');
        $configurator->addConfig($root . '/config/services.neon');
        $configurator->addConfig($root . '/config/test.example.neon');
        $configurator->addStaticParameters([
            'appDir' => $root . '/app',
            'carouselDirectory' => $root . '/temp/missing-carousel-' . $locale,
            'locale' => $locale,
            'scope' => 'cli',
        ]);

        return $configurator->createContainer();
    }
}
