<?php

declare(strict_types=1);

namespace ImSuperlative\FilamentPhpstan\Parser;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\ParserException;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;

final class TypeStringParser
{
    public function __construct(
        protected readonly Lexer $lexer,
        protected readonly TypeParser $typeParser,
        protected readonly PhpDocParser $phpDocParser,
    ) {}

    public static function make(): self
    {
        $config = new ParserConfig(usedAttributes: []);
        $constExprParser = new ConstExprParser($config);
        $typeParser = new TypeParser($config, $constExprParser);

        return new self(
            lexer: new Lexer($config),
            typeParser: $typeParser,
            phpDocParser: new PhpDocParser($config, $typeParser, $constExprParser)
        );
    }

    public function getLexer(): Lexer
    {
        return $this->lexer;
    }

    public function getTypeParser(): TypeParser
    {
        return $this->typeParser;
    }

    public function getPhpDocParser(): PhpDocParser
    {
        return $this->phpDocParser;
    }

    public function tokenize(string $value): TokenIterator
    {
        return new TokenIterator($this->lexer->tokenize($value));
    }

    public function parsePhpDoc(string $phpDoc): PhpDocNode
    {
        return $this->phpDocParser->parse($this->tokenize($phpDoc));
    }

    /**
     * Parse a type string into a TypeNode.
     *
     * @throws ParserException When the type string is malformed
     */
    public function parseTypeString(string $typeString): TypeNode
    {
        return $this->typeParser->parse($this->tokenize($typeString));
    }
}
