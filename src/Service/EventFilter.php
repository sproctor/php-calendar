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

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Which events a month or day page shows: those in one category, by one author, or both. Comes from the
 * page's `category` and `author` query parameters, and only takes choices the visitor is offered, so an
 * unknown or hidden one shows everything.
 */
final class EventFilter
{
    public function __construct(
        public readonly ?Category $category = null,
        public readonly ?User $author = null,
    ) {
    }

    /**
     * @param Category[] $categories the categories the visitor can choose
     * @param User[] $authors the authors the visitor can choose
     */
    public static function fromRequest(Request $request, array $categories, array $authors): self
    {
        $catid = $request->query->getInt('category');
        $uid = $request->query->getInt('author');

        return new self(
            array_values(array_filter($categories, fn(Category $c) => $c->getCatid() === $catid))[0] ?? null,
            array_values(array_filter($authors, fn(User $u) => $u->getUid() === $uid))[0] ?? null,
        );
    }

    public function isActive(): bool
    {
        return $this->category !== null || $this->author !== null;
    }

    public function matches(Event $event): bool
    {
        return ($this->category === null || $event->getCategory()?->getCatid() === $this->category->getCatid())
            && ($this->author === null || $event->isOwner($this->author));
    }

    /**
     * The query parameters that keep this filter in links
     *
     * @return array{category?: int, author?: int}
     */
    public function query(): array
    {
        return array_filter([
            'category' => $this->category?->getCatid(),
            'author' => $this->author?->getUid(),
        ], fn(?int $id) => $id !== null);
    }
}
