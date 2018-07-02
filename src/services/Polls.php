<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls\services;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\elements\Entry;
use craft\errors\EntryTypeNotFoundException;
use craft\helpers\ArrayHelper;
use craft\models\EntryType;
use craft\models\Structure;
use craft\queue\jobs\ResaveElements;
use craft\records\EntryType as EntryTypeRecord;
use luwes\polls\errors\PollNotFoundException;
use luwes\polls\events\EntryTypeEvent;
use luwes\polls\events\PollEvent;
use luwes\polls\models\Poll;
use luwes\polls\models\Poll_SiteSettings;
use luwes\polls\records\Poll as PollRecord;
use luwes\polls\records\Poll_SiteSettings as Poll_SiteSettingsRecord;
use yii\base\Component;
use yii\base\Exception;

class Polls extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event PollEvent The event that is triggered before a poll is saved.
     */
    const EVENT_BEFORE_SAVE_POLL = 'beforeSavePoll';

    /**
     * @event PollEvent The event that is triggered after a poll is saved.
     */
    const EVENT_AFTER_SAVE_POLL = 'afterSavePoll';

    // Properties
    // =========================================================================

    /**
     * @var
     */
    private $_allPollIds;

    /**
     * @var
     */
    private $_editablePollIds;

    /**
     * @var
     */
    private $_pollsById;

    /**
     * @var bool
     */
    private $_fetchedAllPolls = false;

    /**
     * @var
     */
    private $_entryTypesById;

    // Public Methods
    // =========================================================================

    // Polls
    // -------------------------------------------------------------------------

    /**
     * Returns all of the poll IDs.
     *
     * ---
     *
     * ```php
     * $pollIds = Craft::$app->polls->allPollIds;
     * ```
     * ```twig
     * {% set pollIds = craft.app.polls.allPollIds %}
     * ```
     *
     * @return int[] All the polls’ IDs.
     */
    public function getAllPollIds(): array
    {
        if ($this->_allPollIds !== null) {
            return $this->_allPollIds;
        }

        $this->_allPollIds = [];

        foreach ($this->getAllPolls() as $poll) {
            $this->_allPollIds[] = $poll->id;
        }

        return $this->_allPollIds;
    }

    /**
     * Returns all polls.
     *
     * ---
     *
     * ```php
     * $polls = Craft::$app->polls->allPolls;
     * ```
     * ```twig
     * {% set polls = craft.app.polls.allPolls %}
     * ```
     *
     * @return Poll[] All the polls.
     */
    public function getAllPolls(): array
    {
        if ($this->_fetchedAllPolls) {
            return array_values($this->_pollsById);
        }

        $results = $this->_createPollQuery()
            ->all();

        $this->_pollsById = [];

        foreach ($results as $result) {
            $poll = new Poll($result);
            $this->_pollsById[$poll->id] = $poll;
        }

        $this->_fetchedAllPolls = true;

        return array_values($this->_pollsById);
    }

    /**
     * Saves a poll.
     *
     * ---
     *
     * ```php
     * use craft\models\Poll;
     * use craft\models\Poll_SiteSettings;
     *
     * $poll = new Poll([
     *     'name' => 'News',
     *     'handle' => 'news',
     *     'siteSettings' => [
     *         new Poll_SiteSettings([
     *             'siteId' => Craft::$app->sites->getPrimarySite()->id,
     *             'enabledByDefault' => true,
     *             'hasUrls' => true,
     *             'uriFormat' => 'foo/{slug}',
     *             'template' => 'foo/_entry',
     *         ]),
     *     ]
     * ]);
     *
     * $success = Polls::getInstance()->polls->savePoll($poll);
     * ```
     *
     * @param Poll $poll The poll to be saved
     * @param bool $runValidation Whether the poll should be validated
     * @return bool
     * @throws PollNotFoundException if $poll->id is invalid
     * @throws \Throwable if reasons
     */
    public function savePoll(Poll $poll, bool $runValidation = true): bool
    {
        $isNewPoll = !$poll->id;

        // Fire a 'beforeSavePoll' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_POLL)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_POLL, new PollEvent([
                'poll' => $poll,
                'isNew' => $isNewPoll
            ]));
        }

        if ($runValidation && !$poll->validate()) {
            Craft::info('Poll not saved due to validation error.', __METHOD__);
            return false;
        }

        if (!$isNewPoll) {
            $pollRecord = PollRecord::find()
                ->where(['id' => $poll->id])
                ->with('structure')
                ->one();

            if (!$pollRecord) {
                throw new PollNotFoundException("No poll exists with the ID '{$poll->id}'");
            }

            $oldPoll = new Poll($pollRecord->toArray([
                'id',
                'name',
                'handle',
                'type',
                'propagateQuestions',
            ]));
        } else {
            $pollRecord = new PollRecord();
        }

        /** @var PollRecord $pollRecord */
        $pollRecord->name = $poll->name;
        $pollRecord->handle = $poll->handle;
        $pollRecord->propagateQuestions = (bool)$poll->propagateQuestions;

        // Get the site settings
        $allSiteSettings = $poll->getSiteSettings();

        if (empty($allSiteSettings)) {
            throw new Exception('Tried to save a poll without any site settings');
        }

        $db = Craft::$app->getDb();
        $transaction = $db->beginTransaction();

        try {
            $pollRecord->save(false);

            // Now that we have a poll ID, save it on the model
            if ($isNewPoll) {
                $poll->id = $pollRecord->id;
            }

            // Might as well update our cache of the poll while we have it. (It's possible that the URL format
            //includes {poll.handle} or something...)
            $this->_pollsById[$poll->id] = $poll;

            // Update the site settings
            // -----------------------------------------------------------------

            if (!$isNewPoll) {
                // Get the old poll site settings
                $allOldSiteSettingsRecords = Poll_SiteSettingsRecord::find()
                    ->where(['pollId' => $poll->id])
                    ->indexBy('siteId')
                    ->all();
            } else {
                $allOldSiteSettingsRecords = [];
            }

            foreach ($allSiteSettings as $siteId => $siteSettings) {
                // Was this already selected?
                if (!$isNewPoll && isset($allOldSiteSettingsRecords[$siteId])) {
                    $siteSettingsRecord = $allOldSiteSettingsRecords[$siteId];
                } else {
                    $siteSettingsRecord = new Poll_SiteSettingsRecord();
                    $siteSettingsRecord->pollId = $poll->id;
                    $siteSettingsRecord->siteId = $siteId;
                }

                $siteSettingsRecord->enabledByDefault = $siteSettings->enabledByDefault;

                if ($siteSettingsRecord->hasUrls = $siteSettings->hasUrls) {
                    $siteSettingsRecord->uriFormat = $siteSettings->uriFormat;
                    $siteSettingsRecord->template = $siteSettings->template;
                } else {
                    $siteSettingsRecord->uriFormat = $siteSettings->uriFormat = null;
                    $siteSettingsRecord->template = $siteSettings->template = null;
                }

                $siteSettingsRecord->save(false);

                // Set the ID on the model
                $siteSettings->id = $siteSettingsRecord->id;
            }

            if (!$isNewPoll) {
                // Drop any sites that are no longer being used, as well as the associated entry/element site
                // rows
                $siteIds = array_keys($allSiteSettings);

                /** @noinspection PhpUndefinedVariableInspection */
                foreach ($allOldSiteSettingsRecords as $siteId => $siteSettingsRecord) {
                    if (!in_array($siteId, $siteIds, false)) {
                        $siteSettingsRecord->delete();
                    }
                }
            }

            // Finally, deal with the existing entries...
            // -----------------------------------------------------------------

            if (!$isNewPoll) {
                if ($poll->propagateQuestions) {
                    // Find a site that the poll was already enabled in, and still is
                    $oldSiteIds = array_keys($allOldSiteSettingsRecords);
                    $newSiteIds = array_keys($allSiteSettings);
                    $persistentSiteIds = array_values(array_intersect($newSiteIds, $oldSiteIds));

                    // Try to make that the primary site, if it's in the list
                    $siteId = Craft::$app->getSites()->getPrimarySite()->id;
                    if (!in_array($siteId, $persistentSiteIds, false)) {
                        $siteId = $persistentSiteIds[0];
                    }

                    Craft::$app->getQueue()->push(new ResaveElements([
                        'description' => Craft::t('app', 'Resaving {poll} entries', [
                            'poll' => $poll->name,
                        ]),
                        'elementType' => Entry::class,
                        'criteria' => [
                            'siteId' => $siteId,
                            'pollId' => $poll->id,
                            'status' => null,
                            'enabledForSite' => false,
                        ]
                    ]));
                } else {
                    // Resave entries for each site
                    foreach ($allSiteSettings as $siteId => $siteSettings) {
                        Craft::$app->getQueue()->push(new ResaveElements([
                            'description' => Craft::t('app', 'Resaving {poll} entries ({site})', [
                                'poll' => $poll->name,
                                'site' => $siteSettings->getSite()->name,
                            ]),
                            'elementType' => Entry::class,
                            'criteria' => [
                                'siteId' => $siteId,
                                'pollId' => $poll->id,
                                'status' => null,
                                'enabledForSite' => false,
                            ]
                        ]));
                    }
                }
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSavePoll' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_POLL)) {
            $this->trigger(self::EVENT_AFTER_SAVE_POLL, new PollEvent([
                'poll' => $poll,
                'isNew' => $isNewPoll
            ]));
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns a Query object prepped for retrieving polls.
     *
     * @return Query
     */
    private function _createPollQuery(): Query
    {
        return (new Query())
            ->select([
                'polls.id',
                'polls.name',
                'polls.handle',
                'polls.propagateQuestions',
            ])
            ->from(['{{%polls_polls}} polls'])
            ->orderBy(['name' => SORT_ASC]);
    }
}
