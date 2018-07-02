<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Class Poll_SiteSettings record.
 *
 * @property int $id ID
 * @property int $pollId Poll ID
 * @property int $siteId Site ID
 * @property bool $enabledByDefault Enabled by default
 * @property bool $hasUrls Has URLs
 * @property string $uriFormat URI format
 * @property string $template Template
 * @property Poll $poll Poll
 * @property Site $site Site
 * @author Wesley Luyten <me@wesleyluyten.com>
 * @since 2.0
 */
class Poll_SiteSettings extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%polls_polls_sites}}';
    }

    /**
     * Returns the associated poll.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getPoll(): ActiveQueryInterface
    {
        return $this->hasOne(Poll::class, ['id' => 'pollId']);
    }

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
