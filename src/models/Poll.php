<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls\models;

use Craft;
use craft\base\Model;
use craft\helpers\ArrayHelper;
use luwes\polls\records\Poll as PollRecord;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

/**
 * Poll model class.
 *
 * @author Wesley Luyten <me@wesleyluyten.com>
 * @since  2.0
 *
 * @property Poll_SiteSettings[] $siteSettings Site-specific settings
 */
class Poll extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var bool Propagate entries
     */
    public $propagateQuestions = true;

    /**
     * @var
     */
    private $_siteSettings;

    /**
     * @var
     */
    private $_entryTypes;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => PollRecord::class],
            [['name', 'handle', 'siteSettings'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['siteSettings'], 'validateSiteSettings'],
        ];
    }

    /**
     * Validates the site settings.
     */
    public function validateSiteSettings()
    {
        // If this is an existing section, make sure they aren't moving it to a
        // completely different set of sites in one fell swoop
        if ($this->id) {
            $currentSiteIds = (new Query())
                ->select(['siteId'])
                ->from(['{{%polls_sites}}'])
                ->where(['pollId' => $this->id])
                ->column();

            if (empty(array_intersect($currentSiteIds, array_keys($this->getSiteSettings())))) {
                $this->addError('siteSettings', Craft::t('app', 'At least one currently-enabled site must remain enabled.'));
            }
        }

        foreach ($this->getSiteSettings() as $i => $siteSettings) {
            if (!$siteSettings->validate()) {
                $this->addModelErrors($siteSettings, "siteSettings[{$i}]");
            }
        }
    }

    /**
     * Use the translated poll name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('site', $this->name);
    }

    /**
     * Returns the poll's site-specific settings.
     *
     * @return Poll_SiteSettings[]
     */
    public function getSiteSettings(): array
    {
        if ($this->_siteSettings !== null) {
            return $this->_siteSettings;
        }

        if (!$this->id) {
            return [];
        }

        // Set them with setSiteSettings() so setPoll() gets called on them
        $this->setSiteSettings(ArrayHelper::index(Craft::$app->getPolls()->getPollSiteSettings($this->id), 'siteId'));

        return $this->_siteSettings;
    }

    /**
     * Sets the poll's site-specific settings.
     *
     * @param Poll_SiteSettings[] $siteSettings
     *
     * @return void
     */
    public function setSiteSettings(array $siteSettings)
    {
        $this->_siteSettings = ArrayHelper::index($siteSettings, 'siteId');

        foreach ($this->_siteSettings as $settings) {
            $settings->setPoll($this);
        }
    }

    /**
     * Returns the site IDs that are enabled for the section.
     *
     * @return int[]
     */
    public function getSiteIds(): array
    {
        return array_keys($this->getSiteSettings());
    }

    /**
     * Adds site-specific errors to the model.
     *
     * @param array $errors
     * @param int   $siteId
     *
     * @return void
     */
    public function addSiteSettingsErrors(array $errors, int $siteId)
    {
        foreach ($errors as $attribute => $siteErrors) {
            $key = $attribute.'-'.$siteId;
            foreach ($siteErrors as $error) {
                $this->addError($key, $error);
            }
        }
    }

    /**
     * Returns the poll's entry types.
     *
     * @return EntryType[]
     */
    public function getEntryTypes(): array
    {
        if ($this->_entryTypes !== null) {
            return $this->_entryTypes;
        }

        if (!$this->id) {
            return [];
        }

        $this->_entryTypes = Craft::$app->getPolls()->getEntryTypesByPollId($this->id);

        return $this->_entryTypes;
    }
}
