<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Tests\integration\api;

use Flarum\PackageManager\Tests\integration\ChangeComposerConfig;
use Flarum\PackageManager\Tests\integration\DummyExtensions;
use Flarum\PackageManager\Tests\integration\RefreshComposerSetup;
use Flarum\PackageManager\Tests\integration\TestCase;

class MajorUpdateTest extends TestCase
{
    use RefreshComposerSetup, ChangeComposerConfig, DummyExtensions;

    /**
     * @test--
     */
    public function cannot_update_when_no_update_check_ran()
    {
        $this->makeDummyExtensionCompatibleWith("flarum/dummy-incompatible-extension", ">=0.1.0-beta.15 <=0.1.0-beta.16");
        $this->setComposerConfig([
            'require' => [
                'flarum/core' => '^0.1.0-beta.15',
                'flarum/tags' => '^0.1.0-beta.15',
                'flarum/dummy-incompatible-extension' => '^1.0.0'
            ],
            'minimum-stability' => 'beta',
        ]);

        $response = $this->send(
            $this->request('POST', '/api/package-manager/major-update', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('no_new_major_version', json_decode($response->getBody()->getContents(), true)['errors'][0]['code']);
    }

    /**
     * @test--
     */
    public function can_update_when_major_update_available()
    {
        $this->makeDummyExtensionCompatibleWith("flarum/dummy-compatible-extension", "^0.1.0-beta.15 | ^1.0.0");
        $this->setComposerConfig([
            'require' => [
                'flarum/core' => '^0.1.0-beta.15',
                'flarum/tags' => '^0.1.0-beta.15',
                'flarum/dummy-compatible-extension' => '^1.0.0'
            ],
            'minimum-stability' => 'beta',
        ]);

        $this->send(
            $this->request('POST', '/api/package-manager/check-for-updates', [
                'authenticatedAs' => 1,
            ])
        );

        $response = $this->send(
            $this->request('POST', '/api/package-manager/major-update', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertPackageVersion("flarum/core", "^1.0.0");
        $this->assertPackageVersion("flarum/tags", "*");
        $this->assertPackageVersion("flarum/dummy-compatible-extension", "*");
    }

    /**
     * @test
     */
    public function cant_update_with_incompatible_extensions()
    {
        $this->makeDummyExtensionCompatibleWith("flarum/dummy-incompatible-extension-a", ">=0.1.0-beta.15 <0.1.0-beta.16");
        $this->makeDummyExtensionCompatibleWith("flarum/dummy-incompatible-extension-b", ">=0.1.0-beta.15 <=0.1.0-beta.16");
        $this->makeDummyExtensionCompatibleWith("flarum/dummy-incompatible-extension-c", "0.1.0-beta.15");
        $this->setComposerConfig([
            'require' => [
                'flarum/core' => '^0.1.0-beta.15',
                'flarum/tags' => '^0.1.0-beta.15',
                'flarum/dummy-incompatible-extension-a' => '^1.0.0',
                'flarum/dummy-incompatible-extension-b' => '^1.0.0',
                'flarum/dummy-incompatible-extension-c' => '^1.0.0',
            ],
            'minimum-stability' => 'beta',
        ]);

        $this->send(
            $this->request('POST', '/api/package-manager/check-for-updates', [
                'authenticatedAs' => 1,
            ])
        );

        $response = $this->send(
            $this->request('POST', '/api/package-manager/major-update', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals('extensions_incompatible_with_new_major', $this->errorGuessedCause($response));
        $this->assertEquals([
            'flarum/dummy-incompatible-extension-a',
            'flarum/dummy-incompatible-extension-b',
            'flarum/dummy-incompatible-extension-c'
        ], $this->errorDetails($response)['incompatible_extensions']);
    }
}
