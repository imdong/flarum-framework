<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager;

use Flarum\Foundation\AbstractValidator;

class UpdateExtensionValidator extends AbstractValidator
{
    protected array $rules = [
        'extensionId' => 'required|string'
    ];
}
