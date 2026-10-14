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

/*
 * Embeds a calendar in another page. Add where the calendar should appear:
 *
 *   <script src="https://calendar.example.com/static/embed.js"
 *           data-src="https://calendar.example.com/en/calendar/club-events/"></script>
 *
 * data-src is any month or day view, event or search URL. The iframe grows to fit its content. Optional:
 *
 *   data-title        the iframe's accessible title
 *   data-min-height   pixels, default 400
 *   data-header="0"   hides the bar with the calendar's name and "Open the full calendar"
 *   data-background   "transparent" or a color like "#f8f4e8"
 *   data-accent       a color like "#0a6" for links, today and buttons
 *   data-font         a font-family list like "Georgia, serif"
 *   data-mode         "light" (default), "dark" or "auto" to follow the visitor's browser
 *   data-theme        a theme ID, instead of the calendar's theme
 *
 * The calendar admin page's Embed tab writes this code for you.
 */
(function () {
    const script = document.currentScript;
    if (!script || !script.dataset.src) {
        console.error('PHP Calendar embed: add a data-src attribute with the calendar URL to the script tag');
        return;
    }
    const src = new URL(script.dataset.src, script.src);
    src.searchParams.set('embed', '1');
    // data-* attribute → query parameter; see EmbedOptions
    const options = {header: 'header', background: 'bg', accent: 'accent', font: 'font', mode: 'mode', theme: 'theme'};
    for (const [attribute, param] of Object.entries(options)) {
        if (script.dataset[attribute]) {
            src.searchParams.set(param, script.dataset[attribute]);
        }
    }

    const iframe = document.createElement('iframe');
    iframe.src = src.toString();
    iframe.title = script.dataset.title || 'Calendar';
    iframe.loading = 'lazy';
    iframe.style.width = '100%';
    iframe.style.border = '0';
    iframe.style.minHeight = (parseInt(script.dataset.minHeight, 10) || 400) + 'px';
    // A transparent frame only shows the page behind it when both use the same color scheme
    iframe.style.colorScheme = {dark: 'dark', auto: 'light dark'}[script.dataset.mode] || 'light';
    script.after(iframe);

    window.addEventListener('message', (event) => {
        if (event.origin === src.origin && event.source === iframe.contentWindow
            && event.data && event.data.type === 'phpc-height' && Number.isFinite(event.data.height)) {
            iframe.style.height = Math.ceil(event.data.height) + 'px';
        }
    });
})();
