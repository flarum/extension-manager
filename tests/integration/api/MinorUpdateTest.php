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

class MinorUpdateTest extends TestCase
{
    use RefreshComposerSetup, ChangeComposerConfig, DummyExtensions;

    /**
     * @test
     */
    public function can_update_to_next_minor_version()
    {
        $this->makeDummyExtensionCompatibleWith("flarum/dummy-compatible-extension", "^1.0.0");
        $this->setComposerConfig([
            'require' => [
                // The only reason we don't set this to `^1.0.0` and let it update to latest minor,
                // is because migrations that run DDL queries might be introduced in future versions,
                // therefore breaking the test transaction.
                'flarum/core' => '>=1.0.0 <= 1.1.0',
                // We leave tags fixed to a version,
                // the update handler must be able to set it to `*`.
                'flarum/tags' => '1.0.3',
                'flarum/lang-english' => '*',
                'flarum/dummy-compatible-extension' => '^1.0.0'
            ]
        ]);

        $response = $this->send(
            $this->request('POST', '/api/package-manager/minor-update', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertPackageVersion('flarum/tags', '*');
        $this->assertPackageVersion('flarum/dummy-compatible-extension', '*');
    }

    /**
     * @test
     */
    public function can_update_with_latest_ext_incompatible_with_latest_core()
    {
        $this->makeDummyExtensionCompatibleWith("flarum/dummy-extension", "1.0.0");
        $this->setComposerConfig([
            'require' => [
                'flarum/core' => '>=1.0.0 <=1.1.0',
                'flarum/tags' => '1.0.3',
                'flarum/lang-english' => '*',
                'flarum/dummy-extension' => '^1.0.0'
            ]
        ]);

        $response = $this->send(
            $this->request('POST', '/api/package-manager/minor-update', [
                'authenticatedAs' => 1,
            ])
        );

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertPackageVersion("flarum/tags", "*");
        $this->assertPackageVersion("flarum/dummy-extension", "*");
    }
}
