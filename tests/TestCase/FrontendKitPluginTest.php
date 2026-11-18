<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase;

use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\FrontendKitPlugin;
use Chialab\FrontendKit\Test\TestApp\Application;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FrontendKitPlugin::class)]
class FrontendKitPluginTest extends TestCase
{
    // CakePHP 5.3+ deprecates plugin classes named `Plugin`: loading must resolve FrontendKitPlugin silently.
    public function testLoadPlugin(): void
    {
        $deprecations = [];
        set_error_handler(function (int $errno, string $errstr) use (&$deprecations): bool {
            $deprecations[] = $errstr;

            return true;
        }, E_USER_DEPRECATED);
        try {
            $app = new Application(CONFIG);
            $app->addPlugin('Chialab/FrontendKit');
        } finally {
            restore_error_handler();
        }

        static::assertInstanceOf(FrontendKitPlugin::class, $app->getPlugins()->get('Chialab/FrontendKit'));
        static::assertSame([], $deprecations);
    }
}
