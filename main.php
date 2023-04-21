<?php

require 'vendor/autoload.php';

use Symfony\Component\Finder\Finder;

$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->path('/^[\w-]+\/composer\.lock/');

$url = getenv("CODING_URL");
$login = getenv("CODING_LOGIN");
$password = getenv("CODING_PASSWORD");

foreach ($finder as $value) {
    $json = json_decode(
        file_get_contents($value->getPathname()),
        true
    );

    $packages = $json['packages'] ?? [];
    foreach ($packages as $package) {
        $name = $package['name'] ?? null;
        $version = $package['version'] ?? null;
        $packageUrl = $package['dist']['url'] ?? null;

        if ($name && $version && $packageUrl) {
            $dir = dirname($name);
            $realDir = dirname($value->getRealPath()) . '/vendor/' . $name;
            @mkdir(__DIR__ . '/build/' . $dir, 0777, true);

            $build = __DIR__ . '/build/' . $name . '.zip';
            $cmd = "cd {$realDir} && zip -r {$build} .";
            @exec($cmd);

            $client = new \GuzzleHttp\Client([
                'http_errors' => false,
            ]);

            chmod("build/{$name}.zip", 777);

            $res = $client->put("{$url}?version={$version}", [
                'auth' => [$login, $password],
                'multipart' => [
                    [
                        'name' => $name,
                        'contents' => fopen("build/{$name}.zip", 'r')
                    ]
                ]
            ]);

            var_dump($res->getStatusCode());
        }
    }
}
