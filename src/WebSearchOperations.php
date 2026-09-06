<?php

/**
 * This file is part of milpa/web-search — a governed web search capability for Milpa apps.
 *
 * (c) Rodrigo Vicente - TeamX Agency — https://teamx.agency <hola@teamx.agency>
 *
 * @license Apache-2.0
 *
 * @link    https://github.com/getmilpa/web-search
 */

declare(strict_types=1);

namespace Milpa\WebSearch;

use Milpa\Command\CommandProvider;
use Milpa\Command\Declaration\DeclaredOperation;
use Milpa\Command\Operation;

/**
 * The capability's discovery seam: it contributes the single declared `web:search`.
 *
 * The operation itself is {@see Search} — its attributes carry the intent and its constructor the
 * input, so this provider no longer restates a JSON-Schema PHP already knew (greenhouse
 * decisions/0212). What is left here is the one thing a provider is for: naming what it contributes
 * and how its collaborator is built.
 */
class WebSearchOperations implements CommandProvider
{
    public function __construct(private readonly ?Searx $searx = null)
    {
    }

    /**
     * The operations this capability contributes to the app — here, the single `web:search`.
     *
     * @return list<Operation>
     */
    public function operations(): array
    {
        $searx = $this->searx ?? new Searx();

        return [DeclaredOperation::from(Search::class, static fn (string $type): object => $searx)];
    }
}
