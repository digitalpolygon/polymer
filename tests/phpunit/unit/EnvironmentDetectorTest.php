<?php

namespace DigitalPolygon\PolymerTest\phpunit\unit;

use DigitalPolygon\Polymer\Core\Environment\AcquiaEnvironmentDetector;
use DigitalPolygon\Polymer\Core\Environment\EnvironmentDetectorBase;
use DigitalPolygon\Polymer\Core\Environment\PantheonEnvironmentDetector;
use PHPUnit\Framework\TestCase;

/**
 * Environment detectors (PWT-165): pure getenv() logic, driven by
 * controlled environment variables.
 *
 * Every variable the detectors read is saved and cleared in setUp and
 * restored in tearDown — the suite itself runs under DDEV and GitHub
 * Actions, where IS_DDEV_PROJECT and GITHUB_ACTIONS are genuinely set.
 */
class EnvironmentDetectorTest extends TestCase
{
    private const VARS = [
        'AH_SITE_ENVIRONMENT',
        'PANTHEON_ENVIRONMENT',
        'IS_DDEV_PROJECT',
        'LANDO',
        'CIRCLECI',
        'BITBUCKET_BUILD_NUMBER',
        'GITHUB_ACTIONS',
        'GITLAB_CI',
        'JENKINS_URL',
        'TRAVIS',
    ];

    /** @var array<string, string|false> */
    private array $saved = [];

    protected function setUp(): void
    {
        foreach (self::VARS as $var) {
            $this->saved[$var] = getenv($var);
            putenv($var);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->saved as $var => $value) {
            putenv($value === false ? $var : "$var=$value");
        }
    }

    public function testCiIsNotDetectedInACleanEnvironment(): void
    {
        $this->assertFalse(EnvironmentDetectorBase::isCiEnv());
    }

    public function testEachCiProviderVariableIsDetected(): void
    {
        foreach (['CIRCLECI', 'BITBUCKET_BUILD_NUMBER', 'GITHUB_ACTIONS', 'GITLAB_CI', 'JENKINS_URL', 'TRAVIS'] as $var) {
            putenv("$var=1");
            $this->assertTrue(EnvironmentDetectorBase::isCiEnv(), "$var must mark the environment as CI.");
            putenv($var);
        }
    }

    public function testDdevDetectionRequiresTheExactTrueValue(): void
    {
        $this->assertFalse(EnvironmentDetectorBase::isDdevEnv());
        putenv('IS_DDEV_PROJECT=true');
        $this->assertTrue(EnvironmentDetectorBase::isDdevEnv());
        putenv('IS_DDEV_PROJECT=false');
        $this->assertFalse(EnvironmentDetectorBase::isDdevEnv());
    }

    public function testLandoDetection(): void
    {
        $this->assertFalse(EnvironmentDetectorBase::isLandoEnv());
        putenv('LANDO=ON');
        $this->assertTrue(EnvironmentDetectorBase::isLandoEnv());
    }

    public function testAcquiaEnvironmentIds(): void
    {
        $this->assertSame('', AcquiaEnvironmentDetector::getEnvironmentId());

        $cases = [
            // [env value, isDev, isTest, isProd]
            ['dev', true, false, false],
            ['test', false, true, false],
            ['01test', false, true, false],
            ['stg', false, true, false],
            ['stage', false, true, false],
            ['prod', false, false, true],
            ['01live', false, false, true],
            ['02live', false, false, true],
        ];
        foreach ($cases as [$env, $isDev, $isTest, $isProd]) {
            putenv("AH_SITE_ENVIRONMENT=$env");
            $this->assertSame($env, AcquiaEnvironmentDetector::getEnvironmentId());
            $this->assertSame($isDev, AcquiaEnvironmentDetector::isDevEnv(), "$env: isDevEnv");
            $this->assertSame($isTest, AcquiaEnvironmentDetector::isTestEnv(), "$env: isTestEnv");
            $this->assertSame($isProd, AcquiaEnvironmentDetector::isProdEnv(), "$env: isProdEnv");
        }
    }

    public function testPantheonEnvironmentIds(): void
    {
        $this->assertSame('', PantheonEnvironmentDetector::getEnvironmentId());

        $cases = [
            ['dev', true, false, false],
            ['test', false, true, false],
            ['live', false, false, true],
            // Multidev environments are identified but are none of the three.
            ['pr-12', false, false, false],
        ];
        foreach ($cases as [$env, $isDev, $isTest, $isProd]) {
            putenv("PANTHEON_ENVIRONMENT=$env");
            $this->assertSame($env, PantheonEnvironmentDetector::getEnvironmentId());
            $this->assertSame($isDev, PantheonEnvironmentDetector::isDevEnv(), "$env: isDevEnv");
            $this->assertSame($isTest, PantheonEnvironmentDetector::isTestEnv(), "$env: isTestEnv");
            $this->assertSame($isProd, PantheonEnvironmentDetector::isProdEnv(), "$env: isProdEnv");
        }
    }

    public function testLocalMeansNoPlatformIdAndNoCi(): void
    {
        $this->assertTrue(AcquiaEnvironmentDetector::isLocalEnv());
        $this->assertTrue(PantheonEnvironmentDetector::isLocalEnv());

        // The platform's own id kills local.
        putenv('PANTHEON_ENVIRONMENT=live');
        $this->assertFalse(PantheonEnvironmentDetector::isLocalEnv());
        putenv('PANTHEON_ENVIRONMENT');

        // CI kills local everywhere.
        putenv('GITHUB_ACTIONS=true');
        $this->assertFalse(AcquiaEnvironmentDetector::isLocalEnv());
        $this->assertFalse(PantheonEnvironmentDetector::isLocalEnv());
    }

    /**
     * The conjunction polymer.settings.php relies on: a single detector
     * reports "local" whenever its own platform variables are absent — on
     * Pantheon live, the Acquia detector still says local. Only the AND of
     * the per-platform results means "actually local".
     */
    public function testPerPlatformLocalResultsMustBeConjoined(): void
    {
        putenv('PANTHEON_ENVIRONMENT=live');

        $this->assertTrue(AcquiaEnvironmentDetector::isLocalEnv(), 'Acquia detector cannot see Pantheon.');
        $this->assertFalse(
            AcquiaEnvironmentDetector::isLocalEnv() && PantheonEnvironmentDetector::isLocalEnv(),
            'The ANDed result must not report local on Pantheon live.'
        );
    }
}
