=== AsideShop ===
Contributors: maijs
Donate link: http://tifau.net/asideshop
Tags: asides, templates, categories, posts
Requires at least: 2.3
Tested up to: 2.9.1
Stable tag: 1.2

A WordPress plugin which allows you to create templates for your asides posts. Instantly.

== Description ==

AsideShop is a WordPress plugin which allows you to show aside posts on your blog's front page in a different way without theme template modification.

Features:

* No need to edit and modify theme templates - use your own.
* Easily create small templates within WordPress Administration Panel to display posts differently on the front page.
* Templates can contain dozens of pre-defined tags to use for posts and comments (e.g. `%posts_title%`, `%comments_count%`, etc.).
* Assign templates to categories which contain aside posts.
* Testing mode lets you preview the changes on the front page before the world sees it.
* No extra data is added to categories or posts - AsideShop minds its own settings.
* Upon AsideShop deactivation, all your posts are displayed as regular posts. Non-destructively.
* Works fine with text filters (Markdown, Textiler), caching plugins (WP\_Cache, WP\_Super\_Cache) and other plugins.
* May not work with plugins and themes which use [The Loop](http://codex.wordpress.org/The_Loop) in uncommon way.

AsideShop is primarily intended to use with aside posts but you can use it to have posts parsed differently according to the categories the posts are placed in.

ChangeLog is available on the [Other Notes](http://wordpress.org/extend/plugins/asideshop/other_notes/) tab.

== Installation ==

= How to Install =

1. Download the plugin archive and extract the files it contains.
2. Upload extracted `asideshop/` folder to the `/wp-content/plugins/` directory of your blog.
3. Activate the AsideShop plugin through the 'Plugins' menu in WordPress Administration Panel.
4. Look for AsideShop submenu under Settings menu in WordPress Administration Panel.

= How to Configure =

1. Create a posts category (in WordPress Administration Panel under **Manage** > **Categories**) which will contain your aside posts, e.g. `Asides` if you don't have one already.
2. Go to AsideShop settings page (look under **Settings** (WordPress 2.5 and later) or **Options** (older versions)).
3. Click on **Add Template** button and create a template for you aside posts. All the tags available for use will be displayed under the text input box.
4. Click on **Save Changes** (WordPress 2.5 and later) or **Update AsideShop Options** (older versions) button to save the template.
5. Select the category that contains (or will contain) aside posts and assign the template you just created.
6. Select **Enable AsideShop for testing** option and check whether your blog's front page looks alright. Your aside posts will be displayed inline with another posts.
7. If the front page seems to work fine, select **Enable AsideShop** option. That's it.

== Screenshots ==

1. WordPress native option page.
2. AsideShop can be disabled, enabled for testing or production environment.
3. Options to parse templates on front, search, category, tag, date, author, archive view pages.
4. Create as many templates as you need.
5. Assign your templates to your aside post categories.
6. Have your asides displayed the way you like.

== Other Notes ==

= ChangeLog =

**1.2**

1. Fixed a bug which caused a disappearance of post tags if `%post_tags%` tag was used in AsideShop template. (Credit goes to [tquizzle](http://www.tquizzle.com) for pointing this out.)

2. You should not upgrade to AsideShop 1.2 if you are using WordPress 2.2.

**1.1**

1. Fixed a bug which causes endless loop on certain WordPress 2.8 and 2.9 systems. Now uses `the_post` hook instead of WP_Query override. (For WordPress 2.7 and earlier versions WP_Query override is still used but I will drop support for these versions in near future.)
2. Credit goes to [Chris McLaren](http://www.chrismclaren.com/blog) for his work on making AsideShop on WordPress 2.8 and 2.9 systems possible.

**1.0.9**

1. Tested with WordPress 2.8 and 2.9.

**1.0.8**

1. Bug fixed which caused checkboxes on settings page check and uncheck in unintended matter.
2. Tested with WordPress 2.7 beta1.

**1.0.7**

1. Added Search View option to enable/disable aside post parsing in search result pages.
2. Added new tag - `%post_date_regular%` to display post date on every aside post not just the lastest one in particular day.

**1.0.6**

1. Checked compatibility with WordPress 2.6.
2. Fixed a bug when AsideShop shows an error if installed on a fresh WordPress installation.
3. When installed on a fresh WordPress installation, 'parse on Front Page' option is selected by default.

**1.0.5**
 
1. Added new tags `%post_categories%` and `%post_tags%` to display categories and tags of aside posts. Multiple categories or tags will be separated by commas. (`%post_tags%` will only work with WordPress 2.3 and later releases.)
2. Added Italian translation by [Gianni Diurno](http://gidibao.net/).
3. Spanish translation files were renamed from `asideshop-es` to `asideshop-es_ES`. This should work with common Spanish translations.

**1.0.4**
 
1. Added options to choose whether templates should be parsed on front, category, tag, date, author, archive view pages.
2. Default template is offered upon adding new template.
3. Added Spanish translation by [Marcelo Lynch](http://microutopia.com.ar/).

**1.0.3**

1. Settings panel has WordPress 2.5 native looks. 

= Translation = 

If you wish to translate AsideShop, use `asideshop.pot` file which is included in the download. E-mail me at `miro [at] apollo [dot] lv` and I will include your translation in future releases.

= Known Issues =

* If AsideShop plugin is enabled, logged in user won't see *Edit This Post* link as there is no such tag to use in templates.

* If AsideShop plugin is enabled, templates created in WordPress Administration Panel have higher priority than those create in theme templates (using `is_aside()` function).

* If text filter plugins are used (Textile, Markdown, etc), WordPress wraps the text in paragraph tags (`<p>..</p>`) while parsing. Therefore if you use `%post_content_filtered%` tag in AsideShop templates, the text will also be wrapped in paragraph tags.

* If a post is placed in several categories and at least one category of these is selected as containing aside posts, the post will be displayed as an aside post according to the template which is assigned to the first category (in alphabetical order).

* If you split aside post with `<!--more-->` tag, only the part before `<!--more-->` is displayed with `%post_content%` and `%post_content_filtered%` tags.

