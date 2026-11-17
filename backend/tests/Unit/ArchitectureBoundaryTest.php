<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\SharedKernel\Domain\ValueObjects\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ArchitectureBoundaryTest extends TestCase
{
    public function test_domain_layers_are_100_percent_pure_php_with_zero_framework_imports(): void
    {
        $modulesBase = dirname(__DIR__, 2).'/app/Modules';
        $this->assertDirectoryExists($modulesBase);

        $domainFiles = [];
        $moduleDirs = glob($modulesBase.'/*/Domain', GLOB_ONLYDIR) ?: [];
        $this->assertCount(7, $moduleDirs, 'All 7 bounded contexts must define a Domain directory.');

        foreach ($moduleDirs as $domainDir) {
            foreach ($this->phpFilesIn($domainDir) as $file) {
                $domainFiles[] = $file;
                $contents = (string) file_get_contents($file);

                $this->assertDoesNotMatchRegularExpression(
                    '/\b(?:Illuminate|Laravel)\\\\/',
                    $contents,
                    sprintf('Framework namespace leaked into pure Domain file: %s', $file)
                );

                $this->assertDoesNotMatchRegularExpression(
                    '/\bEloquent\b/',
                    $contents,
                    sprintf('Eloquent reference leaked into pure Domain file: %s', $file)
                );
            }
        }

        $this->assertGreaterThanOrEqual(20, count($domainFiles));
    }

    public function test_no_module_imports_another_modules_infrastructure_layer(): void
    {
        $modulesBase = dirname(__DIR__, 2).'/app/Modules';
        $modulePaths = glob($modulesBase.'/*', GLOB_ONLYDIR) ?: [];

        foreach ($modulePaths as $modulePath) {
            $currentModule = basename($modulePath);

            foreach ($this->phpFilesIn($modulePath) as $file) {
                $contents = (string) file_get_contents($file);

                if (preg_match_all('/App\\\\Modules\\\\([A-Za-z0-9_]+)\\\\Infrastructure\b/', $contents, $matches)) {
                    foreach ($matches[1] as $referencedModule) {
                        $this->assertSame(
                            $currentModule,
                            $referencedModule,
                            sprintf(
                                'Cross-module Infrastructure boundary violation in %s: module [%s] referenced [%s\\Infrastructure].',
                                $file,
                                $currentModule,
                                $referencedModule
                            )
                        );
                    }
                }
            }
        }
    }

    public function test_money_value_object_enforces_immutability_currency_and_integer_math(): void
    {
        $base = Money::vnd(890000);
        $shipping = Money::vnd(30000);

        $discount = $base->percentage(10);
        $this->assertSame(89000, $discount->amountMinor);
        $this->assertSame(890000, $base->amountMinor, 'Original Money instance must remain immutable.');

        $grandTotal = $base->subtract($discount)->add($shipping);
        $this->assertSame(831000, $grandTotal->amountMinor);
        $this->assertSame('VND', $grandTotal->currency);

        $truncatedPercent = Money::vnd(999)->percentage(10);
        $this->assertSame(99, $truncatedPercent->amountMinor);

        $multiplied = $base->multiply(2);
        $this->assertSame(1780000, $multiplied->amountMinor);

        $this->expectException(InvalidArgumentException::class);
        $base->add(new Money(100, 'USD'));
    }

    public function test_money_rejects_negative_amounts(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::vnd(-1);
    }

    public function test_backend_repo_is_pure_headless_api_with_zero_frontend_bundler_leftovers(): void
    {
        $backendRoot = dirname(__DIR__, 2);

        $this->assertFileDoesNotExist($backendRoot.'/vite.config.js', 'Headless Backend API must not contain vite.config.js');
        $this->assertFileDoesNotExist($backendRoot.'/package.json', 'Headless Backend API must not contain frontend package.json');
        $this->assertDirectoryDoesNotExist($backendRoot.'/resources/js', 'Headless Backend API must not contain resources/js');
        $this->assertDirectoryDoesNotExist($backendRoot.'/resources/css', 'Headless Backend API must not contain resources/css');
    }

    /**
     * @return array<int, string>
     */
    private function phpFilesIn(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        /** @var SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $files[] = $item->getPathname();
            }
        }

        return $files;
    }
}
