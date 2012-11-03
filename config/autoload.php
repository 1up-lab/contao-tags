<?php

/**
 * Contao Open Source CMS
 * 
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * @package Tags
 * @link    http://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'Aurealis',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'Contao\CalendarTags'            => 'system/modules/tags/classes/CalendarTags.php',
	'Contao\ContentHeadlineTags'     => 'system/modules/tags/classes/ContentHeadlineTags.php',
	'Contao\NewsTags'                => 'system/modules/tags/classes/NewsTags.php',
	'Contao\TagField'                => 'system/modules/tags/classes/TagField.php',
	'Contao\TagHelper'               => 'system/modules/tags/classes/TagHelper.php',
	'Contao\TagList'                 => 'system/modules/tags/classes/TagList.php',

	// Models
	'Contao\TagModel'                => 'system/modules/tags/models/TagModel.php',
	'Contao\TagsNewsModel'           => 'system/modules/tags/models/TagsNewsModel.php',

	// Modules
	'Aurealis\ModuleArticle'         => 'system/modules/tags/modules/ModuleArticle.php',
	'Contao\ModuleEventlistTags'     => 'system/modules/tags/modules/ModuleEventlistTags.php',
	'Contao\ModuleFaqListTags'       => 'system/modules/tags/modules/ModuleFaqListTags.php',
	'Contao\ModuleGlobalArticlelist' => 'system/modules/tags/modules/ModuleGlobalArticlelist.php',
	'Contao\ModuleLastEventsTags'    => 'system/modules/tags/modules/ModuleLastEventsTags.php',
	'Contao\ModuleNewsArchiveTags'   => 'system/modules/tags/modules/ModuleNewsArchiveTags.php',
	'Contao\ModuleNewsListTags'      => 'system/modules/tags/modules/ModuleNewsListTags.php',
	'Contao\ModuleNewsReaderTags'    => 'system/modules/tags/modules/ModuleNewsReaderTags.php',
	'Contao\ModuleTagCloud'          => 'system/modules/tags/modules/ModuleTagCloud.php',
	'Contao\ModuleTagContentList'    => 'system/modules/tags/modules/ModuleTagContentList.php',
	'Contao\ModuleTagListByCategory' => 'system/modules/tags/modules/ModuleTagListByCategory.php',
	'Contao\ModuleTagScope'          => 'system/modules/tags/modules/ModuleTagScope.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'event_default_tags'     => 'system/modules/tags/templates',
	'mod_article_tags'       => 'system/modules/tags/templates',
	'mod_global_articlelist' => 'system/modules/tags/templates',
	'mod_tag_contentlist'    => 'system/modules/tags/templates',
	'mod_tag_listbycategory' => 'system/modules/tags/templates',
	'mod_tagcloud'           => 'system/modules/tags/templates',
	'mod_tagscope'           => 'system/modules/tags/templates',
	'news_full_tags'         => 'system/modules/tags/templates',
	'taglist'                => 'system/modules/tags/templates',
	'tags_feed'              => 'system/modules/tags/templates',
	'tags_inserttag'         => 'system/modules/tags/templates',
	'tags_used'              => 'system/modules/tags/templates',
));
