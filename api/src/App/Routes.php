<?php

declare(strict_types=1);

$app->post('/auth', 'App\Controller\Home:doLogin')->setName('login');
$app->get('/messages/{id}', 'App\Controller\Messages:getMessages');
