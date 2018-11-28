<?php //declare(strict_types=1);
/*
 * Copyright 2017 Sean Proctor
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PhpCalendar;

require_once 'vendor/autoload.php';

require_once 'bootstrap.php';

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

// TODO: Make this conditional
Debug::enable();
error_reporting(-1);
ini_set('display_errors', '1');

$request = Request::createFromGlobals();

try {
    $context = new Context($request, $entityManager);
    
    $context->getPage()->action($context)->send();
} catch (PermissionException $e) {
    $context->addMessage($e->getMessage());
    (new RedirectResponse($context->createUrl($context->user->isUser() ? null : 'login')))->send();
} catch (InvalidConfigException $e) {
    (new RedirectResponse("/install.php"))->send();
} catch (InvalidInputException $e) {
    if ($context !== null) {
        (new Response($context->render('error.html.twig', array('message' => $e->getMessage()))))->send();
    } else {
        throw $e;
    }
}
