# Craft CMS Polls plugin

A fully featured polls plugin for [Craft CMS](https://craftcms.com/). Including translatable questions and options, custom fields for answer options, anonymous voting or login required, a bar graph in the answers admin section, other option for free text input.

A license for commercial use and more information can be found at [wesleyluyten.com/projects/craft-cms-polls](https://wesleyluyten.com/projects/craft-cms-polls)

### Important 
The source of this plugin is shared but it's not free to use on commercial websites, please consult the [LICENSE](LICENSE.md "Craft CMS Polls plugin license") before using this plugin.

###Requirements
- Craft 2.4+  
- PHP 5.4+  

###Install
1. Download and unzip Polls plugin zip file.  
2. Drop polls plugin folder in craft/plugins.  
3. Go to Admin / Settings / Plugins and click install.  

###Update
1. Download and unzip Polls plugin zip file.  
2. Replace craft/plugins/polls folder by the one that you have downloaded.  

###Usage
To add a basic poll form to your website insert this code in your template.

``` twig
{{ craft.polls.form({ 
    pollResponse: pollResponse|default(null)
}) }}
```

You can also write your own code by copying the contents of `polls/templates/forms/basic.html` in your own template and tweaking as you see fit.
