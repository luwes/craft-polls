<?php
/**
 * @link      https://wesleyluyten.com
 * @copyright Copyright (c) Wesley Luyten
 * @license   https://git.io/craft-polls-license
 */

namespace luwes\polls\controllers;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use luwes\polls\Polls;
use luwes\polls\models\Poll;
use luwes\polls\models\Poll_SiteSettings;
use yii\web\Response;

class PollsController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        // All poll actions require an admin
        $this->requireAdmin();
    }

    /**
     * Polls index.
     *
     * @param array $variables
     *
     * @return Response The rendering result
     */
    public function actionIndex(array $variables = []): Response
    {
        $variables['polls'] = Polls::getInstance()->polls->getAllPolls();

        return $this->renderTemplate('polls/_index', $variables);
    }

    /**
     * Edit a poll.
     *
     * @param int|null  $pollId The poll’s id, if any.
     * @param Poll|null $poll   The poll being edited, if there were any validation errors.
     *
     * @return Response
     * @throws NotFoundHttpException if the requested poll cannot be found
     * @throws BadRequestHttpException if attempting to do something not allowed by the current Craft edition
     */
    public function actionEditPoll(int $pollId = null, Poll $poll = null): Response
    {
        $variables = [
            'pollId' => $pollId,
            'brandNewPoll' => false
        ];

        if ($pollId !== null) {
            if ($poll === null) {
                $poll = Craft::$app->getPolls()->getPollById($pollId);

                if (!$poll) {
                    throw new NotFoundHttpException('Poll not found');
                }
            }

            $variables['title'] = $poll->name;
        } else {
            if ($poll === null) {
                $poll = new Poll();
                $variables['brandNewPoll'] = true;
            }

            $variables['title'] = Craft::t('polls', 'Create a new poll');
        }

        $variables['poll'] = $poll;

        $variables['crumbs'] = [
            [
                'label' => Craft::t('polls', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('polls', 'Polls'),
                'url' => UrlHelper::url('polls')
            ],
        ];

        return $this->renderTemplate('polls/_edit', $variables);
    }

    /**
     * Saves a poll.
     *
     * @return Response|null
     * @throws BadRequestHttpException if any invalid site IDs are specified in the request
     */
    public function actionSavePoll()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $poll = new Poll();

        // Main poll settings
        $poll->id = $request->getBodyParam('pollId');
        $poll->name = $request->getBodyParam('name');
        $poll->handle = $request->getBodyParam('handle');

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $request->getBodyParam('sites.' . $site->handle);

            // Skip disabled sites if this is a multi-site install
            if (Craft::$app->getIsMultiSite() && empty($postedSettings['enabled'])) {
                continue;
            }

            $siteSettings = new Poll_SiteSettings();
            $siteSettings->siteId = $site->id;
            $allSiteSettings[$site->id] = $siteSettings;
        }

        $poll->setSiteSettings($allSiteSettings);

        // Save it
        if (!Polls::getInstance()->polls->savePoll($poll)) {
            Craft::$app->getSession()->setError(Craft::t('polls', 'Couldn’t save poll.'));

            // Send the poll back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'poll' => $poll
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('polls', 'Poll saved.'));

        return $this->redirectToPostedUrl($poll);
    }

    /**
     * Deletes a section.
     *
     * @return Response
     */
    public function actionDeleteSection(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $sectionId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getSections()->deleteSectionById($sectionId);

        return $this->asJson(['success' => true]);
    }

    // Entry Types

    /**
     * Entry types index
     *
     * @param int $sectionId The ID of the section whose entry types we’re listing
     * @return Response
     * @throws NotFoundHttpException if the requested section cannot be found
     */
    public function actionEntryTypesIndex(int $sectionId): Response
    {
        $section = Craft::$app->getSections()->getSectionById($sectionId);

        if ($section === null) {
            throw new NotFoundHttpException('Section not found');
        }

        $crumbs = [
            [
                'label' => Craft::t('polls', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('polls', 'Sections'),
                'url' => UrlHelper::url('settings/sections')
            ],
            [
                'label' => Craft::t('site', $section->name),
                'url' => UrlHelper::url('settings/sections/' . $section->id)
            ],
        ];

        $title = Craft::t('polls', '{section} Entry Types',
            ['section' => Craft::t('site', $section->name)]);

        return $this->renderTemplate('settings/sections/_entrytypes/index', [
            'sectionId' => $sectionId,
            'section' => $section,
            'crumbs' => $crumbs,
            'title' => $title,
        ]);
    }

    /**
     * Edit an entry type
     *
     * @param int $sectionId The section’s ID.
     * @param int|null $entryTypeId The entry type’s ID, if any.
     * @param EntryType|null $entryType The entry type being edited, if there were any validation errors.
     * @return Response
     * @throws NotFoundHttpException if the requested section/entry type cannot be found
     * @throws BadRequestHttpException if the requested entry type does not belong to the requested section
     */
    public function actionEditEntryType(int $sectionId, int $entryTypeId = null, EntryType $entryType = null): Response
    {
        $section = Craft::$app->getSections()->getSectionById($sectionId);

        if (!$section) {
            throw new NotFoundHttpException('Section not found');
        }

        if ($entryTypeId !== null) {
            if ($entryType === null) {
                $entryType = Craft::$app->getSections()->getEntryTypeById($entryTypeId);

                if (!$entryType) {
                    throw new NotFoundHttpException('Entry type not found');
                }

                if ($entryType->sectionId != $section->id) {
                    throw new BadRequestHttpException('Entry type does not belong to the requested section');
                }
            }

            $title = $entryType->name;
        } else {
            if ($entryType === null) {
                $entryType = new EntryType();
                $entryType->sectionId = $section->id;
            }

            $title = Craft::t('polls', 'Create a new {section} entry type',
                ['section' => Craft::t('site', $section->name)]);
        }

        $crumbs = [
            [
                'label' => Craft::t('polls', 'Settings'),
                'url' => UrlHelper::url('settings')
            ],
            [
                'label' => Craft::t('polls', 'Sections'),
                'url' => UrlHelper::url('settings/sections')
            ],
            [
                'label' => $section->name,
                'url' => UrlHelper::url('settings/sections/' . $section->id)
            ],
            [
                'label' => Craft::t('polls', 'Entry Types'),
                'url' => UrlHelper::url('settings/sections/' . $sectionId . '/entrytypes')
            ],
        ];

        return $this->renderTemplate('settings/sections/_entrytypes/edit', [
            'sectionId' => $sectionId,
            'section' => $section,
            'entryTypeId' => $entryTypeId,
            'entryType' => $entryType,
            'title' => $title,
            'crumbs' => $crumbs
        ]);
    }

    /**
     * Saves an entry type.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested entry type cannot be found
     */
    public function actionSaveEntryType()
    {
        $this->requirePostRequest();

        $entryTypeId = Craft::$app->getRequest()->getBodyParam('entryTypeId');

        if ($entryTypeId) {
            $entryType = Craft::$app->getSections()->getEntryTypeById($entryTypeId);

            if (!$entryType) {
                throw new NotFoundHttpException('Entry type not found');
            }
        } else {
            $entryType = new EntryType();
        }

        // Set the simple stuff
        $entryType->sectionId = Craft::$app->getRequest()->getRequiredBodyParam('sectionId');
        $entryType->name = Craft::$app->getRequest()->getBodyParam('name', $entryType->name);
        $entryType->handle = Craft::$app->getRequest()->getBodyParam('handle', $entryType->handle);
        $entryType->hasTitleField = (bool)Craft::$app->getRequest()->getBodyParam('hasTitleField', $entryType->hasTitleField);
        $entryType->titleLabel = Craft::$app->getRequest()->getBodyParam('titleLabel', $entryType->titleLabel);
        $entryType->titleFormat = Craft::$app->getRequest()->getBodyParam('titleFormat', $entryType->titleFormat);

        // Set the field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Entry::class;
        $entryType->setFieldLayout($fieldLayout);

        // Save it
        if (!Craft::$app->getSections()->saveEntryType($entryType)) {
            Craft::$app->getSession()->setError(Craft::t('polls', 'Couldn’t save entry type.'));

            // Send the entry type back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'entryType' => $entryType
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('polls', 'Entry type saved.'));

        return $this->redirectToPostedUrl($entryType);
    }

    /**
     * Reorders entry types.
     *
     * @return Response
     */
    public function actionReorderEntryTypes(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $entryTypeIds = Json::decode(Craft::$app->getRequest()->getRequiredBodyParam('ids'));
        Craft::$app->getSections()->reorderEntryTypes($entryTypeIds);

        return $this->asJson(['success' => true]);
    }

    /**
     * Deletes an entry type.
     *
     * @return Response
     */
    public function actionDeleteEntryType(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $entryTypeId = Craft::$app->getRequest()->getRequiredBodyParam('id');

        Craft::$app->getSections()->deleteEntryTypeById($entryTypeId);

        return $this->asJson(['success' => true]);
    }
}
