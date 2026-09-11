<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Router\RouterFactory;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use PHPUnit\Framework\TestCase;

final class RouterCompatibilityTest extends TestCase
{
    public function testLegacySongRouteResolvesToEnglishPresenter(): void
    {
        $params = RouterFactory::createRouter()->match($this->request('/skladby/show/12'));

        self::assertSame('Songs', $params['presenter'] ?? null);
        self::assertSame('show', $params['action'] ?? null);
        self::assertSame('12', $params['id'] ?? null);
    }

    public function testEnglishSongLinksUseCanonicalRoute(): void
    {
        $url = RouterFactory::createRouter()->constructUrl(
            ['presenter' => 'Songs', 'action' => 'show', 'id' => 12],
            new UrlScript('https://example.test/index.php', '/index.php'),
        );

        self::assertSame('https://example.test/songs/show/12', $url);
    }

    public function testLegacyConcertRouteResolvesToEnglishPresenter(): void
    {
        $params = RouterFactory::createRouter()->match($this->request('/koncerty/edit/7'));

        self::assertSame('Concerts', $params['presenter'] ?? null);
        self::assertSame('edit', $params['action'] ?? null);
        self::assertSame('7', $params['id'] ?? null);
    }

    public function testEnglishConcertLinksUseCanonicalRoute(): void
    {
        $url = RouterFactory::createRouter()->constructUrl(
            ['presenter' => 'Concerts', 'action' => 'edit', 'id' => 7],
            new UrlScript('https://example.test/index.php', '/index.php'),
        );

        self::assertSame('https://example.test/concerts/edit/7', $url);
    }

    public function testLegacySettingsRouteResolvesToEnglishPresenter(): void
    {
        $params = RouterFactory::createRouter()->match($this->request('/setting/edit'));

        self::assertSame('Settings', $params['presenter'] ?? null);
        self::assertSame('edit', $params['action'] ?? null);
    }

    public function testEnglishSettingsLinksUseCanonicalRoute(): void
    {
        $url = RouterFactory::createRouter()->constructUrl(
            ['presenter' => 'Settings', 'action' => 'edit'],
            new UrlScript('https://example.test/index.php', '/index.php'),
        );

        self::assertSame('https://example.test/settings/edit', $url);
    }

    public function testLegacyLoginRouteResolvesToAuthenticationPresenter(): void
    {
        $params = RouterFactory::createRouter()->match($this->request('/sign/in'));

        self::assertSame('Authentication', $params['presenter'] ?? null);
        self::assertSame('login', $params['action'] ?? null);
    }

    public function testLegacyLogoutRouteResolvesToAuthenticationPresenter(): void
    {
        $params = RouterFactory::createRouter()->match($this->request('/sign/out'));

        self::assertSame('Authentication', $params['presenter'] ?? null);
        self::assertSame('logout', $params['action'] ?? null);
    }

    public function testEnglishAuthenticationLinksUseCanonicalRoute(): void
    {
        $url = RouterFactory::createRouter()->constructUrl(
            ['presenter' => 'Authentication', 'action' => 'login'],
            new UrlScript('https://example.test/index.php', '/index.php'),
        );

        self::assertSame('https://example.test/authentication/login', $url);
    }

    private function request(string $path): Request
    {
        return new Request(new UrlScript('https://example.test' . $path, '/index.php'));
    }
}
