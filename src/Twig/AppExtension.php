<?php
/*
 * Copyright Sean Proctor
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

namespace App\Twig;

use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'menu_item',
                [$this, 'menuItem'],
                ['needs_context' => true]
            ),
            new TwigFunction(
                'append_parameter_url',
                [$this, 'append_parameter_url']
            )
        ];
    }

    /**
     * Takes a menu $html and appends an entry
     *
     * @param Context     $context
     * @param string      $action
     * @param string      $text
     * @param string|null $icon
     * @return string
     */
    function menuItem($context, $action, $text, $icon = null)
    {
        print("menu item");
        var_dump($context);
        $url = htmlentities($context->createDateUrl($action));
        $active = $context->getAction() == $action ? " active" : "";
        if ($icon != null) {
            $text = "<i class=\"fas fa-$icon\"></i> $text";
        }
        return "<li class=\"nav-item$active\"><a class=\"nav-link\" href=\"$url\">$text</a></li>";
    }

    /**
     * @param Request $request
     * @param string $parameter
     * @return string
     */
    function append_parameter_url(Request $request, string $parameter): string
    {
        $uri = $request->getRequestUri();
        if (strpos($uri, "?") !== false) {
            $uri .= '&';
        } else {
            $uri .= '?';
        }
        return $uri.$parameter;
    }
}