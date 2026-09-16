<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class UiTranslationKeyAuditTest extends TestCase
{
    public function testPresenterAndFormMessagesUseSemanticKeys(): void
    {
        $root = dirname(__DIR__, 2);
        $paths = array_merge(
            $this->filesUnder($root . '/app/Modules', ['php']),
            $this->filesUnder($root . '/app/Forms', ['php']),
        );

        foreach ($paths as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            $relativePath = str_replace('\\', '/', substr($path, strlen($root) + 1));

            self::assertDoesNotMatchRegularExpression(
                '/->flashMessage\(\s*[\'\"]/',
                $source,
                "Raw flash message in {$relativePath}",
            );
            self::assertDoesNotMatchRegularExpression(
                '/->error\(\s*[\'\"]/',
                $source,
                "Raw HTTP error message in {$relativePath}",
            );
            self::assertDoesNotMatchRegularExpression(
                '/(?:pageName|showPageName)\s*=\s*[\'\"]/',
                $source,
                "Raw page title in {$relativePath}",
            );

            preg_match_all(
                '/->(?:addText|addPassword|addCheckbox|addSubmit|addUpload|addTextArea|addCheckboxList)'
                . '\(\s*\'[^\']+\'\s*,\s*\'([^\']+)\'/s',
                $source,
                $captions,
            );
            preg_match_all('/->setRequired\(\s*\'([^\']+)\'/s', $source, $requiredMessages);
            preg_match_all(
                '/->addRule\(\s*Form::[A-Z_]+\s*,\s*\'([^\']+)\'/s',
                $source,
                $ruleMessages,
            );

            foreach (array_merge($captions[1], $requiredMessages[1], $ruleMessages[1]) as $message) {
                self::assertMatchesRegularExpression(
                    '/^[a-z][a-z0-9_]*(?:\.[a-z0-9_]+)+$/D',
                    $message,
                    "Raw form UI message '{$message}' in {$relativePath}",
                );
            }
        }
    }

    public function testTemplatesContainNoRawTextNodes(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ($this->filesUnder($root . '/app/Modules', ['latte', 'phtml']) as $path) {
            $source = file_get_contents($path);
            self::assertIsString($source);
            $relativePath = str_replace('\\', '/', substr($path, strlen($root) + 1));

            $source = preg_replace('/\{\*.*?\*\}/s', '', $source);
            $source = preg_replace('/<!--.*?-->/s', '', (string) $source);
            $source = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', (string) $source);
            $source = preg_replace('/<\?.*?\?>/s', '', (string) $source);
            $source = preg_replace('/\{[^{}]*\}/s', '', (string) $source);
            $source = preg_replace('/<(?:"[^"]*"|\'[^\']*\'|[^\'">])*>/s', '', (string) $source);
            $source = html_entity_decode((string) $source, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $source = preg_replace('/[\s\p{P}\p{S}\p{N}]+/u', '', (string) $source);

            self::assertSame('', $source, "Raw template text '{$source}' in {$relativePath}");
        }
    }

    /**
     * @param list<string> $extensions
     * @return list<string>
     */
    private function filesUnder(string $directory, array $extensions): array
    {
        $paths = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile()) {
                continue;
            }
            if (!in_array(strtolower($file->getExtension()), $extensions, true)) {
                continue;
            }
            $paths[] = $file->getPathname();
        }
        sort($paths);

        return $paths;
    }
}
