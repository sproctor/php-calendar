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
namespace App\View;

use App\Entity\View;
use App\Repository\ViewRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Gives controllers the View named by the route's {slug}: a saved view, or the built-in one of every
 * calendar for "all"
 */
class ViewValueResolver implements ValueResolverInterface
{
    public function __construct(
        private ViewRepository $viewRepository,
        private AllCalendarsView $allCalendars,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getType() !== View::class) {
            return [];
        }
        $slug = (string) $request->attributes->get('slug');
        $view = $slug === View::ALL ? $this->allCalendars->build() : $this->viewRepository->findOneBy(['slug' => $slug]);

        return [$view ?? throw new NotFoundHttpException('No such view')];
    }
}
