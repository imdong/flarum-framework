<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;

class MentionedFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'mentioned';
    }

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $mentionedId = $this->asInt($filterValue);

        $filterState
            ->getQuery()
            ->join('post_mentions_user', 'posts.id', '=', 'post_mentions_user.post_id')
            ->where('post_mentions_user.mentions_user_id', $negate ? '!=' : '=', $mentionedId);
    }
}
