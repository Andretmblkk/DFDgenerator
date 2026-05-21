<?php

declare(strict_types=1);

namespace LaravelDfd\Commands;

use Illuminate\Console\Command;
use LaravelDfd\Builder\DFDBuilder;
use LaravelDfd\Builder\HierarchyBuilder;
use LaravelDfd\Generator\MermaidGenerator;
use LaravelDfd\IR\DFDLevel;
use LaravelDfd\Renderer\HtmlRenderer;
use LaravelDfd\Renderer\SvgRenderer;
use Throwable;

final class DfdCommand extends Command
{
    protected $signature = 'dfd:generate
        {--output= : Output directory for the DFD documentation suite}
        {--format=suite : Output format: suite or mermaid}
        {--level= : Deprecated. The suite always generates levels 0 through 3}
        {--json : With --format=mermaid, export the legacy flat IR as JSON}
        {--debug : Show debug error details}';

    protected $description = 'Generate data flow documentation for the Laravel application.';

    public function handle(): int
    {
        try {
            $format = (string) $this->option('format');

            if ($format === 'suite') {
                return $this->handleSuite();
            }

            if ($format !== 'mermaid') {
                $this->components->error('Unsupported format: ' . $format);

                return self::FAILURE;
            }

            $output = $this->outputPath();

            if ($output === null) {
                return self::FAILURE;
            }

            $this->components->info('Scanning routes and building DFD IR...');

            /** @var DFDBuilder $builder */
            $builder = app(DFDBuilder::class);
            $ir = $builder->build();

            $this->components->info('Generating output...');

            $contents = $this->option('json')
                ? $this->jsonOutput($ir)
                : $this->mermaidOutput($ir);

            if (! $this->writeOutput($output, $contents)) {
                return self::FAILURE;
            }

            $this->components->info('DFD generated: ' . $output);

            if ($this->option('debug')) {
                $this->line('Processes: ' . count($ir['processes']));
                $this->line('Data stores: ' . count($ir['dataStores']));
                $this->line('External entities: ' . count($ir['externalEntities']));
                $this->line('Flows: ' . count($ir['flows']));
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error('Failed to generate DFD: ' . $exception->getMessage());

            if ($this->option('debug')) {
                $this->line($exception->getTraceAsString());
            }

            return self::FAILURE;
        }
    }

    private function handleSuite(): int
    {
        $this->renderBanner();

        $directory = $this->outputDirectory();

        if ($directory === null) {
            return self::FAILURE;
        }

        $this->progressLine('Scanning Laravel routes');
        $this->progressLine('Filtering business processes');
        $this->progressLine('Parsing controllers and detecting business flows');

        /** @var HierarchyBuilder $builder */
        $builder = app(HierarchyBuilder::class);
        $hierarchy = $builder->build(3);
        $this->progressLine('Building DFD hierarchy');

        /** @var SvgRenderer $svgRenderer */
        $svgRenderer = app(SvgRenderer::class);
        /** @var HtmlRenderer $htmlRenderer */
        $htmlRenderer = app(HtmlRenderer::class);

        $files = [];
        $levelsByNumber = $this->levelsByNumber($hierarchy['levels']);

        for ($level = 0; $level <= 3; $level++) {
            $this->progressLine('Generating Level ' . $level);
            $levels = $levelsByNumber[$level] ?? [];
            $files[$directory . DIRECTORY_SEPARATOR . 'level-' . $level . '.json'] = $this->levelJson($hierarchy, $level, $levels);
            $files[$directory . DIRECTORY_SEPARATOR . 'level-' . $level . '.svg'] = $svgRenderer->renderDocument($levels, 'DFD Level ' . $level);
        }

        $this->progressLine('Rendering SVG diagrams');
        $this->progressLine('Building HTML viewer');
        $files[$directory . DIRECTORY_SEPARATOR . 'index.html'] = $htmlRenderer->render($hierarchy);
        $files[$directory . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'styles.css'] = $htmlRenderer->styles();
        $files[$directory . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'viewer.js'] = $htmlRenderer->script();
        $this->progressLine('Exporting assets');

        foreach ($files as $path => $contents) {
            if (! $this->writeOutput($path, $contents)) {
                return self::FAILURE;
            }
        }

        $this->renderSummary($directory, $hierarchy, array_keys($files));

        if ($this->option('debug')) {
            $this->line('Levels: ' . count($hierarchy['levels']));
            $this->line('Files: ' . count($files));
        }

        return self::SUCCESS;
    }

    private function renderBanner(): void
    {
        $this->line('╔══════════════════════════════════════════════╗');
        $this->line('║        Laravel DFD Generator v1.0           ║');
        $this->line('║         Created by Andre Tumbelaka          ║');
        $this->line('╚══════════════════════════════════════════════╝');
        $this->newLine();
    }

    private function progressLine(string $message): void
    {
        $this->line('<fg=green>✔</> ' . $message);
    }

    /**
     * @param array{meta?: array<string, mixed>} $hierarchy
     * @param array<int, string> $files
     */
    private function renderSummary(string $directory, array $hierarchy, array $files): void
    {
        $meta = $hierarchy['meta'] ?? [];

        $this->newLine();
        $this->line('──────────────────────────────────────────────');
        $this->newLine();
        $this->components->info('DFD generation completed successfully.');
        $this->newLine();
        $this->line('<fg=gray>Generated:</>');

        foreach ($files as $file) {
            $this->line('  • ' . str_replace($directory . DIRECTORY_SEPARATOR, '', $file));
        }

        $this->newLine();
        $this->line('<fg=gray>Output:</> ' . $directory);
        $this->line('<fg=gray>Open viewer:</> ' . $directory . DIRECTORY_SEPARATOR . 'index.html');
        $this->newLine();
        $this->line('Processes detected : ' . (string) ($meta['processes'] ?? 0));
        $this->line('Data stores        : ' . (string) ($meta['dataStores'] ?? 0));
        $this->line('External entities  : ' . (string) ($meta['externalEntities'] ?? 0));

        foreach ((array) ($meta['warnings'] ?? []) as $warning) {
            $this->components->warn((string) $warning);
        }

        $this->newLine();
        $this->line('Created by Andre Tumbelaka');
        $this->line('Special thanks to Andre Tumbelaka for dedication and development of this project.');
    }

    private function outputPath(): ?string
    {
        $output = $this->option('output');

        if ($output === null || $output === '') {
            return storage_path($this->option('json') ? 'dfd/diagram.json' : 'dfd/diagram.mmd');
        }

        $path = (string) $output;

        if (is_dir($path)) {
            $this->components->error('Output path must be a file, directory given: ' . $path);

            return null;
        }

        return $path;
    }

    private function outputDirectory(): ?string
    {
        $output = $this->option('output');

        if ($output === null || $output === '') {
            $configured = config('laravel-dfd.output_path', config('dfd.output_path', storage_path('dfd')));

            return is_string($configured) && $configured !== '' ? $configured : storage_path('dfd');
        }

        $path = (string) $output;

        if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
            $this->components->error('DFD suite output must be a directory: ' . $path);

            return null;
        }

        return $path;
    }

