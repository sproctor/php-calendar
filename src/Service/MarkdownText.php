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
namespace App\Service;

use Twig\Environment;
use Twig\Extra\Markdown\MarkdownRuntime;

/**
 * An event description's Markdown as plain text, for places that can't show HTML (structured data,
 * calendar feeds)
 */
class MarkdownText
{
    public function __construct(private Environment $twig)
    {
    }

    public function toPlainText(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }
        // Rendered the same way as on the page, then stripped, keeping a line break per paragraph or item
        $html = $this->twig->getRuntime(MarkdownRuntime::class)->convert($markdown);
        $text = html_entity_decode(strip_tags(preg_replace('~<(br|/p|/li|/h\d)>~i', "$0\n", $html)), ENT_QUOTES | ENT_HTML5);

        return trim(preg_replace(["~[ \t]+~", "~\n{2,}~"], [' ', "\n"], $text));
    }
}
