<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;

class EmailFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    public function apply(SearchState $search, string $bit): bool
    {
        if (! $search->getActor()->hasPermission('user.edit')) {
            return false;
        }

        return parent::apply($search, $bit);
    }

    public function getGambitPattern(): string
    {
        return 'email:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, bool $negate): void
    {
        $this->constrain($search->getQuery(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'email';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        if (! $filterState->getActor()->hasPermission('user.edit')) {
            return;
        }

        $this->constrain($filterState->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, string|array $rawEmail, bool $negate): void
    {
        $email = $this->asString($rawEmail);

        $query->where('email', $negate ? '!=' : '=', $email);
    }
}
