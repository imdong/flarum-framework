<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\Http\SlugManager;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;

class TagFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected SlugManager $slugger
    ) {
    }

    protected function getGambitPattern(): string
    {
        return 'tag:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, bool $negate): void
    {
        $this->constrain($search->getQuery(), $matches[1], $negate, $search->getActor());
    }

    public function getFilterKey(): string
    {
        return 'tag';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $this->constrain($filterState->getQuery(), $filterValue, $negate, $filterState->getActor());
    }

    protected function constrain(Builder $query, string|array $rawSlugs, bool $negate, User $actor): void
    {
        $slugs = $this->asStringArray($rawSlugs);

        $query->where(function (Builder $query) use ($slugs, $negate, $actor) {
            foreach ($slugs as $slug) {
                if ($slug === 'untagged') {
                    $query->whereIn('discussions.id', function (Builder $query) {
                        $query->select('discussion_id')
                            ->from('discussion_tag');
                    }, 'or', ! $negate);
                } else {
                    // @TODO: grab all IDs first instead of multiple queries.
                    try {
                        $id = $this->slugger->forResource(Tag::class)->fromSlug($slug, $actor)->id;
                    } catch (ModelNotFoundException) {
                        $id = null;
                    }

                    $query->whereIn('discussions.id', function (Builder $query) use ($id) {
                        $query->select('discussion_id')
                            ->from('discussion_tag')
                            ->where('tag_id', $id);
                    }, 'or', $negate);
                }
            }
        });
    }
}
