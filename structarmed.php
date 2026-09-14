<?php
declare(strict_types=1);

use Boundwize\StructArmed\Architecture;

return Architecture::define()
    ->layerPattern('Foundation', [
        '/^Crustum\\\\Notification\\\\(Exception|Message|Trait)(\\\\.*)?$/',
        '/^Crustum\\\\Notification\\\\(Notification|AnonymousNotifiable|ShouldQueueInterface)$/',
    ])
    ->layerPattern('Channel', '/^Crustum\\\\Notification\\\\Channel(\\\\.*)?$/')
    ->layerPattern('Registry', '/^Crustum\\\\Notification\\\\Registry(\\\\.*)?$/')
    ->layerPattern('Event', '/^Crustum\\\\Notification\\\\Event(\\\\.*)?$/')
    ->layerPattern('Job', '/^Crustum\\\\Notification\\\\Job(\\\\.*)?$/')
    ->layerPattern('Model', '/^Crustum\\\\Notification\\\\Model(\\\\.*)?$/')
    ->layerPattern('View', '/^Crustum\\\\Notification\\\\View(\\\\.*)?$/')
    ->layerPattern('Extension', '/^Crustum\\\\Notification\\\\Extension(\\\\.*)?$/')
    ->layerPattern('Facades', [
        '/^Crustum\\\\Notification\\\\(NotificationManager|NotificationSender)$/',
    ])
    ->layerPattern('Plugin', [
        '/^Crustum\\\\Notification\\\\NotificationPlugin$/',
        '/^Crustum\\\\Notification\\\\Command(\\\\.*)?$/',
    ])
    ->layerPattern('TestSuite', '/^Crustum\\\\Notification\\\\TestSuite(\\\\.*)?$/')
    ->ruleset([
        'Foundation' => [],
        'Channel' => ['Foundation'],
        'Registry' => ['Channel', 'Foundation'],
        'Event' => ['Foundation'],
        'Job' => ['Facades', 'Foundation'],
        'Model' => ['Facades', 'Foundation'],
        'View' => ['Model', 'Foundation'],
        'Extension' => ['Registry', 'Foundation'],
        'Facades' => ['+Registry', 'Job', 'Model'],
        'Plugin' => ['+Facades', 'Extension'],
        'TestSuite' => ['+Plugin'],
    ])
    // Convenience notify()/notifyNow() delegate to the manager facade.
    ->skipClassViolation('Crustum\\Notification\\AnonymousNotifiable', 'Crustum\\Notification\\NotificationManager');