    /**
     * @param array{
     *     processes: array<int, mixed>,
     *     dataStores: array<int, mixed>,
     *     externalEntities: array<int, mixed>,
     *     flows: array<int, mixed>
     * } $ir
     */
    private function mermaidOutput(array $ir): string
    {
        /** @var MermaidGenerator $generator */
        $generator = app(MermaidGenerator::class);

        return $generator->generate([
            ...$ir['externalEntities'],
            ...$ir['processes'],
            ...$ir['dataStores'],
        ], $ir['flows']);
    }

    /**
     * @param array{
     *     processes: array<int, mixed>,
     *     dataStores: array<int, mixed>,
     *     externalEntities: array<int, mixed>,
     *     flows: array<int, mixed>
     * } $ir
     */
    private function jsonOutput(array $ir): string
    {
        return (string) json_encode([
            'processes' => array_map(static fn (object $node): array => $node->toArray(), $ir['processes']),
            'dataStores' => array_map(static fn (object $node): array => $node->toArray(), $ir['dataStores']),
            'externalEntities' => array_map(static fn (object $node): array => $node->toArray(), $ir['externalEntities']),
            'flows' => array_map(static fn (object $flow): array => $flow->toArray(), $ir['flows']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    /**
     * @param array{system: string, selectedLevel: int, groups: array<int, mixed>, levels: array<int, DFDLevel>} $hierarchy
     */
    private function hierarchyJson(array $hierarchy): string
    {
        return (string) json_encode([
            'system' => $hierarchy['system'],
            'selectedLevel' => $hierarchy['selectedLevel'],
            'meta' => $hierarchy['meta'] ?? [],
            'groups' => array_map(static fn (object $group): array => $group->toArray(), $hierarchy['groups']),
            'levels' => array_map(static fn (DFDLevel $level): array => $level->toArray(), $hierarchy['levels']),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    /**
     * @param array<int, DFDLevel> $levels
     * @return array<int, array<int, DFDLevel>>
     */
    private function levelsByNumber(array $levels): array
    {
        $grouped = [];

        foreach ($levels as $level) {
            $grouped[$level->getLevel()][] = $level;
        }

        return $grouped;
    }

    /**
     * @param array{system: string, selectedLevel: int, groups: array<int, mixed>, levels: array<int, DFDLevel>} $hierarchy
     * @param array<int, DFDLevel> $levels
     */
    private function levelJson(array $hierarchy, int $level, array $levels): string
    {
        return (string) json_encode([
            'system' => $hierarchy['system'],
            'level' => $level,
            'meta' => $hierarchy['meta'] ?? [],
            'groups' => array_map(static fn (object $group): array => $group->toArray(), $hierarchy['groups']),
            'diagrams' => array_map(static fn (DFDLevel $diagram): array => $diagram->toArray(), $levels),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    }

    private function writeOutput(string $path, string $contents): bool
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            $this->components->error('Unable to create output directory: ' . $directory);

            return false;
        }

        if (file_put_contents($path, $contents) === false) {
            $this->components->error('Unable to write output file: ' . $path);

            return false;
        }

        return true;
    }
}
