<?php

use App\Service\UtmClient;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

if (!file_exists(__DIR__.'/../app/config/settings.yml')) {
    die('Настройте приложение!');
}

require __DIR__.'/../vendor/autoload.php';

$app = new Application();

$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), ['twig.path' => __DIR__.'/../app/views']);
$app->register(new Rpodwika\Silex\YamlConfigServiceProvider(__DIR__.'/../app/config/settings.yml'));
$app['utm'] = function () use ($app) {
    return new UtmClient($app['config']);
};

$app->get('/', function () use ($app) {
    $utmList = $app['config']['utm'];

    return $app['twig']->render('index.twig', ['utmList' => array_keys($utmList)]);
});

$app->post('/resendDoc', function (Request $request) use ($app) {
    $utm = $request->get('utm');
    $ttn = $request->get('ttn');

    return $app['twig']->render('resendResult.twig', [
        'result' => $app['utm']->resendDoc($ttn, $utm),
        'utm' => $utm,
    ]);
});

$app->run();
