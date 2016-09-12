<?php
namespace Craft;

/**
 * Answers controller
 */
class Polls_AnswersController extends BaseController
{
	protected $allowAnonymous = array('actionSaveAnswers');

	/**
	 * Answes index
	 */
	public function actionAnswersIndex(array $variables = array())
	{
		if (!empty($variables['pollHandle']))
		{
			$variables['poll'] = craft()->polls->getPollByHandle($variables['pollHandle']);
		}
		else if (!empty($variables['pollId']))
		{
			$variables['poll'] = craft()->polls->getPollById($variables['pollId']);
		}

		if (!empty($variables['questionId']))
		{
			$variables['question'] = craft()->polls_questions->getQuestionById($variables['questionId']);

			if (!$variables['question'])
			{
				throw new HttpException(404);
			}
		}

		$variables['options'] = $variables['question']->options;
		$variables['answers'] = $variables['question']->answers;

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Polls'), 'url' => UrlHelper::getUrl('polls')),
			array('label' => $variables['poll']->name, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle)),
			array('label' => Craft::t('Questions'), 'url' => UrlHelper::getUrl('polls/questions')),
			array('label' => $variables['question']->title, 'url' => UrlHelper::getUrl('polls/'.$variables['poll']->handle.'/questions/'.$variables['question']->id)),
		);

		$this->renderTemplate('polls/answers/index', $variables);
	}

	/**
	 * Save answers
	 */
	
	public function actionSaveAnswers()
	{
    $this->requirePostRequest();
    
    $errors = array();
    $errorCount = 0;

    $user = craft()->userSession->getUser();

    $settings = craft()->plugins->getPlugin('polls')->getSettings();
    if ($settings->requireLogin && !$user) 
    {
    	$errorCount += 1;
    	$errors[] = Craft::t('You must be logged in to vote.');
    }

  	$questionOptions = craft()->request->getPost('polls_options');
  	$questionTexts = craft()->request->getPost('polls_texts');

  	if ($questionOptions)
  	{
  		$questions = array();
  		$answeredQuestions = array();

  		$questionIds = array_keys($questionOptions);
  		foreach ($questionIds as $qid) 
  		{
				$question = craft()->polls_questions->getQuestionById($qid);
	  		if ($question)
	  		{
	  			$options = array();
	  			if (isset($questionOptions[$qid]))
	  			{
	  				$options = array_filter($questionOptions[$qid]);
	  			}

	  			$texts = array();
	  			if (isset($questionTexts[$qid]))
	  			{
	  				$texts = array_filter($questionTexts[$qid]);
	  			}

	  			$answers = array();

	  			//remember values user posted
	  			foreach ($question->options as $option) 
					{
						if ($option->kind == Polls_OptionKind::Defined)
						{
	  					if (in_array($option->id, $options)) 
	  					{
								$option->selected = true;
								$answers[] = $option;
	  					}
						}
						elseif ($option->kind == Polls_OptionKind::Other) 
						{
							if (in_array($option->id, $options))
							{
								$option->selected = true;
							}

							if (isset($texts[$option->id]))
							{
								$option->selected = true;
								$option->value = $texts[$option->id];
								$answers[] = $option;
							}
						}
					}

					$canAnswer = $question->multipleVotes || !craft()->polls_answers->hasAnswered($user, $question);

	  			if (!$canAnswer && count($answers) > 0) 
	  			{
	  				$errorCount += 1;
	  				$question->addError('multipleVotes', Craft::t('You can only answer one time on this question.'));
	  			}

	  			if ($canAnswer && $question->answerRequired && count($answers) == 0)
	  			{
	  				$errorCount += 1;
	  				$question->addError('answerRequired', Craft::t('An answer is required for this question.'));
	  			}
		  			
	  			if ($canAnswer && !$question->multipleOptions && count($answers) > 1) 
	  			{
	  				$errorCount += 1;
	  				$question->addError('multipleOptions', Craft::t('You can only give 1 answer for this question.'));
	  			}

	  			$questions[] = $question;

	  			if (count($answers) > 0) 
	  			{
	  				$answeredQuestions[] = $question;
	  			}
	  		}
  		}

	    if (count($answeredQuestions) == 0) 
	    {
	    	$errorCount += 1;
	    	$errors[] = Craft::t('No answers were submitted.');
	    }

	  	if ($errorCount > 0)
	  	{
		  	craft()->urlManager->setRouteVariables(array(
		  		'pollResponse' => array(
						'questions' => $questions,
						'errors' => $errors,
					)
				));
	  	}
	  	else
	  	{
	  		$answers = array();

	  		foreach ($answeredQuestions as $question) 
	  		{
	  			foreach ($question->options as $option) 
					{
						if ($option->selected)
						{
					  	$answer = new Polls_AnswerModel();
					  	$answer->questionId = $question->id;
					  	$answer->optionId = $option->id;
					  	$answer->userId = ($user) ? $user->id : null;
					  	$answer->ipAddress = craft()->request->getUserHostAddress();
					  	$answer->userAgent = craft()->request->getUserAgent();

							if ($option->kind == Polls_OptionKind::Other) 
							{
								$answer->text = $option->value;
							}

							if (craft()->polls_answers->saveAnswer($answer)) 
							{
								$answers[] = $answer;
							}
						}
					}

	  		}

		  	craft()->urlManager->setRouteVariables(array(
		  		'pollResponse' => array(
						'success' => true,
						'answers' => $answers,
						'answeredQuestions' => $answeredQuestions,
					)
				));
	  	}
  	}
  	else
  	{
  		$this->redirectToPostedUrl();
  	}
	}

	/**
	 * Deletes an answer.
	 */
	public function actionDeleteAnswer()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$answerId = craft()->request->getRequiredPost('id');

		craft()->polls_answers->deleteAnswerById($answerId);
		$this->returnJson(array('success' => true));
	}

}
