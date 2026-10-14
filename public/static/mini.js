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
 * The mini calendar (calendar/mini.html.twig): pointing at or focusing a day shows its events under the
 * grid. On touch screens the first tap on a day number chooses the day rather than opening it; the
 * day's heading under the grid opens it.
 */
addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.phpc-mini').forEach((mini) => {
        const show = (cell) => {
            mini.querySelectorAll('.phpc-mini-selected').forEach((c) => c.classList.remove('phpc-mini-selected'));
            cell.classList.add('phpc-mini-selected');
            mini.querySelectorAll('[data-day-panel]').forEach((panel) => {
                panel.hidden = panel.dataset.dayPanel !== cell.dataset.day;
            });
        };
        mini.querySelectorAll('.phpc-mini-day').forEach((cell) => {
            cell.addEventListener('mouseenter', () => show(cell));
            cell.addEventListener('focusin', () => show(cell));
            // Whether the day was already chosen when the tap started, before focus chose it
            const number = cell.querySelector('.phpc-mini-number');
            let chosen = true;
            number.addEventListener('pointerdown', (event) => {
                chosen = event.pointerType !== 'touch' || cell.classList.contains('phpc-mini-selected');
            });
            number.addEventListener('click', (event) => {
                if (!chosen) {
                    event.preventDefault();
                    show(cell);
                }
                chosen = true;
            });
        });
    });
});
