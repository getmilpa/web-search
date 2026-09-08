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

namespace Milpa\WebSearch\Tests;

use Milpa\WebSearch\WebSearchOperations;
use PHPUnit\Framework\TestCase;

/**
 * The manifest this package PUBLISHES is what the registry reads, so it is testable from disk.
 *
 * A capability is discovered by an app in two moves, and both live in `composer.json`:
 *
 *   1. `https://packagist.org/packages/list.json?type=milpa-capability` enumerates the packages
 *      — that listing keys on `type`, so a package published as `library` is INVISIBLE to it;
 *   2. `https://repo.packagist.org/p2/<name>.json` carries the newest version's whole manifest,
 *      and `extra.milpa.capability` is the contract the app reads from it.
 *
 * Either half alone announces nothing: a contract nobody enumerates is never fetched, and a type
 * with no contract is enumerated and then discarded. Measured in the greenhouse (evidence/0568):
 * this package carried the contract and shipped `"type": "library"`, so the marketplace index saw
 * 9 of 13 capability packages. That is why the assertion is BOTH halves in one test — an edit that
 * drops either one goes red here.
 */
final class PackageManifestTest extends TestCase
{
    public function testTheManifestDeclaresBothHalvesOfDiscoveryTheCapabilityContractAndTheTypeThatEnumeratesIt(): void
    {
        $path = \dirname(__DIR__) . '/composer.json';
        self::assertFileExists($path);

        $manifest = json_decode((string) file_get_contents($path), true);
        self::assertIsArray($manifest, 'the manifest must be readable JSON');

        self::assertSame(
            'milpa-capability',
            $manifest['type'] ?? null,
            'without this type the package is absent from list.json?type=milpa-capability — Composer defaults an omitted type to "library"',
        );

        $capability = $manifest['extra']['milpa']['capability'] ?? null;
        self::assertIsArray($capability, 'extra.milpa.capability is the contract the app reads from the registry');
        self::assertSame('web-search', $capability['id'] ?? null);
        self::assertSame(['web:search'], $capability['unlocks'] ?? null);
        self::assertSame(
            [WebSearchOperations::class],
            $capability['operations'] ?? null,
            'the enumerated package is only useful if its manifest names the provider the host builds',
        );
    }
}
