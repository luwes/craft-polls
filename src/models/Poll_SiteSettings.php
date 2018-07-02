<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls\models;

use Craft;
use craft\base\Model;
use craft\validators\SiteIdValidator;
use craft\validators\UriFormatValidator;
use yii\base\InvalidConfigException;

/**
 * Poll_SiteSettings model class.
 *
 * @author Wesley Luyten <me@wesleyluyten.com>
 * @since 2.0
 */
class Poll_SiteSettings extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Poll ID
     */
    public $pollId;

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var bool Enabled by default
     */
    public $enabledByDefault = true;

    /**
     * @var bool|null Has URLs?
     */
    public $hasUrls;

    /**
     * @var string|null URI format
     */
    public $uriFormat;

    /**
     * @var string|null Entry template
     */
    public $template;

    /**
     * @var Poll|null
     */
    private $_poll;

    // Public Methods
    // =========================================================================

    /**
     * Returns the poll.
     *
     * @return Poll
     * @throws InvalidConfigException if [[pollId]] is missing or invalid
     */
    public function getPoll(): Poll
    {
        if ($this->_poll !== null) {
            return $this->_poll;
        }

        if (!$this->pollId) {
            throw new InvalidConfigException('Poll site settings model is missing its poll ID');
        }

        if (($this->_poll = Craft::$app->getPolls()->getPollById($this->pollId)) === null) {
            throw new InvalidConfigException('Invalid poll ID: ' . $this->pollId);
        }

        return $this->_poll;
    }

    /**
     * Sets the poll.
     *
     * @param Poll $poll
     */
    public function setPoll(Poll $poll)
    {
        $this->_poll = $poll;
    }

    /**
     * Returns the site.
     *
     * @return Site
     * @throws InvalidConfigException if [[siteId]] is missing or invalid
     */
    public function getSite(): Site
    {
        if (!$this->siteId) {
            throw new InvalidConfigException('Poll site settings model is missing its site ID');
        }

        if (($site = Craft::$app->getSites()->getSiteById($this->siteId)) === null) {
            throw new InvalidConfigException('Invalid site ID: ' . $this->siteId);
        }

        return $site;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        $labels = [
            'template' => Craft::t('app', 'Template'),
            'uriFormat' => Craft::t('app', 'URI Format'),
        ];

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'pollId', 'siteId'], 'number', 'integerOnly' => true],
            [['siteId'], SiteIdValidator::class],
            [['template'], 'string', 'max' => 500],
            [['uriFormat'], UriFormatValidator::class],
        ];

        if ($this->hasUrls) {
            $rules[] = [['uriFormat'], 'required'];
        }

        return $rules;
    }
}
