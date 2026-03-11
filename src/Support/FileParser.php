<?php

declare(strict_types=1);

namespace ImSuperlative\PhpstanFilament\Support;

use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PhpParser\Parser;

final class FileParser
{
    public function __construct(
        protected readonly Parser $parser,
        protected readonly NodeFinder $nodeFinder,
    ) {}

    /** @return array<Stmt>|null */
    public function parseFile(string $filePath): ?array
    {
        $code = file_get_contents($filePath);

        return $code !== false ? $this->parse($code) : null;
    }

    /** @return array<Stmt> */
    public function parse(string $code): array
    {
        return $this->parser->parse($code) ?? [];
    }

    public function parser(): Parser
    {
        return $this->parser;
    }

    public function nodeFinder(): NodeFinder
    {
        return $this->nodeFinder;
    }
}
