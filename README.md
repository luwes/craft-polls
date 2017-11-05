# Craft CMS Polls plugin

A fully featured polls plugin for [Craft CMS](https://craftcms.com/). Including translatable questions and options, custom fields for answer options, anonymous voting or login required, a bar graph in the answers admin section, other option for free text input.

A license for commercial use and more information can be found at [wesleyluyten.com/projects/craft-cms-polls](https://wesleyluyten.com/projects/craft-cms-polls)

### Important 
The source of this plugin is shared but it's not free to use on commercial websites, please consult the [LICENSE](LICENSE.md "Craft CMS Polls plugin license") before using this plugin.

### Requirements
- Craft 2.4+  
- PHP 5.4+  

### Install
1. Download and unzip Polls plugin zip file.  
2. Drop polls plugin folder in craft/plugins.  
3. Go to Admin / Settings / Plugins and click install.  

### Update
1. Download and unzip Polls plugin zip file.  
2. Replace craft/plugins/polls folder by the one that you have downloaded.  

### Quick start

To add a basic poll form to your website insert this code in your template.

``` twig
{{ craft.polls.form({ 
    pollResponse: pollResponse|default(null)
}) }}
```

> You can also write your own HTML by copying the contents of [templates/forms/basic.html](templates/forms/basic.html) in your own template and tweaking as you see fit.

##### Parameters

###### questions
Limit the questions that get added to the form. For example: `questions: craft.polls.questions({ pollId: 1 })`

###### pollResponse
This parameter is a route variable send back from the Polls_AnswersController which returns information when the form is submitted. In case it fails this variable holds the errors, when the submission is a success it returns the answers and answeredQuestions.


### Templating Reference

#### craft.polls.questions
You can access your site’s poll questions from your templates via craft.polls.questions. It returns an ElementCriteriaModel object. This is a simplified example, for a more full and robust solution refer to the html in [templates/forms/basic.html](templates/forms/basic.html)

``` twig
<form class="poll-form" method="post" accept-charset="UTF-8">
	{{ getCsrfInput() }}
	<input type="hidden" name="action" value="polls/answers/saveAnswers">
	{% for question in craft.polls.questions %}
		<h1>{{ question.title }}</h1>
		<div class="poll-options">
			{% for option in question.options %}
				<div class="poll-option {{ option.kind }}">
					<label>
						<input type="hidden" value="" name="{{ option.optionInputName }}"> 
						<input class="poll-option-input {{ option.kind }}" type="radio" value="{{ option.id }}" name="{{ option.optionInputName }}" {% if option.selected %} checked="checked"{% endif %}> 
						{{ option.label }}
					</label>
				</div>
			{% endfor %}
		</div>
	{% endfor %}
	<button type="submit">Vote</button>
</form>
```

##### Parameters

###### poll
Only fetch questions that belong to a given poll(s). Accepted values include a poll handle, an array of poll handles.

###### pollId
Only fetch questions that belong to a given poll(s), referenced by its ID.


#### craft.polls.getAllPolls()
Returns an array of Polls_PollModel objects representing each of your site’s polls.
``` twig
{% set polls = craft.polls.getAllPolls() %}
```

#### craft.polls.getTotalPolls()
Returns the total number of polls your site has.
``` twig
{% set total = craft.polls.getTotalPolls() %}
```

#### craft.polls.getPollById( pollId )
Returns a Polls_PollModel object representing a section in your site, by its ID.
``` twig
{% set poll = craft.polls.getPollById(pollId) %}
```

#### craft.polls.getPollByHandle( pollHandle )
Returns a Polls_PollModel object representing a poll in your site, by its handle.
``` twig
{% set poll = craft.polls.getPollByHandle(pollHandle) %}
```

#### craft.polls.hasAnswered( questions )
Returns true if the user/guest has answered all the questions.
``` twig
{% if craft.polls.hasAnswered(questions) %}
	<a class="poll-results-link" href="#">Results</a>
{% endif %}
```
