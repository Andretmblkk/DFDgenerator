<?php

declare(strict_types=1);

namespace LaravelDfd\Http\Controllers;

use Illuminate\Http\Response;
use LaravelDfd\Builder\HierarchyBuilder;
use LaravelDfd\Renderer\HtmlRenderer;

final class DfdController
{
    public function show(HierarchyBuilder $builder, HtmlRenderer $renderer): Response
    {
        $this->abortIfDisabled();

        $maxLevel = (int) config('laravel-dfd.max_level', 3);
        $hierarchy = $builder->build($maxLevel);

        return response($renderer->render($hierarchy, [
            'styles' => asset($this->routeAssetPath('styles.css')),
            'script' => asset($this->routeAssetPath('viewer.js')),
        ]), 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function styles(HtmlRenderer $renderer): Response
    {
        $this->abortIfDisabled();

        return response($renderer->styles(), 200)
            ->header('Content-Type', 'text/css; charset=UTF-8');
    }

    public function script(HtmlRenderer $renderer): Response
    {
        $this->abortIfDisabled();

        return response($renderer->script(), 200)
            ->header('Content-Type', 'application/javascript; charset=UTF-8');
    }

    private function abortIfDisabled(): void
    {
        if (! (bool) config('laravel-dfd.route.enabled', true)) {
            abort(404);
        }
    }

    private function routeAssetPath(string $filename): string
    {
        $prefix = trim((string) config('laravel-dfd.route.prefix', 'dfd'), '/');
        $path = trim($prefix . '/assets/' . $filename, '/');

        return $path === '' ? 'assets/' . $filename : $path;
    }
}
