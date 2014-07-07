<?php

use Composer\Script\Event;

class Installer
{
    /**
     * Composer post install script
     *
     * @param Event $event
     */
    public static function postInstall(Event $event = null)
    {
        $skeletonRoot = dirname(__DIR__);
        $splFile = new \SplFileInfo($skeletonRoot);
        $folderName = $splFile->getFilename();
        list($vendorName, $packageName) = explode('.', $folderName);
        $jobRename = function (\SplFileInfo $file) use ($vendorName, $packageName) {
            $fineName = $file->getFilename();
            if ($file->isDir() || strpos($fineName, '.') === 0 || ! is_writable($file)) {
                return;
            }
            $contents = file_get_contents($file);
            $contents = str_ireplace('Php.Skeleton', $vendorName.'.'.$packageName, $contents);
            $contents = str_replace('Skeleton', $packageName, $contents);
            $contents = str_replace('Php', $vendorName, $contents);
            $contents = str_replace('{package_name}', strtolower("{$vendorName}/{$packageName}"), $contents);
            file_put_contents($file, $contents);
        };

        // rename file contents
        self::recursiveJob("{$skeletonRoot}/src", $jobRename);
        self::recursiveJob("{$skeletonRoot}/tests", $jobRename);
        $jobRename(new \SplFileInfo("{$skeletonRoot}/build.xml"));
        $jobRename(new \SplFileInfo("{$skeletonRoot}/phpcs.xml"));
        $jobRename(new \SplFileInfo("{$skeletonRoot}/phpdox.xml.dist"));
        $jobRename(new \SplFileInfo("{$skeletonRoot}/phpmd.xml"));
        $jobRename(new \SplFileInfo("{$skeletonRoot}/phpunit.xml.dist"));

        rename("{$skeletonRoot}/src/Skeleton.php", "{$skeletonRoot}/src/{$packageName}.php");
        rename("{$skeletonRoot}/tests/SkeletonTest.php", "{$skeletonRoot}/tests/{$packageName}Test.php");

        // composer.json
        unlink("{$skeletonRoot}/composer.json");
        rename("{$skeletonRoot}/src/composer.json", "{$skeletonRoot}/composer.json");

        self::dumpAutoload($event);

        // delete self
        unlink(__FILE__);
    }

    /**
     * @param string   $path
     * @param Callable $job
     *
     * @return void
     */
    private static function recursiveJob($path, $job)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        foreach($iterator as $file) {
            $job($file);
        }
    }

    /**
     * @param Event $event
     * @return void
     */
    private static function dumpAutoload(Event $event)
    {
        $composer = $event->getComposer();
        $config = $composer->getConfig();
        $repo = $composer->getRepositoryManager()->getLocalRepository();
        $package = $composer->getPackage();
        $installer = $composer->getInstallationManager();

        $generator = $composer->getAutoloadGenerator();
        $generator->dump($config, $repo, $package, $installer, 'composer');
    }
}
