<?php

declare(strict_types=1);

namespace LaravelDfd\Renderer;

use LaravelDfd\IR\DFDLevel;

final class HtmlRenderer
{
    public function __construct(private SvgRenderer $svgRenderer = new SvgRenderer())
    {
    }

    /**
     * @param array{system: string, selectedLevel: int, levels: array<int, DFDLevel>, groups?: array<int, mixed>} $hierarchy
     * @param array{styles?: string, script?: string} $assets
     */
    public function render(array $hierarchy, array $assets = []): string
    {
        $stylesUrl = $assets['styles'] ?? 'assets/styles.css';
        $scriptUrl = $assets['script'] ?? 'assets/viewer.js';
        $levels = $hierarchy['levels'];
        $payload = [
            'system' => $hierarchy['system'],
            'selectedLevel' => $hierarchy['selectedLevel'],
            'meta' => $hierarchy['meta'] ?? [],
            'levels' => array_map(static fn (DFDLevel $level): array => $level->toArray(), $levels),
        ];
        $svgs = [];

        foreach ($levels as $level) {
            $svgs[$level->getId()] = $this->svgRenderer->render($level);
        }

        return '<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>' . $this->escape($hierarchy['system']) . ' DFD</title>
<link rel="stylesheet" href="' . $this->escape($stylesUrl) . '">
</head>
<body>
<div class="app">
<aside class="side">
<button class="mobile-toggle" type="button" id="toggleSidebar">Menu</button>
<h1 class="brand">Laravel DFD Generator</h1>
<p class="creator">Created by Andre Tumbelaka</p>
<div class="project-card">
<strong>' . $this->escape($hierarchy['system']) . '</strong>
<span id="generatedAt"></span>
</div>
<input class="search" id="search" type="search" placeholder="Cari diagram atau proses">
<nav class="nav" id="levelNav"></nav>
<div class="tree" id="tree"></div>
<div class="stats" id="stats"></div>
</aside>
<main class="main">
<header class="top">
<div><div class="breadcrumb" id="breadcrumb"></div><div class="title" id="title"></div><div class="hint">Drag, touch, or middle mouse to pan. Scroll untuk zoom halus.</div></div>
<div class="tool"><button type="button" id="fit">Fit</button><button type="button" id="zoomOut" title="Zoom out">-</button><button type="button" id="zoomIn" title="Zoom in">+</button><button type="button" id="reset">Reset</button><button type="button" id="fullscreen">Fullscreen</button><button type="button" id="exportSvg">SVG</button><button type="button" id="exportPng">PNG</button><button type="button" id="exportJson">JSON</button></div>
</header>
<section class="canvas" id="canvas"><div class="stage" id="stage"></div><div class="minimap" id="minimap"></div></section>
<footer class="footer">Special thanks to Andre Tumbelaka for dedication and development of this project.</footer>
</main>
</div>
<script>window.DFD_DATA=' . $this->json($payload) . ';window.DFD_SVGS=' . $this->json($svgs) . ';</script>
<script src="' . $this->escape($scriptUrl) . '"></script>
</body>
</html>
';
    }

    public function styles(): string
    {
        return $this->assetContents('styles.css');
    }

    public function script(): string
    {
        return $this->assetContents('viewer.js');
    }

    private function assetContents(string $filename): string
    {
        $path = __DIR__ . '/../../public/assets/' . $filename;
        $contents = file_get_contents($path);

        return $contents === false ? '' : $contents;
    }

    /**
     * @param mixed $payload
     */
    private function json(mixed $payload): string
    {
        return (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
