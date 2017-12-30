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

use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\MoFileLoader;

// TODO: Make this conditional
Debug::enable();
error_reporting(-1);
ini_set('display_errors', '1');

$request = Request::createFromGlobals();

try {
    $context = new Context($request);
    
    if ($context->getLang() != 'en') {
        $translator = new Translator($context->getLang(), new MessageSelector());
        $translator->addLoader('mo', new MoFileLoader());
        $translator->addResource('mo', __DIR__ . "locale/" . $context->getLang()
            . "/LC_MESSAGES/messages.mo", $context->getLang());
    }
    
    $page = $context->getPage();
    $response = $page->action($context);
    $response->send();
} catch (PermissionException $e) {
    $msg = __('You do not have permission to do that: ') . $e->getMessage();
    if ($context->getUser()->is_user()) {
        echo error_message_redirect($context, $msg, $context->script);
    } else {
        echo error_message_redirect($context, $msg, "{$context->script}?action=login");
    }
} catch (InvalidConfigException $e) {
    (new RedirectResponse("/install"))->send();
} catch (InvalidInputException $e) {
    if ($context !== null) {
        (new Response($context->twig->render('error.html.twig', array('message' => $e->getMessage()))))->send();
    } else {
        throw $e;
    }
}
