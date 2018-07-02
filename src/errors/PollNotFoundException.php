<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace craft\errors;

use yii\base\Exception;

/**
 * Class PollNotFoundException
 *
 * @author Wesley Luyten <me@wesleyluyten.com>
 * @since 2.0
 */
class PollNotFoundException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName()
    {
        return 'Poll not found';
    }
}
