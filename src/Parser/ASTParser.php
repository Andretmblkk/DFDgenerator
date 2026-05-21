<?php

declare(strict_types=1);

namespace LaravelDfd\Parser;

use PhpParser\Node;
use PhpParser\ParserFactory;

final class ASTParser
{
    /**
     * @return array<int, Node>
     */
    public function parse(string $source): array
    {
        return (new ParserFactory())
            ->createForNewestSupportedVersion()
            ->parse($source) ?? [];
    }

    /**
     * @return array<int, Node>
     */
    public function parseFile(string $path): array
    {
        $source = file_get_contents($path);

        if ($source === false) {
            return [];
        }

        return $this->parse($source);
    }
}
