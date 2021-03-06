<?php namespace Construction;

use Construction\Composer;
use Mockery as m;

class ComposerTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }



    public function testReadComposerJson()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());

        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldReceive('comment')
            ->once()
            ->with("Foreman", "Reading composer.json from {$composerPath}");

        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );

        $this->assertEquals(
            json_decode($this->getComposerJson(), true),
            $composer->getComposerArray()
        );
    }



    public function testRequirePackages()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';
        $require = $this->getConfig()['require'];

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        foreach ($require as $package) {

            $pkg = $package['package'];
            $ver = $package['version'];

            $mCmd->shouldReceive('comment')
                ->once()
                ->with("Composer", "Require: {$pkg} {$ver}");
        }
        

        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
        $composer->requirePackages();

        $expectedPackages = [
            'laravel/framework'  => '4.1.*',
            'nesbot/Carbon'      => '*',
            'doctrine/inflector' => '1.0.*@dev'
        ];

        $this->assertEquals(
            $expectedPackages,
            $composer->getComposerArray()[Composer::REQUIRE_DEPENDENCIES]
        );

    }


    public function testRequireDevPackagesWithNoReqDevAlready()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';
        $require = $this->getConfig()['require-dev'];

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        foreach ($require as $package) {

            $pkg = $package['package'];
            $ver = $package['version'];

            $mCmd->shouldReceive('comment')
                ->once()
                ->with("Composer", "Require Dev: {$pkg} {$ver}");
        }
        
        //test on a config with NO require-dev
        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
        $composer->requireDevPackages();

        $expectedPackages = [
            'mockery/mockery'           =>'dev-master@dev',
            'fzaninotto/faker'          => '1.3.*',
            'squizlabs/php_codesniffer' => '*'
        ];

        $this->assertEquals(
            $expectedPackages,
            $composer->getComposerArray()[Composer::REQUIRE_DEV_DEPENDENCIES]
        );
    }



    public function testRequireDevPackagesWithExistingReqDev()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';
        $require = $this->getConfig()['require-dev'];

        $jArray = json_decode($this->getComposerJson(), true);
        $jArray['require-dev'] = ["mockery/mockery" =>"dev-master@dev"];
        $json = json_encode($jArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($json);
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        foreach ($require as $package) {

            $pkg = $package['package'];
            $ver = $package['version'];

            $mCmd->shouldReceive('comment')
                ->once()
                ->with("Composer", "Require Dev: {$pkg} {$ver}");
        }
        
        //test on a config with NO require-dev
        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
        $composer->requireDevPackages();

        $expectedPackages = [
            'mockery/mockery'           =>'dev-master@dev',
            'fzaninotto/faker'          => '1.3.*',
            'squizlabs/php_codesniffer' => '*'
        ];

        $this->assertEquals(
            $expectedPackages,
            $composer->getComposerArray()[Composer::REQUIRE_DEV_DEPENDENCIES]
        );
    }


    public function testAutoloadClassmap()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';
        $classmap = $this->getConfig()['autoload']['classmap'];

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        foreach ($classmap as $entry) {

            $mCmd->shouldReceive('comment')
                ->once()
                ->with("Composer", "Autoload Classmap adding: {$entry}");
        }
        

        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
        $composer->autoloadClassmap();

        $expectedEntries = [
            'app/lib',
            'app/commands',
            'app/controllers',
            'app/models',
            'app/database/migrations',
            'app/database/seeds',
            'app/test/TestCase.php'
        ];

        $this->assertEquals(
            $expectedEntries,
            array_get($composer->getComposerArray(), Composer::AUTOLOAD_CLASSMAP)
        );

    }



    public function testAutoloadPsr0()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';
        $load = $this->getConfig()['autoload']['psr-0'];

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        foreach ($load as $name => $value) {

            $mCmd->shouldReceive('comment')
                ->once()
                ->with("Composer", "Adding PSR0 entry {$name} => {$value}");
        }
        

        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
        $composer->autoloadPsr0();

        $expectedEntries = [
            "Acme" => "app/lib"
        ];

        $this->assertEquals(
            $expectedEntries,
            array_get($composer->getComposerArray(), Composer::AUTOLOAD_PSR0)
        );

    }



    public function testAutoloadPsr4()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';
        $load = $this->getConfig()['autoload']['psr-4'];

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        foreach ($load as $name => $value) {

            $mCmd->shouldReceive('comment')
                ->once()
                ->with("Composer", "Adding PSR4 entry {$name} => {$value}");
        }
        

        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
        $composer->autoloadPsr4();

        $expectedEntries = [
            "Foo\\Bar\\" => "src/Foo/Bar/"
        ];

        $this->assertEquals(
            $expectedEntries,
            array_get($composer->getComposerArray(), Composer::AUTOLOAD_PSR4)
        );

    }


    public function testGetComposerJson()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
   
        $this->assertEquals(
            $this->getComposerJson(),
            $composer->getComposerJson()
        );
    }


    public function testWriteComposerJson()
    {
        $appDir = '/path/to/app';
        $composerPath = $appDir.DIRECTORY_SEPARATOR.'composer.json';

        $mFS = m::mock('Illuminate\Filesystem\Filesystem');
        $mFS->shouldReceive('get')
            ->once()
            ->with($composerPath)
            ->andReturn($this->getComposerJson());

        $mFS->shouldReceive('put')
            ->once()
            ->with($composerPath, $this->getComposerJson());
   
        $mCmd = m::mock('Console\BuildCommand');
        $mCmd->shouldIgnoreMissing();

        $mCmd->shouldReceive('comment')
            ->once()
            ->with("Foreman", "Writing composer file to {$composerPath}");

        $composer = new Composer(
            $appDir,
            $this->getConfig(),
            $mFS,
            $mCmd
        );
        $composer->writeComposerJson();
    }


    private function getConfig()
    {
        return [
            'require' => [
                ['package' => 'laravel/framework', 'version' => '4.1.*'],
                ['package' => 'nesbot/Carbon', 'version' => '*'],
                ['package' => 'doctrine/inflector', 'version' => '1.0.*@dev'],
            ],
            'require-dev' => [
                ['package' => 'mockery/mockery', 'version' => 'dev-master@dev'],
                ['package' => 'fzaninotto/faker', 'version' => '1.3.*'],
                ['package' => 'squizlabs/php_codesniffer', 'version' => '*'],
            ],
            'autoload' => [
                'classmap' => [
                    'app/lib',
                    'app/commands',
                    'app/controllers',
                    'app/models',
                    'app/database/migrations',
                    'app/database/seeds',
                    'app/test/TestCase.php'
                ],
                'psr-0' => [
                    "Acme" => "app/lib"
                ],
                'psr-4' => [
                    "Foo\\Bar\\" => "src/Foo/Bar/"
                ]
            ]
        ];
    }


    private function getComposerJson()
    {
        return $json = <<<JSON
{
    "name": "laravel/laravel",
    "description": "The Laravel Framework.",
    "keywords": [
        "framework",
        "laravel"
    ],
    "license": "MIT",
    "require": {
        "laravel/framework": "4.1.*"
    },
    "autoload": {
        "classmap": [
            "app/commands",
            "app/controllers",
            "app/models",
            "app/database/migrations",
            "app/database/seeds",
            "app/tests/TestCase.php"
        ]
    },
    "scripts": {
        "post-install-cmd": [
            "php artisan clear-compiled",
            "php artisan optimize"
        ],
        "post-update-cmd": [
            "php artisan clear-compiled",
            "php artisan optimize"
        ],
        "post-create-project-cmd": [
            "php artisan key:generate"
        ]
    },
    "config": {
        "preferred-install": "dist"
    },
    "minimum-stability": "stable"
}
JSON;
    }
}
