<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace craft\events;

use yii\base\Event;

/**
 * Poll event class.
 *
 * @author Wesley Luyten <me@wesleyluyten.com>
 * @since 2.0
 */
class PollEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\models\Poll|null The poll model associated with the event.
     */
    public $poll;

    /**
     * @var bool Whether the poll is brand new
     */
    public $isNew = false;
}
