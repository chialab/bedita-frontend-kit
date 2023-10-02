<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\View\ViewBuilder;
use CallbackFilterIterator;
use Chialab\FrontendKit\View\AppView;
use Exception;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Twig\Source;

/**
 * Check syntax of Twig templates in CakePHP application.
 */
class TwigSyntaxCheckCommand extends Command
{
    /**
     * @inheritDoc
     */
    public static function defaultName(): string
    {
        return 'twig:syntax-check';
    }

    /**
     * @inheritDoc
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription('Check syntax of Twig templates.')
            ->addOption('ignored-paths', [
                'help' => 'Paths to ignore. This may be passed multiple times, and glob-like templates are expected.',
                'short' => 'i',
                'multiple' => true,
            ])
            ->setEpilog([
                '<info>Example usage:</info>',
                'bin/cake twig:syntax-check --ignored-paths \'vendor/*\' --ignored-paths \'*/bake/*\'',
            ]);
    }

    /**
     * @inheritDoc
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $view = (new ViewBuilder())->build();
        if (!$view instanceof AppView) {
            $io->abort(sprintf('App view must be an instance of %s, %s given', AppView::class, get_debug_type($view)));
        }

        $twig = $view->getTwig();
        $ignoredPaths = (array)$args->getOption('ignored-paths');
        $errors = [];
        foreach (static::filterIgnoredPaths(static::pathsIterator(), $ignoredPaths) as $path) {
            $io->verbose(sprintf('=====> Processing templates in path <info>%s</info>...', static::makePathRelative($path)));
            foreach (static::filterIgnoredPaths(static::templatesIterator($path, $view->getExtensions()), $ignoredPaths) as $tplPath) {
                $tplPathRelative = static::makePathRelative($tplPath);
                $io->verbose(sprintf('=====>   - Checking <info>%s</info>... ', $tplPathRelative), 0);
                try {
                    $twig->compileSource(new Source(file_get_contents($tplPath), $tplPath, $tplPath));

                    $io->verbose('<success>OK</success>');
                } catch (Exception $e) {
                    $io->verbose('<error>FAIL</error>');
                    $errors[] = $tplPathRelative;

                    $io->err(sprintf('<error>Error in template %s: %s</error>', $tplPathRelative, $e));
                }
            }
        }

        if (count($errors) === 0) {
            $io->out('=====> <success>Twig syntax is valid</success>');

            return static::CODE_SUCCESS;
        }

        $io->out(sprintf('=====> <error>Found %d invalid template(s)</error>:', count($errors)));
        foreach ($errors as $tpl) {
            $io->out(sprintf('=====>   - <error>%s</error>', $tpl));
        }

        return static::CODE_ERROR;
    }

    /**
     * List configured paths where templates can be stored.
     *
     * @return \Iterator<array-key, string>
     */
    protected static function pathsIterator(): Iterator
    {
        $filterPaths = fn (array $paths): array => array_filter($paths, is_dir(...));

        yield from $filterPaths(App::path('templates'));

        foreach (Plugin::loaded() as $plugin) {
            yield from $filterPaths(App::path('templates', $plugin));
        }
    }

    /**
     * List templates within a path.
     *
     * @param string $path Path to search templates into.
     * @param array<string> $extensions Extensions to filter by.
     * @return \Iterator<string, string>
     */
    protected static function templatesIterator(string $path, array $extensions): Iterator
    {
        $extensions = array_map(
            fn (string $ext): string => str_starts_with($ext, '.') ? $ext : '.' . $ext,
            $extensions,
        );

        yield from new CallbackFilterIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::KEY_AS_PATHNAME | RecursiveDirectoryIterator::CURRENT_AS_PATHNAME)),
            function (string $pathname) use ($extensions): bool {
                $filename = basename($pathname);
                foreach ($extensions as $ext) {
                    if (str_ends_with($filename, $ext)) {
                        return true;
                    }
                }

                return false;
            },
        );
    }

    /**
     * Filter out ignored paths from an iterator.
     *
     * @param \Iterator<array-key, string> $paths Paths iterator.
     * @param array<string> $ignoredPaths List of ignored paths.
     * @return \Iterator<array-key, string>
     */
    protected static function filterIgnoredPaths(Iterator $paths, array $ignoredPaths): Iterator
    {
        return new CallbackFilterIterator(
            $paths,
            function (string $path) use ($ignoredPaths): bool {
                $path = static::makePathRelative($path);
                foreach ($ignoredPaths as $ignoredPath) {
                    if (fnmatch($ignoredPath, $path)) {
                        return false;
                    }
                }

                return true;
            },
        );
    }

    /**
     * Turn an absolute path into a relative one.
     *
     * @param string $path Path to check.
     * @param string $relativeTo Directory relative to which path should be made relative.
     * @return string
     */
    protected static function makePathRelative(string $path, string $relativeTo = ROOT): string
    {
        if (!str_ends_with($relativeTo, DS)) {
            $relativeTo .= DS;
        }

        return str_starts_with($path, $relativeTo) ? substr($path, strlen($relativeTo)) : $path;
    }
}
