<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;

class CreatedFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    public function getGambitPattern(): string
    {
        return 'created:(\d{4}\-\d\d\-\d\d)(\.\.(\d{4}\-\d\d\-\d\d))?';
    }

    protected function conditions(SearchState $search, array $matches, bool $negate): void
    {
        $this->constrain($search->getQuery(), Arr::get($matches, 1), Arr::get($matches, 3), $negate);
    }

    public function getFilterKey(): string
    {
        return 'created';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $filterValue = $this->asString($filterValue);

        preg_match('/^'.$this->getGambitPattern().'$/i', 'created:'.$filterValue, $matches);

        $this->constrain($filterState->getQuery(), Arr::get($matches, 1), Arr::get($matches, 3), $negate);
    }

    public function constrain(Builder $query, ?string $firstDate, ?string $secondDate, bool $negate): void
    {
        // If we've just been provided with a single YYYY-MM-DD date, then find
        // discussions that were started on that exact date. But if we've been
        // provided with a YYYY-MM-DD..YYYY-MM-DD range, then find discussions
        // that were started during that period.
        if (empty($secondDate)) {
            $query->whereDate('created_at', $negate ? '!=' : '=', $firstDate);
        } else {
            $query->whereBetween('created_at', [$firstDate, $secondDate], 'and', $negate);
        }
    }
}
