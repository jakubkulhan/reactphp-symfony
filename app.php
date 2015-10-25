#!/usr/bin/env php
<?php
namespace App;

use App\Command\RunCommand;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Console\Application;

require_once __DIR__ . "/vendor/autoload.php";

AnnotationRegistry::registerLoader("class_exists");

$app = new Application("app");
$app->add(new RunCommand());
$app->run();
