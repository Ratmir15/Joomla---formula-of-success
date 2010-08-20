<?php
/**
 * @package JoomlaPack
 * @copyright Copyright (c)2006-2009 JoomlaPack Developers
 * @license GNU General Public License version 2, or later
 * @version $id$
 * @since 1.3
 */

// Protect from unauthorized access
defined('_JEXEC') or die('Restricted Access');

?>
<div id="jpcontainer">
<h1>Welcome to JoomlaPack</h1>
<div style="font-size: larger;">
<p>JoomlaPack is distributed under the terms of the <a
	href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html"
	target="_blank"> GNU General Public License, version 2</a> or - at your
option - any later version. As stated in the license, paragraphs 11 and
12, JoomlaPack comes with <em>no warranty, either expressed or implied</em>.
Keeping this in mind, we'd like to tell you what you should do in order
to protect your data and their integrity.</p>
<p>The backup operation is harmless to your site. Backing up <b>does not</b>
modify any of your site's files or database contents, except data
written in special, JoomlaPack-only tables. Thus, backing up your site
will cause no trouble. Should the backup process gets stuck, you should
first read the thorough documentation available both <a
	href="http://www.joomlapack.net/documentation2x.html">on-line</a> and <a
	href="http://joomlacode.org/gf/projects/jpack/frs">as a PDF file</a>
for off-line reading and/or printing. There is also integrated on-line
help, available from the &quot;Help&quot; button on each page. Should
this not be enough, we have a <a href="http://forum.joomlapack.net">support
forum</a> where we can help you out.</p>
<p>The <b>restoration process</b> is a different story. Restoring a site
<em>overwrites</em> any existing files and / or database content. If you
are not carefull, it will break your site! To this end <strong>we
strongly advise you to practice backup restoration on a local testing
server first</strong> - in order to get the hang of it - before
attempting so on a live, working site. Having read the documentation
first is imperative and it will save you much trouble and support
requests.</p>
<p>If you have read this text, understand it and accept it, please
indicate so by pressing the button labelled &quot;I agree&quot;.
Pressing the &quot;I do not agree&quot; button will get you back to the
administrator's home page.</p>
<form method="post" action="index2.php"><input type="hidden"
	name="option" value="com_joomlapack" /> <input type="hidden"
	name="view" value="nag" /> <input type="hidden" name="task"
	value="agree" /> <input type="submit" name="accept" value="I agree"></form>
<input type="submit" value="I do not agree"
	onclick="window.location = 'index2.php'" /></div>
</div>
